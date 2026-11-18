<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\VoucherScope;
use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Digital stamp card: stamps are earned on paid orders and referrals, and every
 * full card turns into a platform voucher.
 */
class LoyaltyService
{
    public function award(User $user, int $stamps, string $reason, ?Order $order = null): void
    {
        DB::transaction(function () use ($user, $stamps, $reason, $order) {
            $user = User::whereKey($user->id)->lockForUpdate()->first();
            $perCard = (int) config('marketplace.stamps_per_card');

            $user->stampEntries()->create(['stamps' => $stamps, 'reason' => $reason, 'order_id' => $order?->id]);
            $user->stamps += $stamps;

            while ($user->stamps >= $perCard) {
                $user->stamps -= $perCard;
                $user->stampEntries()->create(['stamps' => -$perCard, 'reason' => 'Kad penuh — ditukar kepada baucar']);
                $this->issueReward($user);
            }

            $user->save();
        });
    }

    public function qualifies(Order $order): bool
    {
        return $order->subtotal >= (float) config('marketplace.stamp_min_order');
    }

    private function issueReward(User $user): void
    {
        $voucher = Voucher::create([
            'user_id' => $user->id,
            'code' => 'COP'.Str::upper(Str::random(8)),
            'name' => 'Ganjaran Kad Cop',
            'scope' => VoucherScope::Platform,
            'discount_type' => DiscountType::Fixed,
            'discount_value' => (float) config('marketplace.stamp_reward'),
            'min_spend' => (float) config('marketplace.stamp_min_order'),
            'quantity' => 1,
            'claimed_count' => 1,
            'ends_at' => now()->addDays((int) config('marketplace.stamp_reward_valid_days')),
        ]);

        $user->vouchers()->create(['voucher_id' => $voucher->id]);
    }
}
