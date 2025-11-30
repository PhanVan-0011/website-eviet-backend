<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Category extends Model
{
    use HasFactory;

    // Constants cho loại danh mục
    const TYPE_PRODUCT = 'product';
    const TYPE_POST = 'post';
    const TYPE_ALL = 'all';

    protected $fillable = ['name', 'status', 'parent_id', 'description', 'type'];
    // Quan hệ: nhiều nhiều
    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }

    // Quan hệ: Một danh mục có thể thuộc về một danh mục cha (self-referencing)
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Quan hệ: Một danh mục có thể có nhiều danh mục con
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
    /**
     * Quan hệ nhiều-nhiều với Post
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'category_post', 'category_id', 'post_id')
                    ->withTimestamps();
    }
    /**
     * Lấy các chương trình khuyến mãi đang áp dụng cho danh mục này.
     */
    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_categories')->withTimestamps();
    }
    // Quan hệ đa hình: Một danh mục có một icon
    public function icon()
    {
        return $this->morphOne(Image::class, 'imageable');
    }
}
