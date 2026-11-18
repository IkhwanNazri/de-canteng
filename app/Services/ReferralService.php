<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralService
{
    public function __construct(private LoyaltyService $loyalty) {}

    public function codeFor(User $user): string
    {
        if (! $user->referral_code) {
            do {
                $code = 'DB'.Str::upper(Str::random(6));
            } while (User::where('referral_code', $code)->exists());

            $user->forceFill(['referral_code' => $code])->save();
        }

        return $user->referral_code;
    }

    public function findReferrer(?string $code): ?User
    {
        $code = Str::upper(trim((string) $code));

        return $code === '' ? null : User::where('referral_code', $code)->first();
    }

    /**
     * A: buyer invites buyer. Rewarded with stamps after the new buyer's first paid order.
     */
    public function attachBuyerReferral(User $newUser, ?string $code): void
    {
        $referrer = $this->findReferrer($code);

        if ($referrer && $referrer->id !== $newUser->id) {
            $newUser->forceFill(['referred_by_id' => $referrer->id])->save();
        }
    }

    /**
     * B: seller invites seller. Both shops get commission-free orders right away.
     */
    public function applySellerReferral(Shop $newShop, ?string $code): bool
    {
        $referrerShop = $this->findReferrer($code)?->shop;

        if (! $referrerShop || $referrerShop->id === $newShop->id) {
            return false;
        }

        $freeOrders = (int) config('marketplace.referral_commission_free_orders');

        $newShop->forceFill(['referred_by_shop_id' => $referrerShop->id])->save();
        $newShop->increment('commission_free_orders', $freeOrders);
        $referrerShop->increment('commission_free_orders', $freeOrders);

        return true;
    }

    public function rewardFirstPurchase(User $buyer): void
    {
        DB::transaction(function () use ($buyer) {
            $buyer = User::whereKey($buyer->id)->lockForUpdate()->first();

            if (! $buyer->referred_by_id || $buyer->referral_rewarded_at) {
                return;
            }

            $buyer->forceFill(['referral_rewarded_at' => now()])->save();

            $this->loyalty->award($buyer->referrer, (int) config('marketplace.referral_stamps_referrer'), "Bonus ajak kawan ({$buyer->name})");
            $this->loyalty->award($buyer, (int) config('marketplace.referral_stamps_referee'), 'Bonus guna kod referral');
        });
    }
}
