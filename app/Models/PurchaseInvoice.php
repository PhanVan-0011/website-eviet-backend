<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; 

class PurchaseInvoice extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_code',
        'supplier_id',
        'branch_id',
        'user_id',
        'invoice_date',
        'total_quantity',
        'total_items',
        'subtotal_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'amount_owed',
        'notes',
        'status',
    ];
    // Mối quan hệ: Một hóa đơn nhập thuộc về một nhà cung cấp
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Mối quan hệ: Một hóa đơn nhập thuộc về một chi nhánh
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Mối quan hệ: Một hóa đơn nhập thuộc về một người dùng
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Mối quan hệ: Một hóa đơn nhập có nhiều chi tiết
    public function details(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceDetail::class, 'invoice_id');
    }
}
