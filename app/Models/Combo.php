<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\ComboItem;
use Illuminate\Database\Eloquent\Model;


class Combo extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'price',
        'slug',
        'image_url',
        'start_date',
        'end_date',
        'is_active'
    ];
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Một combo có nhiều combo_item
    public function items()
    {
        return $this->hasMany(ComboItem::class);
    }

    // Một combo có nhiều sản phẩm thông qua bảng combo_items
    public function products()
    {
        return $this->belongsToMany(Product::class, 'combo_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
