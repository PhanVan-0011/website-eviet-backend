<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotion;
use App\Models\Product;
use App\Models\Combo;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionRelationSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            // Lấy khuyến mãi cho sản phẩm
            $productPromotion = Promotion::where('application_type', 'products')
                ->where('code', 'SUMMER2024')
                ->first();

            // Lấy khuyến mãi cho danh mục
            $categoryPromotion = Promotion::where('application_type', 'categories')
                ->where('code', 'CATEGORY2024')
                ->first();

            // Lấy khuyến mãi cho combo
            $comboPromotion = Promotion::where('application_type', 'combos')
                ->where('code', 'SPECIAL2024')
                ->first();

            Log::info('Khuyến mãi tìm thấy:', [
                'product_promotion' => $productPromotion ? $productPromotion->code : 'Không tìm thấy',
                'category_promotion' => $categoryPromotion ? $categoryPromotion->code : 'Không tìm thấy',
                'combo_promotion' => $comboPromotion ? $comboPromotion->code : 'Không tìm thấy'
            ]);

            // Lấy sản phẩm đầu tiên
            $product = Product::first();
            if ($product && $productPromotion) {
                DB::table('promotion_products')->insert([
                    'promotion_id' => $productPromotion->id,
                    'product_id' => $product->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                Log::info('Đã thêm sản phẩm vào khuyến mãi: ' . $product->name);
            } else {
                Log::warning('Không tìm thấy sản phẩm hoặc khuyến mãi sản phẩm');
            }

            // Lấy danh mục đầu tiên
            $category = Category::first();
            if ($category && $categoryPromotion) {
                DB::table('promotion_categories')->insert([
                    'promotion_id' => $categoryPromotion->id,
                    'category_id' => $category->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                Log::info('Đã thêm danh mục vào khuyến mãi: ' . $category->name);
            } else {
                Log::warning('Không tìm thấy danh mục hoặc khuyến mãi danh mục');
            }

            // Lấy combo đầu tiên
            $combo = Combo::first();
            if ($combo && $comboPromotion) {
                DB::table('promotion_combos')->insert([
                    'promotion_id' => $comboPromotion->id,
                    'combo_id' => $combo->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                Log::info('Đã thêm combo vào khuyến mãi: ' . $combo->name);
            } else {
                Log::warning('Không tìm thấy combo hoặc khuyến mãi combo');
            }

            DB::commit();
            Log::info('Đã thêm thành công các quan hệ khuyến mãi');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi thêm quan hệ khuyến mãi: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
