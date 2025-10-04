<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProductUnitConversionService;
use App\Http\Requests\Api\ProductUnitConversion\StoreProductUnitConversionRequest;
use App\Http\Requests\Api\ProductUnitConversion\UpdateProductUnitConversionRequest;
use App\Http\Resources\ProductUnitConversionResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Log;

class ProductUnitConversionController extends Controller
{
    protected ProductUnitConversionService $unitConversionService;

    public function __construct(ProductUnitConversionService $unitConversionService)
    {
        $this->unitConversionService = $unitConversionService;
    }
    /**
     * Lấy danh sách TẤT CẢ các quy tắc chuyển đổi cho một sản phẩm.
     */
    public function index(string $product_id)
    {
        try {
            // Lấy danh sách quy tắc chuyển đổi dựa trên product_id
            $conversions = $this->unitConversionService->getConversionsByProductId((int)$product_id);
            
            return response()->json([
                'success' => true,
                'data' => ProductUnitConversionResource::collection($conversions),
                'message' => 'Lấy danh sách quy tắc chuyển đổi thành công.',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Lỗi hệ thống khi lấy danh sách quy tắc.', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy chi tiết một quy tắc chuyển đổi.
     */
    public function show(string $id)
    {
        try {
            $conversion = $this->unitConversionService->getConversionById((int)$id);
            
            return response()->json([
                'success' => true, 
                'data' => new ProductUnitConversionResource($conversion)
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Không tìm thấy quy tắc chuyển đổi đơn vị.'
            ], 404);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Lỗi hệ thống khi lấy chi tiết quy tắc.'
            ], 500);
        }
    }
    /**
     * Tạo mới một quy tắc chuyển đổi đơn vị. (Yêu cầu 201 hoặc 500)
     */
    public function store(StoreProductUnitConversionRequest $request)
    {
        try {
            $conversion = $this->unitConversionService->createConversion($request->validated());
            
            return response()->json([
                'success' => true, 
                'data' => new ProductUnitConversionResource($conversion), 
                'message' => 'Tạo quy tắc chuyển đổi đơn vị thành công.'
            ], 201);
            
        } catch (Exception $e) {
            // Bất kỳ lỗi nào (kể cả lỗi Unique Key từ Service) đều trả về 500
            // *Lưu ý: Yêu cầu này bỏ qua việc trả về 422 cho lỗi Unique Rule*
            
            Log::error("Lỗi khi tạo quy tắc đơn vị: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Lỗi hệ thống khi tạo quy tắc.'
            ], 500);
        }
    }

    /**
     * Cập nhật quy tắc chuyển đổi đơn vị. (Yêu cầu 200, 404 hoặc 500)
     */
    public function update(UpdateProductUnitConversionRequest $request, string $id)
    {
        try {
            $conversion = $this->unitConversionService->updateConversion((int)$id, $request->validated());
            
            return response()->json([
                'success' => true, 
                'data' => new ProductUnitConversionResource($conversion), 
                'message' => 'Cập nhật quy tắc chuyển đổi đơn vị thành công.'
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            // Trường hợp không tìm thấy (Yêu cầu 404)
            return response()->json([
                'success' => false, 
                'message' => 'Không tìm thấy quy tắc chuyển đổi đơn vị.'
            ], 404);
            
        } catch (Exception $e) {
            // Bất kỳ lỗi nào từ Service (kể cả lỗi nghiệp vụ) đều trả về 500
            Log::error("Lỗi khi cập nhật quy tắc đơn vị ID {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Lỗi hệ thống khi cập nhật quy tắc.'
            ], 500);
        }
    }

    /**
     * Xóa một quy tắc chuyển đổi đơn vị. (Yêu cầu 200, 404 hoặc 500)
     */
    public function destroy(string $id)
    {
        try {
            // Hàm deleteConversion ném ra ModelNotFoundException hoặc Exception
            $this->unitConversionService->deleteConversion((int)$id);
            
            return response()->json([
                'success' => true, 
                'message' => 'Xóa quy tắc chuyển đổi đơn vị thành công.'
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            // Trường hợp không tìm thấy (Yêu cầu 404)
            return response()->json([
                'success' => false, 
                'message' => 'Không tìm thấy quy tắc chuyển đổi đơn vị.'
            ], 404);
            
        } catch (Exception $e) {
            // Bất kỳ lỗi nào từ Service (kể cả lỗi nghiệp vụ/phát sinh dữ liệu) đều trả về 500
            Log::error("Lỗi khi xóa quy tắc đơn vị ID {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Lỗi hệ thống khi xóa quy tắc.'
            ], 500);
        }
    }
}
