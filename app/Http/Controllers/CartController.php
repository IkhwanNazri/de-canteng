<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Request $request): Response
    {
        $items = $request->user()->cartItems()
            ->with('product:id,shop_id,name,slug,price,compare_price,stock,image', 'product.shop:id,name,slug')
            ->latest()
            ->get();

        return Inertia::render('Cart/Index', [
            'groups' => $items->groupBy('product.shop_id')->map(fn ($shopItems) => [
                'shop' => $shopItems->first()->product->shop,
                'items' => $shopItems->values(),
            ])->values(),
            'shippingFee' => (float) config('marketplace.shipping_fee'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'buy_now' => ['nullable', 'boolean'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($request->user()->shop?->id === $product->shop_id) {
            return back()->withErrors(['cart' => 'Anda tidak boleh beli produk kedai sendiri.']);
        }

        $item = $request->user()->cartItems()->firstOrNew(['product_id' => $product->id]);
        $item->quantity = min($product->stock, ($item->quantity ?? 0) + ($validated['quantity'] ?? 1));

        if ($item->quantity < 1) {
            return back()->withErrors(['cart' => 'Produk ini sudah habis stok.']);
        }

        $item->save();

        if ($request->boolean('buy_now')) {
            return redirect()->route('cart.index');
        }

        return back()->with('success', 'Ditambah ke troli.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->user_id === $request->user()->id, 403);

        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $cartItem->update(['quantity' => min($validated['quantity'], $cartItem->product->stock)]);

        return back();
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->user_id === $request->user()->id, 403);

        $cartItem->delete();

        return back();
    }
}
