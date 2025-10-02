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
                
                //Tính toán lại tổng tiền và công nợ
                $calculatedData = $this->calculateInvoiceTotals($data);

                //Tạo hóa đơn chính
                $invoice = PurchaseInvoice::create($calculatedData);
                
                //LƯU chi tiết hóa đơn (Dù là draft hay received)
                $this->syncDetails($invoice, $calculatedData['details']);

                if ($invoice->status === 'received') {
                    $this->updateStock($invoice, $calculatedData['details'], 1);
                }

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
    /**
     * Cập nhật hóa đơn và các chi tiết liên quan.
     */
    public function updateInvoice(string $id, array $data): PurchaseInvoice
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $invoice = PurchaseInvoice::with('details')->findOrFail($id);
                $oldStatus = $invoice->status;
                $oldDetails = $invoice->details->toArray(); // LƯU LẠI DETAILS CŨ TRƯỚC KHI TÍNH TOÁN
                
                $hasNewDetails = isset($data['details']) && is_array($data['details']);
                $newStatus = $data['status'] ?? $oldStatus;
                
                // Tính toán lại tổng tiền và công nợ. $calculatedData sẽ chứa chi tiết mới nếu có.
                $calculatedData = $this->calculateInvoiceTotals($data, $invoice->fresh());

                // 1. HOÀN TÁC TỒN KHO CŨ nếu chi tiết thay đổi HOẶC status chuyển từ received đi
                if ($oldStatus === 'received' && ($hasNewDetails || $newStatus !== 'received')) {
                    // Dùng chi tiết CŨ đã lưu ở trên để đảo ngược tồn kho
                    $this->updateStock($invoice, $oldDetails, -1); // -1: Giảm tồn
                }

                // Cập nhật hóa đơn chính 
                $invoice->update($calculatedData);
                
                // 2. CẬP NHẬT DETAILS MỚI (Nếu có)
                if ($hasNewDetails) {
                    // XÓA chi tiết cũ bằng truy vấn trực tiếp trước khi thêm chi tiết mới
                    PurchaseInvoiceDetail::where('invoice_id', $invoice->id)->delete();
                    $this->syncDetails($invoice, $calculatedData['details']);
                    
                    // FIX MỚI: Tải lại chi tiết MỚI đã được lưu vào DB
                    $invoice->load('details'); 
                }
                
                // 3. XỬ LÝ TỒN KHO MỚI
                if ($newStatus === 'received' && $oldStatus !== 'received') {
                    // FIX: LUÔN DÙNG $invoice->details (đã được load mới nhất)
                    $detailsToUse = $invoice->details->toArray();

                    $this->updateStock($invoice, $detailsToUse, 1);
                } 
                
                return $invoice->refresh()->load(['supplier', 'branch', 'user', 'details.product']);
            });
        } catch (Exception $e) {
            Log::error("Lỗi khi cập nhật hóa đơn nhập (ID: {$id}): " . $e->getMessage());
            throw $e;
        }
    }


   protected function calculateInvoiceTotals(array $data, ?PurchaseInvoice $existingInvoice = null): array
    {
        $paid = (float)($data['paid_amount'] ?? ($existingInvoice->paid_amount ?? 0));
        $hasNewDetails = isset($data['details']) && is_array($data['details']);
        $hasNewDiscount = isset($data['discount_amount']);

        if ($existingInvoice && !$hasNewDetails && !$hasNewDiscount) {
            
            $totalAmount = (float)($data['total_amount'] ?? 0.00);
            
            // Lấy các giá trị tổng tiền còn lại
            $data['subtotal_amount'] = (float)($data['subtotal_amount'] ?? 0.00);
            $data['discount_amount'] = (float)($data['discount_amount'] ?? 0.00);
            $data['total_quantity'] = (float)($data['total_quantity'] ?? 0);
            $data['total_items'] = (int)($data['total_items'] ?? 0);
            
            
            if ($totalAmount === 0.00 && $existingInvoice->id) {
                // QUERY TRỰC TIẾP DB BẰNG DB::table NẾU REQUEST KHÔNG CÓ GIÁ TRỊ TỔNG TIỀN
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
            
            // Đặt Total Amount cuối cùng (đã được FIX)
            $data['total_amount'] = $totalAmount;
            
            // Tính Công nợ MỚI dựa trên Total Amount chính xác
            $data['amount_owed'] = max(0, $totalAmount - $paid); 
            
            // Loại bỏ trường details 
            unset($data['details']); 
            
            return $data; // Dùng early return để kết thúc hàm tại đây.
        }
        

        $details = $data['details'] ?? ($existingInvoice->details->toArray() ?? []);
        
        $netSubtotal = 0; 
        $grossSubtotal = 0; 
        $totalItemDiscount = 0; 
        $totalQuantity = 0;
        $totalItems = 0;
        
        // Chiết khấu HĐ gốc (Lấy từ trường đã merge trong Request)
        $invoiceDiscountOnly = (float)($data['invoice_discount_only'] ?? ($data['discount_amount'] ?? 0)); 
        

        // Nếu có details mới được gửi hoặc có details cũ (khi cập nhật), ta tính toán
        if (!empty($details)) { 
            foreach ($details as $index => $detail) { // **Sử dụng $index và gán lại $details[$index]**
                $detail = is_array($detail) ? $detail : $detail->toArray();
                
                // Tính Gross Line Total
                $grossLineTotal = (float)($detail['quantity'] * $detail['unit_price']);
                $itemDiscount = (float)($detail['item_discount'] ?? 0);

                //GIỚI HẠN CHIẾT KHẤU MẶT HÀNG KHÔNG VƯỢT QUÁ GIÁ TRỊ DÒNG
                $adjustedItemDiscount = round(min($itemDiscount, $grossLineTotal), 2);
                
                // BẮT BUỘC CẬP NHẬT GIÁ TRỊ ĐÃ GIỚI HẠN VÀO MẢNG $details CHÍNH
                $details[$index]['item_discount'] = $adjustedItemDiscount; 

                // Cộng dồn Gross Subtotal và Total Item Discount đã điều chỉnh
                $grossSubtotal += $grossLineTotal; 
                $totalItemDiscount += $adjustedItemDiscount; // Dùng giá trị đã điều chỉnh
                
                // Tính Net Line Total cho chi tiết 
                $details[$index]['subtotal'] = max(0, round($grossLineTotal - $adjustedItemDiscount, 2)); 
                $netSubtotal += $details[$index]['subtotal']; 
                
                $totalQuantity += $detail['quantity'] ?? 0;
                $totalItems++;
            }
        }
        
        // CÁC BƯỚC TÍNH TOÁN CUỐI CÙNG (Đã tối ưu logic và FIX lỗi giới hạn CK Header)

        $netSubtotal = round($netSubtotal, 2); 
        
        //Net Subtotal (Đã trừ CK Item)
        $data['subtotal_amount'] = max(0, $netSubtotal);

        //GIỚI HẠN CK HEADER KHÔNG VƯỢT QUÁ NET SUBTOTAL
        $adjustedInvoiceDiscount = round(min(max(0, $invoiceDiscountOnly), $netSubtotal), 2);
        
        //Cập nhật lại discount_amount của Hóa đơn (Chỉ lưu CK Header đã giới hạn)
        $data['discount_amount'] = $adjustedInvoiceDiscount; 
        
        //Total Amount CUỐI CÙNG: Net Subtotal - CK Header đã giới hạn
        $totalAmount = max(0, $netSubtotal - $adjustedInvoiceDiscount);

        //Gán các giá trị đã tính toán vào mảng $data
        $data['total_quantity'] = $totalQuantity;
        $data['total_items'] = $totalItems;
        $data['total_amount'] = round($totalAmount, 2);
        
        //Tính Công nợ
        $data['amount_owed'] = max(0, round($data['total_amount'] - $paid, 2)); 
        
        //Loại bỏ trường trung gian invoice_discount_only trước khi lưu vào Model
        unset($data['invoice_discount_only']); 
        
        //Gán lại details đã được tính toán (có thêm 'subtotal' và 'item_discount' đã giới hạn)
        $data['details'] = $details; 

        return $data;
    }

    /**
     *Tái tính toán tổng tiền và công nợ của Nhà cung cấp 
     */
    public function recalculateSupplierTotals(int $supplierId): void
    {
        $supplier = Supplier::lockForUpdate()->find($supplierId);

        if (!$supplier) {
            return;
        }

        // Tính tổng Total Amount và tổng Amount Owed từ TẤT CẢ hóa đơn có trạng thái 'received'
        $totals = PurchaseInvoice::where('supplier_id', $supplierId)
            ->where('status', 'received')
            ->selectRaw('ROUND(SUM(total_amount), 2) as sum_total_amount, ROUND(SUM(amount_owed), 2) as sum_amount_owed')
            ->first();

        $sumTotalAmount = (float) ($totals->sum_total_amount ?? 0.00);
        $sumAmountOwed = (float) ($totals->sum_amount_owed ?? 0.00);

        // GÁN GIÁ TRỊ TỔNG HỢP VÀO NHÀ CUNG CẤP
        $supplier->total_purchase_amount = $sumTotalAmount;
        $supplier->balance_due = $sumAmountOwed;
        $supplier->save();
    }


    /**
     * đồng bộ chi tiết hóa đơn vào DB, không cập nhật tồn kho.
     */
    protected function syncDetails(PurchaseInvoice $invoice, array $details): void
    {
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
        }
    }
    
    /**
     *Cập nhật tồn kho (Thêm hoặc Trừ)
     * @param int $direction 1: Tăng (+), -1: Giảm (-)
     */
     protected function updateStock(PurchaseInvoice $invoice, array $details, int $direction): void
    {
        $branchId = $invoice->branch_id;
        $sign = $direction === 1 ? '+' : '-';

        foreach ($details as $detail) {
            $quantity = $detail['quantity'];
            
            // Fix: Đảm bảo sử dụng DB::raw() với logic cộng dồn an toàn
            BranchProductStock::updateOrCreate(
                ['branch_id' => $branchId, 'product_id' => $detail['product_id']],
                [
                    // Cập nhật quantity bằng cách cộng/trừ số lượng
                    'quantity' => DB::raw("GREATEST(0, quantity {$sign} {$quantity})")
                ]
            );
        }
    }

    /**
     * Hoàn tác tồn kho và xóa chi tiết hóa đơn cũ.
     */
    protected function reverseDetailsAndStocks(PurchaseInvoice $invoice): void
    {
        // Hoàn tác tồn kho chỉ khi hóa đơn ở trạng thái 'received'
        if ($invoice->status === 'received') {
            $details = $invoice->details->toArray();
            $this->updateStock($invoice, $details, -1); // -1: Giảm tồn
        }
    }

    /**
     * Xóa một hóa đơn nhập hàng và hoàn tác tồn kho/công nợ.
     */
    public function deleteInvoice(string $id): bool
    {
        try {
            return DB::transaction(function () use ($id) {
                $invoice = PurchaseInvoice::with('details')->findOrFail($id); // Load details để hoàn tác

                $this->reverseDetailsAndStocks($invoice); 
                
                //Xóa chi tiết bằng truy vấn trực tiếp trước khi xóa hóa đơn cha
                PurchaseInvoiceDetail::where('invoice_id', $invoice->id)->delete();
                
                return $invoice->delete();
            });
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa hóa đơn nhập: ' . $e->getMessage());
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
                // Tải hóa đơn với details để đảm bảo hoàn tác tồn kho
                $invoices = PurchaseInvoice::whereIn('id', $ids)->with('details')->get();
                
                foreach ($invoices as $invoice) {
                    $this->reverseDetailsAndStocks($invoice); 
                    
                    // FIX LỖI: Xóa chi tiết bằng truy vấn trực tiếp
                    PurchaseInvoiceDetail::where('invoice_id', $invoice->id)->delete();
                    
                    $result = $invoice->delete();
                    if ($result) {
                        $deletedCount++;
                    }
                }
                return $deletedCount;
            });
        } catch (Exception $e) {
            Log::error('Lỗi khi xóa nhiều hóa đơn nhập: ' . $e->getMessage());
            throw $e;
        }
    }
}