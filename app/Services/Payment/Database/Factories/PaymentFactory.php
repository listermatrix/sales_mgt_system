<?php

namespace App\Services\Payment\Database\Factories;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Services\Order\Models\Order;
use App\Services\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;


class PaymentFactory extends Factory
{

    protected $model = Payment::class;


    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'transaction_id' => fake()->uuid(),
            'gateway' => fake()->randomElement(PaymentGateway::cases()),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'currency' => 'USD',
            'status' => PaymentStatus::PENDING,
            'metadata' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PENDING,
            'transaction_id' => null,
            'paid_at' => null,
        ]);
    }


    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::COMPLETED,
            'transaction_id' => fake()->uuid(),
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::FAILED,
            'transaction_id' => null,
            'paid_at' => null,
        ]);
    }


    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => PaymentGateway::STRIPE,
        ]);
    }


    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => PaymentGateway::PAYPAL,
        ]);
    }


    public function paystack(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => PaymentGateway::PAYSTACK,
        ]);
    }
}
