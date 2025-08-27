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
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained('product_attributes')->onDelete('cascade');
            $table->string('value'); // Ví dụ: "Size S", "Size M", "70% đường", "Thêm trân châu"
            $table->decimal('price_adjustment', 10, 2)->default(0); // Số tiền cộng thêm vào giá gốc
            $table->integer('display_order')->default(0); // Để sắp xếp thứ tự hiển thị
            $table->boolean('is_active')->default(true); // Để bật/tắt một tùy chọn (ví dụ: hết topping)
            $table->boolean('is_default')->default(false); // Để chọn làm giá trị mặc định
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
