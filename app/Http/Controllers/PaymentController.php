<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Fake payment page used by the "simulasi" gateway during development.
     */
    public function simulate(Request $request, Payment $payment): Response|RedirectResponse
    {
        $this->authorizeSimulation($request, $payment);

        if ($payment->status !== PaymentStatus::Pending) {
            return redirect()->route('orders.index');
        }

        return Inertia::render('Payments/Simulate', [
            'payment' => $payment->load('orders:id,payment_id,number,total'),
        ]);
    }

    public function completeSimulation(Request $request, Payment $payment, PaymentProcessor $processor): RedirectResponse
    {
        $this->authorizeSimulation($request, $payment);

        $succeeded = $request->validate(['result' => ['required', 'in:paid,failed']])['result'] === 'paid';

        if ($succeeded) {
            $processor->markPaid($payment, 'SIM-'.strtoupper(substr($payment->reference, 0, 8)));

            return redirect()->route('orders.index')->with('success', 'Bayaran berjaya! Penjual akan proses pesanan anda.');
        }

        $processor->markFailed($payment);

        return redirect()->route('orders.index')->with('success', 'Bayaran gagal. Pesanan dibatalkan.');
    }

    private function authorizeSimulation(Request $request, Payment $payment): void
    {
        abort_unless($payment->gateway === 'simulasi' && $payment->user_id === $request->user()->id, 404);
    }
}
