<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Exception;

class CategoryService
{
    /**
     * Lấy danh sách tất cả danh mục (có phân trang)
     *
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     * @throws Exception
     */
    public function getAllCategories($perPage = 10)
    {
        try {
            return Category::with(['products', 'parent', 'children'])->paginate($perPage);
        } catch (Exception $e) {
            Log::error('Error retrieving categories: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy thông tin chi tiết một danh mục
     *
     * @param int $id
     * @return Category|null
     * @throws Exception
     */
    public function getCategoryById($id)
    {
        try {
            $category = Category::with(['products', 'parent', 'children'])->find($id);
            if (!$category) {
                throw new Exception('Danh mục không tồn tại');
            }
            return $category;
        } catch (Exception $e) {
            Log::error('Error retrieving category: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Tạo mới một danh mục
     *
     * @param array $data
     * @return Category
     * @throws Exception
     */
    public function createCategory(array $data)
    {
        try {
            // Kiểm tra vòng lặp danh mục (không cho phép danh mục cha là chính nó hoặc con của nó)
            if (isset($data['parent_id']) && $data['parent_id']) {
                $parent = Category::find($data['parent_id']);
                if (!$parent) {
                    throw new Exception('Danh mục cha không tồn tại');
                }
            }

            $category = Category::create($data);
            $category->load(['products', 'parent', 'children']);
            return $category;
        } catch (Exception $e) {
            Log::error('Error creating category: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin một danh mục
     *
     * @param int $id
     * @param array $data
     * @return Category
     * @throws Exception
     */
    public function updateCategory($id, array $data)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                throw new Exception('Danh mục không tồn tại');
            }

            // Kiểm tra vòng lặp danh mục
            if (isset($data['parent_id'])) {
                if ($data['parent_id'] == $id) {
                    throw new Exception('Danh mục không thể là cha của chính nó');
                }
                if ($data['parent_id']) {
                    $parent = Category::find($data['parent_id']);
                    if (!$parent) {
                        throw new Exception('Danh mục cha không tồn tại');
                    }
                    // Kiểm tra xem parent_id có phải là con của danh mục hiện tại không
                    $childrenIds = $category->children()->pluck('id')->toArray();
                    if (in_array($data['parent_id'], $childrenIds)) {
                        throw new Exception('Danh mục cha không thể là danh mục con của chính nó');
                    }
                }
            }

            // Loại bỏ các trường null để tránh cập nhật không cần thiết
            $data = array_filter($data, function ($value) {
                return !is_null($value);
            });

            // Kiểm tra xem có thay đổi nào không
            $changes = array_intersect_key($data, $category->toArray());
            if (empty($changes)) {
                return $category;
            }

            $category->update($data);
            $category->load(['products', 'parent', 'children']);
            return $category;
        } catch (Exception $e) {
            Log::error('Error updating category: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa một danh mục
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function deleteCategory($id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                throw new Exception('Danh mục không tồn tại');
            }

            return $category->delete();
        } catch (Exception $e) {
            Log::error('Error deleting category: ' . $e->getMessage());
            throw $e;
        }
    }
}