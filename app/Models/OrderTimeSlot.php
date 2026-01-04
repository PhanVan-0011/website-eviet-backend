<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrderTimeSlot extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'delivery_start_time',
        'delivery_end_time', 
        'is_active',
    ];
    /**
     * Các trường nên được cast
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_time_slot_pivot', 'time_slot_id', 'branch_id')
                    ->withPivot('is_enabled')
                    ->withTimestamps();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'item_time_slots', 'time_slot_id', 'product_id')
                    ->withTimestamps();
    }

    public function combos(): BelongsToMany
    {
        return $this->belongsToMany(Combo::class, 'item_time_slots', 'time_slot_id', 'combo_id')
                    ->withTimestamps();
    }
}
