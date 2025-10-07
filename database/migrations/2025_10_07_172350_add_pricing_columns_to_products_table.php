<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       // Kiểm tra xem bảng 'products' đã tồn tại chưa
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                // Giá Vốn (Cost Price) - Cập nhật tự động khi nhập hàng
                // Dùng after('base_unit') để đặt cột sau base_unit
                $table->decimal('cost_price', 12, 2)
                      ->default(0.00)
                      ->after('base_unit')
                      ->comment('Giá vốn cuối cùng hoặc giá vốn trung bình (cập nhật tự động)');

                // Giá Bán Mặc Định - Cửa Hàng (Store Price)
                $table->decimal('base_store_price', 12, 2)
                      ->default(0.00)
                      ->after('cost_price')
                      ->comment('Giá bán tại cửa hàng mặc định (cho đơn vị cơ sở)');
                
                // Giá Bán Mặc Định - Ứng dụng (App Price)
                $table->decimal('base_app_price', 12, 2)
                      ->default(0.00)
                      ->after('base_store_price')
                      ->comment('Giá bán qua ứng dụng mặc định (cho đơn vị cơ sở)');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['cost_price', 'base_store_price', 'base_app_price']);
            });
        }
    }
};
