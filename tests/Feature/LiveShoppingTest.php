<?php

namespace Tests\Feature;

use App\Models\LiveSession;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LiveShoppingTest extends TestCase
{
    use RefreshDatabase;

    private function liveSessionWithPinnedProduct(array $productAttributes = []): LiveSession
    {
        $product = Product::factory()->create(['name' => 'Kebaya Sakura', 'price' => 189, 'stock' => 7, ...$productAttributes]);
        $session = LiveSession::factory()->live()->create(['shop_id' => $product->shop_id, 'pinned_product_id' => $product->id]);
        $session->products()->attach($product);

        return $session;
    }

    public function test_seller_can_create_a_session_only_with_own_products(): void
    {
        $shop = Shop::factory()->create();
        $ownProduct = Product::factory()->for($shop)->create();
        $foreignProduct = Product::factory()->create();

        $this->actingAs($shop->owner)
            ->post(route('seller.live.store'), ['title' => 'Live Raya', 'product_ids' => [$foreignProduct->id]])
            ->assertSessionHasErrors('product_ids.0');

        $this->actingAs($shop->owner)
            ->post(route('seller.live.store'), ['title' => 'Live Raya', 'product_ids' => [$ownProduct->id]]);

        $session = $shop->liveSessions()->sole();
        $this->assertSame('scheduled', $session->status);
        $this->assertSame($ownProduct->id, $session->pinned_product_id);
    }

    public function test_only_the_owner_can_start_and_end_a_session(): void
    {
        $session = LiveSession::factory()->create();
        $stranger = Shop::factory()->create()->owner;

        $this->actingAs($stranger)->post(route('seller.live.start', $session))->assertForbidden();

        $this->actingAs($session->shop->owner)->post(route('seller.live.start', $session));
        $this->assertSame('live', $session->fresh()->status);

        $this->actingAs($session->shop->owner)->post(route('seller.live.end', $session));
        $this->assertSame('ended', $session->fresh()->status);
    }

    public function test_viewer_question_about_price_gets_an_auto_reply(): void
    {
        $session = $this->liveSessionWithPinnedProduct();

        $this->actingAs(User::factory()->create())
            ->postJson(route('live.comments.store', $session), ['body' => 'Kak, berapa harga ni?'])
            ->assertCreated();

        $reply = $session->comments()->where('is_auto_reply', true)->sole();
        $this->assertStringContainsString('RM189.00', $reply->body);

        $this->getJson(route('live.comments.index', $session))
            ->assertOk()
            ->assertJsonCount(2, 'comments')
            ->assertJsonPath('comments.1.author', 'Pembantu DeBatik');
    }

    public function test_auto_reply_uses_ollama_when_configured(): void
    {
        config(['services.ollama.url' => 'http://ollama.test']);
        Http::fake(['ollama.test/*' => Http::response(['message' => ['content' => 'Stok tinggal 7 helai sahaja!']])]);
        $session = $this->liveSessionWithPinnedProduct();

        $this->actingAs(User::factory()->create())
            ->postJson(route('live.comments.store', $session), ['body' => 'masih ada?']);

        $this->assertSame('Stok tinggal 7 helai sahaja!', $session->comments()->where('is_auto_reply', true)->value('body'));
    }

    public function test_no_auto_reply_when_disabled_or_from_the_host(): void
    {
        $session = $this->liveSessionWithPinnedProduct();

        $this->actingAs($session->shop->owner)->postJson(route('live.comments.store', $session), ['body' => 'Harga RM189']);
        $session->update(['auto_reply' => false]);
        $this->actingAs(User::factory()->create())->postJson(route('live.comments.store', $session), ['body' => 'berapa harga?']);

        $this->assertSame(0, $session->comments()->where('is_auto_reply', true)->count());
    }

    public function test_comments_require_login_and_a_live_session(): void
    {
        $session = LiveSession::factory()->create();

        $this->postJson(route('live.comments.store', $session), ['body' => 'hai'])->assertUnauthorized();
        $this->actingAs(User::factory()->create())
            ->postJson(route('live.comments.store', $session), ['body' => 'hai'])
            ->assertUnprocessable();
    }

    public function test_viewer_gets_a_subscribe_only_livekit_token_when_configured(): void
    {
        config(['services.livekit' => ['url' => 'ws://localhost:7880', 'key' => 'devkey', 'secret' => 'secret']]);
        $session = $this->liveSessionWithPinnedProduct();

        $this->get(route('live.show', $session))->assertInertia(function (Assert $page) use ($session) {
            $page->component('Live/Show', false)->where('video.url', 'ws://localhost:7880');

            [$header, $payload, $signature] = explode('.', $page->toArray()['props']['video']['token']);
            $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
            $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', "{$header}.{$payload}", 'secret', true)), '+/', '-_'), '=');

            $this->assertSame($expected, $signature);
            $this->assertSame($session->roomName(), $claims['video']['room']);
            $this->assertFalse($claims['video']['canPublish']);
        });
    }

    public function test_live_page_has_no_video_when_livekit_is_not_configured(): void
    {
        config(['services.livekit.url' => null]);
        $session = $this->liveSessionWithPinnedProduct();

        $this->get(route('live.show', $session))->assertOk()->assertInertia(fn (Assert $page) => $page->where('video', null));
    }
}
