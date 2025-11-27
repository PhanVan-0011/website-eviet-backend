<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TimeSlotService;
use App\Http\Resources\TimeSlotResource;
use App\Http\Requests\Api\TimeSlot\StoreTimeSlotRequest;
use App\Http\Requests\Api\TimeSlot\UpdateTimeSlotRequest;
use App\Http\Requests\Api\TimeSlot\MultiDeleteTimeSlotRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class TimeSlotController extends Controller
{
    protected $timeSlotService;

    public function __construct(TimeSlotService $timeSlotService)
    {
        $this->timeSlotService = $timeSlotService;
    }

    /**
     * Lấy danh sách tất cả khung giờ
     */
    public function index(Request $request)
    {
        try {
            $data = $this->timeSlotService->getAllTimeSlots($request);
            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách khung giờ thành công',
                'data' => TimeSlotResource::collection($data['data']),
                'pagination' => [
                    'page' => $data['page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'next_page' => $data['next_page'],
                    'pre_page' => $data['pre_page'],
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách khung giờ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tạo mới một khung giờ
     */
    public function store(StoreTimeSlotRequest $request)
    {
        try {
            $timeSlot = $this->timeSlotService->createTimeSlot($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Tạo khung giờ thành công',
                'data' => new TimeSlotResource($timeSlot),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo khung giờ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết một khung giờ
     */
    public function show(int $id)
    {
        try {
            $timeSlot = $this->timeSlotService->getTimeSlotById($id);
            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin khung giờ thành công',
                'data' => new TimeSlotResource($timeSlot),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy khung giờ'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi lấy thông tin khung giờ'], 500);
        }
    }

    /**
     * Cập nhật thông tin một khung giờ
     */
    public function update(UpdateTimeSlotRequest $request, int $id)
    {
        try {
            $timeSlot = $this->timeSlotService->updateTimeSlot($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật khung giờ thành công',
                'data' => new TimeSlotResource($timeSlot)
            ]);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy khung giờ'], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật khung giờ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa một khung giờ
     */
    public function destroy(int $id)
    {
        try {
            $this->timeSlotService->deleteTimeSlot($id);
            return response()->json([
                'success' => true,
                'message' => 'Xóa khung giờ thành công',
            ]);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy khung giờ'], 404);
        } catch (Exception $e) {
            // Trả về lỗi 422 (lỗi nghiệp vụ)
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Xóa nhiều khung giờ cùng lúc
     */
    public function multiDelete(MultiDeleteTimeSlotRequest $request)
    {
        try {
            $deletedCount = $this->timeSlotService->multiDeleteTimeSlots($request->validated()['ids']);
            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} khung giờ",
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
