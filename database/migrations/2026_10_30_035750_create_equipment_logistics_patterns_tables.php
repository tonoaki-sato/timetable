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
        Schema::create('equipment_logistics_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('パターン名称（作成・編集時に自由設定）');
            $table->string('target_type', 30)->default('any')->comment('推奨資材区分: committee, rental, personal, any');
            $table->text('description')->nullable()->comment('パターンの説明・対象資材例メモ');
            $table->integer('display_order')->default(0)->comment('表示順序');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('equipment_logistics_pattern_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pattern_id')->constrained('equipment_logistics_patterns')->cascadeOnDelete();
            $table->string('day_phase', 30)->default('pre_day')->comment('フェーズ: other, pre_day, day1_morning, day1_evening, day2_morning, day2_evening, post_day');
            $table->string('action_type', 30)->default('staging_move')->comment('アクション種別');
            $table->string('title')->comment('ステップ表示名テンプレート');
            $table->string('from_location')->nullable()->comment('移動元テキスト');
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('to_location')->nullable()->comment('移動先テキスト');
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('operator_type', 30)->default('committee')->comment('committee, event_company');
            $table->boolean('witness_required')->default(false)->comment('立会要否フラグ');
            $table->text('notes')->nullable()->comment('注意事項テンプレート');
            $table->integer('display_order')->default(0)->comment('ステップ順序');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_logistics_pattern_steps');
        Schema::dropIfExists('equipment_logistics_patterns');
    }
};
