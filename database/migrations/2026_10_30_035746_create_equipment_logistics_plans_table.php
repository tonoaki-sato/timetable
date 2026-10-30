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
        Schema::create('equipment_logistics_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipments')->cascadeOnDelete();
            $table->date('target_date')->nullable()->comment('実施日');
            $table->string('day_phase', 30)->default('pre_day')->comment('フェーズ: pre_day, day1_morning, day1_evening, day2_morning, day2_evening, post_day, other');
            $table->string('action_type', 30)->default('staging_move')->comment('アクション種別: storage_out, vendor_receive, staging_move, setup, night_storage, return_storage, collection_staging, vendor_setup, vendor_pickup, other');
            $table->string('title')->comment('アクション表示名');
            $table->integer('quantity')->nullable()->comment('移動数量（分散保管時等の内訳）');
            $table->string('start_time', 10)->nullable()->comment('開始予定時刻 (HH:mm)');
            $table->string('end_time', 10)->nullable()->comment('終了予定時刻 (HH:mm)');
            $table->string('from_location')->nullable()->comment('移動元（表示テキスト）');
            $table->string('to_location')->nullable()->comment('移動先（表示テキスト）');
            $table->foreignId('from_location_id')->nullable()->constrained('logistics_locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('logistics_locations')->nullOnDelete();
            $table->string('operator_type', 30)->default('committee')->comment('作業主体: committee, event_company');
            $table->boolean('witness_required')->default(false)->comment('立会要否フラグ');
            $table->foreignId('witness_staff_id')->nullable()->constrained('staff')->nullOnDelete()->comment('立会スタッフ');
            $table->string('contact_info')->nullable()->comment('当日連絡先');
            $table->string('status', 30)->default('planned')->comment('ステータス: planned, in_progress, completed, cancelled');
            $table->text('notes')->nullable()->comment('条件・注意事項メモ');
            $table->integer('display_order')->default(0)->comment('表示順序');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_logistics_plans');
    }
};
