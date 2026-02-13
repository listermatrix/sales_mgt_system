<?php

use App\Services\Product\Models\Product;
use App\Constants\HttpStatusCode;
use App\Constants\ErrorCode;
use Illuminate\Support\Facades\Event;

describe('Product API', function () {

    describe('GET /api/products', function () {

        it('returns all products with stock status', function () {
            Product::factory()->inStock()->create();
            Product::factory()->lowStock()->create();
            Product::factory()->outOfStock()->create();

            $response = $this->getJson('/api/products');

            $response->assertStatus(HttpStatusCode::OK)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id', 'name', 'sku',
                            'price' => ['amount', 'formatted', 'currency'],
                            'stock' => ['quantity', 'available', 'status']
                        ]
                    ],
                    'message'
                ]);

            expect($response->json('data'))->toHaveCount(3);
        });

        it('formats product data correctly', function () {
            $product = Product::factory()->create([
                'price' => 1299.99,
                'stock_quantity' => 50
            ]);

            $response = $this->getJson('/api/products');

            $productData = $response->json('data.0');

            expect($productData['price']['amount'])->toBe(1299.99)
                ->and($productData['price']['formatted'])->toContain('1,299.99')
                ->and($productData['stock']['quantity'])->toBe(50)
                ->and($productData['stock']['available'])->toBeTrue()
                ->and($productData['stock']['status'])->toBe('in_stock');
        });
    });

    describe('POST /api/products', function () {

        it('creates a product successfully', function () {
            $productData = [
                'name' => 'New Product',
                'description' => 'Product description',
                'sku' => 'SKU-001',
                'price' => 99.99,
                'stock_quantity' => 100
            ];

            $response = $this->postJson('/api/products', $productData);

            $response->assertStatus(HttpStatusCode::CREATED);

            expect($response->json('data.name'))->toBe('New Product')
                ->and($response->json('data.sku'))->toBe('SKU-001');

            $this->assertDatabaseHas('products', ['sku' => 'SKU-001']);
        });

        it('validates required fields', function () {
            $response = $this->postJson('/api/products', []);

            $response->assertStatus(HttpStatusCode::UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['name', 'sku', 'price', 'stock_quantity']);
        });

        it('validates price is numeric and positive', function () {
            $response = $this->postJson('/api/products', [
                'name' => 'Product',
                'sku' => 'SKU-001',
                'price' => -10,
                'stock_quantity' => 10
            ]);

            $response->assertStatus(HttpStatusCode::UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['price']);
        });

        it('prevents duplicate SKU', function () {
            Product::factory()->create(['sku' => 'DUPLICATE-SKU']);

            $response = $this->postJson('/api/products', [
                'name' => 'Product',
                'sku' => 'DUPLICATE-SKU',
                'price' => 99.99,
                'stock_quantity' => 10
            ]);

            $response->assertStatus(HttpStatusCode::UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['sku']);
        });

        it('validates stock quantity is non-negative integer', function () {
            $response = $this->postJson('/api/products', [
                'name' => 'Product',
                'sku' => 'SKU-001',
                'price' => 99.99,
                'stock_quantity' => -5
            ]);

            $response->assertStatus(HttpStatusCode::UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['stock_quantity']);
        });
    });

    describe('GET /api/products/{id}', function () {

        it('returns a specific product with correct stock status', function () {
            $product = Product::factory()->lowStock()->create(['stock_quantity' => 5]);

            $response = $this->getJson("/api/products/{$product->id}");

            $response->assertStatus(HttpStatusCode::OK);

            expect($response->json('data.stock.status'))->toBe('low_stock')
                ->and($response->json('data.stock.quantity'))->toBe(5);
        });

        it('shows out of stock status correctly', function () {
            $product = Product::factory()->outOfStock()->create();

            $response = $this->getJson("/api/products/{$product->id}");

            expect($response->json('data.stock.status'))->toBe('out_of_stock')
                ->and($response->json('data.stock.available'))->toBeFalse();
        });
    });

    describe('PUT /api/products/{id}', function () {

        it('updates product successfully', function () {
            $product = Product::factory()->create();

            $response = $this->putJson("/api/products/{$product->id}", [
                'price' => 199.99,
                'stock_quantity' => 50
            ]);

            $response->assertStatus(HttpStatusCode::OK);

            expect($response->json('data.price.amount'))->toBe(199.99);

            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'price' => 199.99,
                'stock_quantity' => 50
            ]);
        });

        it('dispatches ProductStockUpdated event when stock changes', function () {
            Event::fake();

            $product = Product::factory()->create(['stock_quantity' => 100]);

            $this->putJson("/api/products/{$product->id}", [
                'stock_quantity' => 50
            ]);

            Event::assertDispatched(\App\Services\Product\Events\ProductStockUpdated::class);
        });
    });

    describe('DELETE /api/products/{id}', function () {

        it('deletes a product successfully', function () {
            $product = Product::factory()->create();

            $response = $this->deleteJson("/api/products/{$product->id}");

            $response->assertStatus(HttpStatusCode::OK);

            $this->assertSoftDeleted('products', ['id' => $product->id]);
        });
    });

    describe('Stock Management', function () {

        it('decreases stock when product is purchased', function () {
            $product = Product::factory()->create(['stock_quantity' => 100]);

            $decreased = $product->decreaseStock(10);

            expect($decreased)->toBeTrue()
                ->and($product->fresh()->stock_quantity)->toBe(90);
        });

        it('prevents stock from going negative', function () {
            $product = Product::factory()->create(['stock_quantity' => 5]);

            $decreased = $product->decreaseStock(10);

            expect($decreased)->toBeFalse()
                ->and($product->fresh()->stock_quantity)->toBe(5);
        });

        it('increases stock correctly', function () {
            $product = Product::factory()->create(['stock_quantity' => 50]);

            $increased = $product->increaseStock(25);

            expect($increased)->toBeTrue()
                ->and($product->fresh()->stock_quantity)->toBe(75);
        });
    });
});
