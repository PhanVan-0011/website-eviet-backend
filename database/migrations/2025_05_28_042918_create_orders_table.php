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
        // Tạo bảng orders - Lưu thông tin tổng quan về đơn hàng
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->dateTime('order_date')->useCurrent()->comment('Thời gian đặt hàng');
            $table->decimal('total_amount', 10, 2)->comment('Tổng giá trị đơn hàng');
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending')->comment('Trạng thái đơn hàng');
            $table->string('client_name', 50)->comment('Tên khách hàng');
            $table->string('client_phone', 20)->comment('Số điện thoại ');
            $table->string('shipping_address', 255)->comment('Địa chỉ giao hàng');
            $table->decimal('shipping_fee', 10, 2)->default(0.00)->comment('Phí vận chuyển');
            $table->timestamp('cancelled_at')->nullable()->comment('Thời gian hủy đơn hàng');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
