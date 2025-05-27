<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'content',
        'slug',
        'status',
        'image_url',
    ];
    /**
     * Quan hệ nhiều-nhiều với Category
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_post', 'post_id', 'category_id')
                    ->withTimestamps();
    }
}
