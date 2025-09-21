<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    // Mối quan hệ: Một chi tiết thuộc về một hóa đơn nhập
    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'invoice_id');
    }

    // Mối quan hệ: Một chi tiết là của một sản phẩm
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
