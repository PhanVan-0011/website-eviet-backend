<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_date', 'total_amount', 'status', 'client_name',
        'client_phone', 'shipping_address', 'shipping_fee', 'cancelled_at', 'user_id'
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
    ];
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

}
