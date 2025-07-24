<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Http\Requests\Api\Order\StoreOrderRequest;
use App\Http\Requests\Api\Order\UpdateOrderRequest;
use App\Http\Requests\Api\Order\UpdatePaymentStatusRequest;
use App\Http\Requests\Api\Order\MultiDeleteOrderRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Api\Order\MultiCancelOrderRequest;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    public function index(Request $request)
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
    public function show(int $id): JsonResponse
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
                'message' => 'Đã có lỗi xảy ra, không thể lấy chi tiết đơn hàng.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->orderService->createOrder($request->validated(), $request->user());
            return response()->json([
                'success' => true,
                'message' => 'Tạo đơn hàng thành công',
                'data' => new OrderResource($order)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' =>  $e->getMessage()
            ], 400);
        }
    }
    public function updateStatus(UpdateOrderRequest $request, Order $order)
    {
        try {
            $updatedOrder = $this->orderService->updateOrder(
                $order,
                $request->validated()
            );
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật đơn hàng thành công.',
                'data' => new OrderResource($updatedOrder),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật đơn hàng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function updatePaymentStatus(UpdatePaymentStatusRequest $request, Order $order)
    {
        try {
            $updatedOrder = $this->orderService->updateOrderPaymentStatus($order, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thanh toán thành công.',
                'data' => new OrderResource($updatedOrder),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function multiCancel(MultiDeleteOrderRequest $request)
    {
        try {

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
