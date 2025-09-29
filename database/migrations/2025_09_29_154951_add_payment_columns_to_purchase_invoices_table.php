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
        Schema::table('purchase_invoices', function (Blueprint $table) {
           // Cột 1: Số tiền đã trả cho NCC (Paid Amount)
            if (!Schema::hasColumn('purchase_invoices', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)
                      ->default(0.00)
                      ->after('total_amount')
                      ->comment('Số tiền đã trả cho nhà cung cấp');
            }

            // Cột 2: Số tiền công nợ còn lại (Amount Owed)
            if (!Schema::hasColumn('purchase_invoices', 'amount_owed')) {
                $table->decimal('amount_owed', 12, 2)
                      ->default(0.00)
                      ->after('paid_amount')
                      ->comment('Số tiền công nợ còn lại (Cần trả NCC)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoices', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
            if (Schema::hasColumn('purchase_invoices', 'amount_owed')) {
                $table->dropColumn('amount_owed');
            }
        });
    }
};
