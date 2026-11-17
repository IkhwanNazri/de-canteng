<?php

namespace App\Services\Payments;

use App\Models\Payment;

interface PaymentGateway
{
    /**
     * Gateway key stored on the payment record.
     */
    public function name(): string;

    /**
     * URL the buyer is sent to in order to pay.
     */
    public function checkoutUrl(Payment $payment): string;
}
