<?php

use App\Services\Payment\Models\Payment;
use App\Services\Order\Models\Order;
use App\Enums\PaymentStatus;
use App\Enums\PaymentGateway;

describe('Payment Model', static function () {

    it('casts gateway to PaymentGateway enum', static function () {
        $payment = Payment::factory()->stripe()->create();

        expect($payment->gateway)->toBeInstanceOf(PaymentGateway::class)
            ->and($payment->gateway)->toBe(PaymentGateway::STRIPE);
    });

    it('casts status to PaymentStatus enum', static function () {
        $payment = Payment::factory()->completed()->create();

        expect($payment->status)->toBeInstanceOf(PaymentStatus::class)
            ->and($payment->status)->toBe(PaymentStatus::COMPLETED);
    });

    it('casts metadata to array', static function () {
        $metadata = ['key' => 'value', 'another' => 'data'];
        $payment = Payment::factory()->create(['metadata' => $metadata]);

        expect($payment->metadata)->toBeArray()
            ->and($payment->metadata)->toBe($metadata);
    });

    it('marks payment as completed', static function () {
        $payment = Payment::factory()->pending()->create();

        $result = $payment->markAsCompleted('txn_123456789');

        expect($result)->toBeTrue()
            ->and($payment->fresh()->status)->toBe(PaymentStatus::COMPLETED)
            ->and($payment->fresh()->transaction_id)->toBe('txn_123456789')
            ->and($payment->fresh()->paid_at)->not->toBeNull();
    });

    it('marks payment as failed', static function () {
        $payment = Payment::factory()->pending()->create();

        $result = $payment->markAsFailed();

        expect($result)->toBeTrue()
            ->and($payment->fresh()->status)->toBe(PaymentStatus::FAILED);
    });

    it('marks payment as failed with reason', static function () {
        $payment = Payment::factory()->pending()->create();

        $payment->markAsFailed('Insufficient funds');

        expect($payment->fresh()->metadata['failure_reason'])
            ->toBe('Insufficient funds');
    });

    it('marks payment as refunded', static function () {
        $payment = Payment::factory()->completed()->create();

        $result = $payment->markAsRefunded();

        expect($result)->toBeTrue()
            ->and($payment->fresh()->status)->toBe(PaymentStatus::REFUNDED)
            ->and($payment->fresh()->refunded_at)->not->toBeNull();
    });

    it('checks if payment is successful', static function () {
        $completed = Payment::factory()->completed()->create();
        $pending = Payment::factory()->pending()->create();
        $failed = Payment::factory()->failed()->create();

        expect($completed->isSuccessful())->toBeTrue()
            ->and($pending->isSuccessful())->toBeFalse()
            ->and($failed->isSuccessful())->toBeFalse();
    });

    it('checks if payment can be refunded', static function () {
        $completed = Payment::factory()->completed()->create();
        $pending = Payment::factory()->pending()->create();
        $failed = Payment::factory()->failed()->create();

        expect($completed->canBeRefunded())->toBeTrue()
            ->and($pending->canBeRefunded())->toBeFalse()
            ->and($failed->canBeRefunded())->toBeFalse();
    });

    it('belongs to order', static function () {
        $order = Order::factory()->create();
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        expect($payment->order)->toBeInstanceOf(Order::class)
            ->and($payment->order->id)->toBe($order->id);
    });

    it('casts amount to decimal', static function () {
        $payment = Payment::factory()->create(['amount' => 99.99]);

        expect($payment->amount)->toBeFloat()
            ->and($payment->amount)->toBe(99.99);
    });

    it('stores different gateway types', static function () {
        $stripe = Payment::factory()->stripe()->create();
        $paypal = Payment::factory()->paypal()->create();
        $paystack = Payment::factory()->paystack()->create();

        expect($stripe->gateway)->toBe(PaymentGateway::STRIPE)
            ->and($paypal->gateway)->toBe(PaymentGateway::PAYPAL)
            ->and($paystack->gateway)->toBe(PaymentGateway::PAYSTACK);
    });

    it('handles null metadata gracefully', static function () {
        $payment = Payment::factory()->create(['metadata' => null]);

        expect($payment->metadata)->toBeNull();
    });

    it('updates metadata when marking as failed', static function () {
        $payment = Payment::factory()->create(['metadata' => ['existing' => 'data']]);

        $payment->markAsFailed('Card declined');

        $metadata = $payment->fresh()->metadata;

        expect($metadata['existing'])->toBe('data')
            ->and($metadata['failure_reason'])->toBe('Card declined');
    });

    it('sets paid_at timestamp when marking as completed', static function () {
        $payment = Payment::factory()->pending()->create();

        $beforeTime = now();
        $payment->markAsCompleted('txn_123');
        $afterTime = now();

        $paidAt = $payment->fresh()->paid_at;

        expect($paidAt)->not->toBeNull()
            ->and($paidAt->greaterThanOrEqualTo($beforeTime))->toBeTrue()
            ->and($paidAt->lessThanOrEqualTo($afterTime))->toBeTrue();
    });

    it('sets refunded_at timestamp when marking as refunded', static function () {
        $payment = Payment::factory()->completed()->create();

        $beforeTime = now();
        $payment->markAsRefunded();
        $afterTime = now();

        $refundedAt = $payment->fresh()->refunded_at;

        expect($refundedAt)->not->toBeNull()
            ->and($refundedAt->greaterThanOrEqualTo($beforeTime))->toBeTrue()
            ->and($refundedAt->lessThanOrEqualTo($afterTime))->toBeTrue();
    });

    it('handles currency field correctly', static function () {
        $usd = Payment::factory()->create(['currency' => 'USD']);
        $ngn = Payment::factory()->create(['currency' => 'NGN']);

        expect($usd->currency)->toBe('USD')
            ->and($ngn->currency)->toBe('NGN');
    });

    it('allows partial refund status', static function () {
        $payment = Payment::factory()->create(['status' => PaymentStatus::PARTIALLY_REFUNDED]);

        expect($payment->status)->toBe(PaymentStatus::PARTIALLY_REFUNDED)
            ->and($payment->canBeRefunded())->toBeTrue();
    });
});
