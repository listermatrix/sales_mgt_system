<?php

use App\Services\Customer\Models\Customer;
use App\Constants\HttpStatusCode;
use App\Constants\ErrorCode;

describe('Customer API', function () {

    describe('GET /api/customers', function () {

        it('returns all customers successfully', function () {
            $customers = Customer::factory()->count(3)->create();

            $response = $this->getJson('/api/customers');

            $response->assertStatus(HttpStatusCode::OK)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => ['id', 'name', 'email', 'phone', 'address']
                    ],
                    'message'
                ]);

            expect($response->json('success'))->toBeTrue()
                ->and($response->json('data'))->toHaveCount(3);
        });

        it('returns empty array when no customers exist', function () {
            $response = $this->getJson('/api/customers');

            $response->assertStatus(HttpStatusCode::OK);
            expect($response->json('data'))->toBeArray()->toBeEmpty();
        });

        it('respects rate limiting', function () {
            // Make requests up to the limit (100 for read operations)
            for ($i = 0; $i < 101; $i++) {
                $response = $this->getJson('/api/customers');

                if ($i < 100) {
                    $response->assertStatus(HttpStatusCode::OK);
                } else {
                    $response->assertStatus(HttpStatusCode::TOO_MANY_REQUESTS)
                        ->assertJsonStructure([
                            'success',
                            'error' => ['message', 'code', 'retry_after']
                        ]);
                }
            }
        });
    });

    describe('POST /api/customers', function () {

        it('creates a customer successfully', function () {
            $customerData = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'address' => '123 Main St'
            ];

            $response = $this->postJson('/api/customers', $customerData);

            $response->assertStatus(HttpStatusCode::CREATED)
                ->assertJsonStructure([
                    'success',
                    'data' => ['id', 'name', 'email', 'phone', 'address'],
                    'message'
                ]);

            expect($response->json('success'))->toBeTrue()
                ->and($response->json('data.name'))->toBe('John Doe');

            $this->assertDatabaseHas('customers', [
                'email' => 'john@example.com'
            ]);
        });

        it('validates required fields', function () {
            $response = $this->postJson('/api/customers', []);

            $response->assertStatus(HttpStatusCode::UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['name', 'email', 'phone']);
        });

        it('validates email format', function () {
            $response = $this->postJson('/api/customers', [
                'name' => 'John Doe',
                'email' => 'invalid-email',
                'phone' => '+1234567890'
            ]);

            $response->assertStatus(HttpStatusCode::UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['email']);
        });

        it('prevents duplicate email addresses', function () {
            $customer = Customer::factory()->create(['email' => 'john@example.com']);

            $response = $this->postJson('/api/customers', [
                'name' => 'Jane Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890'
            ]);

            $response->assertStatus(HttpStatusCode::UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['email']);
        });
    });

    describe('GET /api/customers/{id}', function () {

        it('returns a specific customer', function () {
            $customer = Customer::factory()->create();

            $response = $this->getJson("/api/customers/{$customer->id}");

            $response->assertStatus(HttpStatusCode::OK)
                ->assertJsonStructure([
                    'success',
                    'data' => ['id', 'name', 'email', 'phone', 'address'],
                    'message'
                ]);

            expect($response->json('data.id'))->toBe($customer->id)
                ->and($response->json('data.email'))->toBe($customer->email);
        });

        it('returns 404 for non-existent customer', function () {
            $response = $this->getJson('/api/customers/99999');

            $response->assertStatus(HttpStatusCode::NOT_FOUND)
                ->assertJsonStructure([
                    'success',
                    'error' => ['message', 'code']
                ]);

            expect($response->json('success'))->toBeFalse()
                ->and($response->json('error.code'))->toBe(ErrorCode::CUSTOMER_NOT_FOUND);
        });
    });

    describe('PUT /api/customers/{id}', function () {

        it('updates a customer successfully', function () {
            $customer = Customer::factory()->create();

            $updateData = [
                'name' => 'Updated Name',
                'phone' => '+9876543210'
            ];

            $response = $this->putJson("/api/customers/{$customer->id}", $updateData);

            $response->assertStatus(HttpStatusCode::OK);

            expect($response->json('data.name'))->toBe('Updated Name')
                ->and($response->json('data.phone'))->toBe('+9876543210');

            $this->assertDatabaseHas('customers', [
                'id' => $customer->id,
                'name' => 'Updated Name'
            ]);
        });

        it('validates email uniqueness on update', function () {
            $customer1 = Customer::factory()->create(['email' => 'first@example.com']);
            $customer2 = Customer::factory()->create(['email' => 'second@example.com']);

            $response = $this->putJson("/api/customers/{$customer2->id}", [
                'email' => 'first@example.com'
            ]);

            $response->assertStatus(HttpStatusCode::UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['email']);
        });

        it('allows updating own email', function () {
            $customer = Customer::factory()->create(['email' => 'test@example.com']);

            $response = $this->putJson("/api/customers/{$customer->id}", [
                'name' => 'New Name',
                'email' => 'test@example.com'
            ]);

            $response->assertStatus(HttpStatusCode::OK);
        });
    });

    describe('DELETE /api/customers/{id}', function () {

        it('deletes a customer successfully (soft delete)', function () {
            $customer = Customer::factory()->create();

            $response = $this->deleteJson("/api/customers/{$customer->id}");

            $response->assertStatus(HttpStatusCode::OK);

            $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        });

        it('returns 404 when deleting non-existent customer', function () {
            $response = $this->deleteJson('/api/customers/99999');

            $response->assertStatus(HttpStatusCode::NOT_FOUND);
        });
    });
});
