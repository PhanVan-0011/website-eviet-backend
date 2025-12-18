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
        // Cần cài đặt doctrine/dbal: composer require doctrine/dbal
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
   public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                // Lưu ý: Revert lại có thể lỗi nếu đã có dữ liệu null
                // $table->unsignedBigInteger('user_id')->nullable(false)->change();
            });
        }
    }
};
