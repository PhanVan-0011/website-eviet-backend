<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // CẬP NHẬT BẢNG ORDERS
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {

                // Thêm cột 'order_method' (Giao hàng/Mang đi/Tại chỗ)
                if (!Schema::hasColumn('orders', 'order_method')) {
                    $table->enum('order_method', ['delivery', 'takeaway', 'dine_in'])
                        ->default('takeaway')
                        ->after('status')
                        ->comment('delivery: Giao hàng, takeaway: Mang đi, dine_in: Tại chỗ');
                }

                // Thêm cột 'pickup_time' (Giờ hẹn lấy cho đơn Mang đi)
                if (!Schema::hasColumn('orders', 'pickup_time')) {
                    $table->dateTime('pickup_time')->nullable()->after('order_method');
                }
                $table->foreignId('pickup_location_id')
                    ->nullable() // Cho phép null (vì đơn Giao hàng sẽ không có cái này)
                    ->after('pickup_time')
                    ->constrained('pickup_locations') // Liên kết khóa ngoại
                    ->nullOnDelete(); // Nếu điểm nhận bị xóa, đơn hàng set null

                // Thêm cột 'notes' (Ghi chú đơn hàng)
                if (!Schema::hasColumn('orders', 'notes')) {
                    $table->text('notes')->nullable()->after('client_phone');
                }

                // Để khi khách chọn "Mang đi", không bắt buộc phải nhập địa chỉ
                if (Schema::hasColumn('orders', 'shipping_address')) {
                    $table->string('shipping_address')->nullable()->change();
                }
            });
            // Chạy lệnh SQL thuần để sửa cột ENUM (An toàn cho MySQL)
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft', 'pending', 'processing', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending'");
        }

        //CẬP NHẬT BẢNG ORDER_DETAILS
        if (Schema::hasTable('order_details')) {
            Schema::table('order_details', function (Blueprint $table) {
                if (Schema::hasColumn('order_details', 'product_id')) {
                    $table->unsignedBigInteger('product_id')->nullable()->change();
                }
                if (!Schema::hasColumn('order_details', 'attributes_snapshot')) {
                    $table->json('attributes_snapshot')->nullable()->after('unit_price');
                }
                if (!Schema::hasColumn('order_details', 'subtotal')) {
                    $table->decimal('subtotal', 15, 2)->default(0)->after('unit_price');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            // Revert status: Chuyển 'draft' về 'pending' trước khi quay lại ENUM cũ
            DB::table('orders')->where('status', 'draft')->update(['status' => 'pending']);
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending'");

            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'order_method')) $table->dropColumn('order_method');
                if (Schema::hasColumn('orders', 'pickup_time')) $table->dropColumn('pickup_time');
                if (Schema::hasColumn('orders', 'notes')) $table->dropColumn('notes');
            });
        }

        if (Schema::hasTable('order_details')) {
            Schema::table('order_details', function (Blueprint $table) {
                if (Schema::hasColumn('order_details', 'attributes_snapshot')) $table->dropColumn('attributes_snapshot');
                if (Schema::hasColumn('order_details', 'subtotal')) $table->dropColumn('subtotal');
            });
        }
    }
};
