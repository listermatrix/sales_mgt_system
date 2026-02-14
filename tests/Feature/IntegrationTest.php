<?php

use App\Services\Customer\Models\Customer;
use App\Services\Order\Notifications\OrderConfirmationMail;
use App\Services\Payment\Notification\PaymentSuccessMail;
use App\Services\Payment\Services\PaymentService;
use App\Services\Product\Models\Product;
use App\Services\Order\Models\Order;
use App\Services\Payment\Models\Payment;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentGateway;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;

describe('Complete Order Workflow Integration', function () {

    beforeEach(static function () {
        Mail::fake();
        Event::fake();
    });

    it('completes full order to payment workflow', function () {
        // 1. Create customer
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);

        // 2. Create products
        $product1 = Product::factory()->create([
            'price' => 100.00,
            'stock_quantity' => 50
        ]);
        $product2 = Product::factory()->create([
            'price' => 50.00,
            'stock_quantity' => 100
        ]);

        // 3. Create order via API
        $orderResponse = $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 1],
            ]
        ]);

        $orderResponse->assertStatus(201);
        $orderId = $orderResponse->json('data.id');

        // 4. Verify order was created correctly
        $order = Order::with('items')->find($orderId);
        expect($order)->not->toBeNull()
            ->and($order?->total_amount)->toBe(250.00)
            ->and($order?->status)->toBe(OrderStatus::PENDING)
            ->and($order?->items)->toHaveCount(2)
            ->and($product1->fresh()->stock_quantity)->toBe(48)
            ->and($product2->fresh()->stock_quantity)->toBe(99);

        // 5. Verify stock was decreased

        // 6. Verify email was queued
        Mail::assertQueued(OrderConfirmationMail::class);

        // 7. Mock payment service for payment initiation
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('processPayment')
                ->once()
                ->andReturn([
                    'success' => true,
                    'reference' => 'test_ref_123',
                    'authorization_url' => 'https://gateway.com/pay'
                ]);

            $mock->shouldReceive('verifyPayment')
                ->once()
                ->andReturn([
                    'success' => true,
                    'status' => PaymentStatus::COMPLETED->value,
                    'transaction_id' => 'txn_complete_123'
                ]);
        });

        // 8. Initiate payment
        $paymentResponse = $this->postJson('/api/payments', [
            'order_id' => $orderId,
            'gateway' => 'stripe',
            'amount' => 250.00
        ]);

        $paymentResponse->assertStatus(201);
        $paymentId = $paymentResponse->json('data.payment.id');

        // 9. Verify payment was created
        $payment = Payment::find($paymentId);
        expect($payment)->not->toBeNull()
            ->and($payment->amount)->toBe(250.00)
            ->and($payment->gateway)->toBe(PaymentGateway::STRIPE);

        // 10. Verify payment (simulate callback)
        $verifyResponse = $this->postJson("/api/payments/{$paymentId}/verify", [
            'reference' => 'test_ref_123'
        ]);

        $verifyResponse->assertStatus(200);

        // 11. Verify payment was marked as completed
        expect($payment->fresh()->status)->toBe(PaymentStatus::COMPLETED)
            ->and($payment->fresh()->transaction_id)->toBe('txn_complete_123')
            ->and($order?->fresh()->status)->toBe(OrderStatus::PROCESSING);

        Mail::assertQueued(PaymentSuccessMail::class);
    });

    it('handles order creation failure with stock rollback', function () {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 5,
            'price' => 100.00
        ]);

        $initialStock = $product->stock_quantity;
        $initialOrderCount = Order::count();

        // Try to order more than available
        $response = $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10]
            ]
        ]);

        $response->assertStatus(400);

        // Verify stock wasn't decreased
        expect($product->fresh()->stock_quantity)->toBe($initialStock)
            ->and(Order::count())->toBe($initialOrderCount);
    });

    it('handles multiple customers ordering same product concurrently', function () {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        // Both try to order 6 units (only 10 available)
        $response1 = $this->postJson('/api/orders', [
            'customer_id' => $customer1->id,
            'items' => [['product_id' => $product->id, 'quantity' => 6]]
        ]);

        $response2 = $this->postJson('/api/orders', [
            'customer_id' => $customer2->id,
            'items' => [['product_id' => $product->id, 'quantity' => 6]]
        ]);

        // First should succeed
        expect($response1->status())->toBe(201)
            ->and($response2->status())->toBe(400)
            ->and($product->fresh()->stock_quantity)->toBe(4);

        // Second should fail due to insufficient stock

        // Only 4 units should remain
    });
});

describe('Payment Refund Workflow Integration', function () {

    beforeEach(static function () {
        Mail::fake();
    });

    it('processes full refund workflow', function () {
        // 1. Create completed order with payment
        $order = Order::factory()->completed()->create();
        $payment = Payment::factory()->completed()->create([
            'order_id' => $order->id,
            'amount' => 100.00
        ]);

        // 2. Mock payment service for refund
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('refundPayment')
                ->once()
                ->andReturn([
                    'success' => true,
                    'refund_id' => 'refund_123',
                    'amount' => 100.00
                ]);
        });

        // 3. Process refund
        $response = $this->postJson("/api/payments/{$payment->id}/refund");

        $response->assertStatus(200);

        // 4. Verify payment status
        expect($payment->fresh()->status)->toBe(PaymentStatus::REFUNDED)
            ->and($order->fresh()->status)->toBe(OrderStatus::REFUNDED);

        // 5. Verify order status
    });

    it('processes partial refund workflow', function () {
        $order = Order::factory()->completed()->create();
        $payment = Payment::factory()->completed()->create([
            'order_id' => $order->id,
            'amount' => 100.00
        ]);

        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('refundPayment')
                ->with(anything(), 50.00)
                ->once()
                ->andReturn([
                    'success' => true,
                    'refund_id' => 'refund_123',
                    'amount' => 50.00
                ]);
        });

        $response = $this->postJson("/api/payments/{$payment->id}/refund", [
            'amount' => 50.00
        ]);

        $response->assertStatus(200);

        // Should be partially refunded
        expect($payment->fresh()->status)->toBe(PaymentStatus::PARTIALLY_REFUNDED)
            ->and($order->fresh()->status)->toBe(OrderStatus::COMPLETED);
    });
});

describe('Customer Journey Integration', function () {

    it('tracks complete customer journey', function () {
        Mail::fake();

        // 1. Create customer
        $customerResponse = $this->postJson('/api/customers', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St'
        ]);

        $customerId = $customerResponse->json('data.id');

        // 2. Create products
        $product1 = Product::factory()->create(['price' => 50.00, 'stock_quantity' => 100]);
        $product2 = Product::factory()->create(['price' => 75.00, 'stock_quantity' => 50]);

        // 3. Customer places first order
        $order1Response = $this->postJson('/api/orders', [
            'customer_id' => $customerId,
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2]
            ]
        ]);

        expect($order1Response->status())->toBe(201);

        // 4. Customer places second order
        $order2Response = $this->postJson('/api/orders', [
            'customer_id' => $customerId,
            'items' => [
                ['product_id' => $product2->id, 'quantity' => 1]
            ]
        ]);

        expect($order2Response->status())->toBe(201);

        // 5. Verify customer has 2 orders
        $customer = Customer::with('orders')->find($customerId);

        // Note: This would work if we add orders relationship to Customer model
        // For now, verify via database
        $orderCount = Order::where('customer_id', $customerId)->count();
        expect($orderCount)->toBe(2);

        // 6. Verify 2 emails were sent
        Mail::assertQueued(OrderConfirmationMail::class, 2);
    });
});

describe('Rate Limiting Integration', function () {

    it('enforces rate limits across different endpoints', function () {
        // Test payment rate limit (10 requests/min)
        $order = Order::factory()->create();

        for ($i = 0; $i < 11; $i++) {
            $response = $this->postJson('/api/payments', [
                'order_id' => $order->id,
                'gateway' => 'stripe',
                'amount' => 100.00
            ]);

            if ($i < 10) {
                expect($response->status())->not->toBe(429);
            } else {
                expect($response->status())->toBe(429);
            }
        }
    });
});
