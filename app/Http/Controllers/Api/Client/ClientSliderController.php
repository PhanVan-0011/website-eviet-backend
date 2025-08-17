<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\SliderResource;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientSliderController extends Controller
{
    /**
     * Lấy danh sách các slider đang hoạt động cho trang client.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Chỉ lấy các slider đang hoạt động
            $sliders = Slider::where('is_active', true)
                ->with(['image', 'linkable'])
                ->orderBy('display_order', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách slider thành công.',
                'data' => SliderResource::collection($sliders),
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy slider cho client: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách slider vào lúc này.',
            ], 500);
        }
    }
}
