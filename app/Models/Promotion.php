<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;
     protected $fillable = [
        'name',
        'code',
        'description',
        'application_type',
        'type',
        'value',
        'min_order_value',
        'max_discount_amount',
        'max_usage',
        'max_usage_per_user',
        'is_combinable',
        'start_date',
        'end_date',
        'is_active',
    ];
    protected $casts = [
        'value' => 'decimal:2',
        'min_order_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'is_combinable' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];
    /**
     * Lấy các sản phẩm được áp dụng khuyến mãi này.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'promotion_products')->withTimestamps();;
    }

    /**
     * Lấy các danh mục được áp dụng khuyến mãi này.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'promotion_categories')->withTimestamps();;
    }

    /**
     * Lấy các combo được áp dụng khuyến mãi này.
     */
    public function combos()
    {
        return $this->belongsToMany(Combo::class, 'promotion_combos')->withTimestamps();;
    }
     /**
     * Lấy lịch sử các đơn hàng đã áp dụng khuyến mãi này.
     */
    public function appliedOrders()
    {
        return $this->hasMany(OrderPromotion::class);
    }

}
