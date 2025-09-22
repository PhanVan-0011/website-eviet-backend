<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    use HasFactory;
      protected $fillable = [
        'product_id',
        'branch_id',
        'price_type',
        'unit_of_measure',
        'unit_multiplier',
        'price',
        'start_date',
        'end_date',
    ];

    // Mối quan hệ: Một mức giá thuộc về một sản phẩm
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Mối quan hệ: Một mức giá thuộc về một chi nhánh
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
