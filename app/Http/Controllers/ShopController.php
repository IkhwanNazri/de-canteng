<?php

namespace App\Http\Controllers;

use App\Enums\VoucherScope;
use App\Models\Shop;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Shops/Index', [
            'shops' => Shop::orderByDesc('followers_count')
                ->get(['id', 'name', 'slug', 'logo', 'rating', 'followers_count', 'is_verified']),
        ]);
    }

    public function show(Request $request, Shop $shop, VoucherService $vouchers): Response
    {
        return Inertia::render('Shops/Show', [
            'shop' => $shop->only('id', 'name', 'slug', 'description', 'logo', 'banner', 'rating', 'followers_count', 'is_verified'),
            'products' => $shop->products()->latest()->paginate(12),
            'live' => $shop->liveSessions()->live()->first(['id', 'title', 'viewers_count']),
            'isFollowing' => (bool) $request->user()?->followedShops()->whereKey($shop->id)->exists(),
            'vouchers' => $vouchers->forDisplay($shop, $request->user(), [VoucherScope::Shop, VoucherScope::Product, VoucherScope::Follower, VoucherScope::Live]),
            'isOwner' => $request->user()?->id === $shop->user_id,
        ]);
    }
}
