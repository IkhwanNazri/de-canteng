<?php

namespace App\Providers;

use App\Services\Payments\BayarcashGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\SimulatedGateway;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, fn () => match (config('marketplace.payment_gateway')) {
            'bayarcash' => $this->app->make(BayarcashGateway::class),
            default => new SimulatedGateway,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
    }
}
