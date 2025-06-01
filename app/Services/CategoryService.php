<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use App\Models\Product;

class CategoryService
{
    /**
     * Lấy danh sách tất cả danh mục với phân trang thủ công, tìm kiếm và sắp xếp
     *
     * @param \Illuminate\Http\Request $request
     * @return array Mảng chứa dữ liệu danh mục và thông tin phân trang
     * @throws Exception
     */
    public function getAllCategories($request)
    {
        try {
            // Chuẩn hóa các tham số đầu vào
            $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $keyword = (string) $request->input('keyword', '');

            // Khởi tạo truy vấn cơ bản
            $query = Category::query();

            // Áp dụng tìm kiếm nếu có từ khóa
            if (!empty($keyword)) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                });
            }

            // Sắp xếp theo thời gian tạo mới nhất
            $query->orderBy('id', 'desc');



            // Tải quan hệ parent và children
            $query->with(['parent', 'children'])->withCount('products');

            // Tính tổng số bản ghi
            $total = $query->count();

            // Thực hiện phân trang thủ công
            $offset = ($currentPage - 1) * $perPage;
            $categories = $query->skip($offset)->take($perPage)->get();

            // Tính toán thông tin phân trang
            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            // Trả về mảng dữ liệu và thông tin phân trang
            return [
                'data' => $categories,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'pre_page' => $prevPage,
            ];
        } catch (Exception $e) {
            Log::error('Error retrieving categories: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy thông tin chi tiết một danh mục
     *
     * @param int $id ID của danh mục
     * @return \App\Models\Category
     * @throws ModelNotFoundException
     */
    public function getCategoryById($id)
    {
        try {
            return Category::with(['parent', 'children'])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::error('Category not found: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Error retrieving category: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Tạo mới một danh mục
     *
     * @param array $data Dữ liệu danh mục
     * @return \App\Models\Category
     * @throws QueryException
     */
    public function createCategory(array $data): Category
    {
        try {
            return Category::create($data);
        } catch (QueryException $e) {
            Log::error('Lỗi khi tạo danh mục: ' . $e->getMessage());
            throw $e; 
        } catch (Exception $e) {
            Log::error('Lỗi không mong muốn khi tạo danh mục: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin một danh mục
     *
     * @param int $id ID của danh mục
     * @param array $data Dữ liệu cập nhật
     * @return \App\Models\Category
     * @throws ModelNotFoundException
     * @throws QueryException
     */
    public function updateCategory($id, array $data): Category
    {
        try {
            $category = Category::findOrFail($id);
            $category->update($data);
            return $category->refresh()->load(['parent', 'children']);
        } catch (ModelNotFoundException $e) {
            Log::error('Category not found for update: ' . $e->getMessage());
            throw $e;
        } catch (QueryException $e) {
            Log::error('Error updating category: ' . $e->getMessage());
            throw $e; // Ném lại ngoại lệ gốc
        } catch (Exception $e) {
            Log::error('Unexpected error updating category: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa một danh mục
     *
     * @param int $id ID của danh mục
     * @return bool
     * @throws ModelNotFoundException
     */
    public function deleteCategory($id): bool
    {
        try {
            $category = Category::findOrFail($id);
            // Kiểm tra xem category có bị ràng buộc bởi sản phẩm không
            $productCount = Product::where('category_id', $id)->count();
            if ($productCount > 0) {
                $message = "Có $productCount danh mục đang được sử dụng, không thể xóa.";
                Log::warning('Blocked category deletion: ' . $message);
                throw new \Exception($message);
            }
            // Nếu không có liên kết, thực hiện xóa
            return $category->Delete();
        } catch (ModelNotFoundException $e) {
            Log::error('Category not found for deletion: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error deleting category: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Xóa nhiều danh mục cùng lúc
     *
     * @param string $ids Chuỗi ID cách nhau bởi dấu phẩy (ví dụ: "1,2,3")
     * @return int Số lượng bản ghi đã xóa
     * @throws ModelNotFoundException
     */
    public function multiDelete($ids): int
    {
        try {
            if (is_string($ids)) {
                $ids = array_map('intval', explode(',', $ids));
            }
            //Kiểm tra ID có tồn tại không
            $existingIds = Category::whereIn('id', $ids)->pluck('id')->toArray();
            $nonExistingIds = array_diff($ids, $existingIds);
            if (!empty($nonExistingIds)) {
                Log::error('IDs not found for deletion: ' . implode(',', $nonExistingIds));
                throw new ModelNotFoundException('ID cần xóa không tồn tại trong hệ thống');
            }
            //Kiểm tra xem category có bị ràng buộc bởi sản phẩm không
            $errors = [];

            foreach ($ids as $id) {
                $productCount = Product::where('category_id', $id)->count();
                if ($productCount > 0) {
                    $errors[] = "Có $productCount danh mục đang được sử dụng, không thể xóa.";
                }
            }

            if (!empty($errors)) {
                // Ghi log lỗi cụ thể
                Log::warning('Blocked category deletion due to linked products: ' . implode(' ', $errors));
                throw new \Exception(implode(' ', $errors));
            }
            //Nếu không có liên kết, thực hiện xóa
            return Category::whereIn('id', $ids)->Delete();
        } catch (ModelNotFoundException $e) {
            Log::error('Error in multi-delete: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error in multi-delete: ' . $e->getMessage());
            throw $e;
        }
    }
}
