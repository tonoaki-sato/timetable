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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('会議・打ち合わせ名');
            $table->date('meeting_date')->index()->comment('開催日');
            $table->time('start_time')->comment('開始時刻');
            $table->time('end_time')->nullable()->comment('終了時刻');
            $table->string('location')->comment('開催場所');
            $table->foreignId('host_staff_id')->constrained('staff')->comment('主催者（スタッフ）');
            $table->text('description')->nullable()->comment('内容・議題・持ち物');
            $table->text('notice_note')->nullable()->comment('LINE告知用特記メモ（返信期日等）');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('登録者');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['meeting_date', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
