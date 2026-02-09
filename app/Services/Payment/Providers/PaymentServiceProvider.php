<?php

namespace App\Services\Payment\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Payment\Services\PaymentService;
use App\Services\Payment\Services\StripeGateway;
use App\Services\Payment\Services\PayPalGateway;
use App\Services\Payment\Services\PaystackGateway;

/**
 * Payment Service Provider
 *
 * Registers payment service and gateways
 */
class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register payment service as singleton
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService();
        });

        // Register payment gateways
        $this->app->singleton(StripeGateway::class);
        $this->app->singleton(PayPalGateway::class);
        $this->app->singleton(PaystackGateway::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/payment.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
    }
}
