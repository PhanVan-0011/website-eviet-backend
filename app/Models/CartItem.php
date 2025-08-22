<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'cart_id',
        'product_id',
        'combo_id',
        'name_snapshot',
        'sku_snapshot',
        'attributes',
        'quantity',
        'unit_price',
        'notes',
    ];
    
    protected $casts = [
        'attributes' => 'array', // Tự động parse JSON sang array
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function combo()
    {
        return $this->belongsTo(Combo::class);
    }
}
