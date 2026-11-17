<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Favorites/Index', [
            'products' => $request->user()->favoriteProducts()
                ->latest('favorites.created_at')
                ->get(['products.id', 'name', 'slug', 'price', 'compare_price', 'image', 'rating', 'reviews_count']),
        ]);
    }

    public function toggle(Request $request, Product $product): RedirectResponse
    {
        $request->user()->favoriteProducts()->toggle($product->id);

        return back();
    }
}
