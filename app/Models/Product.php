<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products'; // Tùy chọn, nếu tên bảng đúng quy ước thì không cần
    protected $fillable = ['name', 'description', 'size', 'original_price', 'sale_price', 'stock_quantity', 'image_url', 'status', 'category_id'];

    // Quan hệ: Một sản phẩm thuộc về một danh mục
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
