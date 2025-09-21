<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;
     protected $fillable = [
        'code',
        'name',
        'address',
        'phone_number',
        'email',
        'is_active',
    ];
    // Mối quan hệ: Một chi nhánh có nhiều sản phẩm
    public function products()
    {
        return $this->belongsToMany(Product::class, 'branch_product_stocks')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    // Mối quan hệ: Một chi nhánh có nhiều người dùng
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
