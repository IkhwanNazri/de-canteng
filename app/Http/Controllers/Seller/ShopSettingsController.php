<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\UpdateShopRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ShopSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Seller/Settings', [
            'shop' => $request->user()->shop->makeVisible(['bank_name', 'bank_account'])
                ->only('id', 'name', 'slug', 'description', 'logo', 'banner', 'bank_name', 'bank_account'),
        ]);
    }

    public function update(UpdateShopRequest $request): RedirectResponse
    {
        $shop = $request->user()->shop;
        $data = $request->safe()->except(['logo', 'banner']);

        foreach (['logo', 'banner'] as $field) {
            if ($request->hasFile($field)) {
                $this->deleteStoredFile($shop->{$field});
                $data[$field] = '/storage/'.$request->file($field)->store("shops/{$field}s", 'public');
            }
        }

        $shop->update($data);

        return back()->with('success', 'Tetapan kedai disimpan.');
    }

    private function deleteStoredFile(?string $url): void
    {
        if ($url && str_starts_with($url, '/storage/')) {
            Storage::disk('public')->delete(substr($url, strlen('/storage/')));
        }
    }
}
