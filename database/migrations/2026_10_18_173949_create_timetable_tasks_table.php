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
        Schema::create('timetable_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable(); // 作業内容
            $table->string('lane', 100)->index(); // 担当班・レーン
            $table->string('location')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('required_people_count')->default(1);
            $table->boolean('is_at_least')->default(false); // 「〇〇名以上」フラグ
            $table->text('description')->nullable(); // 備考・特記事項
            $table->boolean('is_critical')->default(false);
            $table->boolean('requires_review')->default(false);
            $table->string('status', 30)->default('not_started')->index();

            // 2038年問題対策として DATETIME 型を採用
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['timetable_id', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_tasks');
    }
};
