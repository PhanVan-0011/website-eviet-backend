<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Post extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'content',
        'slug',
        'status',
        
    ];
    /**
     * Quan hệ nhiều-nhiều với Category
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_post', 'post_id', 'category_id')
                    ->withTimestamps();
    }
    /**
     * Lấy TẤT CẢ các ảnh của bài viết.
     * Đây là quan hệ đa hình một-nhiều.
     */
     public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
        /**
     * Lấy ảnh ĐẠI DIỆN của bài viết.
     * Đây là một lối tắt tiện lợi để lấy ảnh có is_featured = true.
     */
    public function featuredImage(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->where('is_featured', true);
    }
}
