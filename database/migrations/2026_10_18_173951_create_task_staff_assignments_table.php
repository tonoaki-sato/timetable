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
        Schema::create('task_staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_task_id')->constrained('timetable_tasks')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('role_description', 100)->nullable();
            $table->string('attendance_status', 30)->default('assigned'); // assigned / checked_in / completed / absent
            $table->text('notes')->nullable();

            // 2038年問題対策として DATETIME 型を採用
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['timetable_task_id', 'staff_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_staff_assignments');
    }
};
