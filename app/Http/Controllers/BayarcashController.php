<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentProcessor;
use App\Services\Payments\BayarcashGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class BayarcashController extends Controller
{
    public function __construct(private BayarcashGateway $gateway, private PaymentProcessor $processor) {}

    /**
     * Server-to-server notification from Bayarcash. The source of truth for payment status.
     */
    public function callback(Request $request): Response
    {
        $data = $request->all();

        if (($data['record_type'] ?? null) === 'pre_transaction') {
            abort_unless($this->gateway->verifyPreTransactionCallback($data), 403);

            return response('OK');
        }

        if (! $this->gateway->verifyTransactionCallback($data)) {
            Log::warning('Bayarcash callback: checksum tidak sah', ['order_number' => $data['order_number'] ?? null]);
            abort(403);
        }

        $this->apply($data);

        return response('OK');
    }

    /**
     * The buyer's browser comes back here after paying. Also applies the result,
     * so local development works even when Bayarcash cannot reach the callback URL.
     */
    public function return(Request $request): RedirectResponse
    {
        $data = $request->query();

        if (! $this->gateway->verifyReturn($data)) {
            return redirect()->route('orders.index')->withErrors(['payment' => 'Pengesahan bayaran gagal.']);
        }

        $payment = $this->apply($data);

        return redirect()->route('orders.index')->with('success', $payment?->status === PaymentStatus::Paid
            ? 'Bayaran berjaya! Penjual akan proses pesanan anda.'
            : 'Bayaran tidak berjaya. Pesanan dibatalkan.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function apply(array $data): ?Payment
    {
        $payment = Payment::findByGatewayOrderNumber((string) ($data['order_number'] ?? ''));

        if (! $payment || abs((float) ($data['amount'] ?? 0) - $payment->amount) > 0.001) {
            Log::warning('Bayarcash: bayaran tidak dijumpai atau jumlah tidak sepadan', ['order_number' => $data['order_number'] ?? null]);

            return $payment;
        }

        $status = (int) ($data['status'] ?? -1);

        if ($status === BayarcashGateway::STATUS_SUCCESS) {
            $this->processor->markPaid($payment, $data['transaction_id'] ?? null);
        } elseif (in_array($status, BayarcashGateway::FAILED_STATUSES, true)) {
            $this->processor->markFailed($payment);
        }

        return $payment->fresh();
    }
}
