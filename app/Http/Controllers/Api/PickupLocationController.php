<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PickupLocationService;
use App\Http\Requests\Api\PickupLocation\StorePickupLocationRequest;
use App\Http\Requests\Api\PickupLocation\UpdatePickupLocationRequest;
use App\Http\Requests\Api\PickupLocation\MultiDeletePickupLocationRequest;
use App\Http\Resources\PickupLocationResource;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
class PickupLocationController extends Controller
{
     protected $pickupLocationService;

    public function __construct(PickupLocationService $pickupLocationService)
    {
        $this->pickupLocationService = $pickupLocationService;
    }

    /**
     * Lấy danh sách điểm nhận hàng (Có phân trang thủ công & lọc)
     */
    public function index(Request $request)
    {
        try {
            $data = $this->pickupLocationService->getAllLocations($request);
            
            return response()->json([
                'success' => true,
                'data' => PickupLocationResource::collection($data['data']),
                'pagination' => [
                    'page' => $data['page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'next_page' => $data['next_page'],
                    'pre_page' => $data['pre_page'],
                ],
                'message' => 'Lấy danh sách điểm nhận hàng thành công',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Lỗi khi lấy danh sách',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xem chi tiết
     */
    public function show(int $id)
    {
        try {
            $location = $this->pickupLocationService->getPickupLocationById($id);
            return response()->json([
                'success' => true,
                'data' => new PickupLocationResource($location),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy điểm nhận hàng.'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Tạo mới
     */
    public function store(StorePickupLocationRequest $request)
    {
        try {
            $location = $this->pickupLocationService->createLocation($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Tạo điểm nhận hàng thành công.',
                'data' => new PickupLocationResource($location),
            ], 201);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cập nhật
     */
    public function update(UpdatePickupLocationRequest $request, int $id)
    {
        try {
            $location = $this->pickupLocationService->updateLocation($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thành công.',
                'data' => new PickupLocationResource($location),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy điểm nhận hàng.'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Xóa một
     */
    public function destroy(int $id)
    {
        try {
            $this->pickupLocationService->deleteLocation($id);
            return response()->json([
                'success' => true, 
                'message' => 'Xóa thành công điểm nhận hàng.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy điểm nhận hàng.'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422); // 422 nếu có ràng buộc khóa ngoại
        }
    }

    /**
     * Xóa nhiều
     */
    public function multiDelete(MultiDeletePickupLocationRequest $request)
    {
        try {
            // Lấy mảng ID từ request (đã được validate và merge)
            $deletedCount = $this->pickupLocationService->multiDeleteLocations($request->validated()['ids']);
            
            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} điểm nhận hàng."
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Lỗi khi xóa nhiều.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
