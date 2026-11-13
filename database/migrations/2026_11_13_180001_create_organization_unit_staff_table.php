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
        Schema::create('organization_unit_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_unit_id')->constrained('organization_units')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete()->comment('正規スタッフID');
            $table->string('role', 100)->nullable()->comment('役割');
            $table->integer('display_order')->default(0)->comment('組織内スタッフ表示順（DnD設定）');

            // 2038年問題対策
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['organization_unit_id', 'display_order']);
            $table->index('staff_id');
            $table->unique(['organization_unit_id', 'staff_id'], 'unit_staff_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_unit_staff');
    }
};

