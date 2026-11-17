<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CheckoutController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $items = $request->user()->cartItems()->with('product:id,shop_id,name,price,image', 'product.shop:id,name')->get();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $shopCount = $items->pluck('product.shop_id')->unique()->count();
        $subtotal = $items->sum(fn ($item) => $item->product->price * $item->quantity);
        $shipping = $shopCount * (float) config('marketplace.shipping_fee');

        $lastOrder = $request->user()->orders()->latest()->first();

        return Inertia::render('Checkout/Create', [
            'items' => $items,
            'summary' => ['subtotal' => $subtotal, 'shipping' => $shipping, 'total' => $subtotal + $shipping, 'shops' => $shopCount],
            'defaults' => [
                'recipient_name' => $lastOrder->recipient_name ?? $request->user()->name,
                'phone' => $lastOrder->phone ?? '',
                'address' => $lastOrder->address ?? '',
                'postcode' => $lastOrder->postcode ?? '',
                'city' => $lastOrder->city ?? '',
                'state' => $lastOrder->state ?? '',
            ],
        ]);
    }

    public function store(CheckoutRequest $request, CheckoutService $checkout): HttpResponse
    {
        $payment = $checkout->placeOrder($request->user(), $request->validated());

        return Inertia::location($checkout->checkoutUrl($payment));
    }
}
