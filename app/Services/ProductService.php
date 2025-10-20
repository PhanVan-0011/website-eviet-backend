<?php

namespace App\Services;

use App\Models\Product;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Arr;
use App\Models\Image as ImageModel;
use App\Models\ProductUnitConversion;
use App\Models\Branch;
use App\Models\PurchaseInvoiceDetail;

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
            $perPage = max(1, min(100, (int) $request->input('limit', 25)));
            $currentPage = max(1, (int) $request->input('page', 1));

            $query = Product::query()->with(['categories.icon', 'featuredImage','unitConversions', 'branches']);

            // // Tìm kiếm theo từ khóa (giữ nguyên)
            // if ($request->filled('keyword')) {
            //     $keyword = $request->input('keyword');
            //     $query->where(function ($q) use ($keyword) {
            //         $q->where('product_code', 'like', "%{$keyword}%")
            //             ->orWhere('name', 'like', "%{$keyword}%");
            //     });
            // }
             // Tìm kiếm theo từ khóa
            if ($request->filled('keyword')) {
                $keyword = $request->input('keyword');
                $query->where(function ($q) use ($keyword) {
                    $q->where('product_code', 'like', "%{$keyword}%")
                      ->orWhere('name', 'like', "%{$keyword}%")
                      // Thêm điều kiện tìm kiếm trong mã đơn vị quy đổi
                      ->orWhereHas('unitConversions', function ($subQuery) use ($keyword) {
                          $subQuery->where('unit_code', 'like', "%{$keyword}%");
                      });
                });
            }

            // Lọc theo Danh mục (giữ nguyên)
            if ($request->filled('category_id')) {
                $query->whereHas('categories', function ($q) use ($request) {
                    $q->where('categories.id', $request->input('category_id'));
                });
            }

            // Lọc theo Nhà cung cấp
            if ($request->filled('supplier_id')) {
                $query->whereHas('purchaseInvoiceDetails.invoice', function ($q) use ($request) {
                    $q->where('supplier_id', $request->input('supplier_id'));
                });
            }


            // Lọc theo khoảng ngày tạo
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->input('start_date'));
            }
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '<=', $request->input('end_date'));
            }

            // Lọc theo trạng thái Bán trực tiếp (của đơn vị cơ sở)
            if ($request->filled('is_sales_unit')) {
                $query->where('is_sales_unit', $request->input('is_sales_unit'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            $query->orderBy('created_at', 'desc');

            $total = $query->count();
            $offset = ($currentPage - 1) * $perPage;
            $products = $query->skip($offset)->take($perPage)->get();

            $lastPage = (int) ceil($total / $perPage);
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;

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
                'categories.icon',
                'images',
                'attributes.values',
                'unitConversions',
                'branches',

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
        $lastProduct = Product::where('product_code', 'LIKE', "{$prefix}%")->orderByRaw('CAST(SUBSTRING(product_code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')->first();
        $maxProductNum = $lastProduct ? (int) substr($lastProduct->product_code, strlen($prefix)) : 0;

        $lastUnit = ProductUnitConversion::where('unit_code', 'LIKE', "{$prefix}%")->orderByRaw('CAST(SUBSTRING(unit_code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')->first();
        $maxUnitNum = $lastUnit ? (int) substr($lastUnit->unit_code, strlen($prefix)) : 0;

        $maxGeneratedNum = 0;
        if (!empty($this->generatedCodes)) {
            $generatedNums = array_map(fn($code) => (int) substr($code, strlen($prefix)), $this->generatedCodes);
            $maxGeneratedNum = max($generatedNums);
        }

        $nextNum = max($maxProductNum, $maxUnitNum, $maxGeneratedNum) + 1;
        $newCode = $prefix . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        $this->generatedCodes[] = $newCode;
        return $newCode;
    }
    public function createProduct(array $data): Product
    {
        $this->generatedCodes = [];
        return DB::transaction(function () use ($data) {

            //Tách các dữ liệu mảng ra khỏi $data chính.
            $appliesToAllBranches = Arr::pull($data, 'apply_to_all_branches', false);
            $branchIds = Arr::pull($data, 'branch_ids', []);
            $unitConversionsData = Arr::pull($data, 'unit_conversions', []);
            //$specialPricesData = Arr::pull($data, 'branch_prices', []);

            //Bắt đầu logic tự động sinh mã và tính giá.
            if (empty($data['product_code'])) {
                $data['product_code'] = $this->generateUniqueCode('SP');
            } else {
                $this->generatedCodes[] = $data['product_code'];
            }

            foreach ($unitConversionsData as $key => $unitData) {
                if (empty($unitData['unit_code'])) {
                    $unitConversionsData[$key]['unit_code'] = $this->generateUniqueCode('SP');
                } else {
                    $this->generatedCodes[] = $unitData['unit_code'];
                }

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

                // Thêm 'applies_to_all_branches' vào $data để lưu
                $product = Product::create($data);


                if (!empty($categoryIds)) {
                    $product->categories()->attach($categoryIds);
                }

                if ($product->applies_to_all_branches) {
                    $allBranchIds = Branch::where('active', true)->pluck('id')->all();
                    $product->branches()->sync($allBranchIds);
                } elseif (!empty($branchIds)) {
                    $product->branches()->attach(array_unique($branchIds));
                }

                // //Lưu các giá đặc biệt theo chi nhánh (nếu có).
                // if (!empty($specialPricesData)) {
                //     $product->prices()->createMany($specialPricesData);
                // }

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
                return $product->load(['images', 'categories.icon', 'attributes.values', 'unitConversions', 'branches']);
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
                // Đồng bộ Danh mục
                if (Arr::has($data, 'category_ids')) {
                    $product->categories()->sync(Arr::get($data, 'category_ids', []));
                }

                // Đồng bộ Chi nhánh
                if (Arr::get($data, 'applies_to_all_branches')) {
                    $product->branches()->sync(Branch::where('active', true)->pluck('id')->all());
                } elseif (Arr::has($data, 'branch_ids')) {
                    $product->branches()->sync(Arr::get($data, 'branch_ids', []));
                }

                // // Đồng bộ Giá đặc biệt
                // if (Arr::has($data, 'branch_prices')) {
                //     $product->prices()->delete();
                //     $specialPricesData = Arr::get($data, 'branch_prices', []);
                //     if (!empty($specialPricesData)) {
                //         $product->prices()->createMany($specialPricesData);
                //     }
                // }

                // Đồng bộ Đơn vị quy đổi
                if (Arr::has($data, 'unit_conversions')) {
                    $unitConversionsData = Arr::get($data, 'unit_conversions', []);

                    if (isset($data['product_code'])) {
                        $this->generatedCodes[] = $data['product_code'];
                    } else {
                        $this->generatedCodes[] = $product->product_code;
                    }

                    $baseStorePrice = (float) ($data['base_store_price'] ?? $product->base_store_price);
                    $baseAppPrice = (float) ($data['base_app_price'] ?? $product->base_app_price);

                    foreach ($unitConversionsData as $key => $unitData) {
                        if (empty($unitData['unit_code'])) {
                            $unitConversionsData[$key]['unit_code'] = $this->generateUniqueCode('SP');
                        } else {
                            $this->generatedCodes[] = $unitData['unit_code'];
                        }

                        $factor = (float) ($unitData['conversion_factor'] ?? 1);
                        if (!isset($unitData['store_price']) || $unitData['store_price'] === null) {
                            $unitConversionsData[$key]['store_price'] = $baseStorePrice * $factor;
                        }
                        if (!isset($unitData['app_price']) || $unitData['app_price'] === null) {
                            $unitConversionsData[$key]['app_price'] = $baseAppPrice * $factor;
                        }
                    }
                    $product->unitConversions()->delete();
                    if (!empty($unitConversionsData)) {
                        $product->unitConversions()->createMany($unitConversionsData);
                    }
                }

                // Đồng bộ Thuộc tính
                if (Arr::has($data, 'attributes')) {
                    $attributesData = Arr::get($data, 'attributes', []);
                    $product->attributes()->each(function ($attribute) {
                        $attribute->values()->delete();
                        $attribute->delete();
                    });
                    if (!empty($attributesData)) {
                        foreach ($attributesData as $attributeItem) {
                            $values = Arr::pull($attributeItem, 'values', []);
                            $attribute = $product->attributes()->create($attributeItem);
                            if (!empty($values)) {
                                $attribute->values()->createMany($values);
                            }
                        }
                    }
                }

                // Cập nhật thông tin cơ bản của sản phẩm sau cùng
                $product->update(Arr::except($data, [
                    'category_ids',
                    'branch_ids',
                    'unit_conversions',
                    'attributes',
                    'image_url',
                    'deleted_image_ids',
                    'featured_image_index'
                ]));
                

                // Xử lý ảnh (logic không đổi)
                $newImageFiles = Arr::get($data, 'image_url', []);
                $deletedImageIds = Arr::get($data, 'deleted_image_ids', []);
                $featuredImageIndex = Arr::get($data, 'featured_image_index');

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
                return $product->refresh()->load(['images', 'categories.icon', 'featuredImage', 'attributes.values', 'unitConversions', 'branches']);
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
                    throw new Exception("Sản phẩm đã phát sinh trong Đơn hàng bán ra, không thể xóa.");
                }
                if (PurchaseInvoiceDetail::where('product_id', $id)->exists()) {
                    throw new Exception("Sản phẩm đã phát sinh trong Lịch sử nhập hàng, không thể xóa.");
                }

                foreach ($product->images as $image) {
                    $this->imageService->delete($image->image_url, 'products');
                }

                $product->images()->delete();
                $product->attributes()->each(function ($attribute) {
                    $attribute->values()->delete();
                    $attribute->delete();
                });
                $product->unitConversions()->delete();
                $product->prices()->delete();
                $product->categories()->detach();
                $product->branches()->detach();

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
        // $usedProducts = $products->filter(function ($product) {
        //     return $product->orderDetails()->exists();
        // });

        // if ($usedProducts->isNotEmpty()) {
        //     $productIds = $usedProducts->pluck('id')->implode(', ');
        //     throw new Exception("Không thể xóa các sản phẩm {$productIds} vì đã phát sinh đơn hàng.");
        // }
        $usedInOrders = OrderDetail::whereIn('product_id', $ids)->pluck('product_id')->unique();
        if ($usedInOrders->isNotEmpty()) {
            throw new Exception("Không thể xóa các sản phẩm (ID: " . $usedInOrders->implode(', ') . ") vì đã phát sinh đơn hàng bán ra.");
        }

        $usedInPurchases = PurchaseInvoiceDetail::whereIn('product_id', $ids)->pluck('product_id')->unique();
        if ($usedInPurchases->isNotEmpty()) {
            throw new Exception("Không thể xóa các sản phẩm (ID: " . $usedInPurchases->implode(', ') . ") vì đã phát sinh lịch sử nhập hàng.");
        }

        return DB::transaction(function () use ($products) {
            $deletedCount = 0;

            foreach ($products as $product) {
                foreach ($product->images as $image) {
                    $this->imageService->delete($image->image_url, 'products');
                }

                $product->images()->delete();
                $product->attributes()->each(function ($attribute) {
                    $attribute->values()->delete();
                    $attribute->delete();
                });
                $product->unitConversions()->delete();
                $product->prices()->delete();
                $product->categories()->detach();
                $product->branches()->detach();

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
