<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Product;
use App\Models\PaymentMethod;
use App\Models\Combo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OrderResource;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function getAllOrders($request)
    {
        try {
            // Lấy các tham số đầu vào
            $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));

            // Tạo query cơ bản
            $query = Order::query()
                ->with(['user', 'orderDetails.product', 'payment.method']);
            // Lọc theo từ khóa (keyword)
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
            // Lọc theo trạng thái đơn hàng
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
            // Lọc theo phương thức thanh toán
            if ($request->filled('payment_method_code')) {
                $query->whereHas('payment.method', function ($q) use ($request) {
                    $q->where('code', $request->input('payment_method_code'));
                });
            }
            // Lọc theo khoảng thời gian
            if ($request->filled('start_date')) {
                $query->whereDate('order_date', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('order_date', '<=', $request->input('end_date'));
            }
            $query->latest('order_date');
            // Phân trang thủ công 
            // Tính tổng số bản ghi
            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            $orders = $query->skip($offset)->take($perPage)->get();
            // Tính phân trang
            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
            // Trả kết quả
            return [
                'data' => $orders,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'prev_page' => $prevPage,
            ];
        } catch (\Exception $e) {
            Log::error('Lỗi lấy danh sách đơn hàng: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     *   Lấy thông tin chi tiết của một đơn hàng theo ID
     */
    public function getOrderById(int $id)
    {
        return Order::with([
            'orderDetails.product',
            'payment.method',
            'user'
        ])->findOrFail($id);
    }
    /**
     * Tạo một đơn hàng mới
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            //BƯỚC 1 Kiểm tra tổng số lượng đặt combo+product (Xem có lớn hơn số lượng tồn không)
            $requiredStock = [];
            foreach ($data['items'] as $item) {
                $itemQuantity = $item['quantity'];
                if ($item['type'] === 'product') {
                    $productId = $item['id'];
                    $requiredStock[$productId] = ($requiredStock[$productId] ?? 0) + $itemQuantity;
                } elseif ($item['type'] === 'combo') {
                    $combo = Combo::with('items')->findOrFail($item['id']);
                    foreach ($combo->items as $comboItem) {
                        $productId = $comboItem->product_id;
                        $requiredStock[$productId] = ($requiredStock[$productId] ?? 0) + ($comboItem->quantity * $itemQuantity);
                    }
                }
            }
            // --- BƯỚC 2: Kiểm tra kho tồn
            $productIds = array_keys($requiredStock);
            $productsInStock = Product::whereIn('id', $productIds)->get()->keyBy('id');

            foreach ($requiredStock as $productId => $quantityNeeded) {
                if (!isset($productsInStock[$productId])) {
                    throw new \Exception("Sản phẩm với ID {$productId} không tồn tại.");
                }
                $product = $productsInStock[$productId];
                if ($product->stock_quantity < $quantityNeeded) {
                    throw new \Exception("Sản phẩm '{$product->name}' không đủ hàng trong kho (cần {$quantityNeeded}, còn {$product->stock_quantity}).");
                }
            }

            // Nếu ổn thì xử lý
            $paymentMethod = PaymentMethod::where('code', $data['payment_method_code'])->firstOrFail();
            $totalAmount = 0;
            $orderDetailsPayload = [];

            foreach ($data['items'] as $item) {
                $itemQuantity = $item['quantity'];

                if ($item['type'] === 'product') {
                    $product = $productsInStock[$item['id']]; // Lấy từ cache, không query lại
                    if ($product->status != 1) {
                        throw new \Exception("Sản phẩm '{$product->name}' hiện không kinh doanh.");
                    }

                    $unitPrice = $product->sale_price ?? $product->original_price;
                    $totalAmount += $unitPrice * $itemQuantity;

                    $orderDetailsPayload[] = [
                        'product_id' => $product->id,
                        'quantity' => $itemQuantity,
                        'unit_price' => $unitPrice,
                        'combo_id' => null,
                    ];
                } elseif ($item['type'] === 'combo') {
                    $combo = Combo::with('items.product')->findOrFail($item['id']);
                    if (!$combo->is_active) {
                        throw new \Exception("Combo '{$combo->name}' đã ngừng áp dụng.");
                    }

                    $totalAmount += $combo->price * $itemQuantity;
                    //Áp dụng thuật toán chia giá combo làm tròn
                    $originalComboPrice = $combo->items->sum(fn($ci) => ($ci->product->sale_price ?? $ci->product->original_price) * $ci->quantity);
                    $discountRatio = ($originalComboPrice > 0) ? ($combo->price / $originalComboPrice) : 0;
                    $tempDetails = [];
                    $calculatedComboTotal = 0;
                    $comboItemsCount = count($combo->items);
                    foreach ($combo->items as $index => $comboItem) {
                        $originalItemPrice = $comboItem->product->sale_price ?? $comboItem->product->original_price;
                        if ($index < $comboItemsCount - 1) {
                            $discountedUnitPrice = round($originalItemPrice * $discountRatio);
                            $calculatedComboTotal += $discountedUnitPrice * $comboItem->quantity;
                        } else {
                            $remainingAmount = $combo->price - $calculatedComboTotal;
                            $discountedUnitPrice = $remainingAmount / $comboItem->quantity;
                        }
                        $tempDetails[] = [
                            'product_id' => $comboItem->product->id,
                            'quantity'   => $comboItem->quantity * $itemQuantity,
                            'unit_price' => $discountedUnitPrice,
                            'combo_id'   => $combo->id,
                        ];
                    }
                    $orderDetailsPayload = array_merge($orderDetailsPayload, $tempDetails);
                }
            }

            // 3. Tạo bản ghi Order
            $order = Order::create([
                'order_date' => now(),
                'total_amount' => $totalAmount,
                'shipping_fee' => $data['shipping_fee'],
                'status' => 'pending',
                'client_name' => $data['client_name'],
                'client_phone' => $data['client_phone'],
                'shipping_address' => $data['shipping_address'],
                'user_id' => auth()->id(),
            ]);

            // 4. Tạo chi tiết đơn hàng
            $order->orderDetails()->createMany($orderDetailsPayload);

            // 5. Tạo thông tin thanh toán
            $order->payment()->create([
                'payment_method_id' => $paymentMethod->id,
                'status' => 'pending',
                'amount' => $totalAmount + $data['shipping_fee'],
            ]);

            // 6. Trừ tồn kho
            foreach ($requiredStock as $productId => $quantityToDecrement) {
                Product::where('id', $productId)->decrement('stock_quantity', $quantityToDecrement);
            }

            return $order->fresh(['orderDetails.product', 'payment.method', 'user']);
        });
    }
    /**
     * Cập nhật thông tin đơn hàng
     */
    //Bổ sung thông báo khi cấp nhật đơn hàng sau
    public function updateOrder(Order $order, array $data)
    {
        return DB::transaction(function () use ($order, $data) {
            $currentStatus = $order->status;
            //Không cho phép thay đổi bất cứ điều gì nếu đơn hàng đã được giao thành công hoặc đã hủy.
            if (in_array($currentStatus, ['delivered', 'cancelled'])) {
                throw new \Exception("Không thể cập nhật đơn hàng đã hoàn thành hoặc đã hủy.");
            }
            $order->fill($data);
            //Xử lý logic nghiệp vụ trạng thái thay đổi sang 'cancelled'
            if (isset($data['status']) && $data['status'] === 'cancelled' && $order->isDirty('status')) {
                // Cộng trả lại tồn kho cho các sản phẩm trong đơn hàng
                foreach ($order->orderDetails as $detail) {
                    $product = Product::findOrFail($detail->product_id);
                    $product->increment('stock_quantity', $detail->quantity);
                }
                // Cập nhật thời gian hủy đơn
                $order->cancelled_at = now();
            }
            $order->save();
            return $order;
        });
    }
    public function updateOrderPaymentStatus(Order $order, array $data)
    {
        $payment = $order->payment()->first();

        // Kiểm tra nếu đơn hàng không có thông tin thanh toán
        if (!$payment) {
            throw new \Exception("Đơn hàng này không có thông tin thanh toán để cập nhật.");
        }

        // Kiểm tra logic nghiệp vụ: không cho cập nhật lại khi đã thành công
        if ($payment->status === 'success') {
            throw new \Exception("Thanh toán này đã được xác nhận thành công trước đó.");
        }

        // Cập nhật trạng thái
        $payment->status = $data['status'];

        // Nếu trạng thái là 'success', cập nhật ngày thanh toán
        if ($data['status'] === 'success') {
            $payment->paid_at = $data['paid_at'] ?? now();
        } else {
            $payment->paid_at = null;
        }

        $payment->save();

        // Load lại quan hệ để trả về dữ liệu mới nhất
        return $order->fresh('payment.method');
    }

    public function multiCancel(array $orderIds): array
    {
        $cancelledCount = 0;
        $failedOrders = [];

        $ordersToCancel = Order::with('orderDetails')->whereIn('id', $orderIds)->get();

        // Duyệt qua từng đơn hàng để xử lý riêng lẻ
        foreach ($ordersToCancel as $order) {
            // cho một đơn hàng phải thành công cùng nhau.
            DB::beginTransaction();
            try {
                // Kiểm tra nghiệp vụ: Chỉ hủy các đơn hàng chưa ở trạng thái cuối cùng
                if (!in_array($order->status, ['delivered', 'cancelled'])) {

                    //Cập nhật trạng thái đơn hàng
                    $order->status = 'cancelled';
                    $order->cancelled_at = now();
                    $order->save();

                    //Cộng trả lại tồn kho
                    foreach ($order->orderDetails as $detail) {
                        Product::find($detail->product_id)->increment('stock_quantity', $detail->quantity);
                    }
                    // Nếu cả 2 hành động trên đều thành công, lưu lại thay đổi vào CSDL
                    DB::commit();
                    $cancelledCount++;
                } else {
                    // Nếu đơn hàng không đủ điều kiện để hủy, ghi nhận vào danh sách lỗi.
                    $failedOrders[] = ['id' => $order->id, 'order_code' => $order->order_code, 'reason' => "Đơn hàng đã ở trạng thái '{$order->status}'."];
                    DB::rollBack();
                }
            } catch (\Exception $e) {
                // tất cả các thay đổi cho đơn hàng NÀY sẽ bị hoàn tác (rollback).
                DB::rollBack();
                // Ghi nhận đơn hàng bị lỗi vào danh sách để báo cáo lại cho người dùng.
                $failedOrders[] = ['id' => $order->id, 'order_code' => $order->order_code, 'reason' => $e->getMessage()];
            }
        }

        // Trả về một mảng kết quả tổng hợp
        return [
            'success_count' => $cancelledCount,
            'failed_orders' => $failedOrders
        ];
    }
}
