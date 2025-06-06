<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Http\Requests\Api\Order\StoreOrderRequest;  // Fix: Update namespace
use App\Http\Requests\Api\Order\UpdateOrderRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
    public function show($id)
    {
        try {
            $order = $this->orderService->getOrderById((int) $id);
            return response()->json([
                'success' => true,
                'data' => new OrderResource($order),
            ], 200);
        } catch (\Exception $e) {
            if ($e->getMessage() === "Đơn hàng không tồn tại.") {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 404);
            }
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi lấy chi tiết đơn hàng.',
                'error' => $e->getMessage(),
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
    public function update(UpdateOrderRequest $request, $id)
    {
        try {
            $order = Order::findOrFail($id);
            $updatedOrder = $this->orderService->updateOrder($order, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật đơn hàng thành công',
                'data' => new OrderResource($updatedOrder)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Đơn hàng không tồn tại",
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật đơn hàng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy($id)
    {
        try {
            // Tìm đơn hàng kèm các mối quan hệ cần thiết
            $order = Order::with(['orderDetails', 'payment'])->find($id);
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng không tồn tại hoặc đã bị xóa.'
                ], 404);
            }
            // Gọi service để xóa
            $this->orderService->deleteOrder($order);
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa đơn hàng thành công.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi xóa đơn hàng.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function multiDelete(Request $request)
    {
        $idsString = $request->query('ids');
        $orderIds = array_filter(array_map('intval', explode(',', $idsString)));
        // Kiểm tra đơn hàng còn tồn tại trong DB
        $existingOrders = Order::whereIn('id', $orderIds)->pluck('id')->toArray();
        if (empty($existingOrders)) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng không tồn tại hoặc đã bị xóa.',
            ], 404);
        }
        try {
            $this->orderService->deleteMultipleOrders($orderIds);
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa đơn hàng thành công.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi xóa các đơn hàng.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
