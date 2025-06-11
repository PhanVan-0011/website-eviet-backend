<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'image_url',
        'link_url',
        'display_order',
        'is_active',
        'link_type',
        'combo_id'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];
    public function combo()
    {
        return $this->belongsTo(Combo::class, 'combo_id');
    }
}
