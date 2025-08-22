<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
        protected $fillable = [
        'user_id',
        'guest_token',
        'status',
        'items_count',
        'items_quantity',
        'subtotal',
        'order_id',
    ];
    
    // Một giỏ hàng có nhiều item
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    // Thuộc về 1 user (có thể null)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Liên kết với order sau khi checkout
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
