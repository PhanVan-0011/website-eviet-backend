<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    use HasFactory;

    protected $table = 'order_details';

    protected $fillable = [
        'order_id', 
        'product_id', 
        'combo_id', 
        'quantity', 
        'unit_price', 
        'unit_of_measure', 
        'attributes_snapshot', 
        'subtotal', 
        'discount_amount'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2', // Cast số tiền
        'discount_amount' => 'decimal:2',
        'quantity' => 'integer',
    
        'attributes_snapshot' => 'array', 
    ];


    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }
}
