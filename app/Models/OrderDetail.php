<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;
    protected $fillable = ['order_id', 'product_id', 'combo_id', 'quantity', 'unit_price', 'unit_of_measure', 'attributes_snapshot', 'discount_amount'];
    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
    ];
    // Mối quan hệ với Order (bảng orders)
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    // Mối quan hệ với Product (bảng products)
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    // Mối qua hệ với combo (Bảng combo)
    public function combo()
    {
        return $this->belongsTo(Combo::class);
    }
}
