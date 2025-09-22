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
       Schema::create('return_goods', function (Blueprint $table) {
            $table->id();
            $table->string('return_code', 50)->unique();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->text('reason')->nullable();
            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_goods');
    }
};
