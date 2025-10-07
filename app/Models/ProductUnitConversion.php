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
        'conversion_factor',
        'is_purchase_unit',
        'is_sales_unit',
        'initial_unit_cost', 
    ];

    /**
     * Mỗi quy tắc chuyển đổi thuộc về một sản phẩm duy nhất.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
