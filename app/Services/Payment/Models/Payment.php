<?php

namespace App\Services\Payment\Models;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\Order\Models\Order;

/**
 * Payment Model
 */
class Payment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'transaction_id',
        'gateway',
        'amount',
        'currency',
        'status',
        'metadata',
        'paid_at',
        'refunded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order_id' => 'integer',
        'amount' => 'decimal:2',
        'gateway' => PaymentGateway::class,
        'status' => PaymentStatus::class,
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the order that owns the payment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Mark payment as completed
     *
     * @param string $transactionId
     * @return bool
     */
    public function markAsCompleted(string $transactionId): bool
    {
        $this->status = PaymentStatus::COMPLETED;
        $this->transaction_id = $transactionId;
        $this->paid_at = now();

        return $this->save();
    }

    /**
     * Mark payment as failed
     *
     * @param string|null $reason
     * @return bool
     */
    public function markAsFailed(?string $reason = null): bool
    {
        $this->status = PaymentStatus::FAILED;

        if ($reason) {
            $metadata = $this->metadata ?? [];
            $metadata['failure_reason'] = $reason;
            $this->metadata = $metadata;
        }

        return $this->save();
    }

    /**
     * Mark payment as refunded
     *
     * @return bool
     */
    public function markAsRefunded(): bool
    {
        $this->status = PaymentStatus::REFUNDED;
        $this->refunded_at = now();

        return $this->save();
    }

    /**
     * Check if payment is successful
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    /**
     * Check if payment can be refunded
     *
     * @return bool
     */
    public function canBeRefunded(): bool
    {
        return $this->status->canBeRefunded();
    }
}
