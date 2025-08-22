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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            // Chủ sở hữu giỏ: user (nếu đã đăng nhập) hoặc guest_token (nếu khách)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_token', 64)->nullable()->unique();

            // Trạng thái giỏ: active | converted | abandoned
            $table->string('status', 20)->default('active');

            // Thống kê & tạm tính (chưa áp giảm/thuế/ship)
            $table->unsignedInteger('items_count')->default(0);
            $table->unsignedInteger('items_quantity')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0); // tổng tạm tính = sum(unit_price * qty)

            // Liên kết đơn sau khi checkout
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
            // $table->softDeletes(); // bật nếu muốn có thể khôi phục giỏ đã xoá
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
