<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_sales_unit')
                  ->default(true) // Mặc định cho phép bán đơn vị cơ sở
                  ->after('base_app_price')
                  ->comment('Cho phép bán đơn vị cơ sở (vd: Cái) hay không.');
        });
    }

     /**
     * Reverse the migrations.
     * Xóa cột 'is_sales_unit' khỏi bảng products.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_sales_unit');
        });
    }
};
