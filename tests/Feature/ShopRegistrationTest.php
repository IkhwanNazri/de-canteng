<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_open_a_shop_and_becomes_a_seller(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->post(route('shops.store'), [
                'name' => 'Batik Kak Long',
                'description' => 'Batik Terengganu',
                'bank_name' => 'Maybank',
                'bank_account' => '1122 3344 5566',
            ])
            ->assertRedirect(route('seller.products.index'));

        $this->assertSame(UserRole::Seller, $buyer->fresh()->role);
        $this->assertDatabaseHas('shops', ['user_id' => $buyer->id, 'slug' => 'batik-kak-long']);
    }

    public function test_user_with_a_shop_cannot_open_another(): void
    {
        $shop = Shop::factory()->create();

        $this->actingAs($shop->owner)->get(route('shops.create'))->assertRedirect(route('seller.products.index'));
        $this->actingAs($shop->owner)->post(route('shops.store'), [
            'name' => 'Kedai Kedua', 'bank_name' => 'CIMB', 'bank_account' => '123456789',
        ])->assertForbidden();
    }

    public function test_shop_requires_valid_bank_details(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('shops.store'), ['name' => 'Batik X', 'bank_name' => '', 'bank_account' => 'abc'])
            ->assertSessionHasErrors(['bank_name', 'bank_account']);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('shops.create'))->assertRedirect(route('login'));
    }
}
