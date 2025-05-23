<?php

namespace App\Http\Controllers\Api;

use App\Services\ProductService;
use App\Http\Requests\Api\Product\StoreProductRequest;
use App\Http\Requests\Api\Product\UpdateProductRequest;
use App\Http\Controllers\Controller; 
use Exception;

class ProductController extends Controller
{
    protected $productService;
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
    public function index()
    {
        try {
            $perPage = request()->input('per_page', 10); // Lấy số lượng sản phẩm mỗi trang từ query string
            $products = $this->productService->getAllProducts($perPage);

            return response()->json([
                'success' => true,
                'data' => $products->items(), // Dữ liệu sản phẩm
                'pagination' => [ // Thông tin phân trang
                    'current_page' => $products->currentPage(),
                    'total_pages' => $products->lastPage(),
                    'total_items' => $products->total(),
                    'per_page' => $products->perPage(),
                ],
                'message' => 'Lấy danh sách sản phẩm thành công',
                'timestamp' => now()->format('d-m-Y H:i:s')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách sản phẩm',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Tạo mới một sản phẩm
     */
    public function store(StoreProductRequest $request)
    {
        try {
            $product = $this->productService->createProduct($request->only(['name', 'description', 'size', 'original_price', 'sale_price', 'stock_quantity', 'image_url', 'status', 'category_id']));

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Tạo sản phẩm thành công'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo sản phẩm',
                'errors' => $e->getMessage()
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
                'data' => $product,
                'message' => 'Lấy thông tin sản phẩm thành công',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin sản phẩm',
                'errors' => $e->getMessage()
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
            $product = $this->productService->updateProduct($id, $request->only(['name', 'description', 'size', 'original_price', 'sale_price', 'stock_quantity', 'image_url', 'status', 'category_id']));

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Cập nhật sản phẩm thành công'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật sản phẩm',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa một sản phẩm
     */
    public function destroy($id)
    {
        try {
            $deleted = $this->productService->deleteProduct($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Xóa sản phẩm thất bại',
                    'timestamp' => now()->format('d-m-Y H:i:s')
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Xóa sản phẩm thành công',
                'timestamp' => now()->format('d-m-Y H:i:s')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'timestamp' => now()->format('d-m-Y H:i:s')
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa sản phẩm',
                'errors' => $e->getMessage(),
                'timestamp' => now()->format('d-m-Y H:i:s')
            ], 500);
        }
    }
}
