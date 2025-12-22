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
        'status', //pending, processing, delivered, cancelled
        'price_type',
        // Thông tin khách
        'client_name',
        'client_phone',
        'notes',

        // Thông tin giao nhận 
        'shipping_fee',
        'order_method',
        'branch_id',
        'pickup_location_id', 
        'time_slot_id', 
        // Tài chính
        'total_amount',
        'discount_amount',
        'grand_total',

        'cancelled_at',
        'user_id',
    ];

    protected $casts = [
        'order_date' => 'datetime',
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
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
    public function pickupLocation(): BelongsTo
    {
        return $this->belongsTo(PickupLocation::class, 'pickup_location_id');
    }

    // Mối quan hệ với Branch (bảng branches)
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(OrderTimeSlot::class, 'time_slot_id');
    }
}
