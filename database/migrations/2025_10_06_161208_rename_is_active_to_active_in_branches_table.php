<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'is_active') && !Schema::hasColumn('branches', 'active')) {
                $table->renameColumn('is_active', 'active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'active') && !Schema::hasColumn('branches', 'is_active')) {
                $table->renameColumn('active', 'is_active');
            }
        });
    }
};
