<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
   use HasFactory;

    protected $fillable = [
        'product_attribute_id', 
        'value', 
        'price_adjustment', 
        'display_order', 
        'is_active', 
        'is_default'
    ];
}
