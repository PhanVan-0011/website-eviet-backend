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
       Schema::table('orders', function (Blueprint $table) {
            // Lưu tổng số tiền đã được giảm giá trên toàn bộ đơn hàng
            $table->decimal('discount_amount', 15, 2)->default(0)->after('shipping_fee');
            
            // Lưu số tiền cuối cùng khách hàng phải trả
            // (total_amount + shipping_fee - discount_amount)
            $table->decimal('grand_total', 15, 2)->default(0)->after('discount_amount');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'grand_total']);
        });
    }
};
