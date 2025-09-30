<?php

namespace App\Services;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use Exception;
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

            // --- BỘ LỌC ---
            
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
            //Lọc theo Ngày tạo
            if (!empty($request->input('start_date'))) {
                $query->whereDate('created_at', '>=', $request->input('start_date'));
            }
            if (!empty($request->input('end_date'))) {
                $query->whereDate('created_at', '<=', $request->input('end_date'));
            }
            //Lọc theo Mã hóa đơn
            if ($request->has('keyword')) {
                $query->where('invoice_code', 'like', "%{$request->keyword}%");
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
     * Tạo mới một hóa đơn nhập hàng và cập nhật tồn kho.
     */
    public function createInvoice(array $data): PurchaseInvoice
    {
        try {
            return DB::transaction(function () use ($data) {
                
                //Tính toán lại tổng tiền và công nợ
                $calculatedData = $this->calculateInvoiceTotals($data);

                //Tạo hóa đơn chính
                $invoice = PurchaseInvoice::create($calculatedData);

                //Lưu chi tiết hóa đơn và cập nhật tồn kho
                $this->syncDetailsAndStocks($invoice, $data['details']);

                //Trả về hóa đơn đã tạo (Observer tự động cập nhật công nợ NCC)
                return $invoice->load(['supplier', 'branch', 'user', 'details.product']);
            });
        } catch (Exception $e) {
            Log::error('Lỗi khi tạo hóa đơn nhập: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cập nhật hóa đơn và các chi tiết liên quan.
     */
    public function updateInvoice(string $id, array $data): PurchaseInvoice
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $invoice = $this->getInvoiceById($id);
                
                //Hoàn tác tác động cũ lên tồn kho và công nợ chuẩn bị cho việc cập nhật
                $this->reverseDetailsAndStocks($invoice);
                
                //Tính toán lại tổng tiền
                $calculatedData = $this->calculateInvoiceTotals($data, $invoice);

                //Cập nhật hóa đơn chính
                $invoice->update($calculatedData);

                // Xóa chi tiết cũ và đồng bộ chi tiết mới, cập nhật tồn kho
                $invoice->details()->delete();
                if (isset($data['details'])) {
                    $this->syncDetailsAndStocks($invoice, $data['details']);
                }

                return $invoice->refresh()->load(['supplier', 'branch', 'user', 'details.product']);
            });
        } catch (Exception $e) {
            Log::error("Lỗi khi cập nhật hóa đơn nhập (ID: {$id}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa một hóa đơn nhập hàng và hoàn tác tồn kho/công nợ.
     */
    public function deleteInvoice(string $id): bool
    {
        try {
            return DB::transaction(function () use ($id) {
                $invoice = $this->getInvoiceById($id);

                $this->reverseDetailsAndStocks($invoice); 
                
                return $invoice->delete();
            });
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa hóa đơn nhập (ID: {$id}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa nhiều hóa đơn nhập hàng.
     */
    public function multiDelete(array $ids): int
    {
        try {
            $deletedCount = 0;
            return DB::transaction(function () use ($ids, &$deletedCount) {
                $invoices = PurchaseInvoice::whereIn('id', $ids)->get();

                foreach ($invoices as $invoice) {
                    // Sử dụng deleteInvoice để đảm bảo hoàn tác transaction và tồn kho
                    $this->deleteInvoice($invoice->id); 
                    $deletedCount++;
                }
                return $deletedCount;
            });
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa nhiều hóa đơn nhập: ' . $e->getMessage());
            throw $e;
        }
    }

      /**
     * Tính toán tổng tiền hóa đơn.
     */
    protected function calculateInvoiceTotals(array $data, ?PurchaseInvoice $existingInvoice = null): array
    {
        $subtotal = 0;
        $totalQuantity = 0;
        $totalItems = 0;
        $discount = $data['discount_amount'] ?? 0;
        $paid = $data['paid_amount'] ?? 0;

        foreach ($data['details'] as $detail) {
             // Lấy giá trị đã tính trong FormRequest
            $itemSubtotal = ($detail['quantity'] * $detail['unit_price']) - ($detail['item_discount'] ?? 0);
            
            $subtotal += $itemSubtotal;
            $totalQuantity += $detail['quantity'] ?? 0;
            $totalItems++;
        }

        $totalAmount = $subtotal - $discount;
        
        // Gán các giá trị đã tính toán
        $data['subtotal_amount'] = max(0, $subtotal);
        $data['total_quantity'] = $totalQuantity;
        $data['total_items'] = $totalItems;
        $data['total_amount'] = max(0, $totalAmount);
        
        return $data;
    }

    /**
     * Đồng bộ chi tiết hóa đơn và cập nhật tồn kho.
     */
    protected function syncDetailsAndStocks(PurchaseInvoice $invoice, array $details): void
    {
        $branchId = $invoice->branch_id;

        foreach ($details as $detail) {
            // Subtotal đã được tính (cả item_discount)
            $subtotal = ($detail['quantity'] * $detail['unit_price']) - ($detail['item_discount'] ?? 0);

            // 1. Tạo chi tiết hóa đơn
            $invoice->details()->create([
                'product_id' => $detail['product_id'],
                'quantity' => $detail['quantity'],
                'unit_price' => $detail['unit_price'],
                'subtotal' => $subtotal,
                // --- Lưu 2 cột mới ---
                'unit_of_measure' => $detail['unit_of_measure'],
                'item_discount' => $detail['item_discount'] ?? 0,
            ]);

            // 2. Cập nhật tồn kho (Thêm số lượng)
            BranchProductStock::updateOrCreate(
                ['branch_id' => $branchId, 'product_id' => $detail['product_id']],
                [
                    // Tăng số lượng tồn kho
                    'quantity' => DB::raw('quantity + ' . $detail['quantity'])
                ]
            );
        }
    }

    /**
     * Hoàn tác tồn kho và xóa chi tiết hóa đơn cũ.
     */
    protected function reverseDetailsAndStocks(PurchaseInvoice $invoice): void
    {
        // Hoàn tác tồn kho chỉ khi hóa đơn không phải là 'cancelled'
        if ($invoice->status !== 'cancelled') {
            $branchId = $invoice->branch_id;
            
            foreach ($invoice->details as $detail) {
                // Giảm số lượng tồn kho
                BranchProductStock::where('branch_id', $branchId)
                    ->where('product_id', $detail->product_id)
                    ->update([
                        'quantity' => DB::raw('GREATEST(0, quantity - ' . $detail->quantity . ')') // Đảm bảo không âm
                    ]);
            }
        }
        
        // Xóa tất cả chi tiết hóa đơn
        $invoice->details()->delete();
    }
}