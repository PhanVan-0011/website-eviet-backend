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
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();// id tự động
            $table->string('title', 200); // tiêu đề
            $table->string('description', 255)->nullable(); // mô tả
            $table->string('image_url', 255); // URL hình ảnh
            $table->string('link_url', 255)->nullable(); // liên kết khi nhấn vào
            $table->integer('display_order')->default(0); // thứ tự hiển thị
            $table->boolean('is_active')->default(true); // trạng thái
            $table->enum('link_type', ['promotion', 'post','product'])->default('promotion'); // loại link khuyến mãi, bài viết mới, sản phẩm mới
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sliders');
    }
};
