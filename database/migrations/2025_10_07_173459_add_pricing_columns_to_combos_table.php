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
        if (Schema::hasTable('combos')) {
            Schema::table('combos', function (Blueprint $table) {
                //Giá Bán Mặc Định - Cửa Hàng (Store Price)
                $table->decimal('base_store_price', 12, 2)
                      ->default(0.00)
                      ->after('slug')
                      ->comment('Giá bán tại cửa hàng mặc định cho Combo');
                
                //Giá Bán Mặc Định - Ứng dụng (App Price)
                $table->decimal('base_app_price', 12, 2)
                      ->default(0.00)
                      ->after('base_store_price')
                      ->comment('Giá bán qua ứng dụng mặc định cho Combo');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('combos')) {
            Schema::table('combos', function (Blueprint $table) {
                $table->dropColumn(['base_store_price', 'base_app_price']);
            });
        }
    }
};
