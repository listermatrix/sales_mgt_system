<?php

namespace App\Services\Order\Database\Factories;

use App\Services\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;


class ProductFactory extends Factory
{


    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'sku' => strtoupper(fake()->bothify('SKU-####-????')),
            'price' => fake()->randomFloat(2, 10, 1000),
            'stock_quantity' => fake()->numberBetween(0, 100),
        ];
    }


    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }


    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => fake()->numberBetween(1, 5),
        ]);
    }


    public function inStock(int $quantity = null): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => $quantity ?? fake()->numberBetween(50, 200),
        ]);
    }
}
