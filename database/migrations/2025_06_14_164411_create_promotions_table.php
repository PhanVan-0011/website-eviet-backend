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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();

            // Xác định phạm vi áp dụng của khuyến mãi
            $table->enum('application_type', ['all_orders', 'specific_products', 'specific_categories'])
                  ->default('all_orders');
            
            // Loại khuyến mãi: Giảm theo % hay số tiền cố định
            $table->enum('type', ['percentage', 'fixed_amount']);
            $table->decimal('value', 15, 2); // Lưu giá trị (ví dụ: 20 cho 20%, hoặc 50000 cho 50,000đ)

            $table->decimal('min_order_value', 15, 2)->nullable(); // Giá trị đơn hàng tối thiểu
            //Giới hạn số tiền giảm tối đa ví dụ: "Giảm 20% tối đa 50k"
            $table->decimal('max_discount_amount', 15, 2)->nullable();
            // Giới hạn tổng số lần mã này có thể được sử dụng.
            $table->unsignedInteger('max_usage')->nullable(); 
            // Giới hạn số lần mỗi người dùng có thể sử dụng mã này. 
            $table->unsignedInteger('max_usage_per_user')->nullable();

            // Cho phép kết hợp với các khuyến mãi khác hay không
            $table->boolean('is_combinable')->default(false);

            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
