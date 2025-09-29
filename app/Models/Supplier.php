<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;
     protected $fillable = [
        'code',
        'name',
        'group_id',
        'phone_number',
        'address',
        'email',
        'tax_code',
        'notes',
        'total_purchase_amount',
        'balance_due',
        'is_active',
        'user_id',
    ];
    /**
     * Mối quan hệ: Một nhà cung cấp thuộc về một nhóm nhà cung cấp.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(SupplierGroup::class, 'group_id');
    }
      // Mối quan hệ: Một nhà cung cấp thuộc về một người dùng (người tạo)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Mối quan hệ: Một nhà cung cấp có nhiều hóa đơn nhập hàng
    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    // Mối quan hệ: Một nhà cung cấp có nhiều phiếu trả hàng
    public function returns()
    {
        return $this->hasMany(ReturnGood::class);
    }

}
