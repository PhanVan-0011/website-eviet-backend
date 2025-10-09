<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductAttribute;

class ProductAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // --- Dữ liệu mẫu cho sản phẩm "Cà phê đá" (ID: 1) và "Coca cola" (ID: 10) ---
        $drinks = Product::whereIn('name', ['Cà phê đen', 'Cà phê sữa', 'Bạc xỉu'])->get();

        foreach ($drinks as $drink) {
            // Tạo thuộc tính "Kích cỡ"
            $sizeAttribute = ProductAttribute::create([
                'product_id' => $drink->id,
                'name' => 'Kích cỡ',
                'type' => 'select',
                'display_order' => 1,
            ]);

            // Thêm các giá trị cho "Kích cỡ"
            $sizeAttribute->values()->createMany([
                [
                    'value' => 'Size S (Nhỏ)',
                    'price_adjustment' => 0,
                    'display_order' => 1,
                    'is_default' => true,
                ],
                [
                    'value' => 'Size M (Vừa)',
                    'price_adjustment' => 3000,
                    'display_order' => 2,
                ],
                [
                    'value' => 'Size L (Lớn)',
                    'price_adjustment' => 5000,
                    'display_order' => 3,
                ]
            ]);

            // Tạo thuộc tính "Mức đường"
            $sugarAttribute = ProductAttribute::create([
                'product_id' => $drink->id,
                'name' => 'Mức đường',
                'type' => 'select',
                'display_order' => 2,
            ]);

            // Thêm các giá trị cho "Mức đường"
            $sugarAttribute->values()->createMany([
                [
                    'value' => '100% đường',
                    'price_adjustment' => 0,
                    'display_order' => 1,
                    'is_default' => true,
                ],
                [
                    'value' => '70% đường',
                    'price_adjustment' => 0,
                    'display_order' => 2,
                ],
                [
                    'value' => '50% đường',
                    'price_adjustment' => 0,
                    'display_order' => 3,
                ],
                [
                    'value' => 'Ít đường',
                    'price_adjustment' => 0,
                    'display_order' => 4,
                ]
            ]);
        }

        // --- Dữ liệu mẫu cho sản phẩm "Bánh mì thịt" (ID: 2) ---
        $banhMi = Product::find(2);
        if ($banhMi) {
            // Tạo thuộc tính "Thêm topping"
            $toppingAttribute = ProductAttribute::create([
                'product_id' => $banhMi->id,
                'name' => 'Thêm topping',
                'type' => 'checkbox', // Cho phép chọn nhiều
                'display_order' => 1,
            ]);

            // Thêm các giá trị cho "Thêm topping"
            $toppingAttribute->values()->createMany([
                [
                    'value' => 'Thêm trứng ốp la',
                    'price_adjustment' => 7000,
                    'display_order' => 1,
                ],
                [
                    'value' => 'Thêm chả',
                    'price_adjustment' => 5000,
                    'display_order' => 2,
                ],
                [
                    'value' => 'Thêm phô mai',
                    'price_adjustment' => 4000,
                    'display_order' => 3,
                    'is_active' => false, // Ví dụ topping này đang tạm hết
                ]
            ]);

            // Tạo thuộc tính "Yêu cầu khác"
            $extraRequestAttribute = ProductAttribute::create([
                'product_id' => $banhMi->id,
                'name' => 'Yêu cầu khác',
                'type' => 'checkbox',
                'display_order' => 2,
            ]);

            // Thêm các giá trị cho "Yêu cầu khác"
            $extraRequestAttribute->values()->createMany([
                [
                    'value' => 'Không hành',
                    'price_adjustment' => 0,
                    'display_order' => 1,
                ],
                [
                    'value' => 'Không ớt',
                    'price_adjustment' => 0,
                    'display_order' => 2,
                ]
            ]);
        }
    }
}
