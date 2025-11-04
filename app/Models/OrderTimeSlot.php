<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrderTimeSlot extends Model
{
    use HasFactory;
    /**
     * Các trường nên được cast
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Mối quan hệ: Một khung giờ có thể được áp dụng cho nhiều Chi Nhánh.
     * Bảng trung gian: branch_time_slot_pivot
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_time_slot_pivot', 'time_slot_id', 'branch_id')
                    ->withPivot('is_enabled')
                    ->withTimestamps();
    }
    /**
     * Mối quan hệ: Một khung giờ có thể áp dụng cho nhiều Sản Phẩm.
     * Bảng trung gian: item_time_slots
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'item_time_slots', 'time_slot_id', 'product_id')
                    ->withTimestamps();
    }

    /**
     * Mối quan hệ: Một khung giờ có thể áp dụng cho nhiều Combo.
     * Bảng trung gian: item_time_slots
     */
    public function combos(): BelongsToMany
    {
        return $this->belongsToMany(Combo::class, 'item_time_slots', 'time_slot_id', 'combo_id')
                    ->withTimestamps();
    }
}
