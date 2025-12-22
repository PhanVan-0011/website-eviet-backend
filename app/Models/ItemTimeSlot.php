<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemTimeSlot extends Model
{
    use HasFactory;
     /**
     * Tên bảng trong database.
     */
    protected $table = 'item_time_slots';

    protected $fillable = [
        'time_slot_id',
        'product_id',
        'combo_id',
    ];

    /**
     * Quan hệ với bảng Khung giờ
     */
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(OrderTimeSlot::class, 'time_slot_id');
    }

    /**
     * Quan hệ với Sản phẩm
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    /**
     * Quan hệ với Combo
     */
    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }
}
