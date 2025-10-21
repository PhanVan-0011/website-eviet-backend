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
     * @param Request $request
     * @return array
     */
    public function getAllInvoices($request)
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $query = PurchaseInvoice::query();

            // Lọc theo Nhà cung cấp
            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }
            
            // Lọc theo Chi nhánh
            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            // Lọc theo Trạng thái
            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            // Lọc theo Người tạo
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            //Tìm kiếm theo Mã hóa đơn
            if ($request->filled('keyword')) {
                $query->where('invoice_code', 'like', "%{$request->keyword}%");
            }
            
            // Lọc theo Ngày hóa đơn (invoice_date)
            if ($request->filled('start_date')) {
                $query->whereDate('invoice_date', '>=', $request->input('start_date'));
            }

            if ($request->filled('end_date')) {
                $query->whereDate('invoice_date', '<=', $request->input('end_date'));
            }
            
            $query->with('supplier', 'branch', 'user')->orderBy('invoice_date', 'desc');
            
            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            $invoices = $query->skip($offset)->take($perPage)->get();

            return [
                'data' => $invoices,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
                'next_page' => $currentPage < (int) ceil($total / $perPage) ? $currentPage + 1 : null,
                'pre_page' => $currentPage > 1 ? $currentPage - 1 : null,
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

            $newStatus = $data['status'] ?? $oldStatus;

            // 1. LUÔN HOÀN TÁC TRẠNG THÁI CŨ (NẾU CẦN)
            if ($oldStatus === 'received') {
                $this->_updateStockAndCostPrice($invoice, $invoice->details->toArray(), -1);
            }

            // 2. TÍNH TOÁN LẠI DỮ LIỆU
            $calculatedData = $this->_calculateInvoiceTotals($data, $invoice);

            $details = $calculatedData['details'] ?? null;
            unset($calculatedData['details']);

            // 3. CẬP NHẬT HÓA ĐƠN
            $invoice->update($calculatedData);

            // Nếu có chi tiết mới, xóa cái cũ và thêm cái mới vào
            if ($details !== null) {
                $invoice->details()->delete();
                $invoice->details()->createMany($details);
            }

            // 4. ÁP DỤNG TRẠNG THÁI MỚI (NẾU CẦN)
            if ($newStatus === 'received') {
                $newDetails = $invoice->refresh()->details->toArray();
                $this->_updateStockAndCostPrice($invoice, $newDetails, 1);
            }

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
            
            if ($invoice->status === 'received') {
                $this->_updateStockAndCostPrice($invoice, $invoice->details->toArray(), -1);
            }
            $invoice->details()->delete();
            return $invoice->delete();
        });
    }

    /**
     * Xóa nhiều hóa đơn nhập hàng cùng lúc.
     */
    public function multiDelete(array $ids): int
    {
        $deletedCount = 0;
        foreach ($ids as $id) {
            try {
                if ($this->deleteInvoice($id)) {
                    $deletedCount++;
                }
            } catch (Exception $e) {
                Log::error("Lỗi khi xóa nhiều hóa đơn nhập - ID: {$id}: " . $e->getMessage());
            }
        }
        return $deletedCount;
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

     private function _calculateInvoiceTotals(array $data, ?PurchaseInvoice $existingInvoice = null): array
    {
        $details = $data['details'] ?? ($existingInvoice ? $existingInvoice->details->toArray() : []);
        $productIds = array_column($details, 'product_id');
        $products = Product::with('unitConversions')->whereIn('id', $productIds)->get()->keyBy('id');

        $totalQuantityOnInvoice = 0;
        $subtotal = 0;

        foreach ($details as $key => $detail) {
            $quantity = (float)($detail['quantity'] ?? 0);
            $unitPrice = (float)($detail['unit_price'] ?? 0);
            
            $lineTotal = round($quantity * $unitPrice, 2);
            $details[$key]['subtotal'] = $lineTotal;
            $subtotal += $lineTotal;
            $totalQuantityOnInvoice += $quantity;
        }

        $discount = array_key_exists('discount_amount', $data) ? (float)$data['discount_amount'] : ($existingInvoice ? (float)$existingInvoice->discount_amount : 0);
        $paidAmount = array_key_exists('paid_amount', $data) ? (float)$data['paid_amount'] : ($existingInvoice ? (float)$existingInvoice->paid_amount : 0);
        
        $totalAmount = $subtotal - $discount;
        
        $result = $data;
        $result['total_quantity'] = $totalQuantityOnInvoice;
        $result['total_items'] = count($details);
        $result['subtotal_amount'] = $subtotal;
        $result['discount_amount'] = $discount;
        $result['total_amount'] = $totalAmount;
        $result['amount_owed'] = $totalAmount - $paidAmount;
        
        if (array_key_exists('details', $data)) {
            $result['details'] = $details;
        } else {
            unset($result['details']);
        }

        return $result;
    }

    private function _updateStockAndCostPrice(PurchaseInvoice $invoice, array $details, int $direction): void
    {
        $branchId = $invoice->branch_id;
        $productIds = array_unique(array_column($details, 'product_id'));
        $products = Product::with('unitConversions')->whereIn('id', $productIds)->get()->keyBy('id');
        
        $aggregatedChanges = [];

        // 1. Gom nhóm và tính toán tổng thay đổi cho mỗi sản phẩm
        foreach ($details as $detail) {
            $productId = $detail['product_id'];
            $product = $products->get($productId);
            if (!$product) continue;

            $incomingQty = (float)$detail['quantity'];
            $incomingPrice = (float)$detail['unit_price'];
            $unitOfMeasure = $detail['unit_of_measure'];

            $conversionFactor = 1.0;
            if ($unitOfMeasure !== $product->base_unit) {
                $unitConversion = $product->unitConversions->firstWhere('unit_name', $unitOfMeasure);
                if ($unitConversion) {
                    $conversionFactor = (float)$unitConversion->conversion_factor;
                }
            }

            $baseUnitQty = $incomingQty * $conversionFactor;
            $baseUnitPrice = ($conversionFactor > 0) ? $incomingPrice / $conversionFactor : 0;
            
            if (!isset($aggregatedChanges[$productId])) {
                $aggregatedChanges[$productId] = ['total_base_qty' => 0, 'total_value' => 0];
            }
            
            $aggregatedChanges[$productId]['total_base_qty'] += $baseUnitQty;
            $aggregatedChanges[$productId]['total_value'] += $baseUnitQty * $baseUnitPrice;
        }

        // 2. Áp dụng các thay đổi đã được gom nhóm
        foreach ($aggregatedChanges as $productId => $changes) {
            $product = $products->get($productId);
            $totalBaseQtyChange = $changes['total_base_qty'];
            $totalValueChange = $changes['total_value'];
            
            // --- Cập nhật Tồn kho ---
            $stock = BranchProductStock::firstOrCreate(
                ['branch_id' => $branchId, 'product_id' => $productId],
                ['quantity' => 0]
            );
            $stock->quantity = max(0, $stock->quantity + ($totalBaseQtyChange * $direction));
            $stock->save();

            // --- Cập nhật Giá vốn ---
            $totalStockAllBranches = (float)BranchProductStock::where('product_id', $productId)->sum('quantity');
            $oldCostPrice = (float)$product->cost_price;

            if ($direction === 1) { // Nhập hàng
                 $oldStockForCalc = $totalStockAllBranches - $totalBaseQtyChange;
                 $oldTotalValue = $oldStockForCalc * $oldCostPrice;
                 $newCostPrice = ($totalStockAllBranches > 0) ? (($oldTotalValue + $totalValueChange) / $totalStockAllBranches) : 0;
                 if ($totalStockAllBranches > 0 && $oldStockForCalc <= 0) { // Lần đầu nhập hàng
                    $newCostPrice = $totalValueChange / $totalBaseQtyChange;
                 }
            } else { // Hoàn tác
                $oldStockForCalc = $totalStockAllBranches + $totalBaseQtyChange;
                $oldTotalValue = $oldStockForCalc * $oldCostPrice;
                $newCostPrice = ($totalStockAllBranches > 0) ? (($oldTotalValue - $totalValueChange) / $totalStockAllBranches) : 0;
            }
            
            $product->cost_price = max(0, round($newCostPrice, 2));
            $product->save();
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
