<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::create('branch_time_slot_pivot', function (Blueprint $table) {
            $table->id();
            
            // Khóa ngoại tới Chi nhánh
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            
            // Khóa ngoại tới Khung giờ
            $table->foreignId('time_slot_id')->constrained('order_time_slots')->onDelete('cascade');
            
            $table->boolean('is_enabled')->default(true);
            
            // Đảm bảo mỗi khung giờ chỉ có một thiết lập tại mỗi chi nhánh
            $table->unique(['branch_id', 'time_slot_id']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_time_slot_pivot');
    }
};
