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
       Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'is_flexible_time')) {
                // Thêm cột is_flexible_time, mặc định là false (không linh hoạt)
                $table->boolean('is_flexible_time')->default(false)->after('active')
                    ->comment('True: Bán linh hoạt mọi khung giờ, False: Tuân thủ giờ chặt chẽ');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'is_flexible_time')) {
                $table->dropColumn('is_flexible_time');
            }
        });
    }
};
