<?php

namespace App\Services;

use App\Models\Product;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    /**
     * Lấy danh sách tất cả sản phẩm với phân trang thủ công, tìm kiếm và sắp xếp
     *
     * @param \Illuminate\Http\Request $request
     * @return array Mảng chứa dữ liệu sản phẩm và thông tin phân trang
     * @throws Exception
     */
    public function getAllProducts($request): array
    {
        try {
            // Chuẩn hóa các tham số đầu vào
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $keyword = (string) $request->input('keyword', '');
            $categoryId = $request->input('category_id');
            $originalPriceFrom = $request->input('original_price_from');
            $originalPriceTo = $request->input('original_price_to');
            $salePriceFrom = $request->input('sale_price_from');
            $salePriceTo = $request->input('sale_price_to');
            $stockQuantity = $request->input('stock_quantity');
            $status = $request->input('status');

            // Khởi tạo truy vấn cơ bản
            $query = Product::query();

            // Áp dụng tìm kiếm nếu có từ khóa
            if (!empty($keyword)) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('size', 'like', "%{$keyword}%");
                });
            }

            // Lọc theo category_id
            if (!empty($categoryId)) {
                $query->where('category_id', $categoryId);
            }
            // Lọc theo original_price_from
            if (!empty($originalPriceFrom)) {
                $query->where('original_price', '>=', $originalPriceFrom);
            }
            // Lọc theo original_price_to
            if (!empty($originalPriceTo)) {
                $query->where('original_price', '<=', $originalPriceTo);
            }
            // Lọc theo sale_price_from
            if (!empty($salePriceFrom)) {
                $query->where('sale_price', '>=', $salePriceFrom);
            }
            // Lọc theo sale_price_to
            if (!empty($salePriceTo)) {
                $query->where('sale_price', '<=', $salePriceTo);
            }
            // Lọc theo stock_quantity
            if (!empty($stockQuantity)) {
                $query->where('stock_quantity', $stockQuantity);
            }
            // Lọc theo status
            if ($status !== null && $status !== '') {
                $query->where('status', $status);
            }

            // Sắp xếp theo thời gian tạo mới nhất
            $query->orderBy('created_at', 'desc');

            // Chỉ lấy các trường cần thiết
            $query->select([
                'id',
                'name',
                'description',
                'original_price',
                'sale_price',
                'stock_quantity',
                'status',
                'image_url',
                'size', // Thêm trường size
                'created_at',
                'updated_at',
                'category_id',
            ]);

            // Tải quan hệ category
            $query->with(['category']);

            // Tính tổng số bản ghi
            $total = $query->count();

            // Thực hiện phân trang thủ công
            $offset = ($currentPage - 1) * $perPage;
            $products = $query->skip($offset)->take($perPage)->get();

            // Tính toán thông tin phân trang
            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            // Trả về mảng dữ liệu và thông tin phân trang
            return [
                'data' => $products,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'pre_page' => $prevPage,
            ];
        } catch (Exception $e) {
            Log::error('Error retrieving products: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Lấy thông tin chi tiết một sản phẩm
     *
     * @param int $id
     * @return Product|null
     * @throws Exception
     */
    public function getProductById($id)
    {
        return Product::with('category')->findOrFail($id);
    }

    /**
     * Tạo mới một sản phẩm
     *
     * @param array $data
     * @return Product
     * @throws Exception
     */
    public function createProduct(array $data): Product
    {
        try {
            // Xử lý upload ảnh nếu có file ảnh truyền lên
            if (isset($data['image_url']) && $data['image_url'] instanceof \Illuminate\Http\UploadedFile) {
                $year = now()->format('Y');
                $month = now()->format('m');
                $slug = \Illuminate\Support\Str::slug($data['name'] ?? 'product');
                $path = "products_images/{$year}/{$month}";
                $filename = uniqid($slug . '-') . '.' . $data['image_url']->getClientOriginalExtension();
                $fullPath = $data['image_url']->storeAs($path, $filename, 'public');
                $data['image_url'] = $fullPath;
            } else {
                unset($data['image_url']);
            }
            $product = Product::create($data);
            return $product->load(['category']);
        } catch (QueryException $e) {
            Log::error('Error creating product: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error creating product: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Cập nhật thông tin một sản phẩm
     *
     * @param int $id
     * @param array $data
     * @return Product
     * @throws Exception
     */
    public function updateProduct($id, array $data): Product
    {
        try {
            $product = Product::findOrFail($id);
            // if (isset($data['original_price']) && isset($data['sale_price']) && $data['sale_price'] > $data['original_price']) {
            //     throw new Exception('Giá khuyến mãi không được lớn hơn giá gốc.');
            // }
            // Xóa ảnh cũ nếu có truyền lên ảnh mới
            if (isset($data['image_url']) && $data['image_url'] instanceof \Illuminate\Http\UploadedFile) {
                if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                    Storage::disk('public')->delete($product->image_url);
                }
                $year = now()->format('Y');
                $month = now()->format('m');
                $slug = Str::slug($data['name'] ?? $product->name);
                $path = "products_images/{$year}/{$month}";
                $filename = uniqid($slug . '-') . '.' . $data['image_url']->getClientOriginalExtension();
                $fullPath = $data['image_url']->storeAs($path, $filename, 'public');
                $data['image_url'] = $fullPath;
            } else {
                unset($data['image_url']);
            }
            $product->update($data);
            return $product->refresh()->load(['category']);
        } catch (ModelNotFoundException $e) {
            Log::error('Product not found for update: ' . $e->getMessage());
            throw $e;
        } catch (QueryException $e) {
            Log::error('Error updating product: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error updating product: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Xóa một sản phẩm
     *
     * @param int $id ID của sản phẩm
     * @return bool
     * @throws ModelNotFoundException
     */
    public function deleteProduct($id): bool
    {
        try {
            $product = Product::findOrFail($id);
            // Kiểm tra xem sản phẩm có đang được dùng trong order_details không
            $usedCount = OrderDetail::where('product_id', $id)->count();
            if ($usedCount > 0) {
                $message = "Có $usedCount sản phẩm đang được sử dụng, không thể xóa..";
                Log::warning('Blocked product deletion: ' . $message);
                throw new \Exception($message);
            }
            return $product->delete();
        } catch (ModelNotFoundException $e) {
            Log::error('Product not found for deletion: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error deleting product: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
    /**
     * Xóa nhiều sản phẩm cùng lúc
     *
     * @param string $ids Chuỗi ID cách nhau bởi dấu phẩy (ví dụ: "1,2,3")
     * @return int Số lượng bản ghi đã xóa
     * @throws ModelNotFoundException
     */
    public function multiDeleteProducts($ids)
    {
        try {
            $ids = array_map('intval', explode(',', $ids));

            $existingIds = Product::whereIn('id', $ids)->pluck('id')->toArray();
            $nonExistingIds = array_diff($ids, $existingIds);

            if (!empty($nonExistingIds)) {
                Log::error('IDs not found for deletion: ' . implode(',', $nonExistingIds));
                throw new ModelNotFoundException('ID cần xóa không tồn tại trong hệ thống');
            }
            // Kiểm tra sản phẩm đang được dùng trong order_details
            $usedCount = OrderDetail::whereIn('product_id', $ids)->count();
            if ($usedCount > 0) {
                throw new \Exception("Có $usedCount sản phẩm đang được sử dụng, không thể xóa.");
            }
            return Product::whereIn('id', $ids)->forceDelete();
        } catch (ModelNotFoundException $e) {
            Log::error('Error in multi-delete products: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error in multi-delete products: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}
