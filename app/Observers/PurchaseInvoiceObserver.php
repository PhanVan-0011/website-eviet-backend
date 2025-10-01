<?php

namespace App\Observers;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseInvoiceObserver
{
      /**
     * Logic để tính toán amount_owed trước khi lưu vào DB (saving event).
     * Sự kiện này xảy ra trước cả created và updated.
     */
    public function saving(PurchaseInvoice $invoice): void
    {
        // Công nợ = Tổng tiền hóa đơn - Số tiền đã trả
        $amountOwed = $invoice->total_amount - $invoice->paid_amount;
        
        // Gán giá trị vào cột amount_owed
        // Đảm bảo không âm
        $invoice->amount_owed = max(0, $amountOwed);

        // Tạo mã hóa đơn nếu chưa tồn tại
        if (is_null($invoice->invoice_code)) {
            $invoice->invoice_code = 'HDN_' . time();
        }
    }

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
        // Khi các cột tiền tệ hoặc trạng thái thay đổi, cần cập nhật lại số dư nhà cung cấp.
        if ($invoice->isDirty(['total_amount', 'paid_amount', 'status'])) {
            $this->updateSupplierBalances($invoice);
        }
    }

    /**
     * Lắng nghe sự kiện trước khi PurchaseInvoice bị xóa (deleting event).
     */
    public function deleting(PurchaseInvoice $invoice): void
    {
        // Hoàn tác tác động lên tổng tiền của nhà cung cấp khi hóa đơn bị xóa
        // Chỉ hoàn tác nếu hóa đơn không bị hủy
        if ($invoice->status !== 'cancelled') {
             $this->updateSupplierBalances($invoice, true); 
        }
    }

    /**
     * Logic tính toán và cập nhật số dư của nhà cung cấp.
     */
    private function updateSupplierBalances(PurchaseInvoice $invoice, bool $reverse = false): void
    {
        // Chỉ cập nhật nếu hóa đơn không phải là 'draft'
        if ($invoice->status === 'draft' && !$reverse) {
            return;
        }

        DB::transaction(function () use ($invoice, $reverse) {
            $supplier = Supplier::lockForUpdate()->find($invoice->supplier_id);

            if (!$supplier) {
                Log::warning("Không tìm thấy Nhà cung cấp ID: {$invoice->supplier_id} cho hóa đơn {$invoice->invoice_code}.");
                return;
            }

            // Lấy giá trị CŨ và MỚI của các cột quan trọng
            // Dùng getOriginal() để lấy giá trị trước khi lưu
            $oldAmount = $invoice->getOriginal('total_amount');
            $newAmount = $invoice->total_amount;
            $oldOwed = $invoice->getOriginal('amount_owed');
            $newOwed = $invoice->amount_owed;

            // Tính toán sự thay đổi
            $totalAmountDiff = $newAmount - $oldAmount; // Sự thay đổi tổng tiền
            $balanceDueDiff = $newOwed - $oldOwed;     // Sự thay đổi công nợ

            if ($reverse) {
                // Khi xóa, đảo ngược tác động của toàn bộ hóa đơn
                $supplier->total_purchase_amount -= $newAmount;
                $supplier->balance_due -= $newOwed;
            } else {
                // Khi cập nhật/tạo mới:
                $supplier->total_purchase_amount += $totalAmountDiff;
                $supplier->balance_due += $balanceDueDiff;
            }
            
            // Đảm bảo số dư không âm 
            $supplier->balance_due = max(0, $supplier->balance_due); 
            $supplier->save();
        });
    }
}
