<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\UserVoucher;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private PaymentGateway $gateway, private VoucherService $vouchers) {}

    /**
     * Turn the buyer's cart into one order per shop, all paid by a single payment.
     * Up to one shop voucher per shop and one platform voucher may be applied.
     *
     * @param  array{recipient_name: string, phone: string, address: string, postcode: string, city: string, state: string}  $shipping
     * @param  list<int>  $userVoucherIds
     */
    public function placeOrder(User $buyer, array $shipping, array $userVoucherIds = []): Payment
    {
        return DB::transaction(function () use ($buyer, $shipping, $userVoucherIds) {
            $cartItems = $buyer->cartItems()->with('product')->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Troli anda kosong.']);
            }

            $products = Product::whereKey($cartItems->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
            $this->ensureStockAvailable($cartItems, $products);

            $drafts = $this->draftOrders($cartItems, $products);
            $chosen = $this->resolveVouchers($buyer, $userVoucherIds);
            $this->applyShopVouchers($drafts, $chosen['shop']);
            $this->applyPlatformVoucher($drafts, $chosen['platform']);

            $payment = Payment::create([
                'reference' => (string) Str::uuid(),
                'user_id' => $buyer->id,
                'amount' => 0,
                'gateway' => $this->gateway->name(),
                'status' => PaymentStatus::Pending,
            ]);

            $grandTotal = 0;

            foreach ($drafts as $draft) {
                $order = Order::create([
                    ...$shipping,
                    'number' => $this->orderNumber(),
                    'payment_id' => $payment->id,
                    'user_id' => $buyer->id,
                    'shop_id' => $draft['shop_id'],
                    'status' => OrderStatus::PendingPayment,
                    'subtotal' => $draft['subtotal'],
                    'shipping_fee' => $draft['shipping_fee'],
                    'shop_discount' => $draft['shop_discount'],
                    'platform_discount' => $draft['platform_discount'],
                    'total' => $draft['subtotal'] + $draft['shipping_fee'] - $draft['shop_discount'] - $draft['platform_discount'],
                ]);

                $order->items()->createMany($draft['lines']);

                foreach ($draft['vouchers'] as $owned) {
                    $owned->update(['used_at' => now(), 'order_id' => $order->id]);
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
     * @return array<int, array{shop_id: int, subtotal: float, shipping_fee: float, shop_discount: float, platform_discount: float, lines: list<array<string, mixed>>, vouchers: list<UserVoucher>}>
     */
    private function draftOrders(Collection $cartItems, Collection $products): array
    {
        $drafts = [];

        foreach ($cartItems->groupBy(fn (CartItem $item) => $products[$item->product_id]->shop_id) as $shopId => $items) {
            $lines = $items->map(function (CartItem $item) use ($products) {
                $product = $products[$item->product_id];

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'size' => $item->size,
                    'color' => $item->color,
                    'price' => $product->price,
                    'quantity' => $item->quantity,
                    'line_total' => round($product->price * $item->quantity, 2),
                ];
            })->values()->all();

            $drafts[$shopId] = [
                'shop_id' => $shopId,
                'subtotal' => round(array_sum(array_column($lines, 'line_total')), 2),
                'shipping_fee' => (float) config('marketplace.shipping_fee'),
                'shop_discount' => 0.0,
                'platform_discount' => 0.0,
                'lines' => $lines,
                'vouchers' => [],
            ];
        }

        return $drafts;
    }

    /**
     * @param  list<int>  $userVoucherIds
     * @return array{shop: Collection<int, UserVoucher>, platform: ?UserVoucher}
     */
    private function resolveVouchers(User $buyer, array $userVoucherIds): array
    {
        $owned = $userVoucherIds === [] ? collect() : UserVoucher::whereIn('id', $userVoucherIds)
            ->where('user_id', $buyer->id)
            ->whereNull('used_at')
            ->with('voucher.products:id')
            ->lockForUpdate()
            ->get();

        if ($owned->count() !== count(array_unique($userVoucherIds)) || $owned->contains(fn (UserVoucher $o) => ! $o->voucher->isUsableNow())) {
            throw ValidationException::withMessages(['voucher' => 'Baucar tidak sah atau sudah digunakan.']);
        }

        [$platform, $shop] = $owned->partition(fn (UserVoucher $o) => $o->voucher->isPlatform());

        if ($platform->count() > 1 || $shop->groupBy('voucher.shop_id')->contains(fn ($group) => $group->count() > 1)) {
            throw ValidationException::withMessages(['voucher' => 'Hanya satu baucar kedai untuk setiap kedai dan satu baucar DeBatik.']);
        }

        return ['shop' => $shop->keyBy('voucher.shop_id'), 'platform' => $platform->first()];
    }

    /**
     * @param  array<int, array<string, mixed>>  $drafts
     * @param  Collection<int, UserVoucher>  $shopVouchers
     */
    private function applyShopVouchers(array &$drafts, Collection $shopVouchers): void
    {
        foreach ($shopVouchers as $shopId => $owned) {
            if (! isset($drafts[$shopId])) {
                throw ValidationException::withMessages(['voucher' => 'Baucar kedai tidak sepadan dengan item dalam troli.']);
            }

            $discount = $owned->voucher->discountFor($this->vouchers->eligibleSubtotal($owned->voucher, $drafts[$shopId]['lines']));

            if ($discount <= 0) {
                throw ValidationException::withMessages(['voucher' => "Belanja minimum untuk baucar {$owned->voucher->code} belum dicapai."]);
            }

            $drafts[$shopId]['shop_discount'] = $discount;
            $drafts[$shopId]['vouchers'][] = $owned;
        }
    }

    /**
     * The platform voucher goes to the order with the largest remaining subtotal.
     *
     * @param  array<int, array<string, mixed>>  $drafts
     */
    private function applyPlatformVoucher(array &$drafts, ?UserVoucher $owned): void
    {
        if (! $owned) {
            return;
        }

        $target = collect($drafts)->sortByDesc(fn (array $d) => $d['subtotal'] - $d['shop_discount'])->keys()->first();
        $remaining = $drafts[$target]['subtotal'] - $drafts[$target]['shop_discount'];
        $discount = min($owned->voucher->discountFor($remaining), $remaining);

        if ($discount <= 0) {
            throw ValidationException::withMessages(['voucher' => "Belanja minimum untuk baucar {$owned->voucher->code} belum dicapai."]);
        }

        $drafts[$target]['platform_discount'] = $discount;
        $drafts[$target]['vouchers'][] = $owned;
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
