<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_upload_logo_and_banner(): void
    {
        Storage::fake('public');
        $shop = Shop::factory()->create(['bank_name' => 'Maybank', 'bank_account' => '112233445566']);

        $this->actingAs($shop->owner)->post(route('seller.settings.update'), [
            'description' => 'Batik buatan tangan',
            'bank_name' => 'CIMB',
            'bank_account' => '8000123456',
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
            'banner' => UploadedFile::fake()->image('banner.jpg', 1200, 400),
        ])->assertSessionHasNoErrors();

        $shop->refresh();
        $this->assertSame('CIMB', $shop->bank_name);
        $this->assertStringStartsWith('/storage/shops/logos/', $shop->logo);
        Storage::disk('public')->assertExists(substr($shop->banner, strlen('/storage/')));
    }

    public function test_banner_must_be_wide_enough(): void
    {
        Storage::fake('public');
        $shop = Shop::factory()->create();

        $this->actingAs($shop->owner)->post(route('seller.settings.update'), [
            'bank_name' => 'CIMB', 'bank_account' => '8000123456',
            'banner' => UploadedFile::fake()->image('small.jpg', 200, 100),
        ])->assertSessionHasErrors('banner');
    }

    public function test_buyers_cannot_open_shop_settings(): void
    {
        $this->actingAs(User::factory()->create())->get(route('seller.settings.edit'))->assertForbidden();
    }
}
