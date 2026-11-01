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
        Schema::create('location_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->dateTime('start_at')->comment('開始年月日時分');
            $table->dateTime('end_at')->comment('終了年月日時分');
            $table->string('application_status', 30)->default('unapplied')->comment('利用申請ステータス: unapplied, applying, approved, not_required, rejected');
            $table->string('title')->nullable()->comment('枠名（例: 午前枠、夜間枠）');
            $table->text('note')->nullable()->comment('枠メモ・申請特記事項');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_schedules');
    }
};
