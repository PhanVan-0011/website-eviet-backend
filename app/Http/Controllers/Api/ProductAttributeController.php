<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProductAttributeService;
use App\Models\AttributeValue;
use App\Models\CartItem;
use App\Http\Requests\ProductAttribute\StoreProductAttributeRequest;
use App\Http\Requests\ProductAttribute\UpdateProductAttributeRequest;
use App\Http\Requests\ProductAttribute\MultiDeleteProductAttributeRequest;
use App\Http\Resources\ProductAttributeResource;
use App\Http\Requests\ProductAttribute\GetProductAttributeRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductAttributeController extends Controller
{
    protected $productAttributeService;

    public function __construct(ProductAttributeService $productAttributeService)
    {
        $this->productAttributeService = $productAttributeService;
    }

    public function index(GetProductAttributeRequest $request)
    {
        // Toàn bộ logic validation đã được chuyển vào IndexProductAttributeRequest
        try {
            $data = $this->productAttributeService->getPaginatedAttributes($request);
            
            return response()->json([
                'success' => true,
                'data' => ProductAttributeResource::collection($data['data']),
                'pagination' => [
                    'page' => $data['page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'next_page' => $data['next_page'],
                    'prev_page' => $data['prev_page'],
                ],
                'message' => 'Lấy danh sách thuộc tính thành công',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách thuộc tính',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreProductAttributeRequest $request): JsonResponse
    {
        try {
            $attribute = $this->productAttributeService->createAttribute($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Tạo thuộc tính thành công',
                'data' => new ProductAttributeResource($attribute),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo thuộc tính',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $attribute = $this->productAttributeService->getAttributeById($id);
            return response()->json([
                'success' => true,
                'data' => new ProductAttributeResource($attribute),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thuộc tính không tồn tại',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin thuộc tính',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdateProductAttributeRequest $request, int $id): JsonResponse
    {
        try {
            $attribute = $this->productAttributeService->updateAttribute($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thuộc tính thành công',
                'data' => new ProductAttributeResource($attribute),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thuộc tính không tồn tại',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() // Trả về lỗi nghiệp vụ từ Service
            ], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->productAttributeService->deleteAttribute($id);
            return response()->json([
                'success' => true,
                'message' => 'Xóa thuộc tính thành công'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thuộc tính không tồn tại.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function multiDelete(MultiDeleteProductAttributeRequest $request): JsonResponse
    {
        try {
            $deletedCount = $this->productAttributeService->deleteMultiple($request->validated()['ids']);
            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} thuộc tính",
            ]);
        } catch (ModelNotFoundException $e) {
             return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Xóa một giá trị (value) của thuộc tính.
     */
    public function deleteAttributeValue(int $id): void
    {
        $value = AttributeValue::findOrFail($id);
        
        // Kiểm tra ràng buộc trước khi xóa
        $isUsed = CartItem::whereJsonContains('attributes', ['value' => $value->value])
                          ->whereJsonContains('attributes', ['name' => $value->productAttribute->name])
                          ->exists();

        if ($isUsed) {
            throw new Exception("Không thể xóa giá trị '{$value->value}' vì đang được sử dụng.");
        }
        
        try {
            $value->delete();
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa giá trị thuộc tính ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }
}
