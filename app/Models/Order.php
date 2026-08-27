<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED    = 'shipped';
    const STATUS_DELIVERED  = 'delivered';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_RETURNED   = 'returned';

    const PAYMENT_UNPAID               = 'unpaid';
    const PAYMENT_PENDING_VERIFICATION = 'pending_verification';
    const PAYMENT_PAID                 = 'paid';
    const PAYMENT_REFUNDED             = 'refunded';
    const PAYMENT_FAILED               = 'failed';

    const METHOD_COD        = 'cod';
    const METHOD_BKASH      = 'bkash';
    const METHOD_NAGAD      = 'nagad';
    const METHOD_SSLCOMMERZ = 'sslcommerz';

    protected $fillable = [
        'order_number', 'idempotency_key', 'user_id', 'coupon_id', 'shipping_rate_id',
        'subtotal', 'discount_amount', 'shipping_amount', 'tax_amount', 'total_amount',
        'status', 'payment_status', 'payment_method',
        'ship_name', 'ship_phone', 'ship_address', 'ship_city', 'ship_district', 'ship_zip',
        'notes', 'tracking_number',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'        => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'tax_amount'      => 'decimal:2',
            'total_amount'    => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('created_at');
    }

    public function paymentTransaction(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class)->latestOfMany();
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function manualPayment(): HasOne
    {
        return $this->hasOne(ManualPayment::class);
    }

    public function couponUsage(): HasOne
    {
        return $this->hasOne(CouponUsage::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function courierOrder(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CourierOrder::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }
}
