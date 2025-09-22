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
        Schema::table('products', function (Blueprint $table) {
             Schema::table('products', function (Blueprint $table) {
            // Xóa cột giá cũ
            $table->dropColumn('original_price');
            $table->dropColumn('sale_price');
            // Xóa cột tồn kho cũ
            $table->dropColumn('stock_quantity');
            // Xóa cột size
            $table->dropColumn('size');
        });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Thêm lại các cột cũ nếu cần rollback
            $table->decimal('original_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('size', 10)->nullable();
        });
    }
};
