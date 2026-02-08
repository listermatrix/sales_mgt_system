<?php

namespace App\Services\Customer\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Customer\Repositories\CustomerRepository;
use App\Services\Customer\Repositories\CustomerRepositoryInterface;

class CustomerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            CustomerRepositoryInterface::class,
            CustomerRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/customer.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
    }
}
