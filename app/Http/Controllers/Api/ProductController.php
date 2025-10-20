<?php

namespace App\Http\Controllers\Api;

use App\Services\ProductService;
use App\Http\Requests\Api\Product\StoreProductRequest;
use App\Http\Requests\Api\Product\UpdateProductRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use App\Http\Requests\Api\Product\MultiDeleteProductRequest;
use App\Traits\FileUploadTrait;
use App\Models\Product; 

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
     */
    public function index(Request $request)
    {
        try {

            $data = $this->productService->getAllProducts($request);
            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm thành công',
                'data' => ProductResource::collection($data['data']),
                'pagination' => [
                    'page' => $data['page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'next_page' => $data['next_page'],
                    'pre_page' => $data['pre_page'],
                ],
            ], 200);
        } catch (Exception $e) {
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
            $product = $this->productService->createProduct($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Tạo sản phẩm thành công',
                'data' => new ProductResource($product),
            ], 201);
        } catch (Exception $e) {
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
    public function show(int $id)
    {
        try {
            $product = $this->productService->getProductById($id);
            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin sản phẩm thành công',
                'data' => new ProductResource($product),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy sản phẩm'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi lấy thông tin sản phẩm'], 500);
        }
    }

    /**
     * Cập nhật thông tin một sản phẩm
     */
    public function update(UpdateProductRequest $request, int $id)
    {
        try {
            $product = $this->productService->updateProduct($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật sản phẩm thành công',
                'data' => new ProductResource($product)
            ]);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy sản phẩm'], 404);
        } catch (Exception $e) {
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
    public function destroy(int $id)
    {
        try {
            $this->productService->deleteProduct($id);
            return response()->json([
                'success' => true,
                'message' => 'Xóa sản phẩm thành công',
            ]);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy sản phẩm'], 404);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
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
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
        /**
     * API tìm kiếm sản phẩm THEO TỪNG ĐƠN VỊ TÍNH cho các nghiệp vụ Nhập hàng, Bán hàng.
     */
    /**
     * API tìm kiếm sản phẩm THEO TỪNG ĐƠN VỊ TÍNH cho các nghiệp vụ Nhập hàng, Bán hàng.
     */
    public function searchForPurchase(Request $request)
    {
        $keyword = $request->input('keyword', '');
        $limit = $request->input('limit', 10); // Giới hạn số lượng sản phẩm ban đầu được truy vấn

        if (empty($keyword)) { // Vẫn giữ lại kiểm tra rỗng để tránh query không cần thiết
            return response()->json(['data' => []]);
        }

        // Tìm các sản phẩm gốc có liên quan đến từ khóa
        $products = Product::query()
            ->where('status', 1) // Chỉ tìm sản phẩm đang hoạt động
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'LIKE', "%{$keyword}%")
                      ->orWhere('product_code', 'LIKE', "%{$keyword}%")
                      ->orWhereHas('unitConversions', function ($q) use ($keyword) {
                          $q->where('unit_code', 'LIKE', "%{$keyword}%");
                      });
            })
            ->with(['featuredImage', 'branches', 'unitConversions'])
            ->take($limit)
            ->get();

        $results = collect();

        //"Trải phẳng" sản phẩm thành các dòng theo từng đơn vị tính
        foreach ($products as $product) {
            // Thêm đơn vị tính cơ sở
            if ($product->is_sales_unit) {
                $results->push([
                    'product_id'      => $product->id,
                    'display_name'    => $product->name . ' (' . $product->base_unit . ')',
                    'unit_name'       => $product->base_unit,
                    'unit_code'       => $product->product_code,
                    'image_url'       => $product->featuredImage?->image_url,
                    'cost_price'      => (float)$product->cost_price,
                    'total_stock'     => (int)$product->branches->sum('pivot.quantity'),
                ]);
            }
            // Thêm các đơn vị tính quy đổi
            foreach ($product->unitConversions as $unit) {
                if ($unit->is_sales_unit) {
                    $results->push([
                        'product_id'      => $product->id,
                        'display_name'    => $product->name . ' (' . $unit->unit_name . ')',
                        'unit_name'       => $unit->unit_name,
                        'unit_code'       => $unit->unit_code,
                        'image_url'       => $product->featuredImage?->image_url,
                        // Giá vốn được tính theo hệ số quy đổi
                        'cost_price'      => (float)($product->cost_price * $unit->conversion_factor),
                        'total_stock'     => (int)$product->branches->sum('pivot.quantity'),
                    ]);
                }
            }
        }

        //Lọc lại kết quả cuối cùng để đảm bảo khớp chính xác với từ khóa
        $filteredResults = $results->filter(function ($item) use ($keyword) {
            $keywordLower = strtolower($keyword);
            return str_contains(strtolower($item['display_name']), $keywordLower)
                || ($item['unit_code'] && str_contains(strtolower($item['unit_code']), $keywordLower));
        })->values();

        return response()->json(['data' => $filteredResults]);
    }
}
