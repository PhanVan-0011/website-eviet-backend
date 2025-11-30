<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

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

            // Bổ sung các tham số lọc mới
            $status = $request->input('status');
            $parentId = $request->input('parent_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $type = $request->input('type'); // Lọc theo loại danh mục: product, post, all

            $query = Category::query();

            // Lọc theo trạng thái
            if (isset($status)) {
                $query->where('status', (int) $status);
            }

            // Lọc theo loại danh mục
            if (!empty($type)) {
                $query->where('type', $type);
            }

            // Lọc theo danh mục cha
            if (isset($parentId) && $parentId !== 'all') {
                if ($parentId === 'null') {
                    $query->whereNull('parent_id');
                } else {
                    $query->where('parent_id', (int) $parentId);
                }
            }

            if (!empty($keyword)) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                });
            }
            // Lọc theo ngày tạo
            if (!empty($startDate)) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if (!empty($endDate)) {
                $query->whereDate('created_at', '<=', $endDate);
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

            // Lấy các trường cần cập nhật từ $data, loại bỏ 'icon'
            $updateData = collect($data)->only(['name', 'status', 'parent_id', 'description'])->toArray();

            $category->update($updateData);

            // Xử lý icon nếu có
            if (isset($data['icon']) && $data['icon'] instanceof UploadedFile) {
                // Xóa icon cũ nếu tồn tại
                $existingIcon = $category->icon;
                if ($existingIcon) {
                    $this->imageService->delete($existingIcon->image_url, 'categories');
                    $existingIcon->delete();
                }

                // Lưu icon mới
                $iconPath = $this->imageService->store($data['icon'], 'categories', $category->name);
                if ($iconPath) {
                    $category->icon()->create([
                        'image_url' => $iconPath,
                        'is_featured' => true,
                    ]);
                }
            }

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
            // Sử dụng DB::transaction để đảm bảo tính toàn vẹn
            return DB::transaction(function () use ($id) {
                $category = Category::with('products', 'posts', 'icon')->findOrFail($id);

                if ($category->products->isNotEmpty()) {
                    $productCount = $category->products->count();
                    $message = "Danh mục '{$category->name}' đang được sử dụng bởi $productCount sản phẩm, không thể xóa.";
                    Log::warning("Đã chặn xóa danh mục (ID: $id): " . $message);
                    throw new Exception($message);
                }

                if ($category->posts->isNotEmpty()) {
                    $postCount = $category->posts->count();
                    $message = "Danh mục '{$category->name}' đang được sử dụng bởi $postCount bài viết, không thể xóa.";
                    Log::warning("Đã chặn xóa danh mục (ID: $id): " . $message);
                    throw new Exception($message);
                }

                if ($category->icon) {
                    $this->imageService->delete($category->icon->image_url, 'categories');
                    $category->icon()->delete();
                }

                return $category->delete();
            });
        } catch (ModelNotFoundException $e) {
            Log::error("Không tìm thấy danh mục để xóa với ID: $id. " . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            if (!$e instanceof ModelNotFoundException) {
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
            return DB::transaction(function () use ($ids) {
                // Tải các danh mục cần xóa cùng với mối quan hệ products, posts và icon
                $categoriesToDelete = Category::whereIn('id', $ids)
                    ->with(['products', 'posts', 'icon'])
                    ->get();

                // Kiểm tra xem có danh mục nào đang có sản phẩm hoặc bài viết không
                $categoriesInUse = $categoriesToDelete->filter(function ($category) {
                    return $category->products->isNotEmpty() || $category->posts->isNotEmpty();
                });

                if ($categoriesInUse->isNotEmpty()) {
                    $errors = $categoriesInUse->map(function ($category) {
                        $messages = [];
                        if ($category->products->isNotEmpty()) {
                            $messages[] = "{$category->products->count()} sản phẩm";
                        }
                        if ($category->posts->isNotEmpty()) {
                            $messages[] = "{$category->posts->count()} bài viết";
                        }
                        return "Danh mục '{$category->name}' đang được sử dụng bởi " . implode(' và ', $messages) . ", không thể xóa.";
                    })->all();

                    throw new Exception(implode(' ', $errors));
                }

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
            });
        } catch (Exception $e) {
            Log::error("Lỗi khi xóa nhiều danh mục: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy danh sách danh mục theo loại (dùng cho dropdown khi thêm/sửa sản phẩm hoặc bài viết)
     * 
     * Logic:
     * - Khi forType = 'product': lấy categories có type='product' + type='all'
     * - Khi forType = 'post': lấy categories có type='post' + type='all'
     * 
     * @param string $forType Loại: 'product' hoặc 'post'
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCategoriesForType(string $forType)
    {
        try {
            // Chỉ lấy categories đang active
            // Lấy categories có type = $forType HOẶC type = 'all'
            // Ví dụ: forType='product' → lấy type='product' + type='all'
            return Category::where('status', 1)
                ->where(function ($query) use ($forType) {
                    $query->where('type', $forType)
                        ->orWhere('type', Category::TYPE_ALL);
                })
                ->with(['parent', 'icon'])
                ->orderBy('name', 'asc')
                ->get();
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh mục theo loại: ' . $e->getMessage());
            throw $e;
        }
    }
}
