<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Combo;
use App\Models\BranchProductStock;
use App\Models\AttributeValue;
use App\Models\ItemTimeSlot;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderService
{
    /**
     * Lấy danh sách đơn hàng (Filter Admin & Phân trang thủ công)
     */
    public function getAllOrders($request)
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('limit', 25)));
            $currentPage = max(1, (int) $request->input('page', 1));

            $query = Order::query()
                ->with(['user', 'orderDetails.product', 'orderDetails.combo', 'payment.method', 'branch', 'pickupLocation']);

            // --- FILTERING ---
            
            // Tìm kiếm tổng quát
            if ($request->filled('keyword')) {
                $keyword = $request->input('keyword');
                $query->where(function ($q) use ($keyword) {
                    $q->where('order_code', 'like', "%{$keyword}%")
                        ->orWhere('client_name', 'like', "%{$keyword}%")
                        ->orWhere('client_phone', 'like', "%{$keyword}%")
                        ->orWhereHas('user', function ($uq) use ($keyword) {
                            $uq->where('email', 'like', "%{$keyword}%");
                        });
                });
            }

            // Các bộ lọc chính xác
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->input('branch_id'));
            }
            if ($request->filled('order_method')) {
                $query->where('order_method', $request->input('order_method'));
            }
            
            // Lọc theo User (Dành cho Client App)
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->input('user_id'));
            }

            // Lọc theo ngày
            if ($request->filled('start_date')) {
                $query->whereDate('order_date', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('order_date', '<=', $request->input('end_date'));
            }

            // Sắp xếp: Ưu tiên đơn cần xử lý lên đầu
            $query->orderByRaw("FIELD(status, 'draft', 'pending', 'processing', 'delivered', 'cancelled')")
                ->orderByDesc('order_date');

            // 3. Phân trang thủ công
            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            $orders = $query->skip($offset)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);
            
            return [
                'data' => $orders,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $currentPage < $lastPage ? $currentPage + 1 : null,
                'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
            ];

        } catch (\Exception $e) {
            Log::error('OrderService::getAllOrders Error: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     *   Lấy thông tin chi tiết của một đơn hàng theo ID
     */
    public function getOrderById(int $id)
    {
        return Order::with([
            'orderDetails.combo',
            'orderDetails.product.images',
            'payment.method',
            'user',
            'branch',
            'pickupLocation'
        ])->findOrFail($id);
    }
    /**
     * TẠO ĐƠN HÀNG MỚI (Logic hoàn chỉnh: Lock kho, Combo, Topping)
     */
    public function createOrder(array $data, $currentUser = null, bool $isAdminCreated = false)
    {
        return DB::transaction(function () use ($data, $currentUser, $isAdminCreated) {
            $branchId = $data['branch_id'];
            $status = 'pending'; 

            $orderDetailsPayload = [];
            $totalAmount = 0; // Tiền hàng (chưa ship)

            // 1. XỬ LÝ ITEMS
            foreach ($data['items'] as $item) {
                if ($item['type'] === 'product') {
                    $res = $this->processProductItem($item, $branchId);
                } else {
                    $res = $this->processComboItem($item, $branchId);
                }
                
                // Merge kết quả vào mảng payload
                $detailsToAdd = isset($res['details']) ? $res['details'] : [$res['detail']];
                $orderDetailsPayload = array_merge($orderDetailsPayload, $detailsToAdd);
                $totalAmount += $res['subtotal'];
            }

            // 2. TÍNH TỔNG TIỀN
            $shippingFee = ($data['order_method'] === 'delivery') ? (float)($data['shipping_fee'] ?? 0) : 0;
            $grandTotal = max(0, $totalAmount + $shippingFee);

            // 3. XÁC ĐỊNH USER (Nullable)
            $userId = null; 
            if ($isAdminCreated) {
                // Admin tạo: Nếu chọn khách -> lấy ID
                if (!empty($data['user_id'])) {
                    $userId = $data['user_id'];
                }
            } else {
                // Khách tự tạo
                if ($currentUser) {
                    $userId = $currentUser->id;
                }
            }

            // 4. GIAO NHẬN
            $pickupTime = null;
            $pickupLocationId = null;
            $shippingAddress = null;

            if ($data['order_method'] === 'takeaway') {
                $pickupTime = $data['pickup_time'] ?? Carbon::now()->addMinutes(15);
                $pickupLocationId = $data['pickup_location_id'] ?? null;
            } elseif ($data['order_method'] === 'delivery') {
                $shippingAddress = $data['shipping_address'];
            }

            // 5. TẠO ORDER
            $order = Order::create([
                'order_code' => $this->generateOrderCode(),
                'user_id' => $userId, 
                'branch_id' => $branchId,
                'order_method' => $data['order_method'],
                'status' => $status,
                
                'client_name' => $data['client_name'],
                'client_phone' => $data['client_phone'],
                'notes' => $data['notes'] ?? null,
                
                'shipping_address' => $shippingAddress,
                'pickup_time' => $pickupTime,
                'pickup_location_id' => $pickupLocationId,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'grand_total' => $grandTotal,
                'order_date' => now(),
            ]);

            // 6. LƯU CHI TIẾT
            $order->orderDetails()->createMany($orderDetailsPayload);

            // 7. TẠO THANH TOÁN
            $this->createPayment($order, $data['payment_method_code'], $grandTotal);

            return $order->load(['orderDetails', 'payment.method']);
        });
    }

    /**
     * CẬP NHẬT TRẠNG THÁI (KÈM LOGIC HOÀN KHO)
     */
    public function updateOrderStatus(Order $order, string $newStatus)
    {
        return DB::transaction(function () use ($order, $newStatus) {
            // Không cho sửa đơn đã hoàn tất
            if (in_array($order->status, ['delivered', 'cancelled'])) {
                throw new Exception("Đơn hàng đã hoàn tất hoặc hủy, không thể thay đổi trạng thái.");
            }

            $oldStatus = $order->status;
            $order->status = $newStatus;

            // NẾU HỦY ĐƠN -> HOÀN KHO
            if ($newStatus === 'cancelled') {
                // Chỉ hoàn kho khi trạng thái cũ chưa phải là cancelled (để tránh hoàn 2 lần)
                if ($oldStatus !== 'cancelled') {
                    $this->restockOrderItems($order);
                    $order->cancelled_at = now();
                }
            }
            
            // NẾU GIAO XONG -> CẬP NHẬT THANH TOÁN (Nếu chưa)
            if ($newStatus === 'delivered') {
                if ($order->payment && $order->payment->status !== 'success') {
                    $this->updatePaymentStatus($order, 'success', now());
                }
            }

            $order->save();
            return $order;
        });
    }

    /**
     * CẬP NHẬT THANH TOÁN
     */
    public function updatePaymentStatus(Order $order, string $status, $paidAt = null)
    {
        $payment = $order->payment;
        if (!$payment) throw new Exception("Đơn hàng chưa có thông tin thanh toán.");

        $payment->status = $status;
        if ($status === 'success') {
            $payment->paid_at = $paidAt ? Carbon::parse($paidAt) : now();
        } else {
            $payment->paid_at = null;
        }
        $payment->save();

        return $order->fresh();
    }
    
    /**
     * HỦY NHIỀU ĐƠN HÀNG (Soft Cancel)
     */
    public function cancelMultipleOrders(array $ids)
    {
        $success = 0;
        $failed = [];
        $orders = Order::whereIn('id', $ids)->get();

        foreach ($orders as $order) {
            try {
                $this->updateOrderStatus($order, 'cancelled');
                $success++;
            } catch (Exception $e) {
                $failed[] = ['id' => $order->id, 'order_code' => $order->order_code, 'reason' => $e->getMessage()];
            }
        }
        return ['success_count' => $success, 'failed_orders' => $failed];
    }


    /**
     * Hoàn kho khi hủy đơn (Sử dụng lockForUpdate)
     */
    private function restockOrderItems(Order $order)
    {
        foreach ($order->orderDetails as $detail) {
            // Chỉ hoàn kho nếu có product_id (Combo đã được tách item khi lưu)
            if ($detail->product_id) { 
                $stock = BranchProductStock::where('branch_id', $order->branch_id)
                    ->where('product_id', $detail->product_id)
                    ->lockForUpdate() // LOCK ROW
                    ->first();
                
                if ($stock) {
                    $stock->increment('quantity', $detail->quantity);
                }
            }
        }
    }

    /**
     * Xử lý Sản phẩm lẻ: Check Giờ, Kho, Tính Topping
     */
    private function processProductItem($itemData, $branchId) {
        $product = Product::findOrFail($itemData['id']);
        $quantity = $itemData['quantity'];

        // Check Giờ
        if (!$product->is_flexible_time) {
            $this->validateSellingTime($product->id, 'product');
        }

        // Check Kho (Có Lock)
        if ($product->is_sales_unit) {
            $stock = BranchProductStock::where('branch_id', $branchId)
                ->where('product_id', $product->id)
                ->lockForUpdate() // LOCK
                ->first();

            if (!$stock || $stock->quantity < $quantity) {
                throw new Exception("Sản phẩm '{$product->name}' không đủ hàng (Kho: " . ($stock->quantity ?? 0) . ").");
            }
            $stock->decrement('quantity', $quantity);
        }

        // Tính Giá (Ưu tiên giá App)
        $basePrice = ($product->base_app_price > 0) ? $product->base_app_price : $product->base_store_price;
        $unitPrice = $basePrice;
        $attributesSnapshot = [];

        if (!empty($itemData['attribute_value_ids'])) {
            $attributes = AttributeValue::with('productAttribute')
                ->whereIn('id', $itemData['attribute_value_ids'])->get();
            foreach ($attributes as $attr) {
                $unitPrice += $attr->price_adjustment;
                $attributesSnapshot[] = [
                    'name' => $attr->productAttribute->name, 
                    'value' => $attr->value, 
                    'price' => (float)$attr->price_adjustment
                ];
            }
        }

        return [
            'subtotal' => $unitPrice * $quantity, 
            'detail' => [
                'product_id' => $product->id, 
                'combo_id' => null, 
                'unit_of_measure' => $product->base_unit,
                'quantity' => $quantity, 
                'unit_price' => $unitPrice, 
                'subtotal' => $unitPrice * $quantity,
                'attributes_snapshot' => json_encode($attributesSnapshot, JSON_UNESCAPED_UNICODE),
            ]
        ];
    }

    /**
     * Xử lý Combo: Check Giờ, Chia giá, Trừ kho thành phần
     */
    private function processComboItem($itemData, $branchId) {
        $combo = Combo::with('items')->findOrFail($itemData['id']);
        $quantity = $itemData['quantity'];

        if (!$combo->is_active) throw new Exception("Combo ngừng hoạt động.");
        if (!$combo->is_flexible_time) $this->validateSellingTime($combo->id, 'combo');

        $details = [];
        $comboPrice = ($combo->base_app_price > 0) ? $combo->base_app_price : $combo->base_store_price;
        $totalSubtotal = $comboPrice * $quantity;
        
        // Chia giá combo cho món con theo tỷ lệ
        $originalTotalValue = $combo->items->sum(fn($i) => ($i->base_app_price ?: $i->base_store_price) * $i->pivot->quantity);
        $ratio = ($originalTotalValue > 0) ? ($comboPrice / $originalTotalValue) : 0;
        $distributed = 0;

        foreach ($combo->items as $index => $cItem) {
            // Trừ kho món con (Có Lock)
            if ($cItem->is_sales_unit) {
                $need = $cItem->pivot->quantity * $quantity;
                $stock = BranchProductStock::where('branch_id', $branchId)
                    ->where('product_id', $cItem->id)
                    ->lockForUpdate() // LOCK ROW
                    ->first();

                if (!$stock || $stock->quantity < $need) {
                    throw new Exception("Món '{$cItem->name}' trong combo hết hàng.");
                }
                $stock->decrement('quantity', $need);
            }
            
            // Tính giá đã chia
            $itemTotalQty = $cItem->pivot->quantity * $quantity;
            $base = $cItem->base_app_price ?: $cItem->base_store_price;
            
            if ($index < $combo->items->count() - 1) {
                $lineTotal = round($base * $ratio * $itemTotalQty);
                $distributed += $lineTotal;
                $uPrice = ($itemTotalQty > 0) ? $lineTotal / $itemTotalQty : 0;
            } else {
                $lineTotal = $totalSubtotal - $distributed;
                $uPrice = ($itemTotalQty > 0) ? $lineTotal / $itemTotalQty : 0;
            }
            
            $details[] = [
                'product_id' => $cItem->id, 
                'combo_id' => $combo->id, 
                'unit_of_measure' => $cItem->base_unit,
                'quantity' => $itemTotalQty, 
                'unit_price' => $uPrice, 
                'subtotal' => $lineTotal, 
                'attributes_snapshot' => null // Combo không có topping lẻ ở bước này
            ];
        }
        return ['subtotal' => $totalSubtotal, 'details' => $details];
    }

    private function validateSellingTime($itemId, $type) {
        $now = Carbon::now()->format('H:i:s');
        $query = ItemTimeSlot::join('order_time_slots', 'item_time_slots.time_slot_id', '=', 'order_time_slots.id')
            ->where('order_time_slots.is_active', 1)
            ->select('order_time_slots.start_time', 'order_time_slots.end_time');
        
        if ($type === 'product') {
            $query->where('item_time_slots.product_id', $itemId);
        } else {
            $query->where('item_time_slots.combo_id', $itemId);
        }

        $slots = $query->get();
        if ($slots->isEmpty()) return; // Không gán slot -> Bán cả ngày

        $can = false;
        foreach ($slots as $s) {
            if ($now >= $s->start_time && $now <= $s->end_time) {
                $can = true;
                break;
            }
        }
        if (!$can) throw new Exception(($type === 'product' ? 'Sản phẩm' : 'Combo') . " chưa đến giờ mở bán.");
    }

    private function createPayment($order, $code, $amount) {
        $method = PaymentMethod::where('code', $code)->firstOrFail();
        $order->payment()->create([
            'payment_method_id' => $method->id, 
            'status' => 'pending', 
            'amount' => $amount, 
            'paid_at' => null
        ]);
    }
private function generateOrderCode(): string
    {
        $prefix = 'DH'; 
        $lastOrder = Order::where('order_code', 'LIKE', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(order_code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->lockForUpdate() 
            ->first();

        $nextId = $lastOrder ? ((int)substr($lastOrder->order_code, strlen($prefix)) + 1) : 1;
        return $prefix . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
}
