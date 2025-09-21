<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnGood extends Model
{
    use HasFactory;
    protected $fillable = [
        'return_code',
        'supplier_id',
        'order_id',
        'purchase_invoice_id',
        'branch_id',
        'user_id',
        'total_amount',
        'discount_amount',
        'reason',
        'status',
    ];
      // Một phiếu trả hàng có thể thuộc về một đơn hàng (từ khách hàng)
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Một phiếu trả hàng có thể thuộc về một hóa đơn nhập (trả cho nhà cung cấp)
    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }
    
    // Một phiếu trả hàng thuộc về một chi nhánh
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Một phiếu trả hàng có nhiều chi tiết
    public function details()
    {
        return $this->hasMany(ReturnGoodDetail::class);
    }
}
