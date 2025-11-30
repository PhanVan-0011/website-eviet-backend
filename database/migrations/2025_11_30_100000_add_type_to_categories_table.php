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
        Schema::table('categories', function (Blueprint $table) {
            // type: 'product', 'post', 'all' (mặc định là 'all')
            $table->enum('type', ['product', 'post', 'all'])->default('all')->after('status')
                ->comment('Loại danh mục: product (sản phẩm), post (bài viết), all (tất cả)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

