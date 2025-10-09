<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
     use HasFactory;
    protected $fillable = [
        'product_code', 
        'name', 
        'description', 
        'status', 
        'base_unit', 
        'cost_price', 
        'base_store_price', 
        'base_app_price',
        'is_sales_unit' 
    ];
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

      /**
     * Quan hệ đa hình: Một sản phẩm có nhiều ảnh.
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Một hàm helper tiện lợi để lấy ảnh đại diện.
     */
    public function featuredImage()
    {
        return $this->morphOne(Image::class, 'imageable')->where('is_featured', true);
    }

    /**
     * Một hàm helper tiện lợi để lấy các ảnh chi tiết (không phải ảnh đại diện).
     */
    public function galleryImages()
    {
        return $this->morphMany(Image::class, 'imageable')->where('is_featured', false);
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
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'product_id');
    }
    //Tùy chọn thuộc tính thêm món ăn  ví dụ: đường ít nhiều....
     public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }
     // Một sản phẩm có nhiều mức giá
    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    // Một sản phẩm có tồn kho ở nhiều chi nhánh
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_product_stocks')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }
    /**
     * Lấy tất cả các quy tắc chuyển đổi đơn vị của sản phẩm.
     */
    public function unitConversions(): HasMany
    {
        return $this->hasMany(ProductUnitConversion::class);
    }
}
