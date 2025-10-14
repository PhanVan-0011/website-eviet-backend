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
use App\Models\ProductUnitConversion;
use App\Models\Branch;

class ProductService
{
    protected ImageService $imageService;
     private array $generatedCodes = [];

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
                    $q->where('product_code', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
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
             return Product::with([
                'categories', 
                'images', 
                'attributes.values', 
                'unitConversions',
                'branches'
             ])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::error("Sản phẩm với ID {$id} không tồn tại: " . $e->getMessage());
            throw new ModelNotFoundException("Sản phẩm không tồn tại");
        } catch (Exception $e) {
            Log::error("Lỗi khi lấy thông tin sản phẩm với ID {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Tạo mã SKU duy nhất bằng cách tìm số lớn nhất hiện có và +1.
     * @param string $prefix Tiền tố của mã (VD: 'SP')
     * @return string Mã duy nhất được tạo ra
     */
    public function generateUniqueCode(string $prefix = 'SP'): string
    {
        // Lấy số lớn nhất từ product_code
        $lastProduct = Product::where('product_code', 'LIKE', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(product_code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();
        $maxProductNum = $lastProduct ? (int) substr($lastProduct->product_code, strlen($prefix)) : 0;
        
        // Lấy số lớn nhất từ unit_code
        $lastUnit = ProductUnitConversion::where('unit_code', 'LIKE', "{$prefix}%")
             ->orderByRaw('CAST(SUBSTRING(unit_code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();
        $maxUnitNum = $lastUnit ? (int) substr($lastUnit->unit_code, strlen($prefix)) : 0;
        
        // Lấy số lớn nhất từ các mã vừa được tạo trong request này
        $maxGeneratedNum = 0;
        if (!empty($this->generatedCodes)) {
            $generatedNums = array_map(function($code) use ($prefix) {
                return (int) substr($code, strlen($prefix));
            }, $this->generatedCodes);
            $maxGeneratedNum = max($generatedNums);
        }

        // Tìm số lớn nhất trong cả 3 nguồn và tăng lên 1
        $nextNum = max($maxProductNum, $maxUnitNum, $maxGeneratedNum) + 1;
        
        $newCode = $prefix . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        
        // Lưu lại mã vừa tạo để kiểm tra cho lần gọi tiếp theo
        $this->generatedCodes[] = $newCode;

        return $newCode;
    }
public function createProduct(array $data): Product
    {
        $this->generatedCodes = []; 
        return DB::transaction(function () use ($data) {
            
            // === THAY ĐỔI: Xử lý logic chọn tất cả chi nhánh ===
            $applyToAllBranches = Arr::pull($data, 'apply_to_all_branches', false);
            $branchIds = Arr::pull($data, 'branch_ids', []);
            // ===================================================
            
            $unitConversionsData = Arr::pull($data, 'unit_conversions', []);

            if (empty($data['product_code'])) {
                $data['product_code'] = $this->generateUniqueCode('SP');
            } else {
                $this->generatedCodes[] = $data['product_code'];
            }

            foreach ($unitConversionsData as $key => $unitData) {
                // tự sinh mã
                if (empty($unitData['unit_code'])) {
                    $unitConversionsData[$key]['unit_code'] = $this->generateUniqueCode('SP');
                } else {
                    $this->generatedCodes[] = $unitData['unit_code'];
                }
                // Tự tính giá
                $factor = (float) ($unitData['conversion_factor'] ?? 1);
                if (!isset($unitData['store_price']) || $unitData['store_price'] === null) {
                    $unitConversionsData[$key]['store_price'] = (float) $data['base_store_price'] * $factor;
                }
                if (!isset($unitData['app_price']) || $unitData['app_price'] === null) {
                    $unitConversionsData[$key]['app_price'] = (float) $data['base_app_price'] * $factor;
                }
            }

            try {
                $categoryIds = Arr::pull($data, 'category_ids', []);
                $attributesData = Arr::pull($data, 'attributes', []);
                $images = Arr::pull($data, 'image_url', []);
                $featuredImageIndex = Arr::pull($data, 'featured_image_index', 0); 

                $product = Product::create($data);

                if (!empty($categoryIds)) {
                    $product->categories()->attach($categoryIds);
                }

                // === THAY ĐỔI: Phân bổ cho tất cả hoặc các chi nhánh được chọn ===
                if ($applyToAllBranches) {
                    $branchIds = Branch::where('active', true)->pluck('id')->all();
                }
                if (!empty($branchIds)) {
                    $product->branches()->attach($branchIds);
                }
                // ===============================================================

                if (!empty($unitConversionsData)) {
                    $product->unitConversions()->createMany($unitConversionsData);
                }

                if (!empty($attributesData)) {
                    foreach ($attributesData as $attributeItem) {
                        $values = Arr::pull($attributeItem, 'values', []);
                        $attribute = $product->attributes()->create($attributeItem);
                        if (!empty($values)) {
                            $attribute->values()->createMany($values);
                        }
                    }
                }

                if (!empty($images)) {
                    foreach ($images as $index => $imageFile) {
                        $path = $this->imageService->store($imageFile, 'products', $product->name);
                        if ($path) {
                            $product->images()->create([
                                'image_url' => $path,
                                'is_featured' => ($index == $featuredImageIndex) 
                            ]);
                        }
                    }
                }
                
                Log::info("Đã tạo sản phẩm mới [ID: {$product->id}]");
                return $product->load(['images', 'categories', 'attributes.values', 'unitConversions', 'branches']);

            } catch (Exception $e) {
                Log::error('Lỗi khi tạo sản phẩm: ' . $e->getMessage());
                throw $e; 
            }
        });
    }
     
     public function updateProduct(int $id, array $data): Product
    {
        $this->generatedCodes = [];
        return DB::transaction(function () use ($id, $data) {
            try {
                $product = Product::findOrFail($id);
                $categoryIds = Arr::pull($data, 'category_ids', null);
                $unitConversionsData = Arr::pull($data, 'unit_conversions', null);
                $attributesData = Arr::pull($data, 'attributes', null);
                $applyToAllBranches = Arr::pull($data, 'apply_to_all_branches', false);
                $branchIds = Arr::pull($data, 'branch_ids', null);
                
                $newImageFiles = Arr::pull($data, 'image_url', []);
                $deletedImageIds = Arr::pull($data, 'deleted_image_ids', []);
                $featuredImageIndex = Arr::pull($data, 'featured_image_index', null);

                $product->update($data);

                if (is_array($categoryIds)) {
                    $product->categories()->sync($categoryIds);
                }
                
                if ($applyToAllBranches) {
                    $idsToSync = Branch::where('active', true)->pluck('id')->all();
                    $product->branches()->sync($idsToSync);
                } elseif (is_array($branchIds)) {
                    $product->branches()->sync($branchIds);
                }

                if (is_array($unitConversionsData)) {
                    if (isset($data['product_code'])) {
                        $this->generatedCodes[] = $data['product_code'];
                    } else {
                        $this->generatedCodes[] = $product->product_code; // Thêm mã hiện tại để tránh trùng
                    }
                    
                    // Lấy giá base mới nhất sau khi update
                    $baseStorePrice = (float) ($data['base_store_price'] ?? $product->base_store_price);
                    $baseAppPrice = (float) ($data['base_app_price'] ?? $product->base_app_price);

                    foreach ($unitConversionsData as $key => $unitData) {
                        // Tự sinh mã
                        if (empty($unitData['unit_code'])) {
                            $unitConversionsData[$key]['unit_code'] = $this->generateUniqueCode('SP');
                        } else {
                             $this->generatedCodes[] = $unitData['unit_code'];
                        }
                        
                        // Tự tính giá
                        $factor = (float) ($unitData['conversion_factor'] ?? 1);
                        if (!isset($unitData['store_price']) || $unitData['store_price'] === null) {
                            $unitConversionsData[$key]['store_price'] = $baseStorePrice * $factor;
                        }
                        if (!isset($unitData['app_price']) || $unitData['app_price'] === null) {
                             $unitConversionsData[$key]['app_price'] = $baseAppPrice * $factor;
                        }
                    }
                    $product->unitConversions()->delete();
                    $product->unitConversions()->createMany($unitConversionsData);
                }
                
                if(is_array($attributesData)) {
                    $product->attributes()->each(function ($attribute) {
                        $attribute->values()->delete();
                        $attribute->delete();
                    });
                    foreach ($attributesData as $attributeItem) {
                        $values = Arr::pull($attributeItem, 'values', []);
                        $attribute = $product->attributes()->create($attributeItem);
                        if (!empty($values)) {
                            $attribute->values()->createMany($values);
                        }
                    }
                }

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
                        $path = $this->imageService->store($imageFile, 'products', $product->name);
                        if ($path) {
                            $product->images()->create(['image_url' => $path, 'is_featured' => false]);
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

                Log::info("Đã cập nhật sản phẩm [ID: {$product->id}]");
                return $product->refresh()->load(['images', 'categories', 'featuredImage', 'attributes.values', 'unitConversions', 'branches']);

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
                $product = Product::with('images', 'attributes.values', 'unitConversions')->findOrFail($id);

                if (OrderDetail::where('product_id', $id)->exists()) {
                    throw new Exception("Sản phẩm đang được sử dụng trong đơn hàng, không thể xóa.");
                }

                // 1. Xóa file ảnh vật lý (giữ logic cũ)
                foreach ($product->images as $image) {
                    $this->imageService->delete($image->image_url, 'products');
                }

                // 2. Xóa các bản ghi liên quan (trong DB)
                // Do DB đã có ON DELETE CASCADE, các lệnh này chỉ để đảm bảo logic rõ ràng
                // và phòng trường hợp thay đổi DB sau này.
                $product->images()->delete();
                $product->attributes()->each(function ($attribute) {
                    $attribute->values()->delete();
                    $attribute->delete();
                });
                $product->unitConversions()->delete();

                // 3. Gỡ bỏ các liên kết many-to-many
                $product->categories()->detach();
                $product->branches()->detach();

                // 4. Xóa sản phẩm chính
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
        // Kiểm tra sự tồn tại và các đơn hàng liên quan trước khi vào transaction
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
                // 1. Xóa file ảnh vật lý (giữ logic cũ)
                foreach ($product->images as $image) {
                    $this->imageService->delete($image->image_url, 'products');
                }
                
                // 2. Xóa các bản ghi liên quan
                $product->images()->delete();
                $product->attributes()->each(function ($attribute) {
                    $attribute->values()->delete();
                    $attribute->delete();
                });
                $product->unitConversions()->delete();
                
                // 3. Gỡ bỏ liên kết many-to-many
                $product->categories()->detach();
                $product->branches()->detach();

                // 4. Xóa sản phẩm chính
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