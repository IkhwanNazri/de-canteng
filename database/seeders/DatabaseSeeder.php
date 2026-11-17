<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\LiveSession;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\PaymentProcessor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Data contoh untuk demo — semua nama kedai & produk adalah rekaan.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin DeBatik',
            'email' => 'admin@debatik.test',
        ]);

        $buyer = User::factory()->create([
            'name' => 'Pembeli Demo',
            'email' => 'pembeli@debatik.test',
        ]);

        $categories = collect([
            'Batik Moden', 'Batik Tradisional', 'Batik Korporat',
            'Batik Wanita', 'Batik Lelaki', 'Aksesori Batik',
        ])->map(fn ($name, $i) => Category::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'sort' => $i,
        ]));

        $shops = collect([
            ['Batik Citra', 4.9, 12500, 1240],
            ['Batik Nusantara', 4.8, 9200, 846],
            ['Kedai Batik Aziz', 4.9, 7600, 439],
        ])->map(function ($data) {
            [$name, $rating, $followers, $viewers] = $data;
            $seller = User::factory()->seller()->create([
                'name' => "Pemilik {$name}",
                'email' => Str::slug($name).'@debatik.test',
            ]);

            $shop = Shop::create([
                'user_id' => $seller->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "Kedai {$name} — batik tempatan buatan tangan.",
                'rating' => $rating,
                'followers_count' => $followers,
                'is_verified' => true,
                'bank_name' => 'Maybank',
                'bank_account' => '1640 0000 0000',
            ]);

            LiveSession::create([
                'shop_id' => $shop->id,
                'title' => "Live {$name}: koleksi terbaru",
                'status' => 'live',
                'viewers_count' => $viewers,
                'started_at' => now()->subMinutes(20),
            ]);

            return $shop;
        });

        $products = [
            ['Kebaya Batik Premium Motif Sakura', 'batik-wanita', 189, 229],
            ['Baju Kurung Batik Moden', 'batik-moden', 189, null],
            ['Kemeja Batik Lelaki Premium', 'batik-lelaki', 159, null],
            ['Kain Batik Canting Coklat 2 Meter', 'batik-tradisional', 85, 99],
            ['Kemeja Batik Korporat Biru', 'batik-korporat', 139, null],
            ['Beg Tangan Batik Ungu', 'aksesori-batik', 75, 89],
            ['Selendang Batik Sutera Hijau', 'batik-wanita', 120, null],
            ['Kemeja Batik Lengan Pendek Emas', 'batik-lelaki', 129, 149],
            ['Dress Batik Moden Bunga Raya', 'batik-moden', 210, 250],
        ];

        foreach ($products as $i => [$name, $catSlug, $price, $compare]) {
            Product::create([
                'shop_id' => $shops[$i % 3]->id,
                'category_id' => $categories->firstWhere('slug', $catSlug)->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "{$name}. Kain selesa, jahitan kemas, sesuai untuk majlis rasmi dan harian.",
                'price' => $price,
                'compare_price' => $compare,
                'stock' => rand(5, 40),
                'sold_count' => rand(50, 1500),
                'rating' => [4.9, 4.8, 4.7][$i % 3],
                'reviews_count' => rand(20, 300),
                'is_featured' => $i < 4,
                'sizes' => in_array($catSlug, ['batik-wanita', 'batik-lelaki', 'batik-moden', 'batik-korporat'], true) ? ['S', 'M', 'L', 'XL'] : null,
                'colors' => $catSlug === 'aksesori-batik' ? null : ['Hijau', 'Biru', 'Coklat'],
            ]);
        }

        foreach ($shops as $shop) {
            $live = $shop->liveSessions()->first();
            $productIds = $shop->products()->pluck('id');
            $live->products()->sync($productIds);
            $live->update(['pinned_product_id' => $productIds->first()]);
        }

        $this->seedDemoOrders($buyer);
    }

    /**
     * A few paid orders spread over the last two weeks so dashboards have data.
     */
    private function seedDemoOrders(User $buyer): void
    {
        $address = [
            'recipient_name' => 'Pembeli Demo', 'phone' => '012-345 6789', 'address' => '12 Jalan Batik',
            'postcode' => '20000', 'city' => 'Kuala Terengganu', 'state' => 'Terengganu',
        ];

        foreach (Product::orderBy('id')->take(6)->get() as $index => $product) {
            $buyer->cartItems()->create([
                'product_id' => $product->id,
                'size' => $product->sizes[1] ?? null,
                'color' => $product->colors[0] ?? null,
                'quantity' => 1 + $index % 2,
            ]);
            $payment = app(CheckoutService::class)->placeOrder($buyer, $address);
            app(PaymentProcessor::class)->markPaid($payment);

            $paidAt = now()->subDays(12 - $index * 2)->setTime(10 + $index, 0);
            $payment->orders()->update(['paid_at' => $paidAt, 'created_at' => $paidAt]);
        }
    }
}
