<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Thêm cột base_unit sau cột status
            $table->string('base_unit', 50)
                  ->default('Cái')
                  ->after('status')
                  ->comment('Đơn vị cơ sở để quản lý tồn kho (Đơn vị nhỏ nhất)');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('base_unit');
        });
    }
};
