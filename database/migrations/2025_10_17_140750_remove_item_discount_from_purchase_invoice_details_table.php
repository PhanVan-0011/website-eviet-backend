<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoice_details', 'item_discount')) {
                $table->dropColumn('item_discount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            // Thêm lại cột nếu rollback migration
            if (!Schema::hasColumn('purchase_invoice_details', 'item_discount')) {
                $table->decimal('item_discount', 12, 2)->default(0.00)->after('unit_price')->comment('Giảm giá cho từng mặt hàng');
            }
        });
    }
};
