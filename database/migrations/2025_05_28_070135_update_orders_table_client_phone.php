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
         // Cập nhật cột client_phone: đổi độ dài thành 11 và cập nhật comment
         Schema::table('orders', function (Blueprint $table) {
            $table->string('client_phone', 11)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hoàn tác: Đổi độ dài client_phone về 20 và cập nhật comment cũ
        Schema::table('orders', function (Blueprint $table) {
            $table->string('client_phone', 20)->change();
        });
    }
};
