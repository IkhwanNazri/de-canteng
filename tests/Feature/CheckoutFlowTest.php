<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\PaymentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array{recipient_name: string, phone: string, address: string, postcode: string, city: string, state: string}
     */
    private array $address = [
        'recipient_name' => 'Siti Aminah',
        'phone' => '012-345 6789',
        'address' => '12 Jalan Batik',
        'postcode' => '20000',
        'city' => 'Kuala Terengganu',
        'state' => 'Terengganu',
    ];

    public function test_adding_to_cart_is_capped_at_available_stock(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['stock' => 3]);

        $this->actingAs($buyer)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->actingAs($buyer)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 5]);

        $this->assertSame(3, $buyer->cartItems()->sole()->quantity);
    }

    public function test_seller_cannot_buy_own_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($product->shop->owner)
            ->post(route('cart.store'), ['product_id' => $product->id])
            ->assertSessionHasErrors('cart');
    }

    public function test_checkout_creates_one_order_per_shop_and_a_single_payment(): void
    {
        $buyer = User::factory()->create();
        $kebaya = Product::factory()->create(['price' => 100, 'stock' => 5]);
        $kemeja = Product::factory()->create(['price' => 50, 'stock' => 5]);
        $buyer->cartItems()->create(['product_id' => $kebaya->id, 'quantity' => 2]);
        $buyer->cartItems()->create(['product_id' => $kemeja->id, 'quantity' => 1]);

        $response = $this->actingAs($buyer)->post(route('checkout.store'), $this->address);

        $payment = Payment::sole();
        $response->assertRedirect(route('payments.simulate', $payment));
        $this->actingAs($buyer)->get(route('payments.simulate', $payment))->assertOk();
        $this->assertSame(2, $payment->orders()->count());
        $this->assertEquals(200 + 8 + 50 + 8, $payment->amount);
        $this->assertSame(0, $buyer->cartItems()->count());
        $this->assertSame(5, $kebaya->fresh()->stock, 'Stock is only reduced after payment');
    }

    public function test_checkout_fails_when_stock_ran_out(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['stock' => 1]);
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
        $product->update(['stock' => 0]);

        $this->actingAs($buyer)->post(route('checkout.store'), $this->address)->assertSessionHasErrors('cart');
        $this->assertSame(0, Payment::count());
    }

    public function test_successful_payment_settles_orders_and_records_rm1_commission(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['price' => 189, 'stock' => 10, 'sold_count' => 0]);
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 2]);
        $this->actingAs($buyer)->post(route('checkout.store'), $this->address);
        $payment = Payment::sole();

        $this->actingAs($buyer)
            ->post(route('payments.simulate.complete', $payment), ['result' => 'paid'])
            ->assertRedirect(route('orders.index'));

        $order = $payment->orders()->sole();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertEquals(1.00, $order->commission);
        $this->assertEquals(386 - 1, $order->seller_amount);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(2, $product->fresh()->sold_count);
        $this->assertEquals(385, $product->shop->walletBalance());
        $this->assertEquals(1.00, WalletTransaction::whereNull('shop_id')->sum('amount'));
    }

    public function test_payment_callbacks_are_idempotent(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($buyer)->post(route('checkout.store'), $this->address);
        $payment = Payment::sole();

        app(PaymentProcessor::class)->markPaid($payment);
        app(PaymentProcessor::class)->markPaid($payment);

        $this->assertSame(9, $product->fresh()->stock);
        $this->assertSame(3, WalletTransaction::count());
    }

    public function test_failed_payment_cancels_orders(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['stock' => 4]);
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($buyer)->post(route('checkout.store'), $this->address);
        $payment = Payment::sole();

        $this->actingAs($buyer)->post(route('payments.simulate.complete', $payment), ['result' => 'failed']);

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Cancelled, $payment->orders()->sole()->status);
        $this->assertSame(0, WalletTransaction::count());
    }

    public function test_other_users_cannot_open_someone_elses_payment_or_order(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create();
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($buyer)->post(route('checkout.store'), $this->address);
        $payment = Payment::sole();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('payments.simulate', $payment))->assertNotFound();
        $this->actingAs($stranger)->post(route('payments.simulate.complete', $payment), ['result' => 'paid'])->assertNotFound();
        $this->actingAs($stranger)->get(route('orders.show', $payment->orders()->sole()->number))->assertNotFound();
    }
}
