<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'status', 'parent_id', 'description'];
    // Quan hệ: Một danh mục có nhiều sản phẩm
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
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
}
