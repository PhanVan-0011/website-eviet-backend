<?php

namespace App\Services;
use App\Models\BranchProductStock;
use App\Models\Category;
use App\Models\Combo;
use App\Models\OrderTimeSlot;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Http\Resources\ProductAttributeResource;

use Illuminate\Support\Facades\DB;

class MenuService
{
    const PRICE_TYPE_STORE = 'store_price'; // Giá tại quầy
    const PRICE_TYPE_APP = 'app_price';     // Giá App

    /**
     * Lấy menu đã được lọc theo chi nhánh, tồn kho, và khung giờ.
     */
    public function getMenu(int $branchId, string $priceType = self::PRICE_TYPE_STORE): array
    {
        $branchStockMap = BranchProductStock::where('branch_id', $branchId)
            ->where('quantity', '>', 0)
            ->pluck('quantity', 'product_id');
        
        $inStockProductIds = $branchStockMap->keys();
        $now = Carbon::now()->toTimeString(); 
        
        $activeTimeSlotIds = OrderTimeSlot::where('is_active', true)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->whereHas('branches', function ($query) use ($branchId) {
                // Đảm bảo Chi nhánh này được phép bán trong Ca này
                $query->where('branches.id', $branchId)
                      ->where('branch_time_slot_pivot.is_enabled', true);
            })
            ->pluck('id'); 

        $flexibleProducts = Product::where('status', true)
            ->where('is_flexible_time', true) // Logic mới
            ->whereIn('id', $inStockProductIds) // Lọc tồn kho
            ->get();

        $fixedProducts = Product::where('status', true)
            ->where('is_flexible_time', false) // Logic mới
            ->whereIn('id', $inStockProductIds) // Lọc tồn kho
            ->whereHas('timeSlots', function ($query) use ($activeTimeSlotIds) {
                $query->whereIn('order_time_slots.id', $activeTimeSlotIds);
            })
            ->get();
        
        $availableProducts = $flexibleProducts->merge($fixedProducts);

        $flexibleCombos = Combo::where('is_active', true)
            ->where('is_flexible_time', true) // Logic mới
            ->get();
        
        $fixedCombos = Combo::where('is_active', true)
            ->where('is_flexible_time', false) // Logic mới
            ->whereHas('timeSlots', function ($query) use ($activeTimeSlotIds) {
                $query->whereIn('order_time_slots.id', $activeTimeSlotIds);
            })
            ->get();

        // Gộp 2 nhóm combo
        $allAllowedCombos = $flexibleCombos->merge($fixedCombos)
            ->load(['image', 'items']);

        $availableCombos = $allAllowedCombos->filter(function ($combo) use ($branchStockMap) {
            return $this->isComboInStock($combo, $branchStockMap);
        });
        
        $availableProducts->load([
            'categories.icon', 
            'images', 
            'featuredImage', 
            'attributes.values', 
            'unitConversions' => fn($q) => $q->where('is_sales_unit', true)
        ]);

        return $this->formatMenu($availableProducts, $availableCombos, $priceType);
    }

    /**
     * [Hàm hỗ trợ] Kiểm tra xem một combo có đủ hàng trong kho hay không.
     */
    private function isComboInStock(Combo $combo, Collection $branchStockMap): bool
    {
        if ($combo->items->isEmpty()) {
            return true; 
        }

        foreach ($combo->items as $item) {
            $requiredQty = $item->pivot->quantity; 
            $stockQty = $branchStockMap->get($item->product_id, 0); 

            if ($stockQty < $requiredQty) {
                return false; 
            }
        }
        return true; // Tất cả sản phẩm con đều đủ hàng
    }

    /**
     * [Hàm hỗ trợ] Tái cấu trúc sản phẩm và combo theo format (Nhóm theo Danh mục).
     */
    private function formatMenu(Collection $products, Collection $combos, string $priceType): array
    {
        
        $categoriesMap = [];
        // Lấy tất cả danh mục đang active
        $allCategories = Category::where('status', true)->with('icon')->get()->keyBy('id');
        /** @var Product $product */ //
        foreach ($products as $product) {
            $productData = $this->formatProduct($product, $priceType);
            
            if ($product->categories->isEmpty()) {
    
                if (!isset($categoriesMap['uncategorized'])) {
                    $categoriesMap['uncategorized'] = (object)[
                        'id' => null,
                        'name' => 'Sản phẩm khác',
                        'icon' => null,
                        'products' => []
                    ];
                }
                $categoriesMap['uncategorized']->products[] = $productData;
            } else {
                // Thêm sản phẩm vào các danh mục của nó
                foreach ($product->categories as $category) {
                    if (!$allCategories->has($category->id)) continue; 

                    if (!isset($categoriesMap[$category->id])) {
                        $categoriesMap[$category->id] = (object)[
                            'id' => $category->id,
                            'name' => $category->name,
                            'icon' => $category->icon?->image_url,
                            'products' => []
                        ];
                    }
                    $categoriesMap[$category->id]->products[] = $productData;
                }
            }
        }
        
        $formattedCombos = $combos->map(function ($combo) use ($priceType) {
            return $this->formatCombo($combo, $priceType);
        });

        return [
            'categories' => array_values($categoriesMap), 
            'combos' => $formattedCombos
        ];
    }

    /**
     * [Hàm hỗ trợ] Định dạng 1 sản phẩm.
     */
    private function formatProduct(Product $product, string $priceType)
    {
        $basePriceField = ($priceType == self::PRICE_TYPE_APP) ? 'base_app_price' : 'base_store_price';

        // Xây dựng danh sách các đơn vị bán (units)
        $units = [];
        if ($product->is_sales_unit) { 
            $units[] = [
                'name' => $product->base_unit,
                'price' => (float) $product->{$basePriceField},
                'conversion_factor' => 1,
            ];
        }

        foreach ($product->unitConversions as $conversion) { 
            $priceField = ($priceType == self::PRICE_TYPE_APP) ? 'app_price' : 'store_price';
            $units[] = [
                'name' => $conversion->unit_name,
                'price' => (float) $conversion->{$priceField}, 
                'conversion_factor' => (float) $conversion->conversion_factor,
            ];
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'image' => $product->featuredImage?->image_url ?? $product->images->first()?->image_url,
            'units' => $units,
            'attributes' => ProductAttributeResource::collection($product->attributes),
        ];
    }

    /**
     * [Hàm hỗ trợ] Định dạng 1 combo.
     */
    private function formatCombo(Combo $combo, string $priceType)
    {
        $priceField = ($priceType == self::PRICE_TYPE_APP) ? 'base_app_price' : 'base_store_price';

        return [
            'id' => $combo->id,
            'name' => $combo->name,
            'description' => $combo->description,
            'image' => $combo->image?->image_url, 
            'price' => (float) $combo->{$priceField},
        ];
    }
}