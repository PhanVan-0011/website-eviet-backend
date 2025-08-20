<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\PromotionResource;
use App\Services\Client\PromotionService;
use App\Models\Promotion;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PromotionController extends Controller
{
    protected $promotionService; 

    public function __construct(PromotionService $promotionService) 
    {
        $this->promotionService = $promotionService;
    }
    /**
     * Lấy danh sách các chương trình khuyến mãi đang hoạt động cho client.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            
            $promotions = $this->promotionService->getActivePromotions($request);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách khuyến mãi thành công.',
                'data' => PromotionResource::collection($promotions),
            ]);

        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách khuyến mãi công khai: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách khuyến mãi.'
            ], 500);
        }
    }
}
