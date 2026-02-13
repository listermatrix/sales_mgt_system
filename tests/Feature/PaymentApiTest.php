<?php

use App\Services\Payment\Models\Payment;
use App\Services\Order\Models\Order;
use App\Services\Customer\Models\Customer;
use App\Enums\PaymentStatus;
use App\Enums\PaymentGateway;
use App\Enums\OrderStatus;
use App\Constants\HttpStatusCode;
use Illuminate\Support\Facades\Mail;

describe('Payment API', function () {

    beforeEach(static function () {
        Mail::fake();
    });

    describe('GET /api/payments/gateways', function () {

        it('returns available payment gateways', function () {
            config(['payment.gateways.stripe.enabled' => true]);
            config(['payment.gateways.paypal.enabled' => true]);
            config(['payment.gateways.paystack.enabled' => false]);

            $response = $this->getJson('/api/payments/gateways');

            $response->assertStatus(HttpStatusCode::OK)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => ['value', 'label', 'currency']
                    ],
                    'message'
                ]);

            $gateways = $response->json('data');
            $gatewayValues = array_column($gateways, 'value');

            expect($gatewayValues)->toContain('stripe')
                ->and($gatewayValues)->toContain('paypal')
                ->and($gatewayValues)->not->toContain('paystack');
        });
    });

    describe('POST /api/payments', function () {

        it('initiates a payment successfully', function () {
            config(['payment.gateways.stripe.enabled' => true]);

            $order = Order::factory()->create(['total_amount' => 100.00]);

            $paymentData = [
                'order_id' => $order->id,
                'gateway' => 'stripe',
                'amount' => 100.00,
                'currency' => 'USD'
            ];

            // Mock the payment service to avoid actual API calls
            $this->mock(\App\Services\Payment\Services\PaymentService::class, function ($mock) {
                $mock->shouldReceive('processPayment')
                    ->once()
                    ->andReturn([
                        'success' => true,
                        'reference' => 'test_ref_123',
                        'authorization_url' => 'https://checkout.stripe.com/test',
                    ]);
            });

            $response = $this->postJson('/api/payments', $paymentData);

            $response->assertStatus(HttpStatusCode::CREATED)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'payment' => ['id', 'order_id', 'gateway', 'amount', 'status'],
                        'reference',
                        'authorization_url'
                    ],
                    'message'
                ]);

            $this->assertDatabaseHas('payments', [
                'order_id' => $order->id,
                'amount' => 100.00,
                'gateway' => PaymentGateway::STRIPE->value
            ]);
        });

        it('validates order exists', function () {
            $response = $this->postJson('/api/payments', [
                'order_id' => 99999,
                'gateway' => 'stripe',
                'amount' => 100.00
            ]);

            $response->assertStatus(HttpStatusCode::UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['order_id']);
        });

        it('validates gateway is valid', function () {
            $order = Order::factory()->create();

            $response = $this->postJson('/api/payments', [
                'order_id' => $order->id,
                'gateway' => 'invalid_gateway',
                'amount' => 100.00
            ]);

            $response->assertStatus(HttpStatusCode::UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['gateway']);
        });

        it('prevents duplicate payment for completed order', function () {
            $order = Order::factory()->create();
            Payment::factory()->completed()->create(['order_id' => $order->id]);

            $response = $this->postJson('/api/payments', [
                'order_id' => $order->id,
                'gateway' => 'stripe',
                'amount' => 100.00
            ]);

            $response->assertStatus(HttpStatusCode::CONFLICT);
        });

        it('validates amount is positive', function () {
            $order = Order::factory()->create();

            $response = $this->postJson('/api/payments', [
                'order_id' => $order->id,
                'gateway' => 'stripe',
                'amount' => -50
            ]);

            $response->assertStatus(HttpStatusCode::UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['amount']);
        });
    });

    describe('GET /api/payments/{id}', function () {

        it('returns payment details', function () {
            $payment = Payment::factory()->completed()->create();

            $response = $this->getJson("/api/payments/{$payment->id}");

            $response->assertStatus(HttpStatusCode::OK)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id', 'transaction_id',
                        'gateway' => ['value', 'label'],
                        'amount' => ['value', 'formatted', 'currency'],
                        'status' => ['value', 'label']
                    ],
                    'message'
                ]);
        });

        it('returns 404 for non-existent payment', function () {
            $response = $this->getJson('/api/payments/99999');

            $response->assertStatus(HttpStatusCode::NOT_FOUND);
        });
    });

    describe('POST /api/payments/{id}/verify', function () {

        it('verifies payment successfully', function () {
            $customer = Customer::factory()->create(['email' => 'test@example.com']);
            $order = Order::factory()->create([
                'customer_id' => $customer->id,
                'status' => OrderStatus::PENDING
            ]);
            $payment = Payment::factory()->pending()->create(['order_id' => $order->id]);

            $this->mock(\App\Services\Payment\Services\PaymentService::class, function ($mock) {
                $mock->shouldReceive('verifyPayment')
                    ->once()
                    ->andReturn([
                        'success' => true,
                        'status' => PaymentStatus::COMPLETED->value,
                        'transaction_id' => 'txn_123456'
                    ]);
            });

            $response = $this->postJson("/api/payments/{$payment->id}/verify", [
                'reference' => 'test_ref_123'
            ]);

            $response->assertStatus(HttpStatusCode::OK);

            expect($payment->fresh()->status)->toBe(PaymentStatus::COMPLETED)
                ->and($order->fresh()->status)->toBe(OrderStatus::PROCESSING);

//            Mail::assertQueued(\App\Services\Notification\Mail\PaymentSuccessMail::class);
        });

        it('requires reference parameter', function () {
            $payment = Payment::factory()->create();

            $response = $this->postJson("/api/payments/{$payment->id}/verify", []);

            $response->assertStatus(HttpStatusCode::UNPROCESSABLE_ENTITY)
                ->assertJsonValidationErrors(['reference']);
        });
    });

    describe('POST /api/payments/{id}/refund', function () {

        it('processes full refund successfully', function () {
            $order = Order::factory()->completed()->create();
            $payment = Payment::factory()->completed()->create([
                'order_id' => $order->id,
                'amount' => 100.00
            ]);

            $this->mock(\App\Services\Payment\Services\PaymentService::class, function ($mock) {
                $mock->shouldReceive('refundPayment')
                    ->once()
                    ->andReturn([
                        'success' => true,
                        'refund_id' => 'refund_123',
                        'amount' => 100.00
                    ]);
            });

            $response = $this->postJson("/api/payments/{$payment->id}/refund");

            $response->assertStatus(HttpStatusCode::OK);

            expect($payment->fresh()->status)->toBe(PaymentStatus::REFUNDED)
                ->and($order->fresh()->status)->toBe(OrderStatus::REFUNDED);
        });

        it('processes partial refund successfully', function () {
            $payment = Payment::factory()->completed()->create(['amount' => 100.00]);

            $this->mock(\App\Services\Payment\Services\PaymentService::class, function ($mock) {
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

            $response->assertStatus(HttpStatusCode::OK);

            expect($payment->fresh()->status)->toBe(PaymentStatus::PARTIALLY_REFUNDED);
        });

        it('prevents refund of non-completed payment', function () {
            $payment = Payment::factory()->pending()->create();

            $response = $this->postJson("/api/payments/{$payment->id}/refund");

            $response->assertStatus(HttpStatusCode::BAD_REQUEST);
        });
    });
});

describe('Payment Model', function () {

    it('marks payment as completed', function () {
        $payment = Payment::factory()->pending()->create();

        $payment->markAsCompleted('txn_123456');

        expect($payment->status)->toBe(PaymentStatus::COMPLETED)
            ->and($payment->transaction_id)->toBe('txn_123456')
            ->and($payment->paid_at)->not->toBeNull();
    });

    it('marks payment as failed with reason', function () {
        $payment = Payment::factory()->pending()->create();

        $payment->markAsFailed('Insufficient funds');

        expect($payment->status)->toBe(PaymentStatus::FAILED)
            ->and($payment->metadata['failure_reason'])->toBe('Insufficient funds');
    });

    it('marks payment as refunded', function () {
        $payment = Payment::factory()->completed()->create();

        $payment->markAsRefunded();

        expect($payment->status)->toBe(PaymentStatus::REFUNDED)
            ->and($payment->refunded_at)->not->toBeNull();
    });

    it('checks if payment is successful', function () {
        $completedPayment = Payment::factory()->completed()->create();
        $pendingPayment = Payment::factory()->pending()->create();

        expect($completedPayment->isSuccessful())->toBeTrue()
            ->and($pendingPayment->isSuccessful())->toBeFalse();
    });

    it('checks if payment can be refunded', function () {
        $completedPayment = Payment::factory()->completed()->create();
        $pendingPayment = Payment::factory()->pending()->create();

        expect($completedPayment->canBeRefunded())->toBeTrue()
            ->and($pendingPayment->canBeRefunded())->toBeFalse();
    });
});

describe('Payment Enums', function () {

    it('validates payment gateway values', function () {
        $gateways = PaymentGateway::values();

        expect($gateways)->toContain('stripe')
            ->and($gateways)->toContain('paypal')
            ->and($gateways)->toContain('paystack');
    });

    it('returns gateway labels', function () {
        expect(PaymentGateway::STRIPE->label())->toBe('Stripe')
            ->and(PaymentGateway::PAYPAL->label())->toBe('PayPal')
            ->and(PaymentGateway::PAYSTACK->label())->toBe('Paystack');
    });

    it('checks payment status', function () {
        expect(PaymentStatus::COMPLETED->isSuccessful())->toBeTrue()
            ->and(PaymentStatus::PENDING->isSuccessful())->toBeFalse()
            ->and(PaymentStatus::FAILED->isSuccessful())->toBeFalse();
    });
});
