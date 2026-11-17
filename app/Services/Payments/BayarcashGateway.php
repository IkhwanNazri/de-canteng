<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Bayarcash API v3 (FPX, DuitNow, e-wallet). Mirrors the request format and
 * checksum rules of the official bayarcash/php-sdk without adding the package.
 */
class BayarcashGateway implements PaymentGateway
{
    public const STATUS_SUCCESS = 3;

    /** @var list<int> */
    public const FAILED_STATUSES = [2, 4, 5, 7];

    public function name(): string
    {
        return 'bayarcash';
    }

    public function checkoutUrl(Payment $payment): string
    {
        $payment->loadMissing('user', 'orders');
        $firstOrder = $payment->orders->first();

        $data = [
            'portal_key' => config('services.bayarcash.portal_key'),
            'order_number' => $payment->gatewayOrderNumber(),
            'amount' => number_format($payment->amount, 2, '.', ''),
            'payer_name' => $firstOrder?->recipient_name ?? $payment->user->name,
            'payer_email' => $payment->user->email,
            'payer_telephone_number' => preg_replace('/\D/', '', (string) $firstOrder?->phone),
            'return_url' => route('bayarcash.return'),
            'callback_url' => route('bayarcash.callback'),
        ];
        $data['checksum'] = $this->paymentIntentChecksum($data);

        $response = Http::withToken((string) config('services.bayarcash.token'))
            ->acceptJson()
            ->asForm()
            ->timeout(20)
            ->post($this->baseUrl().'payment-intents', $data);

        if (! $response->successful() || ! $response->json('url')) {
            throw new RuntimeException('Bayarcash: gagal cipta payment intent ('.$response->status().') '.$response->body());
        }

        $payment->update(['gateway_reference' => $response->json('id'), 'payload' => ['intent' => $response->json()]]);

        return $response->json('url');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function paymentIntentChecksum(array $data): string
    {
        return $this->sign([
            'payment_channel' => implode(',', (array) ($data['payment_channel'] ?? [])),
            'order_number' => $data['order_number'],
            'amount' => $data['amount'],
            'payer_name' => $data['payer_name'],
            'payer_email' => $data['payer_email'],
        ]);
    }

    /**
     * Server-to-server transaction callback (POST, form-encoded).
     *
     * @param  array<string, mixed>  $data
     */
    public function verifyTransactionCallback(array $data): bool
    {
        return $this->matches($data, [
            'record_type', 'transaction_id', 'exchange_reference_number', 'exchange_transaction_id', 'order_number',
            'currency', 'amount', 'payer_name', 'payer_email', 'payer_bank_name', 'status', 'status_description', 'datetime',
        ]);
    }

    /**
     * Pre-transaction callback, sent before the transaction record exists.
     *
     * @param  array<string, mixed>  $data
     */
    public function verifyPreTransactionCallback(array $data): bool
    {
        return $this->matches($data, ['record_type', 'exchange_reference_number', 'order_number']);
    }

    /**
     * Payer's browser redirect back to return_url (GET query on v3).
     *
     * @param  array<string, mixed>  $data
     */
    public function verifyReturn(array $data): bool
    {
        return $this->matches($data, [
            'transaction_id', 'exchange_reference_number', 'exchange_transaction_id', 'order_number',
            'currency', 'amount', 'payer_bank_name', 'status', 'status_description',
        ]);
    }

    private function baseUrl(): string
    {
        return config('services.bayarcash.sandbox')
            ? 'https://api.console.bayarcash-sandbox.com/v3/'
            : 'https://api.console.bayar.cash/v3/';
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $fields
     */
    private function matches(array $data, array $fields): bool
    {
        $payload = [];

        foreach ($fields as $field) {
            $payload[$field] = $data[$field] ?? null;
        }

        return hash_equals($this->sign($payload), (string) ($data['checksum'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sign(array $payload): string
    {
        ksort($payload);

        return hash_hmac('sha256', implode('|', $payload), (string) config('services.bayarcash.secret'));
    }
}
