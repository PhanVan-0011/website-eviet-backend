<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Combo;
use App\Models\BranchProductStock;
use App\Models\AttributeValue;
use App\Models\ItemTimeSlot;
use App\Models\PaymentMethod;
use App\Models\ProductUnitConversion;
use App\Models\PickupLocation;
use App\Models\OrderTimeSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class OrderService
{
    /* Lấy danh sách đơn hàng (Filter Admin & Phân trang thủ công)
     */
    public function getAllOrders($request)
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('limit', 25)));
            $currentPage = max(1, (int) $request->input('page', 1));

            $query = Order::query()
                ->with(['user', 'orderDetails.product', 'orderDetails.combo', 'payment.method', 'branch', 'pickupLocation', 'timeSlot']);

            // Apply branch filter (tự động theo role)
            \App\Services\BranchAccessService::applyBranchFilter($query);

            // --- FILTER ---
            if ($request->filled('keyword')) {
                $keyword = $request->input('keyword');
                $query->where(function ($q) use ($keyword) {
                    $q->where('order_code', 'like', "%{$keyword}%")
                        ->orWhere('client_name', 'like', "%{$keyword}%")
                        ->orWhere('client_phone', 'like', "%{$keyword}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
            // Nếu user chọn filter branch_id, chỉ áp dụng nếu user có quyền với branch đó
            if ($request->filled('branch_id')) {
                $branchId = $request->input('branch_id');
                if (\App\Services\BranchAccessService::hasAccessToBranch($branchId)) {
                    $query->where('branch_id', $branchId);
                }
            }
            if ($request->filled('order_method')) {
                $query->where('order_method', $request->input('order_method'));
            }
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->input('user_id'));
            }

            if ($request->filled('start_date')) {
                $query->whereDate('order_date', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('order_date', '<=', $request->input('end_date'));
            }

            // Sắp xếp: Mới nhất lên đầu
            $query->orderByDesc('order_date');

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
        } catch (Exception $e) {
            Log::error(__METHOD__ . ' Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy chi tiết đơn hàng
     */
    public function getOrderById(int $id)
    {
        try {
            return Order::with([
                'orderDetails.combo',
                'orderDetails.product.images',
                'payment.method',
                'user',
                'branch',
                'pickupLocation',
                'timeSlot'
            ])->findOrFail($id);
        } catch (Exception $e) {
            Log::error(__METHOD__ . " Error (ID: $id): " . $e->getMessage());
            throw $e;
        }
    }

 /**
     * TẠO ĐƠN HÀNG MỚI (Logic hoàn chỉnh)
     */
    public function createOrder(array $data, $currentUser = null, bool $isAdminCreated = false)
    {
        try {
            return DB::transaction(function () use ($data, $currentUser, $isAdminCreated) {
                $branchId = $data['branch_id'];
                $status = 'pending'; 

                // 1. Kiểm tra Khung giờ (Time Slot) có hợp lệ với Chi nhánh không
                $timeSlot = OrderTimeSlot::findOrFail($data['time_slot_id']);
                
                // Xác định thời gian tạo đơn
                $orderDate = !empty($data['order_date']) ? Carbon::parse($data['order_date']) : Carbon::now();

                // Check nếu chi nhánh có áp dụng slot này
                $isActiveInBranch = $timeSlot->branches()
                    ->where('branches.id', $branchId)
                    ->where('branch_time_slot_pivot.is_enabled', true)
                    ->exists();
                if (!$isActiveInBranch) {
                    throw new Exception("Ca bán hàng '{$timeSlot->name}' không áp dụng cho chi nhánh này.");
                }

                // 2. Validate Giờ Tạo Đơn với Ca
                $this->validateSlotAvailability($timeSlot, $branchId, $orderDate);

                // 3. VALIDATE ĐỊA ĐIỂM
                $pickupLocationId = null;
                if (in_array($data['order_method'], ['takeaway', 'delivery'])) {
                    if (empty($data['pickup_location_id'])) {
                        throw new Exception("Vui lòng chọn điểm nhận hàng.");
                    }
                    $exists = PickupLocation::where('id', $data['pickup_location_id'])
                        ->where('branch_id', $branchId)->exists();
                    if (!$exists) throw new Exception("Điểm nhận hàng không thuộc chi nhánh này.");
                    $pickupLocationId = $data['pickup_location_id'];
                }
                
                // --- XỬ LÝ PICKUP TIME ---
                $pickupStartTime = null;
                $pickupEndTime = null;

                // Ngày giao hàng sẽ là ngày của order_date (hôm nay hoặc tương lai)
                $deliveryDate = $orderDate->format('Y-m-d');

                if ($data['order_method'] === 'delivery') {
                    // Delivery: Lấy khung giờ giao của Ca
                    $pickupStartTime = $deliveryDate . ' ' . $timeSlot->delivery_start_time;
                    $pickupEndTime = $deliveryDate . ' ' . $timeSlot->delivery_end_time;
                    
                } else {
                    // Takeaway: 
                    // Mặc định lấy theo khung giờ giao của Ca
                    $pickupStartTime = $deliveryDate . ' ' . $timeSlot->delivery_start_time;
                    $pickupEndTime = $deliveryDate . ' ' . $timeSlot->delivery_end_time;
                    
                    // Nếu khách hẹn giờ cụ thể
                    if (!empty($data['pickup_time'])) {
                        $pickupDateTime = Carbon::parse($data['pickup_time']);
                        $reqTime = $pickupDateTime->format('H:i:s');
                        
                        // Validate giờ hẹn phải nằm trong khoảng delivery window của Ca
                        if ($reqTime < $timeSlot->delivery_start_time || $reqTime > $timeSlot->delivery_end_time) {
                            throw new Exception("Giờ hẹn lấy phải nằm trong khung giờ giao hàng: {$timeSlot->delivery_start_time} - {$timeSlot->delivery_end_time}");
                        }
                        
                        $pickupStartTime = $deliveryDate . ' ' . $reqTime;
                        $pickupEndTime = Carbon::parse($pickupStartTime)->addMinutes(15);
                    }
                }

                $orderDetailsPayload = [];
                $totalAmount = 0; 
                
                $priceType = $data['price_type'] ?? 'app'; 

                // 4. XỬ LÝ ITEMS (Tính toán & Trừ kho)
                foreach ($data['items'] as $item) {
                    if ($item['type'] === 'product') {
                         $product = Product::findOrFail($item['id']);
                         // [QUAN TRỌNG] Nếu sản phẩm KHÔNG linh hoạt -> Check xem nó có bán trong Ca này không
                         if (!$product->is_flexible_time) {
                             $this->validateSellingTime($item['id'], 'product', $timeSlot->id);
                         }
                        $res = $this->processProductItem($item, $branchId, $priceType);
                    } else {
                        $combo = Combo::with('items')->findOrFail($item['id']);
                         // [QUAN TRỌNG] Nếu combo KHÔNG linh hoạt -> Check ca
                         if (!$combo->is_flexible_time) {
                             $this->validateSellingTime($item['id'], 'combo', $timeSlot->id);
                         }
                        $res = $this->processComboItem($item, $branchId, $priceType);
                    }
                    
                    $detailsToAdd = isset($res['details']) ? $res['details'] : [$res['detail']];
                    $orderDetailsPayload = array_merge($orderDetailsPayload, $detailsToAdd);
                    $totalAmount += $res['subtotal'];
                }

                // 5. TÍNH TỔNG TIỀN
                $shippingFee = ($data['order_method'] === 'delivery') ? (float)($data['shipping_fee'] ?? 0) : 0;
                $orderDiscount = isset($data['discount_amount']) ? (float)$data['discount_amount'] : 0;
                
                $grandTotal = max(0, $totalAmount + $shippingFee - $orderDiscount);

                // 6. XÁC ĐỊNH USER
                $userId = null; 
                $clientName = $data['client_name'];
                $clientPhone = $data['client_phone'];

                if ($isAdminCreated) {
                    if (!empty($data['user_id'])) {
                        $userId = $data['user_id'];
                    } elseif (!empty($data['client_phone'])) {
                        $existingUser = User::where('phone', $data['client_phone'])->first();
                        if ($existingUser) {
                            $userId = $existingUser->id;
                            if (empty($clientName)) $clientName = $existingUser->name;
                        }
                    }
                } else {
                    if ($currentUser) $userId = $currentUser->id;
                }

                // 7. TẠO ORDER
                $order = Order::create([
                    'order_code' => $this->generateOrderCode(),
                    'user_id' => $userId, 
                    'branch_id' => $branchId,
                    'order_method' => $data['order_method'],
                    'status' => $status,
                    'price_type' => $priceType, 
                    
                    'client_name' => $clientName,
                    'client_phone' => $clientPhone,
                    'notes' => $data['notes'] ?? null,
                    
                    'pickup_location_id' => $pickupLocationId,
                    'time_slot_id' => $timeSlot->id, 
                    
                    'pickup_start_time' => $pickupStartTime,
                    'pickup_end_time' => $pickupEndTime,

                    'shipping_fee' => $shippingFee,
                    'total_amount' => $totalAmount,
                    'discount_amount' => $orderDiscount, 
                    'grand_total' => $grandTotal,
                    'order_date' => $orderDate, 
                ]);

                // 8. LƯU CHI TIẾT
                $order->orderDetails()->createMany($orderDetailsPayload);

                // 9. TẠO THANH TOÁN
                $this->createPayment($order, $data['payment_method_code'], $grandTotal);

                return $order->load(['orderDetails', 'payment.method', 'timeSlot', 'pickupLocation']);
            });
        } catch (Exception $e) {
            Log::error(__METHOD__ . ' Error: ' . $e->getMessage(), [
                'data' => Arr::except($data, ['items']), 
                'user_id' => $currentUser ? $currentUser->id : 'guest',
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * CẬP NHẬT ĐƠN HÀNG
     */
    public function updateOrder(Order $order, array $data, $currentUser = null)
    {
        try {
            return DB::transaction(function () use ($order, $data, $currentUser) {
                if (in_array($order->status, ['delivered', 'cancelled'])) {
                    throw new Exception("Đơn hàng đã hoàn tất hoặc hủy, không thể chỉnh sửa.");
                }

                $fillData = Arr::except($data, ['items', 'payment_method_code', 'branch_id', 'order_code']);
                $order->fill($fillData);

                if (isset($data['order_date'])) {
                    $order->order_date = Carbon::parse($data['order_date']);
                }
                
                if (isset($data['branch_id']) && $data['branch_id'] != $order->branch_id) {
                     $order->branch_id = $data['branch_id'];
                }

                if (isset($data['items'])) {
                    $this->restockOrderItems($order); 
                    $order->orderDetails()->delete();

                    $orderDetailsPayload = [];
                    $totalAmount = 0;
                    $branchId = $order->branch_id;
                    $priceType = $data['price_type'] ?? $order->price_type;
                    $timeSlotId = $order->time_slot_id; 

                    foreach ($data['items'] as $item) {
                         if ($item['type'] === 'product') {
                             $product = Product::findOrFail($item['id']);
                             if (!$product->is_flexible_time) {
                                 $this->validateSellingTime($item['id'], 'product', $timeSlotId);
                             }
                            $res = $this->processProductItem($item, $branchId, $priceType);
                        } else {
                            $combo = Combo::with('items')->findOrFail($item['id']);
                             if (!$combo->is_flexible_time) {
                                 $this->validateSellingTime($item['id'], 'combo', $timeSlotId);
                             }
                            $res = $this->processComboItem($item, $branchId, $priceType);
                        }
                        
                        $detailsToAdd = isset($res['details']) ? $res['details'] : [$res['detail']];
                        $orderDetailsPayload = array_merge($orderDetailsPayload, $detailsToAdd);
                        $totalAmount += $res['subtotal'];
                    }

                    $order->orderDetails()->createMany($orderDetailsPayload);
                    $order->total_amount = $totalAmount;
                    $order->price_type = $priceType;
                }

                $shippingFee = isset($data['shipping_fee']) ? (float)$data['shipping_fee'] : $order->shipping_fee;
                $discountAmount = isset($data['discount_amount']) ? (float)$data['discount_amount'] : $order->discount_amount;
                
                $order->grand_total = max(0, $order->total_amount + $shippingFee - $discountAmount);

                if (isset($data['payment_method_code'])) {
                    $method = PaymentMethod::where('code', $data['payment_method_code'])->first();
                    if ($method && $order->payment) {
                        $order->payment->update(['payment_method_id' => $method->id]);
                    }
                }
                
                if ($order->payment && $order->payment->status !== 'success') {
                     $order->payment->update(['amount' => $order->grand_total]);
                }

                $order->save();

                return $order->load(['orderDetails', 'payment.method', 'timeSlot', 'pickupLocation']);
            });
        } catch (Exception $e) {
            Log::error(__METHOD__ . ' Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }
    public function updateOrderStatus(Order $order, string $newStatus)
    {
        return DB::transaction(function () use ($order, $newStatus) {
            if (in_array($order->status, ['delivered', 'cancelled'])) {
                throw new Exception("Đơn hàng đã hoàn tất hoặc hủy, không thể thay đổi trạng thái.");
            }

            $oldStatus = $order->status;
            $order->status = $newStatus;

            // NẾU HỦY ĐƠN -> HOÀN KHO
            if ($newStatus === 'cancelled') {
                if ($oldStatus !== 'cancelled') {
                    $this->restockOrderItems($order);
                    $order->cancelled_at = now();
                }
            }
            
            // NẾU GIAO XONG -> CẬP NHẬT THANH TOÁN
            if ($newStatus === 'delivered') {
                if ($order->payment && $order->payment->status !== 'success') {
                    $this->updatePaymentStatus($order, 'success', now());
                }
            }

            $order->save();
            return $order;
        });
    }

    public function updatePaymentStatus(Order $order, string $status, $paidAt = null)
    {
        try {
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
        } catch (Exception $e) {
            Log::error(__METHOD__ . " Error (Order ID: {$order->id}): " . $e->getMessage());
            throw $e;
        }
    }
    
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
                Log::error(__METHOD__ . " Failed to cancel Order ID {$order->id}: " . $e->getMessage());
                $failed[] = ['id' => $order->id, 'order_code' => $order->order_code, 'reason' => $e->getMessage()];
            }
        }
        return ['success_count' => $success, 'failed_orders' => $failed];
    }

    private function restockOrderItems(Order $order)
    {
        foreach ($order->orderDetails as $detail) {
            if ($detail->product_id) { 
                 // Sản phẩm lẻ
                $this->restockSingleProduct($order->branch_id, $detail->product_id, $detail->quantity, $detail->unit_of_measure, $detail->product->base_unit);
            } elseif ($detail->combo_id) {
                // Combo -> Hoàn từng món con
                $combo = Combo::with('items')->find($detail->combo_id);
                if ($combo) {
                    foreach ($combo->items as $cItem) {
                        // Số lượng cần hoàn = SL Combo đã mua * SL món trong công thức
                        $qtyToRestock = $detail->quantity * $cItem->pivot->quantity;
                        $this->restockSingleProduct($order->branch_id, $cItem->id, $qtyToRestock, $cItem->base_unit, $cItem->base_unit);
                    }
                }
            }
        }
    }

    private function restockSingleProduct($branchId, $productId, $quantity, $unitName, $baseUnitName) {
        $stock = BranchProductStock::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->lockForUpdate() 
            ->first();
        
        if ($stock) {
            $conversionFactor = 1;
            if ($unitName && $unitName !== $baseUnitName) {
                $conv = ProductUnitConversion::where('product_id', $productId)
                    ->where('unit_name', $unitName)->first();
                if ($conv) $conversionFactor = $conv->conversion_factor;
            }
            $stock->increment('quantity', $quantity * $conversionFactor);
        }
    }

    private function processProductItem($itemData, $branchId, $priceType = 'app') {
        $product = Product::findOrFail($itemData['id']);
        $quantity = $itemData['quantity'];
        $unitName = $itemData['unit_of_measure'] ?? $product->base_unit;

        if (isset($itemData['price']) && is_numeric($itemData['price']) && $itemData['price'] >= 0) {
             $basePrice = (float)$itemData['price'];
        } elseif ($priceType === 'store') {
            $basePrice = (float)$product->base_store_price;
        } else {
            $basePrice = ($product->base_app_price > 0) ? (float)$product->base_app_price : (float)$product->base_store_price;
        }

        $unitPrice = $basePrice;
        $conversionFactor = 1;

        if ($unitName !== $product->base_unit) {
            $conversion = ProductUnitConversion::where('product_id', $product->id)
                ->where('unit_name', $unitName)->first();
            
            if ($conversion) {
                if (!isset($itemData['price'])) {
                    if ($priceType === 'store') {
                        $convPrice = (float)$conversion->store_price;
                    } else {
                        $convPrice = (float)$conversion->app_price;
                    }
                    $unitPrice = ($convPrice > 0) ? $convPrice : ($basePrice * $conversion->conversion_factor);
                }
                $conversionFactor = $conversion->conversion_factor;
            } else {
                throw new Exception("Đơn vị tính '{$unitName}' không hợp lệ.");
            }
        }

        if ($product->is_sales_unit) {
            $qtyToDeduct = $quantity * $conversionFactor;
            $this->deductStock($branchId, $product->id, $qtyToDeduct);
        }

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
        
        $itemDiscount = isset($itemData['item_discount']) ? (float)$itemData['item_discount'] : 0;
        $subtotal = ($unitPrice * $quantity) - $itemDiscount;

        return [
            'subtotal' => $subtotal, 
            'detail' => [
                'product_id' => $product->id, 
                'combo_id' => null, 
                'unit_of_measure' => $unitName, 
                'quantity' => $quantity, 
                'unit_price' => $unitPrice, 
                'discount_amount' => $itemDiscount,
                'subtotal' => $subtotal,
                'attributes_snapshot' => $attributesSnapshot, 
            ]
        ];
    }

    private function processComboItem($itemData, $branchId, $priceType = 'app') {
        $combo = Combo::with('items')->findOrFail($itemData['id']);
        
        // Số lượng Combo khách đặt
        $quantity = $itemData['quantity']; 

        if (!$combo->is_active) throw new Exception("Combo ngừng hoạt động.");

        if (isset($itemData['price']) && is_numeric($itemData['price']) && $itemData['price'] >= 0) {
             $comboPrice = (float)$itemData['price'];
        } elseif ($priceType === 'store') {
            $comboPrice = (float)$combo->base_store_price;
        } else {
            $comboPrice = ($combo->base_app_price > 0) ? (float)$combo->base_app_price : (float)$combo->base_store_price;
        }

        $itemDiscount = isset($itemData['item_discount']) ? (float)$itemData['item_discount'] : 0;
        $totalSubtotal = ($comboPrice * $quantity) - $itemDiscount;
        
        // 1. Trừ kho các món thành phần
        foreach ($combo->items as $cItem) {
            // [LOGIC MỚI - ĐÚNG] Số lượng cần trừ = SL Combo đặt * SL món trong công thức
            $itemTotalQty = $quantity * $cItem->pivot->quantity;

            // Luôn trừ kho cho thành phần combo
            $this->deductStock($branchId, $cItem->id, $itemTotalQty);
        }

        // 2. Tạo 1 dòng OrderDetail cho Combo (không tách lẻ món con để lưu)
        return [
            'subtotal' => $totalSubtotal, 
            'detail' => [
                'product_id' => null, 
                'combo_id' => $combo->id, 
                'unit_of_measure' => 'Combo', // Hoặc đơn vị mặc định
                'quantity' => $quantity, 
                'unit_price' => $comboPrice, 
                'discount_amount' => $itemDiscount,
                'subtotal' => $totalSubtotal, 
                'attributes_snapshot' => [] 
            ]
        ];
    }

    private function deductStock($branchId, $productId, $quantity) {
        $stock = BranchProductStock::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->lockForUpdate() 
            ->first();

        $productName = Product::find($productId)->name ?? "Sản phẩm #$productId";

        if (!$stock) {
            throw new Exception("Sản phẩm '{$productName}' chưa nhập kho tại chi nhánh này.");
        }

        if ($stock->quantity < $quantity) {
            throw new Exception("Sản phẩm '{$productName}' không đủ hàng (Còn: {$stock->quantity}, Cần: {$quantity}).");
        }

        $stock->decrement('quantity', $quantity);
    }

    // [CẬP NHẬT] Validate Slot với logic mới: Check is_flexible_time của Branch
    private function validateSlotAvailability($slot, $branchId, $checkTime) {
        // Lấy thông tin chi nhánh
        $branch = Branch::findOrFail($branchId);

        // Kiểm tra Slot có thuộc Chi nhánh và đang bật không
        $isActive = $slot->branches()
            ->where('branches.id', $branchId)
            ->where('branch_time_slot_pivot.is_enabled', true)
            ->exists();

        if (!$isActive) {
            throw new Exception("Ca đặt hàng '{$slot->name}' không áp dụng cho chi nhánh này.");
        }
        
        // [LOGIC MỚI] Nếu Chi nhánh cho phép bán linh hoạt -> BỎ QUA check giờ
        if ($branch->is_flexible_time) {
            return;
        }

        // Logic kiểm tra thời gian 
        $orderDate = $checkTime->copy()->startOfDay();
        $today = Carbon::now()->startOfDay();

        $nowTimeStr = Carbon::now()->format('H:i:s');

        // Trường hợp 1: Đặt cho TƯƠNG LAI
        if ($orderDate->gt($today)) {
            if ($orderDate->diffInDays($today) > 2) {
                throw new Exception("Chỉ được phép đặt trước tối đa 2 ngày.");
            }
            return;
        }
        // Trường hợp 2: Đặt cho HÔM NAY
        if ($orderDate->equalTo($today)) {
            if ($nowTimeStr < $slot->start_time) {
                throw new Exception("Chưa đến giờ nhận đơn cho ca này (Mở lúc {$slot->start_time}).");
            }
            if ($nowTimeStr > $slot->end_time) {
                throw new Exception("Đã hết giờ nhận đơn cho ca này (Đóng lúc {$slot->end_time}).");
            }
            return;
        }
    }

    private function validateSellingTime($itemId, $type, $timeSlotId) {
        // Chỉ check nếu món có gán slot
        $hasAnySlot = ItemTimeSlot::where($type === 'product' ? 'product_id' : 'combo_id', $itemId)->exists();
        if (!$hasAnySlot) return; // Không gán slot -> Bán cả ngày

        $isAssigned = ItemTimeSlot::where('time_slot_id', $timeSlotId)->where($type === 'product' ? 'product_id' : 'combo_id', $itemId)->exists();
        if (!$isAssigned) throw new Exception("Món này không phục vụ trong ca hiện tại.");
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
