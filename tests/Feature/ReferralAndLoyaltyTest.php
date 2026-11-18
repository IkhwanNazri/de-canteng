<?php

namespace Tests\Feature;

use App\Enums\VoucherScope;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\LoyaltyService;
use App\Services\PaymentProcessor;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralAndLoyaltyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array{recipient_name: string, phone: string, address: string, postcode: string, city: string, state: string}
     */
    private array $address = [
        'recipient_name' => 'Ali', 'phone' => '0123456789', 'address' => 'Jalan 1',
        'postcode' => '50000', 'city' => 'KL', 'state' => 'W.P. Kuala Lumpur',
    ];

    private function buyAndPay(User $buyer, float $price = 50): void
    {
        $product = Product::factory()->create(['price' => $price, 'stock' => 10]);
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
        app(PaymentProcessor::class)->markPaid(app(CheckoutService::class)->placeOrder($buyer, $this->address));
    }

    public function test_registration_with_a_referral_code_links_the_new_buyer(): void
    {
        $referrer = User::factory()->create();
        $code = app(ReferralService::class)->codeFor($referrer);

        $this->get(route('register', ['ref' => strtolower($code)]))->assertOk();
        $this->post(route('register'), [
            'name' => 'Kawan', 'email' => 'kawan@example.com', 'password' => 'password',
            'password_confirmation' => 'password', 'referral_code' => $code,
        ]);

        $friend = User::firstWhere('email', 'kawan@example.com');
        $this->assertSame($referrer->id, $friend->referred_by_id);
        $this->assertNotNull($friend->referral_code);
    }

    public function test_unknown_referral_code_is_rejected(): void
    {
        $this->post(route('register'), [
            'name' => 'X', 'email' => 'x@example.com', 'password' => 'password',
            'password_confirmation' => 'password', 'referral_code' => 'NOPE1234',
        ])->assertSessionHasErrors('referral_code');
    }

    public function test_referral_stamps_are_given_once_after_the_first_paid_order(): void
    {
        $referrer = User::factory()->create();
        $friend = User::factory()->create(['referred_by_id' => $referrer->id]);

        $this->buyAndPay($friend);
        $this->buyAndPay($friend);

        $this->assertSame(2, $referrer->fresh()->stamps);
        $this->assertSame(1 + 2, $friend->fresh()->stamps, '1 referral bonus + 1 stamp per paid order');
    }

    public function test_small_orders_do_not_earn_a_stamp(): void
    {
        $buyer = User::factory()->create();

        $this->buyAndPay($buyer, 10);

        $this->assertSame(0, $buyer->fresh()->stamps);
    }

    public function test_a_full_card_turns_into_a_platform_voucher(): void
    {
        $buyer = User::factory()->create(['stamps' => 9]);

        app(LoyaltyService::class)->award($buyer, 2, 'Ujian');

        $buyer->refresh();
        $this->assertSame(1, $buyer->stamps);
        $owned = $buyer->vouchers()->with('voucher')->sole();
        $this->assertSame(VoucherScope::Platform, $owned->voucher->scope);
        $this->assertEquals(5, $owned->voucher->discount_value);
    }

    public function test_seller_referral_gives_both_shops_commission_free_orders(): void
    {
        $referrerShop = Shop::factory()->create();
        $code = app(ReferralService::class)->codeFor($referrerShop->owner);
        $newSeller = User::factory()->create();

        $this->actingAs($newSeller)->post(route('shops.store'), [
            'name' => 'Batik Baru', 'bank_name' => 'CIMB', 'bank_account' => '8000123456', 'referral_code' => $code,
        ])->assertRedirect(route('seller.products.index'));

        $newShop = $newSeller->fresh()->shop;
        $this->assertSame(20, $newShop->commission_free_orders);
        $this->assertSame(20, $referrerShop->fresh()->commission_free_orders);
        $this->assertSame($referrerShop->id, $newShop->referred_by_shop_id);
    }

    public function test_commission_free_orders_skip_the_rm1_fee(): void
    {
        $product = Product::factory()->create(['price' => 100, 'stock' => 5]);
        $product->shop->update(['commission_free_orders' => 1]);
        $buyer = User::factory()->create();
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

        app(PaymentProcessor::class)->markPaid(app(CheckoutService::class)->placeOrder($buyer, $this->address));

        $this->assertEquals(108, $product->shop->walletBalance());
        $this->assertSame(0, $product->shop->fresh()->commission_free_orders);
    }

    public function test_dashboard_shows_stamp_card_and_referral_code(): void
    {
        $user = User::factory()->create(['stamps' => 4]);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn ($page) => $page
            ->component('Dashboard', false)
            ->where('loyalty.stamps', 4)
            ->where('loyalty.perCard', 10)
            ->where('referral.code', $user->fresh()->referral_code));
    }
}
