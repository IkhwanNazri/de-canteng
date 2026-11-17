<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_save_sizes_and_colors_as_comma_separated_text(): void
    {
        $shop = Shop::factory()->create();

        $this->actingAs($shop->owner)->post(route('seller.products.store'), [
            'name' => 'Baju Kurung', 'price' => 150, 'stock' => 10, 'sizes' => 'S, M , L,', 'colors' => 'Hijau, Biru',
        ]);

        $product = $shop->products()->sole();
        $this->assertSame(['S', 'M', 'L'], $product->sizes);
        $this->assertSame(['Hijau', 'Biru'], $product->colors);
    }

    public function test_seller_form_sends_options_as_a_list(): void
    {
        $shop = Shop::factory()->create();

        $this->actingAs($shop->owner)->post(route('seller.products.store'), [
            'name' => 'Kemeja', 'price' => 99, 'stock' => 4, 'sizes' => ['S', ' L ', ''], 'colors' => [],
        ])->assertSessionHasNoErrors();

        $product = $shop->products()->sole();
        $this->assertSame(['S', 'L'], $product->sizes);
        $this->assertNull($product->colors);

        $this->actingAs($shop->owner)->post(route('seller.products.store'), [
            'name' => 'Kemeja 2', 'price' => 99, 'stock' => 4, 'sizes' => ['M', 'M'],
        ])->assertSessionHasErrors('sizes.0');
    }

    public function test_size_and_color_must_be_chosen_from_the_product_options(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['sizes' => ['M', 'L'], 'colors' => ['Hijau']]);

        $this->actingAs($buyer)->post(route('cart.store'), ['product_id' => $product->id])->assertSessionHasErrors(['size', 'color']);
        $this->actingAs($buyer)->post(route('cart.store'), ['product_id' => $product->id, 'size' => 'XXL', 'color' => 'Hijau'])->assertSessionHasErrors('size');
        $this->assertSame(0, $buyer->cartItems()->count());
    }

    public function test_each_variant_is_a_separate_cart_line_and_is_kept_on_the_order(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['sizes' => ['M', 'L'], 'colors' => null, 'stock' => 5]);

        $this->actingAs($buyer)->post(route('cart.store'), ['product_id' => $product->id, 'size' => 'M']);
        $this->actingAs($buyer)->post(route('cart.store'), ['product_id' => $product->id, 'size' => 'L', 'quantity' => 2]);
        $this->assertSame(2, $buyer->cartItems()->count());

        $this->actingAs($buyer)->post(route('checkout.store'), [
            'recipient_name' => 'Ali', 'phone' => '0123456789', 'address' => 'Jalan 1',
            'postcode' => '50000', 'city' => 'KL', 'state' => 'W.P. Kuala Lumpur',
        ]);

        $items = Payment::sole()->orders()->sole()->items()->orderBy('size')->get();
        $this->assertSame(['L', 'M'], $items->pluck('size')->all());
        $this->assertSame([2, 1], $items->pluck('quantity')->all());
    }

    public function test_combined_variant_quantities_cannot_exceed_stock(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['sizes' => ['M', 'L'], 'stock' => 2]);
        $buyer->cartItems()->create(['product_id' => $product->id, 'size' => 'M', 'quantity' => 2]);
        $buyer->cartItems()->create(['product_id' => $product->id, 'size' => 'L', 'quantity' => 1]);

        $this->actingAs($buyer)->post(route('checkout.store'), [
            'recipient_name' => 'Ali', 'phone' => '0123456789', 'address' => 'Jalan 1',
            'postcode' => '50000', 'city' => 'KL', 'state' => 'W.P. Kuala Lumpur',
        ])->assertSessionHasErrors('cart');
    }
}
