<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;


class CategoryService
{
    /**
     * Lấy danh sách tất cả danh mục với phân trang thủ công, tìm kiếm và sắp xếp
     */
    public function getAllCategories($request)
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
            $currentPage = max(1, (int) $request->input('page', 1));
            $keyword = (string) $request->input('keyword', '');
            $query = Category::query();

            if (!empty($keyword)) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                });
            }

            $query->orderBy('id', 'desc');

            $query->with(['parent', 'children'])->withCount('products');
            $total = $query->count();

            $offset = ($currentPage - 1) * $perPage;
            $categories = $query->skip($offset)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

            return [
                'data' => $categories,
                'page' => $currentPage,
                'total' => $total,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
                'pre_page' => $prevPage,
            ];
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách danh mục: ' . $e->getMessage());
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
            return Category::with(['parent', 'children', 'products'])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::error("Không tìm thấy danh mục với ID: $id. " . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy thông tin danh mục: ' . $e->getMessage());
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
            Log::error("Không tìm thấy danh mục để cập nhật với ID: $id. " . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Lỗi không mong muốn khi cập nhật danh mục: ' . $e->getMessage());
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
            if ($category->products()->exists()) {
                $productCount = $category->products()->count();
                $message = "Danh mục '{$category->name}' đang được sử dụng bởi $productCount sản phẩm, không thể xóa.";
                Log::warning("Đã chặn xóa danh mục (ID: $id): " . $message);
                throw new \Exception($message);
            }
            
            return $category->delete();
        } catch (ModelNotFoundException $e) {
             Log::error("Không tìm thấy danh mục để xóa với ID: $id. " . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            if ($e instanceof ModelNotFoundException == false) {
                 Log::error("Lỗi không mong muốn khi xóa danh mục (ID: $id): " . $e->getMessage());
            }
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
            $categoriesWithProducts = Category::whereIn('id', $ids)->whereHas('products')->get();

            if ($categoriesWithProducts->isNotEmpty()) {
                $errors = $categoriesWithProducts->map(function ($category) {
                    return "Danh mục '{$category->name}' đang có sản phẩm, không thể xóa.";
                })->all();

                $errorMessage = implode(' ', $errors);
                Log::warning('Đã chặn xóa nhiều danh mục do có liên kết sản phẩm: ' . $errorMessage);
                throw new \Exception($errorMessage);
            }
            return Category::destroy($ids);
        } catch (ModelNotFoundException $e) {
            Log::error('Lỗi khi xóa nhiều danh mục: ' . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            if ($e instanceof ModelNotFoundException == false) {
                Log::error('Lỗi không mong muốn khi xóa nhiều danh mục: ' . $e->getMessage());
            }
            throw $e;
        }
    }
}
