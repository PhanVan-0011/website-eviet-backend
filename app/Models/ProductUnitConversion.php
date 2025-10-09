<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnitConversion extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'unit_name',
        'unit_code',
        'conversion_factor',
        'store_price',
        'app_price',
        'is_sales_unit',
    ];

     // Định nghĩa quan hệ BelongsTo với Product (để truy cập product_id)
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
