<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;
    protected $fillable = [
        'image_url',
        'is_featured',
        'imageable_id',
        'imageable_type',
    ];
     /**
     * Thêm thuộc tính "ảo" full_url để luôn có đường dẫn đầy đủ của ảnh.
     */
    protected $appends = ['full_url'];

    /**
     * Định nghĩa mối quan hệ đa hình "imageable".
     * Một ảnh có thể thuộc về một Product, một Post, hoặc một Combo...
     */
    public function imageable()
    {
        return $this->morphTo();
    }

}
