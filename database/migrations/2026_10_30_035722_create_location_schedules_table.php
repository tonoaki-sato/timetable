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
            $table->dateTime('start_at')->comment('利用可能開始日時');
            $table->dateTime('end_at')->comment('利用可能終了日時');
            $table->string('availability_status', 30)->default('available')->comment('確保ステータス: available(利用可・確保済), unavailable(未確保・調整中)');
            $table->string('title')->nullable()->comment('枠タイトル（貸切枠・資材置場利用可能枠等）');
            $table->text('note')->nullable()->comment('補足メモ');
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
