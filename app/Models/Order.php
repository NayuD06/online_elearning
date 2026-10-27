<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A learner's purchase of a paid course through a payment gateway.
 */
#[Fillable([
    'code', 'user_id', 'course_id', 'course_title', 'amount', 'gateway', 'status',
    'transaction_no', 'failure_reason', 'gateway_response', 'paid_at',
])]
class Order extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'gateway' => PaymentGateway::class,
            'status' => OrderStatus::class,
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @param  Builder<Order>  $query
     */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', OrderStatus::Paid);
    }

    /**
     * Unique order code sent to the gateway, e.g. DH20261027153012AB12.
     * The random tail matters for MoMo, whose shared test account sees order ids from everyone.
     */
    public static function newCode(): string
    {
        return 'DH'.now()->format('YmdHis').Str::upper(Str::random(4));
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
    }

    protected function amountLabel(): Attribute
    {
        return Attribute::get(fn (): string => number_format($this->amount, 0, ',', '.').' đ');
    }
}
