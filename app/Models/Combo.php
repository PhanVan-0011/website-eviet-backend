<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\ComboItem;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrderDetail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;


class Combo extends Model
{
    use HasFactory;
    protected $fillable = [
        'combo_code',
        'name',
        'description',
        'base_store_price',
        'base_app_price',
        'start_date',
        'end_date',
        'is_active',
        'applies_to_all_branches',
        'is_flexible_time',
    ];
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'applies_to_all_branches' => 'boolean',
        'is_flexible_time' => 'boolean', 
        'base_store_price' => 'decimal:2',
        'base_app_price' => 'decimal:2',
    ];
    /**
     * Các thuộc tính ảo sẽ được thêm vào khi model được chuyển đổi thành mảng/JSON.
     *
     * @var array
     */

    /**
     * Mối quan hệ: Một combo bao gồm nhiều sản phẩm (items).
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'combo_items')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_combos')->withTimestamps();
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'combo_id');
    }
     /**
     * Mối quan hệ: Một combo có thể được áp dụng ở nhiều chi nhánh.
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_combo')
                    ->withPivot('is_active') 
                    ->withTimestamps();
    }

    /**
     * Quan hệ đa hình: Một combo có nhiều ảnh (Gallery).
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Quan hệ đa hình: Một combo có một ảnh đại diện (ảnh duy nhất).
     */
    public function image(): MorphOne
    {
        // Giả định ảnh duy nhất cũng là ảnh đại diện
        return $this->morphOne(Image::class, 'imageable')->where('is_featured', true);
    }

    /**
     * Scope để chỉ lấy các combo đang hoạt động.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope để lấy các combo đang trong thời gian áp dụng.r
     */
    public function scopeAvailable($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->where('start_date', '<=', $now)
                ->orWhereNull('start_date');
        })->where(function ($q) use ($now) {
            $q->where('end_date', '>=', $now)
                ->orWhereNull('end_date');
        });
    }
    /**
     * Mối quan hệ: Một combo có thể thuộc nhiều Khung Giờ.
     */
    public function timeSlots(): BelongsToMany
    {
        return $this->belongsToMany(OrderTimeSlot::class, 'item_time_slots', 'combo_id', 'time_slot_id')
                    ->withTimestamps();
    }
}
