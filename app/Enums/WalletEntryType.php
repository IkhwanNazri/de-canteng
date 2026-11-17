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
}
