<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Payment;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'order_date',
        'total_amount',
        'status',
        'client_name',
        'client_phone',
        'shipping_address',
        'shipping_fee',
        'cancelled_at',
        'user_id',
        'discount_amount',
        'grand_total'
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'grand_total' => 'decimal:2',
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
}
