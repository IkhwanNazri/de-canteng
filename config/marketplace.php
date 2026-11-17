<?php

return [

    /*
    | Caj platform (RM) untuk setiap pesanan yang dibayar. Dipotong daripada
    | bahagian penjual dan masuk ke akaun platform.
    */
    'commission_per_order' => (float) env('MARKETPLACE_COMMISSION', 1.00),

    /*
    | Kos pos rata (RM) untuk setiap pesanan (setiap kedai).
    */
    'shipping_fee' => (float) env('MARKETPLACE_SHIPPING_FEE', 8.00),

    /*
    | Gateway bayaran: "simulasi" (dev, tiada duit sebenar) atau "bayarcash".
    */
    'payment_gateway' => env('PAYMENT_GATEWAY', 'simulasi'),

];
