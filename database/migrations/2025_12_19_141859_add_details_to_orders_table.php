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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'time_slot_id')) {
                $table->foreignId('time_slot_id')
                      ->nullable()
                      ->after('branch_id')
                      ->constrained('order_time_slots')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'time_slot_id')) {
                $table->dropForeign(['time_slot_id']);
                $table->dropColumn('time_slot_id');
            }
        });
    }
};
