<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    /**
     * Buyer confirms the parcel arrived.
     */
    public function complete(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        abort_unless($order->status === OrderStatus::Shipped, 422);

        $order->update(['status' => OrderStatus::Completed]);

        return back()->with('success', 'Terima kasih! Jangan lupa beri ulasan.');
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        abort_unless(in_array($order->status, [OrderStatus::Shipped, OrderStatus::Completed], true), 422, 'Ulasan dibuka selepas pesanan dihantar.');

        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::in($order->items()->whereNotNull('product_id')->pluck('product_id')),
                Rule::unique('reviews')->where('order_id', $order->id),
            ],
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['nullable', 'string', 'max:1000'],
        ], ['product_id.unique' => 'Anda sudah mengulas produk ini.']);

        $review = $order->reviews()->create([...$validated, 'user_id' => $request->user()->id]);
        $review->product->refreshRating();

        return back()->with('success', 'Ulasan disimpan. Terima kasih!');
    }
}
