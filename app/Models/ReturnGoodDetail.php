<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnGoodDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'return_good_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'reason',
    ];
     // Một chi tiết thuộc về một phiếu trả hàng
    public function returnGood()
    {
        return $this->belongsTo(ReturnGood::class);
    }

    // Một chi tiết là của một sản phẩm
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
