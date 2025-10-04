<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::table('product_unit_conversions', function (Blueprint $table) {
            $table->decimal('initial_unit_cost', 12, 2)
                  ->after('conversion_factor')
                  ->nullable()
                  ->comment('Giá vốn ban đầu (ước tính) của ĐVT quy đổi này.');
        });
    }

    public function down(): void
    {
        Schema::table('product_unit_conversions', function (Blueprint $table) {
            $table->dropColumn('initial_unit_cost');
        });
    }
};
