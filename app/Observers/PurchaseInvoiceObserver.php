<?php

namespace App\Observers;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseInvoiceObserver
{
     /**
     * Lắng nghe sự kiện sau khi PurchaseInvoice được tạo (created event).
     */
    public function created(PurchaseInvoice $invoice): void
    {
        $this->updateSupplierBalances($invoice);
    }

    /**
     * Lắng nghe sự kiện sau khi PurchaseInvoice được cập nhật (updated event).
     */
    public function updated(PurchaseInvoice $invoice): void
    {
        // Thường được gọi khi trạng thái hoặc số tiền nợ thay đổi (ví dụ: thanh toán thêm)
        $this->updateSupplierBalances($invoice);
    }

    /**
     * Lắng nghe sự kiện trước khi PurchaseInvoice bị xóa (deleting event).
     */
    public function deleting(PurchaseInvoice $invoice): void
    {
        // Khi hóa đơn bị xóa, cần hoàn tác tác động lên tổng tiền của nhà cung cấp.
        // Chỉ áp dụng cho các hóa đơn không phải 'cancelled' (đã hủy)
        if ($invoice->isDirty('status') && $invoice->status !== 'cancelled') {
             // Logic hoàn tác (reverse the transaction)
             $this->updateSupplierBalances($invoice, true); 
        }
    }

    /**
     * Logic tính toán và cập nhật số dư của nhà cung cấp.
     */
    private function updateSupplierBalances(PurchaseInvoice $invoice, bool $reverse = false): void
    {
        // Sử dụng Transaction để đảm bảo tính toàn vẹn
        DB::transaction(function () use ($invoice, $reverse) {
            $supplier = Supplier::lockForUpdate()->find($invoice->supplier_id);

            if (!$supplier) {
                Log::warning("Không tìm thấy Nhà cung cấp ID: {$invoice->supplier_id} cho hóa đơn {$invoice->invoice_code}.");
                return;
            }

            // Tính toán giá trị cần thay đổi
            $totalAmountChange = $invoice->total_amount;
            // Giả sử 'amount_owed' là trường lưu số tiền công nợ phát sinh từ hóa đơn này
            $balanceDueChange = $invoice->total_amount - $invoice->paid_amount; 

            if ($reverse) {
                // Đảo ngược tác động khi xóa hoặc hủy
                $totalAmountChange = -$totalAmountChange;
                $balanceDueChange = -$balanceDueChange;
            }

            // Cập nhật các cột trong bảng suppliers
            $supplier->total_purchase_amount += $totalAmountChange;
            $supplier->balance_due += $balanceDueChange;
            $supplier->save();
        });
    }
}
