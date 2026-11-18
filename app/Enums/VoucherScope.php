<?php

namespace App\Enums;

enum VoucherScope: string
{
    /** Semua produk kedai. */
    case Shop = 'shop';

    /** Produk tertentu sahaja. */
    case Product = 'product';

    /** Hanya untuk pengikut kedai. */
    case Follower = 'follower';

    /** Hanya boleh dituntut semasa kedai sedang live. */
    case Live = 'live';

    /** Baucar platform (cth ganjaran kad cop), ditanggung platform. */
    case Platform = 'platform';

    public function label(): string
    {
        return match ($this) {
            self::Shop => 'Semua produk',
            self::Product => 'Produk terpilih',
            self::Follower => 'Pengikut sahaja',
            self::Live => 'Masa live sahaja',
            self::Platform => 'Baucar DeBatik',
        };
    }
}
