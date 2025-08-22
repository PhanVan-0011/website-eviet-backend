<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\PromotionResource;
use App\Services\Client\PromotionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PromotionController extends Controller
{
    protected $promotionService; 

    public function __construct(PromotionService $promotionService) 
    {
        $this->promotionService = $promotionService;
    }
    /**
     * Lấy danh sách các chương trình khuyến mãi đang hoạt động cho client.
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
            return response()->json(['success' => false, 'message' => 'Không thể lấy danh sách khuyến mãi.'], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết một chương trình khuyến mãi.
     */
    public function show(int $id)
    {
        try {
            $promotion = $this->promotionService->findPublicPromotionById($id);

            return response()->json([
                'success' => true,
                'data' => new PromotionResource($promotion)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy khuyến mãi hoặc khuyến mãi đã hết hạn.'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Lỗi khi lấy chi tiết khuyến mãi công khai #{$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Đã có lỗi xảy ra.'], 500);
        }
    }
}
