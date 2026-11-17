<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreShopRequest;
use App\Models\Shop;
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

    public function store(StoreShopRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->shop()->create([
            ...$request->validated(),
            'slug' => $this->uniqueSlug($request->validated('name')),
        ]);

        if (! $user->isAdmin()) {
            $user->forceFill(['role' => UserRole::Seller])->save();
        }

        return redirect()->route('seller.products.index')->with('success', 'Tahniah! Kedai anda sudah dibuka. Tambah produk pertama anda.');
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
