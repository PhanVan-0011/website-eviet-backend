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
            //Xóa các cột không còn cần thiết
            if (Schema::hasColumn('orders', 'shipping_address')) {
                $table->dropColumn('shipping_address');
            }
            if (Schema::hasColumn('orders', 'pickup_time')) {
                $table->dropColumn('pickup_time');
            }

            // Thêm cột price_type để lưu loại giá (store/app)
            if (!Schema::hasColumn('orders', 'price_type')) {
                $table->string('price_type')->default('app')->after('status')
                    ->comment('app: Giá trên ứng dụng, store: Giá bán tại quầy');
            }

            //Thêm cột & khóa ngoại cho Điểm Nhận Hàng
            if (!Schema::hasColumn('orders', 'pickup_location_id')) {
                $table->unsignedBigInteger('pickup_location_id')->nullable()->after('branch_id');
                $table->foreign('pickup_location_id')
                      ->references('id')->on('pickup_locations')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Khôi phục lại các cột cũ
            $table->string('shipping_address')->nullable();
            $table->dateTime('pickup_time')->nullable();

            // Xóa các cột mới thêm
            if (Schema::hasColumn('orders', 'price_type')) {
                $table->dropColumn('price_type');
            }
            
            if (Schema::hasColumn('orders', 'pickup_location_id')) {
                $table->dropForeign(['pickup_location_id']);
                $table->dropColumn('pickup_location_id');
            }
        });
    }
};
