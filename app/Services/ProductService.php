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
use App\Models\Image as ImageModel;

class ProductService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
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

            $query = Product::query()->with(['categories', 'featuredImage']);

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
             return Product::with(['categories', 'images', 'featuredImage'])->findOrFail($id);
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
                 $images = Arr::pull($data, 'image_url', []);
                $featuredImageIndex = Arr::pull($data, 'featured_image_index', 0);
                // Tạo sản phẩm trước để lấy ID và slug
                $product = Product::create($data);
                // Xử lý upload nhiều ảnh bằng cách gọi ImageService
                if (!empty($images)) {
                    foreach ($images as $index => $imageFile) {
                        $basePath = $this->imageService->store($imageFile, 'products', $product->name);
                        if ($basePath) {
                            $product->images()->create([
                                'image_url' => $basePath,
                                'is_featured' => ($index == $featuredImageIndex)
                            ]);
                        }
                    }
                }

                // Gán danh mục
                if (!empty($categoryIds)) {
                    $product->categories()->attach($categoryIds);
                }
                Log::info("Đã tạo sản phẩm mới [ID: {$product->id}]");
                return $product->load(['images', 'categories']);
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
                $newImages = Arr::pull($data, 'image_url', []);
                $deletedImageIds = Arr::pull($data, 'deleted_image_ids', []);
                $featuredImageId = Arr::pull($data, 'featured_image_id', null);

                $product->update($data);

                if (!empty($deletedImageIds)) {
                    $imagesToDelete = ImageModel::whereIn('id', $deletedImageIds)->where('imageable_id', $product->id)->get();
                    foreach ($imagesToDelete as $image) {
                        $this->imageService->delete($image->image_url, 'products');
                        $image->delete();
                    }
                }

                if (!empty($newImages)) {
                    foreach ($newImages as $imageFile) {
                        $basePath = $this->imageService->store($imageFile, 'products', $product->name);
                        if ($basePath) {
                            $product->images()->create(['image_url' => $basePath]);
                        }
                    }
                }

                if ($featuredImageId) {
                    $product->images()->update(['is_featured' => false]);
                    ImageModel::where('id', $featuredImageId)->where('imageable_id', $product->id)->update(['is_featured' => true]);
                }

                if (is_array($categoryIds)) {
                    $product->categories()->sync($categoryIds);
                }

                Log::info("Đã cập nhật sản phẩm [ID: {$product->id}]");
                return $product->refresh()->load(['images', 'categories']);
            } catch (ModelNotFoundException $e) {
                throw $e;
            } catch (Exception $e) {
                Log::error("Lỗi khi cập nhật sản phẩm ID {$id}: " . $e->getMessage());
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
    public function deleteProduct(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            try {
                $product = Product::with('images')->findOrFail($id);

                if (OrderDetail::where('product_id', $id)->exists()) {
                    throw new Exception("Sản phẩm đang được sử dụng trong đơn hàng, không thể xóa.");
                }

                foreach ($product->images as $image) {
                    $this->imageService->delete($image->image_url, 'products');
                }

                $product->images()->delete();

                $isDeleted = $product->delete();
                Log::warning("Đã xóa sản phẩm [ID: {$id}]");
                return $isDeleted;
            } catch (ModelNotFoundException $e) {
                throw $e;
            } catch (Exception $e) {
                Log::error("Lỗi khi xóa sản phẩm ID {$id}: " . $e->getMessage());
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
     public function multiDeleteProducts(array $ids): int
    {
        return DB::transaction(function () use ($ids) {
            try {
                 // Kiểm tra ràng buộc với đơn hàng
            $usedCount = OrderDetail::whereIn('product_id', $ids)->count();
            if ($usedCount > 0) {
                throw new Exception("Có {$usedCount} sản phẩm đang được sử dụng, không thể xóa.");
            }

            // Lấy các sản phẩm và các ảnh liên quan
            $products = Product::with('images')->whereIn('id', $ids)->get();
            $deletedCount = 0;

            foreach ($products as $product) {
                //Xóa các file ảnh vật lý
                foreach ($product->images as $image) {
                    $this->imageService->delete($image->image_url, 'products');
                }              
                // Xóa các bản ghi ảnh trong bảng `images`
                $product->images()->delete();
                
                //Xóa các quan hệ trong bảng trung gian category_product)
                $product->categories()->detach();

                //Xóa chính sản phẩm đó
                if ($product->delete()) {
                    $deletedCount++;
                }
            }       
            Log::warning("{$deletedCount} sản phẩm đã bị xóa bởi người dùng [ID: " . auth()->id() . "].");
            return $deletedCount;
            
            } catch (Exception $e) {
                Log::error('Lỗi trong quá trình xóa nhiều sản phẩm tại ProductService: ' . $e->getMessage());
                throw $e;
            }
        });
        
    }
}
