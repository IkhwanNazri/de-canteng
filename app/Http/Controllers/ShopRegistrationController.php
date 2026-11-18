<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreShopRequest;
use App\Models\Shop;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ShopRegistrationController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()->shop) {
            return redirect()->route('seller.products.index');
        }

        return Inertia::render('Shops/Create');
    }

    public function store(StoreShopRequest $request, ReferralService $referrals): RedirectResponse
    {
        $user = $request->user();

        $shop = $user->shop()->create([
            ...$request->safe()->except('referral_code'),
            'slug' => $this->uniqueSlug($request->validated('name')),
        ]);

        $referred = $referrals->applySellerReferral($shop, $request->validated('referral_code'));

        if (! $user->isAdmin()) {
            $user->forceFill(['role' => UserRole::Seller])->save();
        }

        $message = $referred
            ? 'Tahniah! Kedai dibuka dengan bonus '.config('marketplace.referral_commission_free_orders').' pesanan bebas caj platform.'
            : 'Tahniah! Kedai anda sudah dibuka. Tambah produk pertama anda.';

        return redirect()->route('seller.products.index')->with('success', $message);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (Shop::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
