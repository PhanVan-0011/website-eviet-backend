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
        Schema::create('item_time_slots', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // Liên kết đến khung giờ (bắt buộc)
            $table->unsignedBigInteger('time_slot_id')->comment('Liên kết đến order_time_slots.id');
            
            // Liên kết đến sản phẩm (có thể null nếu là combo)
            $table->unsignedBigInteger('product_id')->nullable()->comment('Liên kết đến products.id');
            
            // Liên kết đến combo (có thể null nếu là sản phẩm)
            $table->unsignedBigInteger('combo_id')->nullable()->comment('Liên kết đến combos.id');

            $table->timestamps(); 

            // --- Foreign Keys ---
            // Ràng buộc khóa ngoại đến bảng khung giờ
            $table->foreign('time_slot_id')
                  ->references('id')
                  ->on('order_time_slots')
                  ->onDelete('cascade'); // Nếu xóa 1 khung giờ, các liên kết sản phẩm cũng bị xóa

            // Ràng buộc khóa ngoại đến bảng sản phẩm
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            // Ràng buộc khóa ngoại đến bảng combo
            $table->foreign('combo_id')
                  ->references('id')
                  ->on('combos')
                  ->onDelete('cascade');

            // --- Indexes ---
            // Thêm chỉ mục để tăng tốc độ truy vấn
            $table->index('time_slot_id');
            $table->index('product_id');
            $table->index('combo_id');

            // Đảm bảo không thể thêm trùng lặp (ví dụ: cùng 1 sản phẩm vào 1 khung giờ 2 lần)
            $table->unique(['time_slot_id', 'product_id', 'combo_id'], 'item_time_slot_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_time_slots');
    }
};
