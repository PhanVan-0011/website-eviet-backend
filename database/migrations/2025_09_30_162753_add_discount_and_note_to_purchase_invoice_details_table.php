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
        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoice_details', 'unit_of_measure')) {
                $table->string('unit_of_measure', 50)->after('product_id')->comment('Đơn vị tính khi nhập hàng (Can, Thùng..)');
            }
            if (!Schema::hasColumn('purchase_invoice_details', 'item_discount')) {
                $table->decimal('item_discount', 12, 2)->default(0.00)->after('unit_price')->comment('Giảm giá cho từng mặt hàng');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoice_details', 'unit_of_measure')) {
                $table->dropColumn('unit_of_measure');
            }
            if (Schema::hasColumn('purchase_invoice_details', 'item_discount')) {
                $table->dropColumn('item_discount');
            }
        });
    }
};
