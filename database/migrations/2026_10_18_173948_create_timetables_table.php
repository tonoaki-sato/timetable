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
        Schema::create('timetables', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->date('target_date')->unique()->comment('対象日');
            $table->string('name')->comment('タイムテーブル名');
            $table->text('description')->nullable()->comment('説明');
            $table->time('start_time')->comment('開始時間');
            $table->time('end_time')->comment('終了時間');
            $table->json('road_periods')->nullable()->comment('道路規制時間帯[]');
            $table->json('festival_periods')->nullable()->comment('お祭り時間帯[]');
            $table->string('status', 30)->default('draft')->comment('ステータス');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('作成者');
            $table->dateTime('created_at')->nullable()->comment('作成日時');
            $table->dateTime('updated_at')->nullable()->comment('更新日時');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};
