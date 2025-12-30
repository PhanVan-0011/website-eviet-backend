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
        //Sửa bảng order_time_slots (Cấu hình ca)
        Schema::table('order_time_slots', function (Blueprint $table) {
            // Xóa cột giờ giao duy nhất cũ
            if (Schema::hasColumn('order_time_slots', 'delivery_time')) {
                $table->dropColumn('delivery_time');
            }
            // Thêm khoảng thời gian giao hàng (Ví dụ: 11:30 - 12:00)
            $table->time('delivery_start_time')->nullable()->after('end_time');
            $table->time('delivery_end_time')->nullable()->after('delivery_start_time');
        });
        //Sửa bảng orders (Lưu vết đơn hàng)
        Schema::table('orders', function (Blueprint $table) {
            // Thêm khoảng thời gian nhận hàng thực tế của đơn này
            $table->dateTime('pickup_start_time')->nullable()->after('shipping_fee');
            $table->dateTime('pickup_end_time')->nullable()->after('pickup_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_time_slots', function (Blueprint $table) {
            $table->dropColumn(['delivery_start_time', 'delivery_end_time']);
            $table->time('delivery_time')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_start_time', 'pickup_end_time']);
        });
    }
};
