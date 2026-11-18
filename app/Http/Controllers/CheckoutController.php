<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\CartItem;
use App\Services\CheckoutService;
use App\Services\PaymentProcessor;
use App\Services\VoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CheckoutController extends Controller
{
    public function create(Request $request, VoucherService $vouchers): Response|RedirectResponse
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
            'vouchers' => $this->voucherOptions($request, $items, $vouchers),
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

    public function store(CheckoutRequest $request, CheckoutService $checkout, PaymentProcessor $processor): HttpResponse
    {
        $voucherIds = array_values(array_filter(array_map('intval', (array) $request->input('voucher_ids', []))));
        $payment = $checkout->placeOrder($request->user(), $request->validated(), $voucherIds);

        try {
            return Inertia::location($checkout->checkoutUrl($payment));
        } catch (RuntimeException $exception) {
            Log::error($exception->getMessage());
            $processor->markFailed($payment);
            $checkout->restoreCart($payment);

            return back()->withErrors(['cart' => 'Gateway bayaran tidak dapat dihubungi. Sila cuba lagi.']);
        }
    }

    /**
     * The buyer's usable vouchers with the discount each would give on this cart.
     *
     * @param  Collection<int, CartItem>  $items
     * @return list<array<string, mixed>>
     */
    private function voucherOptions(Request $request, Collection $items, VoucherService $vouchers): array
    {
        $linesByShop = $items->groupBy('product.shop_id')->map(fn ($shopItems) => $shopItems->map(fn ($item) => [
            'product_id' => $item->product_id,
            'line_total' => $item->product->price * $item->quantity,
        ]));

        return $vouchers->usable($request->user())->load('voucher.shop:id,name')
            ->map(function ($owned) use ($linesByShop, $vouchers) {
                $voucher = $owned->voucher;
                $lines = $voucher->isPlatform()
                    ? [['product_id' => 0, 'line_total' => $linesByShop->map(fn ($l) => $l->sum('line_total'))->max() ?? 0]]
                    : ($linesByShop[$voucher->shop_id] ?? collect())->all();
                $eligible = $voucher->isPlatform() ? (float) ($lines[0]['line_total'] ?? 0) : $vouchers->eligibleSubtotal($voucher, $lines);

                return [
                    'id' => $owned->id,
                    'shop_id' => $voucher->shop_id,
                    'shop_name' => $voucher->shop?->name ?? 'DeBatik',
                    'code' => $voucher->code,
                    'name' => $voucher->name,
                    'label' => $voucher->label,
                    'min_spend' => $voucher->min_spend,
                    'platform' => $voucher->isPlatform(),
                    'discount' => $voucher->discountFor($eligible),
                ];
            })
            ->filter(fn (array $option) => $option['platform'] || $linesByShop->has($option['shop_id']))
            ->values()
            ->all();
    }
}
