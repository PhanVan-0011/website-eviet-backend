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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            // Tham chiếu đến giỏ hàng
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();

            // Sản phẩm hoặc combo (chỉ 1 trong 2 có giá trị)
            $table->foreignId('product_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('combo_id')->nullable()->constrained()->restrictOnDelete();

            // Snapshot thông tin tại thời điểm thêm vào giỏ
            $table->string('name_snapshot');          // tên sản phẩm/combo
            $table->string('sku_snapshot')->nullable(); // mã sản phẩm/slug
            $table->json('attributes')->nullable();   // tuỳ chọn: size, đường, đá, topping...

            // Số lượng & giá
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);     // giá gốc tại thời điểm thêm (chưa giảm)

            // Ghi chú (VD: ít cay, không hành...)
            $table->string('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('product_id');
            $table->index('combo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
