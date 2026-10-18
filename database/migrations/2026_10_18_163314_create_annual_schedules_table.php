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
        Schema::create('annual_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('target_year')->index();
            $table->string('title');
            $table->string('category', 50)->default('other')->index();
            $table->date('start_date')->index();
            $table->date('end_date')->nullable();
            $table->string('location')->nullable();
            $table->string('status', 30)->default('planned')->index();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // 2038年問題対策として DATETIME 型を採用
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['target_year', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_schedules');
    }
};
