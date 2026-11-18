<?php

namespace App\Services;

use App\Enums\VoucherScope;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserVoucher;
use App\Models\Voucher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    public function claim(User $user, Voucher $voucher): UserVoucher
    {
        return DB::transaction(function () use ($user, $voucher) {
            $voucher = Voucher::whereKey($voucher->id)->lockForUpdate()->first();

            $error = match (true) {
                $voucher->isPlatform() => 'Baucar ini tidak boleh dituntut.',
                ! $voucher->isUsableNow() => 'Baucar ini sudah tamat atau belum bermula.',
                $voucher->quantity !== null && $voucher->claimed_count >= $voucher->quantity => 'Baucar ini sudah habis.',
                $voucher->shop?->user_id === $user->id => 'Anda tidak boleh tuntut baucar kedai sendiri.',
                $user->vouchers()->where('voucher_id', $voucher->id)->exists() => 'Anda sudah tuntut baucar ini.',
                $voucher->scope === VoucherScope::Follower && ! $user->followedShops()->whereKey($voucher->shop_id)->exists() => 'Ikut kedai ini dahulu untuk tuntut baucar.',
                $voucher->scope === VoucherScope::Live && ! $voucher->shop->liveSessions()->live()->exists() => 'Baucar ini hanya boleh dituntut semasa live.',
                default => null,
            };

            if ($error) {
                throw ValidationException::withMessages(['voucher' => $error]);
            }

            $voucher->increment('claimed_count');

            return $user->vouchers()->create(['voucher_id' => $voucher->id]);
        });
    }

    /**
     * Unused, still-valid vouchers in the user's wallet.
     *
     * @return Collection<int, UserVoucher>
     */
    public function usable(User $user): Collection
    {
        return $user->vouchers()
            ->whereNull('used_at')
            ->with('voucher.products:id')
            ->get()
            ->filter(fn (UserVoucher $owned) => $owned->voucher->isUsableNow())
            ->values();
    }

    /**
     * Subtotal the voucher applies to.
     *
     * @param  iterable<int, array{product_id: int, line_total: float}>  $lines
     */
    public function eligibleSubtotal(Voucher $voucher, iterable $lines): float
    {
        $productIds = $voucher->scope === VoucherScope::Product ? $voucher->products->pluck('id')->all() : null;

        return (float) collect($lines)
            ->filter(fn (array $line) => $productIds === null || in_array($line['product_id'], $productIds, true))
            ->sum('line_total');
    }

    /**
     * Claimable vouchers of a shop for display, with the viewer's claim state.
     *
     * @param  list<VoucherScope>  $scopes
     * @return Collection<int, array<string, mixed>>
     */
    public function forDisplay(Shop $shop, ?User $viewer, array $scopes, ?Product $product = null): Collection
    {
        $claimedIds = $viewer ? $viewer->vouchers()->pluck('voucher_id')->all() : [];

        return $shop->vouchers()
            ->available()
            ->whereIn('scope', $scopes)
            ->with('products:id')
            ->orderBy('min_spend')
            ->get()
            ->filter(fn (Voucher $voucher) => $voucher->scope !== VoucherScope::Product
                || ($product && $voucher->products->contains('id', $product->id))
                || (! $product && $voucher->products->isNotEmpty()))
            ->map(fn (Voucher $voucher) => [
                ...$voucher->only('id', 'code', 'name', 'label', 'scope', 'scope_label', 'min_spend', 'max_discount', 'ends_at'),
                'remaining' => $voucher->quantity === null ? null : max(0, $voucher->quantity - $voucher->claimed_count),
                'claimed' => in_array($voucher->id, $claimedIds, true),
            ])
            ->values();
    }
}
