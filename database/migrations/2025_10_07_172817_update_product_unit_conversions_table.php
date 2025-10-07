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
        if (Schema::hasTable('product_unit_conversions')) {
            Schema::table('product_unit_conversions', function (Blueprint $table) {

                $table->dropColumn('initial_unit_cost');
                $table->dropColumn('is_purchase_unit');
                $table->string('unit_code', 255)->nullable()->unique()->after('unit_name')->comment('Mã hàng/SKU riêng cho đơn vị quy đổi');
                $table->decimal('store_price', 12, 2)->nullable()->after('conversion_factor')->comment('Giá bán tại cửa hàng cho đơn vị quy đổi');
                $table->decimal('app_price', 12, 2)->nullable()->after('store_price')->comment('Giá bán qua ứng dụng cho đơn vị quy đổi');
                
                // Cập nhật lại cột is_sales_unit (chắc chắn là DVT bán)
                $table->boolean('is_sales_unit')->default(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       if (Schema::hasTable('product_unit_conversions')) {
            Schema::table('product_unit_conversions', function (Blueprint $table) {
                $table->decimal('initial_unit_cost', 12, 2)->nullable()->after('conversion_factor');
                $table->boolean('is_purchase_unit')->default(true)->after('initial_unit_cost');
                
                $table->dropColumn(['unit_code', 'store_price', 'app_price']);

                // Đảm bảo is_sales_unit trở lại kiểu cũ nếu cần (hoặc giữ nguyên boolean)
                $table->boolean('is_sales_unit')->default(false)->change();
            });
        }
    }
};
