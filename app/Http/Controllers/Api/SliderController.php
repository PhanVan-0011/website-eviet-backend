<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Slider\StoreSliderRequest;
use App\Http\Requests\Api\Slider\UpdateSliderRequest;
use App\Http\Requests\Api\Slider\MultiDeleteSliderRequest;
use App\Http\Resources\SliderResource;
use App\Services\SliderService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Slider;

class SliderController extends Controller
{
    protected $sliderService;

    public function __construct(SliderService $sliderService)
    {
        $this->sliderService = $sliderService;
    }

    // Lấy danh sách slider
    public function index(Request $request)
    {
        try {
           
                $data = $this->sliderService->getAllSliders($request);
                return response()->json([
                    'success' => true,
                    'data' => SliderResource::collection($data['data']),
                    'pagination' => [
                        'page' => $data['page'],
                        'total' => $data['total'],
                        'last_page' => $data['last_page'],
                        'next_page' => $data['next_page'],
                        'prev_page' => $data['prev_page'],
                    ],
                    'message' => 'Lấy danh sách slider thành công',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách slider ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    // Tạo slider mới
    public function store(StoreSliderRequest $request)
    {
        try {
            $slider = $this->sliderService->createSlider($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Tạo slider thành công',
                'data' => new SliderResource($slider),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                 'message' => 'Lỗi khi tạo slider'
            ], 500);
        }
    }
    // Xem chi tiết slider
    public function show(int $id)
    {
        try {
            $slider = $this->sliderService->getSliderById($id);
            return response()->json([
                'success' => true,
                'data' => new SliderResource($slider),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Slider không tồn tại',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin slider'
            ], 500);
        }
    }

    // Cập nhật slider
    public function update(UpdateSliderRequest $request, int $id)
    {
        try {
           $slider = $this->sliderService->updateSlider($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật slider thành công',
                'data' => new SliderResource($slider),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Slider không tồn tại',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật slider',
            ], 500);
        }
    }

    // Xóa slider đơn
    public function destroy(int $id)
    {
        try {
            $delete = $this->sliderService->delete($id);

            if (!$delete) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slider không tồn tại'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => 'Xóa slider thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa slider',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function multiDelete(MultiDeleteSliderRequest $request)
    {
        try {
            $deletedCount = $this->sliderService->deleteMultiple($request->validated()['ids']);
            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} sliders",
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(), // "ID cần xóa không tồn tại trong hệ thống"
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi xóa sliders',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
