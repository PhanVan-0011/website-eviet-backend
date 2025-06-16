<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PromotionService;
use App\Models\Promotion;
use App\Http\Resources\PromotionResource;
use App\Http\Requests\Api\Promotion\StorePromotionRequest;
use App\Http\Requests\Api\Promotion\UpdatePromotionRequest;
use Illuminate\Http\JsonResponse;


class PromotionController extends Controller
{
   protected PromotionService $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    public function index(Request $request){
        // Thêm logic lọc và tìm kiếm ở đây nếu cần
        $promotions = Promotion::latest()->paginate($request->input('per_page', 15));
        return PromotionResource::collection($promotions)->response();
    }

    public function store(StorePromotionRequest $request)
    {
        try {
            $promotion = $this->promotionService->createPromotion($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Tạo khuyến mãi thành công.',
                'data' => new PromotionResource($promotion->load(['products', 'categories', 'combos'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Promotion $promotion): PromotionResource
    {
        // Load các quan hệ để hiển thị chi tiết
        $promotion->load(['products:id,name', 'categories:id,name', 'combos:id,name']);
        return new PromotionResource($promotion);
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion)
    {
        try {
            $updatedPromotion = $this->promotionService->updatePromotion($promotion, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật khuyến mãi thành công.',
                'data' => new PromotionResource($updatedPromotion),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Promotion $promotion): JsonResponse
    {
        try {
            $isHardDeleted = $this->promotionService->deletePromotion($promotion);
            $message = $isHardDeleted 
                ? 'Đã xóa vĩnh viễn khuyến mãi.' 
                : 'Khuyến mãi đã được sử dụng nên chỉ vô hiệu hóa.';
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
