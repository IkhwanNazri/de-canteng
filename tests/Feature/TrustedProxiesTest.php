<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    private const FORWARDED = [
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_HOST' => 'demo.trycloudflare.com',
        'HTTP_X_FORWARDED_PORT' => '443',
    ];

    protected function tearDown(): void
    {
        TrustProxies::flushState();

        parent::tearDown();
    }

    public function test_urls_use_https_when_proxies_are_trusted_from_config(): void
    {
        config(['app.trusted_proxies' => '*']);
        (new AppServiceProvider($this->app))->boot();

        $this->call('GET', '/login', [], [], [], self::FORWARDED)
            ->assertOk()
            ->assertSee('https://demo.trycloudflare.com/', false);
    }

    public function test_forwarded_headers_are_ignored_by_default(): void
    {
        config(['app.trusted_proxies' => null]);
        (new AppServiceProvider($this->app))->boot();

        $this->call('GET', '/login', [], [], [], self::FORWARDED)
            ->assertOk()
            ->assertDontSee('https://demo.trycloudflare.com/', false);
    }
}
