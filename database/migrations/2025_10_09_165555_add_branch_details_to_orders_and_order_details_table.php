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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('user_id')->comment('Gắn đơn hàng với chi nhánh (Quan trọng cho tồn kho và thống kê)')->constrained('branches');
        });

        //Cập nhật bảng 'order_details'
        Schema::table('order_details', function (Blueprint $table) {
            // Thêm cột unit_of_measure sau product_id
            $table->string('unit_of_measure', 50)->after('product_id')->comment('Lưu tên đơn vị tính đã được bán (Ví dụ: "Lon" hay "Thùng")');

            // Thêm cột attributes_snapshot (JSON) sau unit_price
            $table->json('attributes_snapshot')->nullable()->after('unit_price')->comment('Lưu JSON snapshot của các tùy chọn đã chọn (Size, Đường, v.v.)');

            // Thêm cột discount_amount sau attributes_snapshot
            $table->decimal('discount_amount', 15, 2)->default(0.00)->after('attributes_snapshot')->comment('Lưu giảm giá áp dụng riêng cho dòng sản phẩm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Rollback bảng 'orders'
        Schema::table('orders', function (Blueprint $table) {
            // Loại bỏ khóa ngoại trước
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        // 2. Rollback bảng 'order_details'
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['unit_of_measure', 'attributes_snapshot', 'discount_amount']);
        });
    }
};
