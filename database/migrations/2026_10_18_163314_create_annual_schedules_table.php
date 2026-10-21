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
            $table->id()->comment('ID');
            $table->unsignedSmallInteger('target_year')->index()->comment('対象年度');
            $table->string('title')->comment('タイトル');
            $table->string('category', 50)->default('other')->index()->comment('カテゴリ');
            $table->date('start_date')->index()->comment('開始日');
            $table->date('end_date')->nullable()->comment('終了日');
            $table->string('location')->nullable()->comment('場所');
            $table->string('status', 30)->default('planned')->index()->comment('ステータス');
            $table->text('description')->nullable()->comment('説明');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('作成者');
            $table->dateTime('created_at')->nullable()->comment('作成日時');
            $table->dateTime('updated_at')->nullable()->comment('更新日時');

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
