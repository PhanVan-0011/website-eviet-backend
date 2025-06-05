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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->enum('gateway', ['COD', 'VNPAY', 'MOMO', 'ZALOPAY'])->comment('Cổng thanh toán');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending')->comment('Trạng thái thanh toán');
            $table->decimal('amount', 10, 2)->comment('Tổng số tiền đã thanh toán');
            $table->string('transaction_id')->nullable()->comment('Mã giao dịch trả về từ cổng thanh toán bạn chọn');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt thanh toán (true = đang sử dụng)');
            $table->longText('callback_data')->nullable()->comment('Dữ liệu phản hồi từ cổng thanh toán (dạng JSON/raw)');
            $table->timestamp('paid_at')->nullable()->comment('Thời điểm thanh toán thành công');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
