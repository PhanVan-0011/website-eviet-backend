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
        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (Blueprint $table) {
                // Thêm cột lưu Đơn vị tính đã chọn (Cái, Thùng,...)
                $table->string('unit_of_measure', 50)
                      ->after('combo_id')
                      ->comment('Đơn vị tính được chọn khi thêm vào giỏ hàng');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropColumn('unit_of_measure');
            });
        }
    }
};
