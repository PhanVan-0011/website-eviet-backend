<?php

namespace App\Observers;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PurchaseInvoiceService;

class PurchaseInvoiceObserver
{
    protected $invoiceService;

    // Sử dụng Dependency Injection để lấy instance của Service
    public function __construct(PurchaseInvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Logic để tính toán amount_owed trước khi lưu vào DB (saving event).
     */
       public function saving(PurchaseInvoice $invoice): void
    {
        //Tính toán Amount Owed (Công nợ)
        // Công nợ = Tổng tiền hóa đơn - Số tiền đã trả (Sử dụng round để tránh lỗi float)
        $amountOwed = round((float)$invoice->total_amount - (float)$invoice->paid_amount, 2);
        
        $invoice->amount_owed = max(0, $amountOwed);

        //Tự động sinh Mã Phiếu Nhập (PNXXXXXX)
        if (is_null($invoice->invoice_code)) {
            // Lấy mã hóa đơn cuối cùng (theo ID giảm dần)
            $lastInvoice = PurchaseInvoice::select('invoice_code')
                                        ->orderByDesc('id')
                                        ->first();
            
            $nextNumber = 1;
            $prefix = 'PN';

            if ($lastInvoice && str_starts_with($lastInvoice->invoice_code, $prefix)) {
                // Tách phần số từ mã phiếu cuối cùng (ví dụ: PN000001 -> 1)
                $lastNumber = (int) substr($lastInvoice->invoice_code, strlen($prefix));
                $nextNumber = $lastNumber + 1;
            }

            // Định dạng lại mã phiếu (ví dụ: PN000001)
            $invoice->invoice_code = $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        }
    }
    public function created(PurchaseInvoice $invoice): void
    {
        $this->invoiceService->recalculateSupplierTotals($invoice->supplier_id);
    }

    public function updated(PurchaseInvoice $invoice): void
    {
        if ($invoice->isDirty(['total_amount', 'paid_amount', 'status'])) {
            $this->invoiceService->recalculateSupplierTotals($invoice->supplier_id);
        }
    }

    public function deleted(PurchaseInvoice $invoice): void
    {
        // GỌI HÀM TÁI TÍNH TỔNG trong Service
        $this->invoiceService->recalculateSupplierTotals($invoice->supplier_id);
    }
}
