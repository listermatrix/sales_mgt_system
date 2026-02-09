<?php

namespace App\Services\Order\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Order\Repositories\OrderRepository;
use App\Services\Order\Repositories\OrderRepositoryInterface;
use App\Services\Order\Events\OrderPlaced;
use App\Services\Order\Listeners\UpdateProductStock;
use Illuminate\Support\Facades\Event;

class OrderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            OrderRepositoryInterface::class,
            OrderRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/order.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');

        // Register event listeners
        Event::listen(
            OrderPlaced::class,
            UpdateProductStock::class
        );
    }
}
