<?php

namespace App\Services\Payments;

use App\Models\Payment;

/**
 * Development-only gateway: shows a page where the buyer chooses whether the
 * payment succeeds or fails. No real money moves.
 */
class SimulatedGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'simulasi';
    }

    public function checkoutUrl(Payment $payment): string
    {
        return route('payments.simulate', $payment);
    }
}
