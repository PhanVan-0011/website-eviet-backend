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
        // === BƯỚC 1: Thêm cột mới nếu nó chưa tồn tại ===
        if (!Schema::hasColumn('payments', 'payment_method_id')) {
            Schema::table('payments', function (Blueprint $table) {
                // Thêm cột mới và cho phép NULL tạm thời để không gây lỗi trên bảng đã có dữ liệu.
                $table->unsignedBigInteger('payment_method_id')->nullable()->after('id');
            });
        }

        // === BƯỚC 2: Di chuyển dữ liệu từ cột cũ sang cột mới ===
        if (Schema::hasColumn('payments', 'gateway')) {
            // Lấy danh sách các phương thức thanh toán để ánh xạ
            // Ví dụ: ['cod' => 1, 'vnpay' => 2]
            $methodsMap = DB::table('payment_methods')->pluck('id', 'code');

            if ($methodsMap->isNotEmpty()) {
                foreach ($methodsMap as $code => $id) {
                    // Cập nhật payment_method_id cho các dòng có gateway tương ứng
                    DB::table('payments')
                        ->whereRaw('UPPER(gateway) = ?', [strtoupper($code)])
                        ->update(['payment_method_id' => $id]);
                }
            }
        }

        // === BƯỚC 3: Hoàn thiện cấu trúc và dọn dẹp ===
        Schema::table('payments', function (Blueprint $table) {
            // Sau khi di chuyển dữ liệu, chúng ta có thể áp đặt ràng buộc NOT NULL
            if (Schema::hasColumn('payments', 'payment_method_id')) {
                 if (DB::table('payments')->whereNull('payment_method_id')->doesntExist()) {
                    $table->unsignedBigInteger('payment_method_id')->nullable(false)->change();
                 }
                 // Thêm ràng buộc khóa ngoại
                 $table->foreign('payment_method_id')
                      ->references('id')
                      ->on('payment_methods')
                      ->onDelete('restrict');
            }

            // Xóa các cột cũ nếu chúng còn tồn tại
            if (Schema::hasColumn('payments', 'gateway')) {
                $table->dropColumn('gateway');
            }
            if (Schema::hasColumn('payments', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         // Logic để rollback nếu cần
        if (Schema::hasColumn('payments', 'payment_method_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['payment_method_id']);
                $table->dropColumn('payment_method_id');
            });
        }
        // Thêm lại các cột cũ nếu chúng chưa tồn tại
        if (!Schema::hasColumn('payments', 'gateway')) {
             Schema::table('payments', function (Blueprint $table) {
                $table->string('gateway')->after('id');
                $table->boolean('is_active')->default(true)->after('transaction_id');
            });
        }
    }
};
