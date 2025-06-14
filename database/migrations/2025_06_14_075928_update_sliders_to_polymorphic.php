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
        Schema::table('sliders', function (Blueprint $table) {
            // 1. Xóa khóa ngoại và các cột cũ không còn phù hợp.
            $table->dropForeign(['combo_id']);
            $table->dropColumn(['link_type', 'link_url', 'combo_id']);
            // 2. Thêm các cột đa hình mới.
            // - linkable_id (BIGINT UNSIGNED)
            // - linkable_type (VARCHAR)
            $table->morphs('linkable');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sliders', function (Blueprint $table) {
             // 1. Xóa các cột đa hình.
             $table->dropMorphs('linkable');
            // 2. Thêm lại các cột cũ y như trong file migration ban đầu của bạn.
            $table->enum('link_type', ['promotion', 'post','product'])->default('promotion');
            $table->string('link_url', 255)->nullable();
            $table->foreignId('combo_id')
                  ->nullable()
                  ->constrained('combos')
                  ->nullOnDelete();
        });
    }
};
