<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;


class CategoryService
{
    /**
     * @var ImageService
     */
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    /**
     * Lấy danh sách tất cả danh mục với phân trang thủ công, tìm kiếm và sắp xếp
     */
    public function getAllCategories($request)
    {
        try {
            // Chuẩn hóa các tham số đầu vào
            $perPage = max(1, min(100, (int) $request->input('limit', 10)));
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

            $query->with(['parent', 'children', 'icon'])->withCount('products');
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


    public function getCategoryById($id)
    {
        try {
            return Category::with(['parent', 'children', 'products', 'icon'])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::error("Không tìm thấy danh mục với ID: $id. " . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy thông tin danh mục: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Tạo mới một danh mục và xử lý icon nếu có.
     *
     * @param array $data Dữ liệu danh mục
     * @return \App\Models\Category
     */
    public function createCategory(array $data): Category
    {
        try {
            $category = Category::create($data);

            if (isset($data['icon']) && $data['icon'] instanceof UploadedFile) {
                $iconFile = $data['icon'];
                $iconPath = $this->imageService->store($iconFile, 'categories', $category->name);

                $category->icon()->create([
                    'image_url' => $iconPath,
                    'is_featured' => true,
                ]);
            }

            return $category->load('icon');
        } catch (Exception $e) {
            Log::error('Lỗi không mong muốn khi tạo danh mục: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin một danh mục và icon nếu có.
     *
     * @param int $id ID của danh mục
     * @param array $data Dữ liệu cập nhật
     * @return \App\Models\Category
     */
    public function updateCategory($id, array $data): Category
    {
        try {
            $category = Category::findOrFail($id);
           // Bước 1: Cập nhật thông tin cơ bản của danh mục trước
        $category->update($data);

        // Bước 2: Sau đó, xử lý icon
        if (isset($data['icon'])) {
            // Xóa icon cũ nếu tồn tại
            if ($category->icon) {
                // Xóa file vật lý
                $this->imageService->delete($category->icon->image_url, 'categories');
                // Xóa bản ghi trong database
                $category->icon()->delete();
            }

            // Lưu icon mới
            $iconPath = $this->imageService->store($data['icon'], 'categories', $category->name);
            
            // Tạo bản ghi mới cho icon
            $category->icon()->create([
                'image_url' => $iconPath,
            ]);
        }
        
        // Bước 3: Tải lại các mối quan hệ và trả về đối tượng đã cập nhật
        return $category->refresh()->load(['parent', 'children', 'icon']);
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
    public function deleteCategory($id)
    {
        try {
            $category = Category::findOrFail($id);
            if ($category->icon) {
                $this->imageService->delete($category->icon->image_url, 'categories');
                $category->icon()->delete();
            }
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
    public function multiDelete($ids)
    {
        try {
            // Chuyển chuỗi ID thành mảng số nguyên
            $ids = is_string($ids) ? array_map('intval', explode(',', $ids)) : (array) $ids;

            // Tải các danh mục cần xóa cùng với mối quan hệ products và icon
            $categoriesToDelete = Category::whereIn('id', $ids)
                ->with(['products', 'icon'])
                ->get();

            // Kiểm tra xem có danh mục nào đang có sản phẩm không
            $categoriesWithProducts = $categoriesToDelete->filter(function ($category) {
                return $category->products->isNotEmpty();
            });

            if ($categoriesWithProducts->isNotEmpty()) {
                $errors = $categoriesWithProducts->map(function ($category) {
                    $productCount = $category->products->count();
                    return "Danh mục '{$category->name}' đang được sử dụng bởi $productCount sản phẩm, không thể xóa.";
                })->all();

                throw new \Exception(implode(' ', $errors));
            }

            // Nếu không có liên kết sản phẩm, tiến hành xóa
            $count = 0;
            foreach ($categoriesToDelete as $category) {
                // Xóa icon trước khi xóa danh mục
                if ($category->icon) {
                    $this->imageService->delete($category->icon->image_url, 'categories');
                    $category->icon()->delete();
                }
                $category->delete();
                $count++;
            }

            return $count;
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa nhiều danh mục: " . $e->getMessage());
        }
    }
}
