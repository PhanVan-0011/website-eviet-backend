<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Slider extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'display_order',
        'is_active',
        'linkable_id',   
        'linkable_type', 
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the parent linkable model (morph to).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }
}
