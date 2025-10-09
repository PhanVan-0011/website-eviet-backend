<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        /**
     * Định nghĩa quan hệ BelongsTo với ProductAttribute.
     */
    public function productAttribute(): BelongsTo
    {
        // Liên kết Model AttributeValue (con) với Model ProductAttribute (cha)
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }
}
