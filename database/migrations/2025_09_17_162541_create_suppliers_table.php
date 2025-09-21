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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('phone_number', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('tax_code', 50)->nullable();//thuế
            $table->text('notes')->nullable();
            $table->decimal('total_purchase_amount', 12, 2)->default(0);//tổng tiền mua
            $table->decimal('balance_due', 12, 2)->default(0);//Số tiền công nợ còn phải trả
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('group_id')->nullable()->constrained('supplier_groups');//liên kết với
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
