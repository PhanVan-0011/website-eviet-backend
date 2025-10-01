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
     * Tạo mới một hóa đơn nhập hàng và cập nhật tồn kho.
     */
    public function createInvoice(array $data): PurchaseInvoice
    {
        try {
            return DB::transaction(function () use ($data) {
                
                //Tính toán lại tổng tiền và công nợ (SỬA LỖI TÍNH TOÁN)
                $calculatedData = $this->calculateInvoiceTotals($data);

                //Tạo hóa đơn chính
                $invoice = PurchaseInvoice::create($calculatedData);

                //Lưu chi tiết hóa đơn và cập nhật tồn kho
                $this->syncDetailsAndStocks($invoice, $calculatedData['details']); 

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
                // Lấy hóa đơn cơ bản
                $invoice = PurchaseInvoice::findOrFail($id); 
                
                // --- ĐÃ CHỈNH SỬA ---
                
                // 1. Kiểm tra xem có details mới được gửi không
                $hasNewDetails = isset($data['details']) && is_array($data['details']);

                // 2. Lấy phiên bản FRESH (để tính toán) và load details (nếu cần cho reverse)
                $freshInvoiceForCalculation = $invoice->fresh(); 

                if (!$hasNewDetails) {
                    // Load details cũ để hoàn tác/giữ nguyên khi không có details mới
                    $invoice->load('details');
                }
                
                $calculatedData = $this->calculateInvoiceTotals($data, $freshInvoiceForCalculation);

                // 3. Nếu có details mới, ta cần hoàn tác tồn kho cũ
                if ($hasNewDetails) {
                    $this->reverseDetailsAndStocks($invoice); 
                }
                
                // Cập nhật hóa đơn chính 
                $invoice->update($calculatedData);

                // 4. Nếu có details mới, xóa chi tiết cũ và đồng bộ chi tiết mới, cập nhật tồn kho
                if ($hasNewDetails) {
                    $invoice->details()->delete();
                    $this->syncDetailsAndStocks($invoice, $calculatedData['details']);
                }
                // --- KẾT THÚC CHỈNH SỬA ---

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

    protected function calculateInvoiceTotals(array $data, ?PurchaseInvoice $existingInvoice = null): array
    {
        $paid = (float)($data['paid_amount'] ?? ($existingInvoice->paid_amount ?? 0));
        $hasNewDetails = isset($data['details']) && is_array($data['details']);
        $hasNewDiscount = isset($data['discount_amount']);

        // ==============================================================================
        // FIX: XỬ LÝ KHI CHỈ CẬP NHẬT PAID_AMOUNT (BẢO TOÀN CÁC TỔNG TIỀN CŨ)
        if ($existingInvoice && !$hasNewDetails && !$hasNewDiscount) {
            
            // 1. UY TIÊN GIÁ TRỊ TỔNG TIỀN TỪ REQUEST (đã được Request merge giá trị cũ)
            $totalAmount = (float)($data['total_amount'] ?? 0.00);
            
            // Lấy các giá trị tổng tiền còn lại (nếu có trong $data)
            $data['subtotal_amount'] = (float)($data['subtotal_amount'] ?? 0.00);
            $data['discount_amount'] = (float)($data['discount_amount'] ?? 0.00);
            $data['total_quantity'] = (float)($data['total_quantity'] ?? 0);
            $data['total_items'] = (int)($data['total_items'] ?? 0);
            
            
            if ($totalAmount === 0.00 && $existingInvoice->id) {
                // 2. QUERY TRỰC TIẾP DB BẰNG DB::table NẾU REQUEST KHÔNG CÓ GIÁ TRỊ TỔNG TIỀN
                // Thao tác này buộc phải đọc từ DB, tránh lỗi cache model trong transaction.
                $db_totals = DB::table('purchase_invoices')
                            ->select('subtotal_amount', 'discount_amount', 'total_amount', 'total_quantity', 'total_items')
                            ->where('id', $existingInvoice->id)
                            ->first();

                if ($db_totals) {
                    // ĐẶT CÁC GIÁ TRỊ CHÍNH XÁC TỪ DB VÀO $data
                    $totalAmount = (float) $db_totals->total_amount;
                    $data['subtotal_amount'] = (float) $db_totals->subtotal_amount;
                    $data['discount_amount'] = (float) $db_totals->discount_amount;
                    $data['total_quantity'] = (float) $db_totals->total_quantity;
                    $data['total_items'] = (int) $db_totals->total_items;
                } else {
                    // Fallback cuối cùng: dùng model bị cache
                    $totalAmount = (float) $existingInvoice->total_amount;
                    $data['subtotal_amount'] = (float) $existingInvoice->subtotal_amount;
                    $data['discount_amount'] = (float) $existingInvoice->discount_amount;
                    $data['total_quantity'] = (float) $existingInvoice->total_quantity;
                    $data['total_items'] = (int) $existingInvoice->total_items;
                }
            }
            
            // Đặt Total Amount cuối cùng (49000.00)
            $data['total_amount'] = $totalAmount;
            
            // Tính Công nợ MỚI dựa trên Total Amount chính xác
            $data['amount_owed'] = max(0, $totalAmount - $paid); 
            
            // Loại bỏ trường details 
            unset($data['details']); 
            
            return $data; // Dùng early return để kết thúc hàm tại đây.
        }
        // ==============================================================================
        
        // --- LOGIC TÍNH TOÁN LẠI (Chạy khi có details hoặc discount_amount mới) ---

        $details = $data['details'] ?? ($existingInvoice->details->toArray() ?? []);
        
        $netSubtotal = 0; 
        $grossSubtotal = 0; 
        $totalItemDiscount = 0; 
        $totalQuantity = 0;
        $totalItems = 0;
        
        // Chiết khấu HĐ gốc (Lấy từ trường đã merge trong Request)
        // LƯU Ý: $data['discount_amount'] / $data['invoice_discount_only'] giờ là CK Header
        $invoiceDiscountOnly = (float)($data['invoice_discount_only'] ?? ($data['discount_amount'] ?? 0)); 
        

        // Nếu có details mới được gửi hoặc có details cũ (khi cập nhật), ta tính toán
        if (!empty($details)) { 
            foreach ($details as &$detail) { 
                $detail = is_array($detail) ? $detail : $detail->toArray();
                
                // Tính Gross Line Total
                $grossLineTotal = (float)($detail['quantity'] * $detail['unit_price']);
                $itemDiscount = (float)($detail['item_discount'] ?? 0);

                // Cộng dồn Gross Subtotal và Total Item Discount
                $grossSubtotal += $grossLineTotal; 
                $totalItemDiscount += $itemDiscount;
                
                // Tính Net Line Total cho chi tiết 
                $detail['subtotal'] = max(0, $grossLineTotal - $itemDiscount); 
                $netSubtotal += $detail['subtotal']; 
                
                $totalQuantity += $detail['quantity'] ?? 0;
                $totalItems++;
            }
        }
        
        // Tổng chiết khấu TOÀN BỘ (Item + Header)
        $totalDiscountAll = $totalItemDiscount + $invoiceDiscountOnly;

        // TÍNH TỔNG TIỀN CUỐI CÙNG: Gross Subtotal - Total Discount TOÀN BỘ
        $totalAmount = max(0, $grossSubtotal - $totalDiscountAll);

        // Gán các giá trị đã tính toán vào mảng $data
        $data['subtotal_amount'] = max(0, $netSubtotal);
        // $data['discount_amount'] đã được set là CK Header trong Request, KHÔNG GHI ĐÈ
        
        $data['total_quantity'] = $totalQuantity;
        $data['total_items'] = $totalItems;
        $data['total_amount'] = $totalAmount;
        
        // Tính Công nợ
        $data['amount_owed'] = max(0, $totalAmount - $paid); 
        
        // Loại bỏ trường trung gian invoice_discount_only trước khi lưu vào Model
        unset($data['invoice_discount_only']); 
        
        // Gán lại details đã được tính toán (có thêm 'subtotal')
        $data['details'] = $details; 

        return $data;
    }


    /**
     * Đồng bộ chi tiết hóa đơn và cập nhật tồn kho.
     */
    protected function syncDetailsAndStocks(PurchaseInvoice $invoice, array $details): void
    {
        $branchId = $invoice->branch_id;

        foreach ($details as $detail) {
            $subtotal = $detail['subtotal'] ?? 0; 

            // Tạo chi tiết hóa đơn
            $invoice->details()->create([
                'product_id' => $detail['product_id'],
                'quantity' => $detail['quantity'],
                'unit_price' => $detail['unit_price'],
                'subtotal' => $subtotal, // Lưu giá trị đã tính
                'unit_of_measure' => $detail['unit_of_measure'],
                'item_discount' => $detail['item_discount'] ?? 0,
            ]);

            //Cập nhật tồn kho (Thêm số lượng)
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