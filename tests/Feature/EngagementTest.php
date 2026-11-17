<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    private function orderWith(Product $product, OrderStatus $status): Order
    {
        $order = Order::factory()->create(['shop_id' => $product->shop_id, 'status' => $status]);
        $order->items()->create([
            'product_id' => $product->id, 'product_name' => $product->name,
            'price' => $product->price, 'quantity' => 1, 'line_total' => $product->price,
        ]);

        return $order;
    }

    public function test_user_can_toggle_a_favorite(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->post(route('favorites.toggle', $product));
        $this->assertTrue($user->favoriteProducts()->whereKey($product->id)->exists());

        $this->actingAs($user)->post(route('favorites.toggle', $product));
        $this->assertFalse($user->favoriteProducts()->whereKey($product->id)->exists());
    }

    public function test_following_a_shop_updates_the_follower_count(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['followers_count' => 10]);

        $this->actingAs($user)->post(route('shops.follow', $shop->slug));
        $this->assertSame(11, $shop->fresh()->followers_count);

        $this->actingAs($user)->post(route('shops.follow', $shop->slug));
        $this->assertSame(10, $shop->fresh()->followers_count);

        $this->actingAs($shop->owner)->post(route('shops.follow', $shop->slug))->assertStatus(422);
    }

    public function test_buyer_can_review_a_received_product_once_and_rating_is_recalculated(): void
    {
        $product = Product::factory()->create(['rating' => 0, 'reviews_count' => 0]);
        $order = $this->orderWith($product, OrderStatus::Shipped);

        $this->actingAs($order->buyer)->post(route('orders.complete', $order->number));
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);

        $this->actingAs($order->buyer)->post(route('reviews.store', $order->number), ['product_id' => $product->id, 'rating' => 4, 'body' => 'Kain cantik']);
        $this->actingAs($order->buyer)
            ->post(route('reviews.store', $order->number), ['product_id' => $product->id, 'rating' => 1])
            ->assertSessionHasErrors('product_id');

        $this->assertEquals(4.0, $product->fresh()->rating);
        $this->assertSame(1, $product->fresh()->reviews_count);
    }

    public function test_reviews_are_closed_before_shipping_and_for_other_users(): void
    {
        $product = Product::factory()->create();
        $paidOrder = $this->orderWith($product, OrderStatus::Paid);

        $this->actingAs($paidOrder->buyer)
            ->post(route('reviews.store', $paidOrder->number), ['product_id' => $product->id, 'rating' => 5])
            ->assertStatus(422);

        $shipped = $this->orderWith($product, OrderStatus::Shipped);
        $this->actingAs(User::factory()->create())
            ->post(route('reviews.store', $shipped->number), ['product_id' => $product->id, 'rating' => 5])
            ->assertNotFound();
    }
}
