<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchProductStock extends Model
{
    use HasFactory;
    protected $table = 'branch_product_stocks';

    protected $fillable = [
        'branch_id',
        'product_id',
        'quantity',
    ];

    // Mối quan hệ: Bản ghi tồn kho này thuộc về một chi nhánh
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Mối quan hệ: Bản ghi tồn kho này thuộc về một sản phẩm
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
