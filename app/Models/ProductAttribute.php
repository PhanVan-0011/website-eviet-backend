<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttribute extends Model
{
   use HasFactory;

    protected $fillable = ['product_id', 'name', 'type', 'display_order'];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('display_order', 'asc');
    }
}
