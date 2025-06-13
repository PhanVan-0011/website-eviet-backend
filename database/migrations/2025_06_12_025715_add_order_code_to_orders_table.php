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
        // Chỉ chạy lệnh thêm cột NẾU cột 'order_code' CHƯA tồn tại
        if (!Schema::hasColumn('orders', 'order_code')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('order_code')->unique()->after('id');
            });
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Chỉ chạy lệnh xóa cột NẾU cột 'order_code' ĐANG tồn tại
            if (Schema::hasColumn('orders', 'order_code')) {
                Schema::table('orders', function (Blueprint $table) {
                    // Để rollback an toàn, cần xóa index trước khi xóa cột
                    $table->dropUnique(['order_code']);
                    $table->dropColumn('order_code');
                });
            }
        });
    }
};
