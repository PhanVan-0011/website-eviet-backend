<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;
     protected $fillable = [
        'code',
        'name',
        'address',
        'phone_number',
        'email',
        'active',
        'is_flexible_time', 
    ];
    
    protected $casts = [
        'active' => 'boolean', 
    ];
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'branch_product_stocks')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }
    /**
     * Mối quan hệ: Một chi nhánh có thể áp dụng nhiều Khung Giờ.
     */
    public function timeSlots(): BelongsToMany
    {
        // Bảng trung gian là 'branch_time_slot_pivot'
        return $this->belongsToMany(OrderTimeSlot::class, 'branch_time_slot_pivot', 'branch_id', 'time_slot_id')
                    ->withPivot('is_enabled') // Lấy thêm cột 'is_enabled' từ bảng pivot
                    ->withTimestamps();
    }

    /**
     * Lấy tất cả người dùng thuộc chi nhánh này.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
    public function pickupLocations(): HasMany
    {
        return $this->hasMany(PickupLocation::class);
    }
}
