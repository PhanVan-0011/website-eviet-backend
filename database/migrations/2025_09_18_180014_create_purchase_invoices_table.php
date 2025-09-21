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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_code', 50)->unique();
            $table->timestamp('invoice_date'); 
            $table->integer('total_quantity'); // Tổng số lượng sản phẩm
            $table->integer('total_items'); // Tổng số mặt hàng
            $table->decimal('subtotal_amount', 12, 2); // Tổng tiền hàng (trước giảm giá)
            $table->decimal('discount_amount', 12, 2)->default(0); // Giảm giá
            $table->text('notes')->nullable(); // Ghi chú


            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', ['draft', 'received', 'cancelled'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
