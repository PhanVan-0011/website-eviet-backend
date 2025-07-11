<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'payment_method_id',
        'status',           
        'amount',           
        'transaction_id',   
        'callback_data',    
        'paid_at',         
        'order_id'          
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
