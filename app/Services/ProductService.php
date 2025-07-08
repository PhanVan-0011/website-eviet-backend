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
     */
     public function updateProduct(int $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            try {
                $product = Product::findOrFail($id);

                $categoryIds = Arr::pull($data, 'category_ids', null);
                $newImageFiles = Arr::pull($data, 'image_url', []);
                $deletedImageIds = Arr::pull($data, 'deleted_image_ids', []);
                $featuredImageIndex = Arr::pull($data, 'featured_image_index', null);

                $product->update($data);

                if (!empty($deletedImageIds)) {
                    $imagesToDelete = ImageModel::whereIn('id', $deletedImageIds)
                        ->where('imageable_id', $product->id)
                        ->get();
                    
                    foreach ($imagesToDelete as $image) {
                        $this->imageService->delete($image->image_url, 'products');
                        $image->delete();
                    }
                }

                if (!empty($newImageFiles)) {
                    foreach ($newImageFiles as $imageFile) {
                        $basePath = $this->imageService->store($imageFile, 'products', $product->name);
                        if ($basePath) {
                            $product->images()->create(['image_url' => $basePath, 'is_featured' => false]);
                        }
                    }
                }

                
                $finalImages = $product->images()->orderBy('created_at')->orderBy('id')->get();

                
                if ($finalImages->isNotEmpty()) {
                    
                    $indexToFeature = $featuredImageIndex ?? 0;

                    
                    if (isset($finalImages[$indexToFeature])) {
                        $featuredImageId = $finalImages[$indexToFeature]->id;

                        
                        
                        $product->images()->update(['is_featured' => false]);
                        ImageModel::where('id', $featuredImageId)->update(['is_featured' => true]);
                    }
                }


                if (is_array($categoryIds)) {
                    $product->categories()->sync($categoryIds);
                }

                Log::info("Đã cập nhật sản phẩm [ID: {$product->id}]");
                return $product->refresh()->load(['images', 'categories', 'featuredImage']);

            } catch (ModelNotFoundException $e) {
                Log::warning("Không tìm thấy sản phẩm để cập nhật. ID: {$id}");
                throw $e;
            } catch (Exception $e) {
                Log::error("Lỗi khi cập nhật sản phẩm ID {$id}: " . $e->getMessage());
                throw $e;
            }
        });
    }
    /**
     * Xóa một sản phẩm
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
     * Xóa nhiều sản phẩm cùng lúcxóa
     * @throws ModelNotFoundException
     */
    public function multiDeleteProducts(array $ids): int
    {
        $products = Product::with('images')->whereIn('id', $ids)->get();

        if (count($products) !== count($ids)) {
            $foundIds = $products->pluck('id')->all();
            $missingIds = array_diff($ids, $foundIds);
            throw new ModelNotFoundException('Một hoặc nhiều sản phẩm không tồn tại: ' . implode(', ', $missingIds));
        }
        $usedProducts = $products->filter(function ($product) {
            return $product->orderDetails()->exists();
        });

        if ($usedProducts->isNotEmpty()) {
            $productIds = $usedProducts->pluck('id')->implode(', ');
            throw new Exception("Không thể xóa các sản phẩm {$productIds} vì đã phát sinh đơn hàng.");
        }

        return DB::transaction(function () use ($products) {
            $deletedCount = 0;

            foreach ($products as $product) {
                foreach ($product->images as $image) {
                    $this->imageService->delete($image->image_url, 'products');
                }

                $product->images()->delete();
                
                $product->categories()->detach();

                if ($product->delete()) {
                    $deletedCount++;
                }
            }
            
            if ($deletedCount > 0) {
                Log::warning("{$deletedCount} sản phẩm đã bị xóa bởi người dùng [ID: " . auth()->id() . "].");
            }
            
            return $deletedCount;
        });
    }
}