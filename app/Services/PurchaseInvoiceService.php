<?php

namespace App\Services;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use Exception;
use App\Models\Product;
use App\Models\PurchaseInvoiceDetail;
use App\Models\BranchProductStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PurchaseInvoiceService
{
    /**
     * Lấy danh sách hóa đơn nhập hàng với các bộ lọc và phân trang.
     */
    public function getAllInvoices($request)
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $query = PurchaseInvoice::query();

            // --- BỘ LỌC DANH SÁCH MỚI ---
            
            //Lọc theo Nhà cung cấp
            if ($request->has('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }
            
            //Lọc theo Chi nhánh
            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            //Lọc theo Trạng thái (draft, received, cancelled)
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            //Lọc theo Người tạo
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            //Lọc theo Mã hóa đơn
            if ($request->has('keyword')) {
                $query->where('invoice_code', 'like', "%{$request->keyword}%");
            }
            
            //Lọc theo Ngày tạo
            if (!empty($request->input('start_date'))) {
                $query->whereDate('created_at', '>=', $request->input('start_date'));
            }

            if (!empty($request->input('end_date'))) {
                $query->whereDate('created_at', '<=', $request->input('end_date'));
            }
            
            $query->with('supplier', 'branch', 'user');
            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            $invoices = $query->skip($offset)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            return [
                'data' => $invoices,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'pre_page' => $prevPage,
            ];
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách hóa đơn nhập: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Lấy chi tiết một hóa đơn nhập hàng.
     */
    public function getInvoiceById(string $id): PurchaseInvoice
    {
        return PurchaseInvoice::with('supplier', 'branch', 'user', 'details.product')->findOrFail($id);
    }

  /**
     * Tạo mới một hóa đơn nhập hàng.
     */
    public function createInvoice(array $data): PurchaseInvoice
    {
        return DB::transaction(function () use ($data) {
            $calculatedData = $this->_calculateInvoiceTotals($data);
            
            if (empty($calculatedData['invoice_code'])) {
                $calculatedData['invoice_code'] = $this->_generateUniqueInvoiceCode();
            }

            $invoice = PurchaseInvoice::create($calculatedData);
            $invoice->details()->createMany($calculatedData['details']);

            // Nếu trạng thái là 'received', cập nhật tồn kho và giá vốn ngay lập tức
            if ($invoice->status === 'received') {
                $this->_updateStockAndCostPrice($invoice, $calculatedData['details'], 1);
            }

            // Observer trong Model sẽ tự động cập nhật công nợ NCC
            return $invoice->load(['supplier', 'branch', 'user', 'details.product']);
        });
    }
    /**
     * Cập nhật một hóa đơn nhập hàng, xử lý cả thay đổi trạng thái.
     */
    public function updateInvoice(string $id, array $data): PurchaseInvoice
    {
        return DB::transaction(function () use ($id, $data) {
            $invoice = PurchaseInvoice::with('details')->findOrFail($id);
            $oldStatus = $invoice->status;
            $oldDetails = $invoice->details->toArray();
            
            $newStatus = $data['status'] ?? $oldStatus;
            
            // 1. LUÔN HOÀN TÁC TRẠNG THÁI CŨ (NẾU CẦN)
            // Nếu phiếu cũ là 'received', chúng ta cần "undo" các tác động của nó trước khi áp dụng thay đổi mới.
            if ($oldStatus === 'received') {
                $this->_updateStockAndCostPrice($invoice, $oldDetails, -1);
            }

            // 2. CẬP NHẬT HÓA ĐƠN VỚI DỮ LIỆU MỚI
            $calculatedData = $this->_calculateInvoiceTotals($data);
            $invoice->update($calculatedData);
            
            // Nếu có chi tiết mới, xóa cái cũ và thêm cái mới vào
            if (!empty($calculatedData['details'])) {
                $invoice->details()->delete();
                $invoice->details()->createMany($calculatedData['details']);
            }
            
            // 3. ÁP DỤNG TRẠNG THÁI MỚI (NẾU CẦN)
            // Nếu trạng thái mới là 'received', chúng ta áp dụng tác động vào kho và giá vốn.
            if ($newStatus === 'received') {
                // Lấy chi tiết mới nhất sau khi đã cập nhật
                $newDetails = $invoice->refresh()->details->toArray();
                $this->_updateStockAndCostPrice($invoice, $newDetails, 1);
            }
            
            // Observer trong Model sẽ tự động cập nhật lại công nợ NCC
            return $invoice->refresh()->load(['supplier', 'branch', 'user', 'details.product']);
        });
    }
    /**
     * Xóa một hóa đơn nhập hàng.
     */
    public function deleteInvoice(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $invoice = PurchaseInvoice::with('details')->findOrFail($id);
            
            // Nếu phiếu đã nhập kho, cần hoàn tác lại
            if ($invoice->status === 'received') {
                $this->_updateStockAndCostPrice($invoice, $invoice->details->toArray(), -1);
            }

            return $invoice->delete(); // Observer sẽ tự động cập nhật công nợ NCC
        });
    }

    /**
     * Tái tính toán và cập nhật công nợ cho một nhà cung cấp.
     * Hàm này được gọi tự động bởi Model Observer.
     */
    public function recalculateSupplierTotals(int $supplierId): void
    {
        $supplier = Supplier::find($supplierId);
        if (!$supplier) return;

        $totals = PurchaseInvoice::where('supplier_id', $supplierId)
            ->where('status', 'received')
            ->selectRaw('SUM(total_amount) as total_purchase, SUM(amount_owed) as total_due')
            ->first();

        $supplier->total_purchase_amount = $totals->total_purchase ?? 0;
        $supplier->balance_due = $totals->total_due ?? 0;
        $supplier->save();
    }

    /**
     * Hàm tính toán nội bộ.
     */
    private function _calculateInvoiceTotals(array $data): array
    {
        $details = $data['details'] ?? [];
        $totalQuantity = 0;
        $subtotal = 0;

        foreach ($details as $key => $detail) {
            $quantity = (float)($detail['quantity'] ?? 0);
            $unitPrice = (float)($detail['unit_price'] ?? 0);
            
            $lineTotal = $quantity * $unitPrice;
            $details[$key]['subtotal'] = $lineTotal;

            $subtotal += $lineTotal;
            $totalQuantity += $quantity;
        }

        $discount = (float)($data['discount_amount'] ?? 0);
        $totalAmount = $subtotal - $discount;
        $paidAmount = (float)($data['paid_amount'] ?? 0);
        
        $data['total_quantity'] = $totalQuantity;
        $data['total_items'] = count($details);
        $data['subtotal_amount'] = $subtotal;
        $data['total_amount'] = $totalAmount;
        $data['amount_owed'] = $totalAmount - $paidAmount;
        $data['details'] = $details;

        return $data;
    }

    /**
     * Hàm nội bộ để cập nhật tồn kho VÀ giá vốn.
     * @param int $direction 1 để cộng (nhập/hoàn thành), -1 để trừ (hủy/xóa)
     */
    private function _updateStockAndCostPrice(PurchaseInvoice $invoice, array $details, int $direction): void
    {
        $branchId = $invoice->branch_id;

        foreach ($details as $detail) {
            $productId = $detail['product_id'];
            $incomingQty = (float)$detail['quantity'];
            $incomingPrice = (float)$detail['unit_price'];

            // --- Cập nhật tồn kho ---
            $stock = BranchProductStock::firstOrCreate(
                ['branch_id' => $branchId, 'product_id' => $productId],
                ['quantity' => 0]
            );
            $newStockQty = $stock->quantity + ($incomingQty * $direction);
            $stock->quantity = max(0, $newStockQty); // Đảm bảo tồn kho không âm
            $stock->save();

            // --- Cập nhật giá vốn ---
            $product = Product::find($productId);
            if ($product) {
                $totalStockAllBranches = (float)DB::table('branch_product_stocks')->where('product_id', $productId)->sum('quantity');
                $oldCostPrice = (float)$product->cost_price;

                if ($direction === 1) { // Nhập hàng
                     $oldStockForCalc = $totalStockAllBranches - $incomingQty;
                     $oldTotalValue = $oldStockForCalc * $oldCostPrice;
                     $incomingValue = $incomingQty * $incomingPrice;
                     $newCostPrice = ($totalStockAllBranches > 0) ? (($oldTotalValue + $incomingValue) / $totalStockAllBranches) : $incomingPrice;
                } else { // Hoàn tác (Hủy / Xóa)
                    $oldStockForCalc = $totalStockAllBranches + $incomingQty;
                    $oldTotalValue = $oldStockForCalc * $oldCostPrice;
                    $reversedValue = $incomingQty * $incomingPrice;
                    $newCostPrice = ($totalStockAllBranches > 0) ? (($oldTotalValue - $reversedValue) / $totalStockAllBranches) : 0;
                }
                
                $product->cost_price = max(0, $newCostPrice);
                $product->save();
            }
        }
    }
    
    /**
     * Tạo mã phiếu nhập duy nhất.
     */
    private function _generateUniqueInvoiceCode(): string
    {
        $prefix = 'PN';
        $lastInvoice = PurchaseInvoice::where('invoice_code', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextId = $lastInvoice ? ((int)substr($lastInvoice->invoice_code, strlen($prefix)) + 1) : 1;
        
        return $prefix . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }

    








}