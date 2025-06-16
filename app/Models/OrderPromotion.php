<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPromotion extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'promotion_id',
        'discount_amount',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];
    // Các quan hệ ngược
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    // quan hệ với promotion
    public function promotion()
    {
        return $this->belongsTo(Promotion::class)->withTimestamps();
    }
}
