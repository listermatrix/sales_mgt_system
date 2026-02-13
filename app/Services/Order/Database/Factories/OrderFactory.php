<?php

namespace App\Services\Order\Database\Factories;

use App\Enums\OrderStatus;
use App\Services\Customer\Models\Customer;
use App\Services\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;


class OrderFactory extends Factory
{


    protected $model = Order::class;


    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'total_amount' => fake()->randomFloat(2, 50, 5000),
            'status' => OrderStatus::PENDING,
        ];
    }


    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PENDING,
        ]);
    }


    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PROCESSING,
        ]);
    }


    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::COMPLETED,
        ]);
    }


    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CANCELLED,
        ]);
    }
}
