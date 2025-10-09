<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->time('start_time')->comment('Giờ bắt đầu nhận đơn (HH:MM:SS)');
            $table->time('end_time')->comment('Giờ kết thúc nhận đơn (HH:MM:SS)');
            $table->time('delivery_time')->comment('Giờ giao hàng dự kiến');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_time_slots_table_simplified');
    }
};
