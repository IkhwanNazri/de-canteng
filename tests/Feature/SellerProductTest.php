<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_add_a_product_with_an_image(): void
    {
        Storage::fake('public');
        $shop = Shop::factory()->create();

        $this->actingAs($shop->owner)
            ->post(route('seller.products.store'), [
                'name' => 'Kemeja Batik Biru',
                'price' => 120,
                'stock' => 10,
                'image' => UploadedFile::fake()->image('kemeja.jpg'),
            ])
            ->assertRedirect(route('seller.products.index'));

        $product = $shop->products()->sole();
        $this->assertStringStartsWith('/storage/products/', $product->image);
        Storage::disk('public')->assertExists($product->storedImagePath());
    }

    public function test_seller_cannot_edit_another_shops_product(): void
    {
        $product = Product::factory()->create();
        $otherShop = Shop::factory()->create();

        $this->actingAs($otherShop->owner)->get(route('seller.products.edit', $product))->assertForbidden();
        $this->actingAs($otherShop->owner)->delete(route('seller.products.destroy', $product))->assertForbidden();
        $this->assertModelExists($product);
    }

    public function test_seller_can_update_and_delete_own_product(): void
    {
        $product = Product::factory()->create(['price' => 100]);
        $owner = $product->shop->owner;

        $this->actingAs($owner)->put(route('seller.products.update', $product), [
            'name' => $product->name, 'price' => 80, 'compare_price' => 100, 'stock' => 3,
        ])->assertRedirect(route('seller.products.index'));
        $this->assertEquals(80, $product->fresh()->price);

        $this->actingAs($owner)->delete(route('seller.products.destroy', $product));
        $this->assertModelMissing($product);
    }

    public function test_buyers_cannot_reach_the_seller_area(): void
    {
        $this->actingAs(User::factory()->create())->get(route('seller.products.index'))->assertForbidden();
    }
}
