<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Models\LiveSession;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Models\Voucher;
use App\Models\WalletTransaction;
use App\Services\LoyaltyService;
use App\Services\PaymentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array{recipient_name: string, phone: string, address: string, postcode: string, city: string, state: string}
     */
    private array $address = [
        'recipient_name' => 'Ali', 'phone' => '0123456789', 'address' => 'Jalan 1',
        'postcode' => '50000', 'city' => 'KL', 'state' => 'W.P. Kuala Lumpur',
    ];

    public function test_seller_creates_a_product_voucher(): void
    {
        $shop = Shop::factory()->create();
        $product = Product::factory()->for($shop)->create();

        $this->actingAs($shop->owner)->post(route('seller.vouchers.store'), [
            'name' => 'Diskaun Kebaya', 'code' => 'kebaya10', 'scope' => 'product', 'discount_type' => 'percent',
            'discount_value' => 10, 'max_discount' => 15, 'min_spend' => 0, 'quantity' => 50,
            'ends_at' => now()->addWeek()->toDateTimeString(), 'product_ids' => [$product->id],
        ])->assertRedirect(route('seller.vouchers.index'));

        $voucher = $shop->vouchers()->sole();
        $this->assertSame('KEBAYA10', $voucher->code);
        $this->assertTrue($voucher->products->contains($product));
    }

    public function test_claim_rules_for_follower_live_quantity_and_own_shop(): void
    {
        $buyer = User::factory()->create();
        $follower = Voucher::factory()->scoped('follower')->create();
        $live = Voucher::factory()->scoped('live')->create();
        $soldOut = Voucher::factory()->create(['quantity' => 1, 'claimed_count' => 1]);

        $this->actingAs($buyer)->post(route('vouchers.claim', $follower))->assertSessionHasErrors('voucher');
        $buyer->followedShops()->attach($follower->shop_id);
        $this->actingAs($buyer)->post(route('vouchers.claim', $follower))->assertSessionHasNoErrors();
        $this->actingAs($buyer)->post(route('vouchers.claim', $follower))->assertSessionHasErrors('voucher');

        $this->actingAs($buyer)->post(route('vouchers.claim', $live))->assertSessionHasErrors('voucher');
        LiveSession::factory()->live()->create(['shop_id' => $live->shop_id]);
        $this->actingAs($buyer)->post(route('vouchers.claim', $live))->assertSessionHasNoErrors();

        $this->actingAs($buyer)->post(route('vouchers.claim', $soldOut))->assertSessionHasErrors('voucher');
        $this->actingAs($follower->shop->owner)->post(route('vouchers.claim', $follower))->assertSessionHasErrors('voucher');

        $this->assertSame(2, $buyer->vouchers()->count());
        $this->assertSame(1, $follower->fresh()->claimed_count);
    }

    public function test_shop_and_platform_vouchers_reduce_the_total_and_are_accounted_correctly(): void
    {
        $buyer = User::factory()->create(['stamps' => 9]);
        app(LoyaltyService::class)->award($buyer, 1, 'Ujian'); // RM5 baucar platform
        $product = Product::factory()->create(['price' => 100, 'stock' => 5]);
        $shopVoucher = Voucher::factory()->create(['shop_id' => $product->shop_id, 'discount_value' => 10, 'min_spend' => 50]);
        $this->actingAs($buyer)->post(route('vouchers.claim', $shopVoucher));
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($buyer)->post(route('checkout.store'), [
            ...$this->address,
            'voucher_ids' => $buyer->vouchers()->pluck('id')->all(),
        ]);

        $payment = Payment::sole();
        $order = $payment->orders()->sole();
        $this->assertEquals(10, $order->shop_discount);
        $this->assertEquals(5, $order->platform_discount);
        $this->assertEquals(100 + 8 - 10 - 5, $payment->amount);
        $this->assertSame(0, $buyer->vouchers()->whereNull('used_at')->count());

        app(PaymentProcessor::class)->markPaid($payment);

        // Penjual: 98 (selepas baucar kedai) - RM1 = 97. Platform: +1 komisen - 5 baucar.
        $this->assertEquals(97, $product->shop->walletBalance());
        $this->assertEquals(-4, WalletTransaction::whereNull('shop_id')->sum('amount'));
    }

    public function test_voucher_below_minimum_spend_is_refused(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['price' => 30, 'stock' => 5]);
        $voucher = Voucher::factory()->create(['shop_id' => $product->shop_id, 'min_spend' => 50]);
        $owned = $buyer->vouchers()->create(['voucher_id' => $voucher->id]);
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($buyer)->post(route('checkout.store'), [...$this->address, 'voucher_ids' => [$owned->id]])
            ->assertSessionHasErrors('voucher');

        $this->assertSame(0, Payment::count());
    }

    public function test_product_voucher_only_counts_selected_products(): void
    {
        $shop = Shop::factory()->create();
        $kebaya = Product::factory()->for($shop)->create(['price' => 100, 'stock' => 5]);
        $beg = Product::factory()->for($shop)->create(['price' => 200, 'stock' => 5]);
        $voucher = Voucher::factory()->scoped('product')->create([
            'shop_id' => $shop->id, 'discount_type' => DiscountType::Percent, 'discount_value' => 10, 'min_spend' => 0,
        ]);
        $voucher->products()->attach($kebaya);
        $buyer = User::factory()->create();
        $owned = $buyer->vouchers()->create(['voucher_id' => $voucher->id]);
        $buyer->cartItems()->create(['product_id' => $kebaya->id, 'quantity' => 1]);
        $buyer->cartItems()->create(['product_id' => $beg->id, 'quantity' => 1]);

        $this->actingAs($buyer)->post(route('checkout.store'), [...$this->address, 'voucher_ids' => [$owned->id]]);

        $this->assertEquals(10, Payment::sole()->orders()->sole()->shop_discount);
    }

    public function test_failed_payment_returns_vouchers(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock' => 5]);
        $voucher = Voucher::factory()->create(['shop_id' => $product->shop_id]);
        $owned = $buyer->vouchers()->create(['voucher_id' => $voucher->id]);
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($buyer)->post(route('checkout.store'), [...$this->address, 'voucher_ids' => [$owned->id]]);

        app(PaymentProcessor::class)->markFailed(Payment::sole());

        $this->assertNull($owned->fresh()->used_at);
    }

    public function test_someone_elses_voucher_cannot_be_used(): void
    {
        $product = Product::factory()->create(['price' => 100, 'stock' => 5]);
        $voucher = Voucher::factory()->create(['shop_id' => $product->shop_id]);
        $othersVoucher = User::factory()->create()->vouchers()->create(['voucher_id' => $voucher->id]);
        $buyer = User::factory()->create();
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($buyer)->post(route('checkout.store'), [...$this->address, 'voucher_ids' => [$othersVoucher->id]])
            ->assertSessionHasErrors('voucher');
    }

    public function test_live_page_shows_live_vouchers_while_live(): void
    {
        $session = LiveSession::factory()->live()->create();
        Voucher::factory()->scoped('live')->create(['shop_id' => $session->shop_id]);

        $this->get(route('live.show', $session))->assertInertia(fn ($page) => $page->has('vouchers', 1));
    }
}
