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

    /*
    | Jumlah minimum (RM) untuk setiap permintaan pengeluaran penjual.
    */
    'withdrawal_minimum' => (float) env('MARKETPLACE_WITHDRAWAL_MINIMUM', 10.00),

    /*
    | Kad cop: setiap pesanan dibayar (subjumlah >= stamp_min_order) dapat 1 cop.
    | Cukup stamps_per_card cop => baucar platform bernilai stamp_reward (RM).
    */
    'stamp_min_order' => 20.00,
    'stamps_per_card' => 10,
    'stamp_reward' => 5.00,
    'stamp_reward_valid_days' => 90,

    /*
    | Referral: cop bonus untuk pembeli, dan pesanan bebas caj untuk penjual.
    */
    'referral_stamps_referrer' => 2,
    'referral_stamps_referee' => 1,
    'referral_commission_free_orders' => 20,

];
