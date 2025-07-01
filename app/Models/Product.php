<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['product_code','name', 'description', 'size', 'original_price',
     'sale_price', 'stock_quantity', 'image_url', 'status'];
 /**
     * Ghi đè phương thức boot của model để đăng ký event.
     */
    protected static function booted(): void
    {      
        static::created(function ($product) {
            if (is_null($product->product_code)) {
                $product->product_code = 'SP' . str_pad($product->id, 6, '0', STR_PAD_LEFT);
                $product->saveQuietly();
            }
        });
    }
    public function categories() 
    {
        return $this->belongsToMany(Category::class, 'category_product')->withTimestamps();
    }
    // Quan hệ với bảng combo thông qua bảng combo_items
    public function combos()
    {
        return $this->belongsToMany(Combo::class, 'combo_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }
    /**
     * Lấy các chương trình khuyến mãi đang áp dụng cho sản phẩm này.
     */
    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_products')->withTimestamps();
    }
}
