<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'role:seller,admin'])->get('/_test/seller-area', fn () => 'ok');
    }

    public function test_new_registrations_are_buyers(): void
    {
        $this->post('/register', [
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertSame(UserRole::Buyer, User::firstWhere('email', 'ali@example.com')->role);
    }

    public function test_buyer_is_forbidden_from_seller_routes(): void
    {
        $this->actingAs(User::factory()->create())->get('/_test/seller-area')->assertForbidden();
    }

    public function test_seller_and_admin_can_access_seller_routes(): void
    {
        $this->actingAs(User::factory()->seller()->create())->get('/_test/seller-area')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/_test/seller-area')->assertOk();
    }
}
