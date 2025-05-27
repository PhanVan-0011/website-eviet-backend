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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique()->comment('Tiêu đề bài viết');
            $table->text('content')->nullable()->comment('Nội dung bài viết');
            $table->string('slug')->unique()->comment('Đường dẫn thân thiện SEO');
            $table->boolean('status')->default(true)->comment('Trạng thái bài viết (true: hiển thị, false: ẩn)');
            $table->string('image_url')->nullable()->comment('URL hình ảnh bài viết');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
