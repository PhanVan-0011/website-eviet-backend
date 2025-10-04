<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::create('product_unit_conversions', function (Blueprint $table) {
            $table->id();
            // Khóa ngoại liên kết với bảng products
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            $table->string('unit_name', 50)->comment('Tên đơn vị thay thế (VD: Thùng, Hộp, Bao)');
            $table->decimal('conversion_factor', 10, 4)->comment('Hệ số chuyển đổi sang đơn vị cơ sở (VD: 1 Thùng = 48 Cái)');
            
            $table->boolean('is_purchase_unit')->default(true)->comment('Có được phép dùng khi nhập hàng');
            $table->boolean('is_sales_unit')->default(false)->comment('Có được phép dùng khi bán hàng');
            
            $table->timestamps();

            // Ràng buộc DUY NHẤT: 1 sản phẩm chỉ có 1 quy tắc cho 1 tên đơn vị
            $table->unique(['product_id', 'unit_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_unit_conversions');
    }
};
