<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComboItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'combo_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer', 
        'combo_id' => 'integer',
        'product_id' => 'integer',
    ];
    
    /**
     * Mỗi combo_item thuộc về 1 combo
     */
    public function combo()
    {
        return $this->belongsTo(Combo::class);
    }

    /**
     * Mỗi combo_item chứa 1 sản phẩm
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
