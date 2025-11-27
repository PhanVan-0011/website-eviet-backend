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
        Schema::table('combos', function (Blueprint $table) {
            // Thêm cột 'is_flexible_time'
            // Đặt sau cột 'applies_to_all_branches'
            $table->boolean('is_flexible_time')->default(true)->after('applies_to_all_branches')
                  ->comment('True = Bán linh hoạt (cả ngày), False = Bán theo khung giờ cố định');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('combos', function (Blueprint $table) {
            $table->dropColumn('is_flexible_time');
        });
    }
};
