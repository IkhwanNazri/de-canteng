<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BayarcashPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'marketplace.payment_gateway' => 'bayarcash',
            'services.bayarcash' => ['token' => 'tok', 'secret' => self::SECRET, 'portal_key' => 'portal-1', 'sandbox' => true],
        ]);
    }

    private function checkout(): Payment
    {
        $buyer = User::factory()->create(['email' => 'siti@example.com']);
        $product = Product::factory()->create(['price' => 100, 'stock' => 5]);
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($buyer)->post(route('checkout.store'), [
            'recipient_name' => 'Siti', 'phone' => '012-345 6789', 'address' => 'Jalan 1',
            'postcode' => '20000', 'city' => 'KT', 'state' => 'Terengganu',
        ]);

        return Payment::sole();
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function signed(array $fields): array
    {
        $payload = $fields;
        ksort($payload);

        return [...$fields, 'checksum' => hash_hmac('sha256', implode('|', $payload), self::SECRET)];
    }

    /**
     * @return array<string, mixed>
     */
    private function callbackData(Payment $payment, int $status, ?string $amount = null): array
    {
        return $this->signed([
            'record_type' => 'transaction', 'transaction_id' => 'trx_123', 'exchange_reference_number' => 'EX1',
            'exchange_transaction_id' => 'EXT1', 'order_number' => $payment->gatewayOrderNumber(), 'currency' => 'MYR',
            'amount' => $amount ?? number_format($payment->amount, 2, '.', ''), 'payer_name' => 'Siti',
            'payer_email' => 'siti@example.com', 'payer_bank_name' => 'Maybank2u', 'status' => (string) $status,
            'status_description' => 'x', 'datetime' => '2026-11-17 10:00:00',
        ]);
    }

    public function test_checkout_creates_a_signed_payment_intent_and_redirects_to_bayarcash(): void
    {
        Http::fake(['api.console.bayarcash-sandbox.com/v3/payment-intents' => Http::response(['id' => 'pi_1', 'url' => 'https://pay.bayarcash.test/pi_1'])]);

        $payment = $this->checkout();

        Http::assertSent(function (Request $request) use ($payment) {
            $expected = hash_hmac('sha256', implode('|', ['108.00', $payment->gatewayOrderNumber(), 'siti@example.com', 'Siti', '']), self::SECRET);

            return $request->hasHeader('Authorization', 'Bearer tok')
                && $request['portal_key'] === 'portal-1'
                && $request['amount'] === '108.00'
                && $request['payer_telephone_number'] === '0123456789'
                && $request['checksum'] === $expected;
        });
        $this->assertSame('pi_1', $payment->gateway_reference);
    }

    public function test_gateway_failure_restores_the_cart(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Unauthorized'], 401)]);

        $payment = $this->checkout();

        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertSame(1, $payment->user->cartItems()->count());
    }

    public function test_valid_success_callback_marks_payment_paid(): void
    {
        Http::fake(['*' => Http::response(['id' => 'pi_1', 'url' => 'https://pay.test'])]);
        $payment = $this->checkout();

        $this->post(route('bayarcash.callback'), $this->callbackData($payment, 3))->assertOk();

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $payment->orders()->sole()->status);
    }

    public function test_callback_with_bad_checksum_or_wrong_amount_is_rejected(): void
    {
        Http::fake(['*' => Http::response(['id' => 'pi_1', 'url' => 'https://pay.test'])]);
        $payment = $this->checkout();

        $tampered = [...$this->callbackData($payment, 3), 'amount' => '1.00'];
        $this->post(route('bayarcash.callback'), $tampered)->assertForbidden();

        $this->post(route('bayarcash.callback'), $this->callbackData($payment, 3, '1.00'))->assertOk();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_failed_status_from_return_url_cancels_orders(): void
    {
        Http::fake(['*' => Http::response(['id' => 'pi_1', 'url' => 'https://pay.test'])]);
        $payment = $this->checkout();

        $query = $this->signed([
            'transaction_id' => 'trx_1', 'exchange_reference_number' => 'EX1', 'exchange_transaction_id' => 'EXT1',
            'order_number' => $payment->gatewayOrderNumber(), 'currency' => 'MYR', 'amount' => '108.00',
            'payer_bank_name' => 'CIMB', 'status' => '2', 'status_description' => 'Failed',
        ]);

        $this->get(route('bayarcash.return', $query))->assertRedirect(route('orders.index'));

        $this->assertSame(OrderStatus::Cancelled, $payment->orders()->sole()->status);
    }
}
