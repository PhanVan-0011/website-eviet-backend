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
        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique()->comment('Mã nhà cung cấp');
                $table->string('name', 255)->comment('Tên nhà cung cấp');
                $table->foreignId('group_id')->nullable()->constrained('supplier_groups')->nullOnDelete()->comment('Nhóm nhà cung cấp');
                $table->string('phone_number', 20)->nullable()->comment('Số điện thoại');
                $table->string('address', 255)->nullable()->comment('Địa chỉ');
                $table->string('email', 255)->nullable()->comment('Email');
                $table->string('tax_code', 50)->nullable()->comment('Mã số thuế');
                $table->text('notes')->nullable()->comment('Ghi chú');
                $table->decimal('total_purchase_amount', 12, 2)->default(0)->comment('Tổng giá trị mua hàng');
                $table->decimal('balance_due', 12, 2)->default(0)->comment('Công nợ còn lại');
                $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('Người phụ trách');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
