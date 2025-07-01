<?php

namespace App\Services;

use App\Models\Product;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

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
            $perPage = max(1, min(100, (int) $request->input('limit', 25)));
            $currentPage = max(1, (int) $request->input('page', 1));

            $query = Product::query()->with('categories');

            // Áp dụng tìm kiếm nếu có từ khóa
            if ($request->filled('keyword')) {
                $keyword = $request->input('keyword');
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('size', 'like', "%{$keyword}%");
                });
            }

            // Lọc theo category_id
            if ($request->filled('category_id')) {
                $category_id = $request->input('category_id');
                $query->whereHas('categories', function ($q) use ($category_id) {
                    $q->where('categories.id', $category_id);
                });
            }
            if ($request->filled('original_price_from')) $query->where('original_price', '>=', $request->input('original_price_from'));
            if ($request->filled('original_price_to')) $query->where('original_price', '<=', $request->input('original_price_to'));
            if ($request->filled('sale_price_from')) $query->where('sale_price', '>=', $request->input('sale_price_from'));
            if ($request->filled('sale_price_to')) $query->where('sale_price', '<=', $request->input('sale_price_to'));
            if ($request->filled('stock_quantity')) $query->where('stock_quantity', $request->input('stock_quantity'));
            if ($request->filled('status')) $query->where('status', $request->input('status'));

            // Sắp xếp theo thời gian tạo mới nhất
            $query->orderBy('created_at', 'desc');

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
            Log::error('Lỗi khi lấy danh sách sản phẩm: ' . $e->getMessage());
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
        try {
            $product = Product::with('categories')->findOrFail($id);
            return $product;
        } catch (ModelNotFoundException $e) {
            Log::error("Sản phẩm với ID {$id} không tồn tại: " . $e->getMessage());
            throw new ModelNotFoundException("Sản phẩm không tồn tại");
        } catch (Exception $e) {
            Log::error("Lỗi khi lấy thông tin sản phẩm với ID {$id}: " . $e->getMessage());
            throw $e;
        }
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
        return DB::transaction(function () use ($data) {
            try {

                $categoryIds = Arr::pull($data, 'category_ids', []);

                if (isset($data['image_url']) && $data['image_url'] instanceof \Illuminate\Http\UploadedFile) {
                    $year = now()->format('Y');
                    $month = now()->format('m');
                    $slug = Str::slug($data['name'] ?? 'product');
                    $path = "products_images/{$year}/{$month}";
                    $filename = uniqid($slug . '-') . '.' . $data['image_url']->getClientOriginalExtension();
                    $data['image_url'] = $data['image_url']->storeAs($path, $filename, 'public');
                }
                $product = Product::create($data);

                if (!empty($categoryIds)) {
                    $product->categories()->attach($categoryIds);
                }
                return $product->load('categories');
            } catch (Exception $e) {
                Log::error('Lỗi khi tạo sản phẩm: ' . $e->getMessage());
                throw $e;
            }
        });
    }
    /**
     * Cập nhật thông tin một sản phẩm
     *
     * @param int $id
     * @param array $data
     * @return Product
     * @throws Exception
     */
    public function updateProduct($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            try {
                $product = Product::findOrFail($id);
                $categoryIds = Arr::pull($data, 'category_ids', null);

                if (isset($data['image_url']) && $data['image_url'] instanceof \Illuminate\Http\UploadedFile) {
                    if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                        Storage::disk('public')->delete($product->image_url);
                    }
                    $year = now()->format('Y');
                    $month = now()->format('m');
                    $slug = Str::slug($data['name'] ?? $product->name);
                    $path = "products_images/{$year}/{$month}";
                    $filename = uniqid($slug . '-') . '.' . $data['image_url']->getClientOriginalExtension();
                    $data['image_url'] = $data['image_url']->storeAs($path, $filename, 'public');
                }
                $product->update($data);

                if (is_array($categoryIds)) {
                    $product->categories()->sync($categoryIds);
                }

                return $product->refresh()->load('categories');
            } catch (Exception $e) {
                Log::error("Lỗi khi cập nhật sản phẩm với {$id}: " . $e->getMessage());
                throw $e;
            }
        });
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
        return DB::transaction(function () use ($id) {
            try {
                $product = Product::findOrFail($id);
                $usedCount = OrderDetail::where('product_id', $id)->count();
                if ($usedCount > 0) {
                    throw new Exception("Có {$usedCount} chi tiết đơn hàng đang sử dụng sản phẩm này, không thể xóa.");
                }
                $product->categories()->detach();
                return $product->delete();
            } catch (Exception $e) {
                Log::error("Lỗi khi xóa sản phẩm với {$id}: " . $e->getMessage());
                throw $e;
            }
        });
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
            $usedCount = OrderDetail::whereIn('product_id', $ids)->count();
            if ($usedCount > 0) {
                throw new \Exception("Có $usedCount sản phẩm đang được sử dụng, không thể xóa.");
            }
            $products = Product::whereIn('id', $ids)->get();
            foreach ($products as $product) {
                $product->categories()->detach();
            }
            return Product::whereIn('id', $ids)->forceDelete();
        } catch (ModelNotFoundException $e) {
            Log::error('Lỗi khi xóa nhiều sản phẩm: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        } catch (Exception $e) {
            Log::error('Lỗi không : ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}
