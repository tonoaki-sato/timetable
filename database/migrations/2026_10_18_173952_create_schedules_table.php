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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->string('title')->comment('タイトル（予定名・会議名）');
            $table->string('category', 50)->default('other')->index()->comment('カテゴリ（committee, permit, briefing, operation, other）');
            $table->date('start_date')->index()->comment('開始日・開催日');
            $table->date('end_date')->nullable()->comment('終了日（複数日期間の場合）');
            $table->time('start_time')->nullable()->comment('開始時刻');
            $table->time('end_time')->nullable()->comment('終了時刻');
            $table->string('location')->nullable()->comment('開催場所・実施場所');
            $table->string('status', 30)->default('planned')->index()->comment('ステータス（planned, in_progress, completed, cancelled）');
            $table->text('description')->nullable()->comment('説明・議題・内容・アジェンダ');
            $table->foreignId('timetable_id')->nullable()->unique()->constrained('timetables')->cascadeOnDelete()->comment('連動する運行スケジュールID（タイムテーブル）');
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete()->comment('担当スタッフ／主催スタッフ');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('登録ユーザー');
            $table->dateTime('created_at')->nullable()->comment('作成日時');
            $table->dateTime('updated_at')->nullable()->comment('更新日時');

            $table->index(['start_date', 'start_time']);
            $table->index(['category', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
