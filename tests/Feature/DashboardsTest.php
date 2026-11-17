<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\PaymentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardsTest extends TestCase
{
    use RefreshDatabase;

    private function paidOrderFor(Shop $shop, float $price = 100): Order
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->for($shop)->create(['price' => $price, 'stock' => 5]);
        $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

        $payment = app(CheckoutService::class)->placeOrder($buyer, [
            'recipient_name' => 'Ali', 'phone' => '0123456789', 'address' => 'Jalan 1',
            'postcode' => '50000', 'city' => 'KL', 'state' => 'W.P. Kuala Lumpur',
        ]);
        app(PaymentProcessor::class)->markPaid($payment);

        return $payment->orders()->sole();
    }

    public function test_seller_dashboard_shows_balance_after_commission(): void
    {
        $shop = Shop::factory()->create();
        $this->paidOrderFor($shop, 100);

        $this->actingAs($shop->owner)->get(route('seller.dashboard'))->assertInertia(fn (Assert $page) => $page
            ->component('Seller/Dashboard', false)
            ->where('stats.balance', 107)
            ->where('stats.ordersToShip', 1)
            ->where('stats.commissionPaid', 1)
            ->has('dailySales', 14));
    }

    public function test_seller_can_ship_a_paid_order_but_not_another_shops(): void
    {
        $shop = Shop::factory()->create();
        $order = $this->paidOrderFor($shop);
        $otherSeller = Shop::factory()->create()->owner;

        $this->actingAs($otherSeller)->patch(route('seller.orders.ship', $order), ['tracking_number' => 'X1'])->assertForbidden();

        $this->actingAs($shop->owner)->patch(route('seller.orders.ship', $order), ['tracking_number' => 'JNT123456']);

        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);
        $this->assertSame('JNT123456', $order->fresh()->tracking_number);
    }

    public function test_admin_dashboard_totals_commission_across_shops(): void
    {
        $this->paidOrderFor(Shop::factory()->create(), 100);
        $this->paidOrderFor(Shop::factory()->create(), 50);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard', false)
            ->where('stats.commission', 2)
            ->where('stats.gmv', 166)
            ->where('stats.orders', 2)
            ->has('topShops', 2));
    }

    public function test_only_admins_can_open_the_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs(Shop::factory()->create()->owner)->get(route('admin.dashboard'))->assertForbidden();
    }
}
