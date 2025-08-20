<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ProductService;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
     protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Lấy danh sách sản phẩm công khai.
     * API: GET /api/public/products
     */
    public function index(Request $request)
    {
        try {
            $result = $this->productService->getPublicProducts($request);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm thành công.',
                'data' => ProductResource::collection($result['data']),
                'pagination' => $result['pagination']
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách sản phẩm công khai: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách sản phẩm.'
            ], 500);
        }
    }

    /**
     * Lấy thông tin chi tiết một sản phẩm công khai.
     * API: GET /api/public/products/{id}
     */
    public function show(int $id)
    {
        try {
            $product = $this->productService->findPublicProductById($id);

            return response()->json([
                'success' => true,
                'data' => new ProductResource($product)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm hoặc sản phẩm đã ngừng kinh doanh.'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Lỗi khi lấy chi tiết sản phẩm công khai #{$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra.'
            ], 500);
        }
    }
     /**
     * Lấy danh sách sản phẩm bán chạy.
     * API: GET /api/public/products/best-sellers
     */
    public function bestSellers(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $products = $this->productService->getBestSellingProducts((int)$limit);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm bán chạy thành công.',
                'data' => ProductResource::collection($products)
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy sản phẩm bán chạy: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Không thể lấy danh sách sản phẩm bán chạy.'], 500);
        }
    }

    /**
     * Lấy danh sách sản phẩm gợi ý (dành cho bạn).
     * API: GET /api/public/products/recommendations
     */
    public function recommendations(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $products = $this->productService->getRecommendedProducts((int)$limit);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm gợi ý thành công.',
                'data' => ProductResource::collection($products)
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy sản phẩm gợi ý: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Không thể lấy danh sách sản phẩm gợi ý.'], 500);
        }
    }
}
