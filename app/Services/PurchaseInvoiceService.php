<?php

namespace App\Services;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use Exception;

class PurchaseInvoiceService
{
     /**
     * Tạo một hóa đơn nhập hàng mới để kích hoạt Observer.
     * @param int $supplierId ID nhà cung cấp
     * @param float $total Tổng tiền hóa đơn
     * @param float $paid Số tiền đã trả (để lại công nợ)
     * @return PurchaseInvoice
     */
    public function createTestInvoice(int $supplierId, float $total, float $paid): PurchaseInvoice
    {
        // Giả sử Super Admin ID là 37 và Chi nhánh ID là 1
        $userId = User::first()->id ?? 1;
        
        return PurchaseInvoice::create([
            'invoice_code' => 'INV_' . time(),
            'supplier_id' => $supplierId,
            'branch_id' => 1, // Thay bằng ID chi nhánh hợp lệ trong DB của bạn
            'user_id' => $userId,
            'invoice_date' => now(),
            'total_amount' => $total,
            'discount_amount' => 0.00,
            'paid_amount' => $paid, 
            'total_items' => 10,
            'status' => 'received',
        ]);
    }
}