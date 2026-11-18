<?php

namespace App\Enums;

enum WalletEntryType: string
{
    /** Seller receives the order total. */
    case SaleCredit = 'sale_credit';

    /** RM1 platform fee deducted from the seller. */
    case CommissionDebit = 'commission_debit';

    /** RM1 platform fee credited to the platform owner (shop_id is null). */
    case CommissionCredit = 'commission_credit';

    /** Seller balance held for a withdrawal request. */
    case WithdrawalHold = 'withdrawal_hold';

    /** Held balance returned when a withdrawal is rejected. */
    case WithdrawalReversal = 'withdrawal_reversal';

    /** Platform pays the seller for a platform voucher used on an order (shop_id is null, negative). */
    case PlatformVoucherDebit = 'platform_voucher_debit';
}
