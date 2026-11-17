<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private PaymentGateway $gateway) {}

    /**
     * Turn the buyer's cart into one order per shop, all paid by a single payment.
     *
     * @param  array{recipient_name: string, phone: string, address: string, postcode: string, city: string, state: string}  $shipping
     */
    public function placeOrder(User $buyer, array $shipping): Payment
    {
        return DB::transaction(function () use ($buyer, $shipping) {
            $cartItems = $buyer->cartItems()->with('product')->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Troli anda kosong.']);
            }

            $products = Product::whereKey($cartItems->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
            $this->ensureStockAvailable($cartItems, $products);

            $shippingFee = (float) config('marketplace.shipping_fee');

            $payment = Payment::create([
                'reference' => (string) Str::uuid(),
                'user_id' => $buyer->id,
                'amount' => 0,
                'gateway' => $this->gateway->name(),
                'status' => PaymentStatus::Pending,
            ]);

            $grandTotal = 0;

            foreach ($cartItems->groupBy(fn (CartItem $item) => $products[$item->product_id]->shop_id) as $shopId => $items) {
                $subtotal = $items->sum(fn (CartItem $item) => $products[$item->product_id]->price * $item->quantity);

                $order = Order::create([
                    ...$shipping,
                    'number' => $this->orderNumber(),
                    'payment_id' => $payment->id,
                    'user_id' => $buyer->id,
                    'shop_id' => $shopId,
                    'status' => OrderStatus::PendingPayment,
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'total' => $subtotal + $shippingFee,
                ]);

                foreach ($items as $item) {
                    $product = $products[$item->product_id];
                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'size' => $item->size,
                        'color' => $item->color,
                        'price' => $product->price,
                        'quantity' => $item->quantity,
                        'line_total' => $product->price * $item->quantity,
                    ]);
                }

                $grandTotal += $order->total;
            }

            $payment->update(['amount' => $grandTotal]);
            $buyer->cartItems()->delete();

            return $payment;
        });
    }

    /**
     * Put the items of an unpaid payment back into the buyer's cart.
     */
    public function restoreCart(Payment $payment): void
    {
        $payment->loadMissing('orders.items');

        foreach ($payment->orders->flatMap->items as $item) {
            if ($item->product_id) {
                CartItem::updateOrCreate(
                    ['user_id' => $payment->user_id, 'product_id' => $item->product_id, 'size' => $item->size, 'color' => $item->color],
                    ['quantity' => $item->quantity],
                );
            }
        }
    }

    public function checkoutUrl(Payment $payment): string
    {
        return $this->gateway->checkoutUrl($payment);
    }

    /**
     * @param  Collection<int, CartItem>  $cartItems
     * @param  Collection<int, Product>  $products
     */
    private function ensureStockAvailable(Collection $cartItems, Collection $products): void
    {
        foreach ($cartItems->groupBy('product_id') as $productId => $items) {
            $product = $products[$productId] ?? null;

            if (! $product || $product->stock < $items->sum('quantity')) {
                throw ValidationException::withMessages([
                    'cart' => "Stok tidak mencukupi untuk {$items->first()->product?->name}. Sila kemas kini troli.",
                ]);
            }
        }
    }

    private function orderNumber(): string
    {
        return 'DBT-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
    }
}
