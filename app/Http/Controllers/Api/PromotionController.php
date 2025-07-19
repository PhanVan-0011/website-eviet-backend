<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PromotionService;
use App\Models\Promotion;
use App\Http\Resources\PromotionResource;
use App\Http\Requests\Api\Promotion\StorePromotionRequest;
use App\Http\Requests\Api\Promotion\UpdatePromotionRequest;
use App\Http\Requests\Api\Promotion\GetPromotionsRequest;
use App\Http\Requests\Api\Promotion\MultiDeletePromotionRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Exception;

class PromotionController extends Controller
{
   protected PromotionService $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

   public function index(GetPromotionsRequest $request)
    {
        try {
                $data = $this->promotionService->getAllPromotions($request);
                return response()->json([
                    'success' => true,
                    'data' => PromotionResource::collection($data['data']),
                    'pagination' => [
                        'page' => $data['page'],
                        'total' => $data['total'],
                        'last_page' => $data['last_page'],
                        'next_page' => $data['next_page'],
                        'pre_page' => $data['pre_page'],
                    ],
                    'message' => 'Lấy danh sách khuyến mãi thành công',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách khuyến mãi',
                'error' => $e->getMessage(),
            ], 500);
        }
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

    public function show(int $id): JsonResponse
    {
        try {
            $promotion = $this->promotionService->getPromotionById($id);
            $promotion->load(['products:id,name', 'categories:id,name', 'combos:id,name']);
            return response()->json([
                'success' => true,
                'message' => 'Lấy chi tiết khuyến mãi thành công.',
                'data' => new PromotionResource($promotion)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy khuyến mãi với ID {$id}."
            ], 404);
        }catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy chi tiết khuyến mãi.'
            ], 500);
        }
    }

    public function update(UpdatePromotionRequest $request, int $id)
    {
        try {
            $promotion = $this->promotionService->getPromotionById($id);
            $updatedPromotion = $this->promotionService->updatePromotion($promotion, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật khuyến mãi thành công.',
                'data' => new PromotionResource($updatedPromotion),
            ]);
        }  catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => "Không tìm thấy khuyến mãi với ID {$id}."], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $promotion = $this->promotionService->getPromotionById($id);
            $isHardDeleted = $this->promotionService->deletePromotion($promotion);
            $message = $isHardDeleted 
                ? 'Đã xóa vĩnh viễn khuyến mãi.' 
                : 'Khuyến mãi đã được sử dụng nên chỉ vô hiệu hóa.';
            return response()->json(['success' => true, 'message' => $message]);
        }catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => "Không tìm thấy khuyến mãi với ID {$id}."], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
     public function multiDelete(MultiDeletePromotionRequest $request)
    {
        try {
            $result = $this->promotionService->deleteMultiplePromotions($request->validated()['promotion_ids']);

            $hardDeleted = $result['hard_deleted_count'];
            $softDeleted = $result['soft_deleted_count'];
            $failedCount = count($result['failed_promotions']);

            $message = "Đã xóa thành công. Xóa vĩnh viễn: {$hardDeleted}, Vô hiệu hóa: {$softDeleted}.";
            if ($failedCount > 0) {
                $message .= " Thất bại: {$failedCount}.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'details' => $result,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi hệ thống xảy ra trong quá trình xử lý.',
            ], 500);
        }
    }
}
