<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'payment_method_id',
        'status',           // Trạng thái thanh toán: pending, success, failed
        'amount',           // Tổng số tiền đã thanh toán
        'transaction_id',   // Mã giao dịch trả về từ cổng thanh toán        // Trạng thái kích hoạt thanh toán (true = đang sử dụng)
        'callback_data',    // Dữ liệu phản hồi từ cổng thanh toán (JSON/raw)
        'paid_at',          // Thời điểm thanh toán thành công
        'order_id'          // Khóa ngoại liên kết với đơn hàng
    ];
    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];
    // Quan hệ ngược với đơn hàng
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    /**
     * QUAN HỆ MỚI:
     * Quan hệ ngược với PaymentMethod.
     * Một giao dịch thanh toán thuộc về một phương thức thanh toán.
     */
    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
