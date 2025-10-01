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
        if (!Schema::hasTable('supplier_groups')) {
            Schema::create('supplier_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique()->comment('Tên nhóm nhà cung cấp');
                $table->text('description')->nullable()->comment('Mô tả nhóm');
                $table->timestamps();
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_groups');
    }
};

