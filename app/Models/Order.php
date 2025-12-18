<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Payment;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'order_date',
        'status', // draf, pending, processing, delivered, cancelled

        // Thông tin khách
        'client_name',
        'client_phone',
        'notes',

        // Thông tin giao nhận (MỚI)
        'shipping_address',
        'shipping_fee',
        'pickup_time',
        'order_method',
        'branch_id',

        // Tài chính
        'total_amount',
        'discount_amount',
        'grand_total',

        'cancelled_at',
        'user_id',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'pickup_time' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    /**
     * Lấy các mã khuyến mãi đã được áp dụng cho đơn hàng này.
     */
    public function appliedPromotions()
    {
        // Quan hệ nhiều-nhiều thông qua bảng order_promotions
        return $this->belongsToMany(Promotion::class, 'order_promotions')
            ->withPivot('discount_amount') // Lấy thêm cột số tiền giảm
            ->withTimestamps();
    }

    /**
     * Tự động sinh mã đơn hàng trước khi tạo mới.
     * @return void
     */
    protected static function booted(): void
    {
        static::created(function ($order) {
            // Nếu chưa có mã đơn hàng, tạo dựa trên ID
            if (empty($order->order_code)) {
                $order->order_code = 'DH' . str_pad($order->id, 6, '0', STR_PAD_LEFT);
                $order->save();
            }
        });
    }
    // Mối quan hệ với User (bảng users)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Mối quan hệ với OrderDetail (bảng order_details)
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
    // Mối quan hệ với bảng peyment
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
    public function pickupLocation(): BelongsTo
    {
        return $this->belongsTo(PickupLocation::class, 'pickup_location_id');
    }
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
