<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;
     protected $fillable = [
        'code',
        'name',
        'address',
        'phone_number',
        'email',
        'active',
    ];
    
    protected $casts = [
        'active' => 'boolean', 
    ];
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'branch_product_stocks')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    /**
     * Lấy tất cả người dùng thuộc chi nhánh này.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
