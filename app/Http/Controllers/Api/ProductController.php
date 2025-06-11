<?php

namespace App\Http\Controllers\Api;

use App\Services\ProductService;
use App\Http\Requests\Api\Product\StoreProductRequest;
use App\Http\Requests\Api\Product\UpdateProductRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Api\Product\MultiDeleteProductRequest;
use App\Traits\FileUploadTrait;

class ProductController extends Controller
{
    use FileUploadTrait;

    protected $productService;
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
    /**
     * Lấy danh sách tất cả sản phẩm
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $data = $this->productService->getAllProducts($request);
            return response()->json([
                'success' => true,
                'data' => ProductResource::collection($data['data']),
                'pagination' => [
                    'page' => $data['page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'next_page' => $data['next_page'],
                    'pre_page' => $data['pre_page'],
                ],
                'message' => 'Lấy danh sách sản phẩm thành công',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Controller error retrieving products: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách sản phẩm',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Tạo mới một sản phẩm
     */
    public function store(StoreProductRequest $request)
    {
        try {
            $data = $request->validated();
            $product = $this->productService->createProduct($data);
            return response()->json([
                'success' => true,
                'data' => new ProductResource($product),
                'message' => 'Tạo sản phẩm thành công'
            ], 201);
        }catch (Exception $e) {
            Log::error('Unexpected error creating product: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo sản phẩm',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết một sản phẩm
     */
    public function show($id)
    {
        try {
            $product = $this->productService->getProductById($id);

            return response()->json([
                'success' => true,
                'data' => new ProductResource($product),
                'message' => 'Lấy thông tin sản phẩm thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error('Product not found: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm',
            ], 404);
        } catch (Exception $e) {
            Log::error('Error retrieving product: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin sản phẩm',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Cập nhật thông tin một sản phẩm
     */
    public function update(UpdateProductRequest $request, $id)
    {
        try {
            // Lấy toàn bộ dữ liệu từ form-data
            $data = $request->all();
            // Validate dữ liệu (sử dụng validated để lấy các trường hợp lệ)
            $validated = $request->validated();
            // Gộp validated vào data để đảm bảo chỉ lấy trường hợp lệ
            $data = array_merge($data, $validated);
            $product = $this->productService->updateProduct($id, $data);
            return response()->json([
                'success' => true,
                'data' => new ProductResource($product),
                'message' => 'Cập nhật sản phẩm thành công'
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error('Product not found for update: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm',
            ], 404);
        } catch (Exception $e) {
            Log::error('Unexpected error updating product: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật sản phẩm',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa một sản phẩm
     */
    public function destroy($id)
    {
        try {
            $this->productService->deleteProduct($id);
            return response()->json([
                'success' => true,
                'message' => 'Xóa sản phẩm thành công',
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error('Product not found for deletion: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm',
            ], 404);
        } catch (Exception $e) {
            Log::error('Unexpected error deleting product: ' . $e->getMessage(), ['exception' => $e]);
            if (str_contains($e->getMessage(), 'không thể xóa') || str_contains($e->getMessage(), 'đang được sử dụng')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi xóa sản phẩm',
            ], 500);
        }
    }
    /**
     * Xóa nhiều sản phẩm cùng lúc
     */
    public function multiDelete(MultiDeleteProductRequest $request)
    {
        try {
            $deletedCount = $this->productService->multiDeleteProducts($request->validated()['ids']);
            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} sản phẩm",
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error('Error in multi-delete products: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            Log::error('Unexpected error in multi-delete products: ' . $e->getMessage(), ['exception' => $e]);
            if (str_contains($e->getMessage(), 'không thể xóa') || str_contains($e->getMessage(), 'đang được sử dụng')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi xóa sản phẩm',
            ], 500);
        }
    }
}
