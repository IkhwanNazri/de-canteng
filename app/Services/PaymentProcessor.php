<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\WalletEntryType;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WalletTransaction;
use App\Notifications\NewOrderNotification;
use App\Notifications\OrderPaidNotification;
use Illuminate\Support\Facades\DB;

/**
 * Applies the outcome of a payment. Safe to call more than once (gateways
 * may send the same callback repeatedly).
 */
class PaymentProcessor
{
    public function markPaid(Payment $payment, ?string $gatewayReference = null): void
    {
        $settled = DB::transaction(function () use ($payment, $gatewayReference) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if ($payment->status === PaymentStatus::Paid) {
                return false;
            }

            $payment->update([
                'status' => PaymentStatus::Paid,
                'gateway_reference' => $gatewayReference,
                'paid_at' => now(),
            ]);

            $payment->orders()->with('items.product')->each(fn (Order $order) => $this->settleOrder($order));

            return true;
        });

        if ($settled) {
            $this->notifyParties($payment);
        }
    }

    /**
     * Emails go out only after the transaction has committed.
     */
    private function notifyParties(Payment $payment): void
    {
        foreach ($payment->orders()->with('buyer', 'shop.owner', 'items')->get() as $order) {
            $order->buyer->notify(new OrderPaidNotification($order));
            $order->shop->owner->notify(new NewOrderNotification($order));
        }
    }

    public function markFailed(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            if ($payment->fresh()->status !== PaymentStatus::Pending) {
                return;
            }

            $payment->update(['status' => PaymentStatus::Failed]);
            $payment->orders()->update(['status' => OrderStatus::Cancelled]);
        });
    }

    private function settleOrder(Order $order): void
    {
        $commission = min((float) config('marketplace.commission_per_order'), $order->total);

        $order->update([
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
            'commission' => $commission,
            'seller_amount' => $order->total - $commission,
        ]);

        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->update([
                    'stock' => max(0, $item->product->stock - $item->quantity),
                    'sold_count' => $item->product->sold_count + $item->quantity,
                ]);
            }
        }

        $entries = [
            [$order->shop_id, WalletEntryType::SaleCredit, $order->total, "Jualan {$order->number}"],
            [$order->shop_id, WalletEntryType::CommissionDebit, -$commission, "Caj platform {$order->number}"],
            [null, WalletEntryType::CommissionCredit, $commission, "Komisen {$order->number}"],
        ];

        foreach ($entries as [$shopId, $type, $amount, $description]) {
            WalletTransaction::create([
                'shop_id' => $shopId,
                'order_id' => $order->id,
                'type' => $type,
                'amount' => $amount,
                'description' => $description,
            ]);
        }
    }
}
