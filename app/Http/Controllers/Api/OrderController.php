<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Http\Requests\Api\Order\StoreOrderRequest;
use App\Http\Requests\Api\Order\UpdateOrderRequest;
use App\Http\Requests\Api\Order\UpdatePaymentStatusRequest;
use App\Http\Requests\Api\Order\MultiDeleteOrderRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Api\Order\GetOrderRequest; 

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    public function index(GetOrderRequest $request)
    {
        try {
            $result = $this->orderService->getAllOrders($request);

            return response()->json([
                'success' => true,
                'data' => OrderResource::collection($result['data']),
                'page' => $result['page'],
                'total' => $result['total'],
                'last_page' => $result['last_page'],
                'next_page' => $result['next_page'],
                'prev_page' => $result['prev_page'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
   
    /**
     * Xem chi tiết đơn hàng
     */
    public function show(int $id)
    {
        try {
            $order = $this->orderService->getOrderById($id);
            return response()->json([
                'success' => true,
                'message' => "Lấy chi tiết đơn hàng thành công.",
                'data' => new OrderResource($order),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Đơn hàng với ID {$id} không tồn tại.",
            ], 404);
        } catch (\Exception $e) {
            Log::error("Lỗi khi lấy chi tiết đơn hàng #{$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống khi lấy chi tiết đơn hàng.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo đơn hàng mới (Dành cho Admin/POS)
     */
    public function store(StoreOrderRequest $request)
    {
        try {
            // Truyền user hiện tại (Admin/Staff) vào để ghi log hoặc xác định người tạo
            $currentUser = auth()->user(); 
            
            // Tham số thứ 3 là true => isAdminCreated = true
            $order = $this->orderService->createOrder($request->validated(), $currentUser, true);
            
            return response()->json([
                'success' => true,
                'message' => 'Tạo đơn hàng thành công',
                // Load các quan hệ cần thiết để hiển thị trên UI (ví dụ tên Ca, tên Điểm nhận)
                'data' => new OrderResource($order->load(['orderDetails', 'payment.method', 'timeSlot', 'pickupLocation']))
            ], 201);
        } catch (\Exception $e) {
            // Trả về 400 Bad Request cho các lỗi nghiệp vụ (hết hàng, sai giờ...)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() 
            ], 400);
        }
    }

    /**
     * Cập nhật thông tin đơn hàng (Full Update: Thông tin chung + Items)
     */
    public function update(UpdateOrderRequest $request, int $id)
    {
        try {
            $order = Order::findOrFail($id);
            $currentUser = auth()->user();
            
            // Gọi Service để xử lý update toàn bộ logic (hoàn kho cũ, trừ kho mới nếu đổi items)
            $updatedOrder = $this->orderService->updateOrder($order, $request->validated(), $currentUser);
            
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật đơn hàng thành công',
                'data' => new OrderResource($updatedOrder->load(['orderDetails', 'payment.method', 'timeSlot', 'pickupLocation']))
            ]);
        } catch (ModelNotFoundException $e) {
             return response()->json([
                'success' => false,
                'message' => "Đơn hàng với ID {$id} không tồn tại.",
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() 
            ], 400);
        }
    }

    /**
     * Chỉ cập nhật trạng thái đơn hàng (Duyệt/Giao hàng/Hủy)
     */
    public function updateStatus(UpdateOrderRequest $request, Order $order)
    {
        try {
            $updatedOrder = $this->orderService->updateOrderStatus(
                $order,
                $request->status
            );
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái đơn hàng thành công.',
                'data' => new OrderResource($updatedOrder),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cập nhật trạng thái thanh toán
     */
    public function updatePaymentStatus(UpdatePaymentStatusRequest $request, Order $order)
    {
        try {
            $updatedOrder = $this->orderService->updatePaymentStatus(
                $order, 
                $request->status, 
                $request->paid_at
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thanh toán thành công.',
                'data' => new OrderResource($updatedOrder),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Hủy nhiều đơn hàng cùng lúc
     */
    public function multiCancel(MultiDeleteOrderRequest $request)
    {
        try {
            // Lấy danh sách ID từ request đã validate
            $result = $this->orderService->cancelMultipleOrders($request->validated()['order_ids']);
            
            $successCount = $result['success_count'];
            $failedCount = count($result['failed_orders']);
            
            $message = "Đã hủy thành công {$successCount} đơn hàng.";
            if ($failedCount > 0) {
                $message .= " Thất bại {$failedCount} đơn hàng.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'details' => [
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'failed_orders' => $result['failed_orders']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Đã có lỗi xảy ra trong quá trình xử lý.'], 500);
        }
    }
}
