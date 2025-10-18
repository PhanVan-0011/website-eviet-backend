<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; 

class PurchaseInvoiceDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id',
        'product_id',
        'unit_of_measure',
        'quantity',
        'unit_price',
        'subtotal',
    ];
    
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'invoice_id');
    }
    // Mối quan hệ: Một chi tiết là của một sản phẩm
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
