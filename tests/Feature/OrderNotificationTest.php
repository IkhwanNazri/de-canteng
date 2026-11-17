<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Notifications\OrderPaidNotification;
use App\Notifications\OrderShippedNotification;
use App\Services\CheckoutService;
use App\Services\PaymentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_and_seller_are_emailed_once_when_payment_succeeds_and_buyer_when_shipped(): void
    {
        Notification::fake();
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['stock' => 3]);
        $seller = $product->shop->owner;
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

        $payment = app(CheckoutService::class)->placeOrder($buyer, [
            'recipient_name' => 'Ali', 'phone' => '0123456789', 'address' => 'Jalan 1',
            'postcode' => '50000', 'city' => 'KL', 'state' => 'W.P. Kuala Lumpur',
        ]);
        app(PaymentProcessor::class)->markPaid($payment);
        app(PaymentProcessor::class)->markPaid($payment);

        Notification::assertSentToTimes($buyer, OrderPaidNotification::class, 1);
        Notification::assertSentToTimes($seller, NewOrderNotification::class, 1);

        $order = $payment->orders()->sole();
        $this->actingAs($seller)->patch(route('seller.orders.ship', $order), ['tracking_number' => 'JNT1']);

        Notification::assertSentTo($buyer, OrderShippedNotification::class, fn ($notification) => $notification->order->is($order));
    }

    public function test_seller_email_shows_amount_after_commission(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock' => 3]);
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
        $payment = app(CheckoutService::class)->placeOrder($buyer, [
            'recipient_name' => 'Ali', 'phone' => '0123456789', 'address' => 'Jalan 1',
            'postcode' => '50000', 'city' => 'KL', 'state' => 'W.P. Kuala Lumpur',
        ]);
        Notification::fake();
        app(PaymentProcessor::class)->markPaid($payment);

        $order = $payment->orders()->sole();
        $mail = (new NewOrderNotification($order->fresh(['shop', 'items'])))->toMail($product->shop->owner);

        $this->assertStringContainsString('RM107.00', implode(' ', $mail->introLines));
    }
}
