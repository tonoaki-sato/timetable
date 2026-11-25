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
        // 1. カラーコーン配置テーブル（ウェイトは1:1セットとして自動連動）
        Schema::create('cone_map_cones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->foreignId('site_base_map_id')->constrained('site_base_maps')->restrictOnDelete();
            $table->decimal('pos_x', 10, 2)->default(0.00)->comment('設置中心X座標 (PDF pt)');
            $table->decimal('pos_y', 10, 2)->default(0.00)->comment('設置中心Y座標 (PDF pt)');
            $table->string('color', 20)->default('red')->comment('コーン色 (red, yellow, blue, green, orange)');
            $table->string('label', 50)->nullable()->comment('任意識別ラベル');
            $table->string('group_key', 36)->nullable()->comment('連結グループUUID');
            $table->text('notes')->nullable()->comment('個別メモ');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['timetable_id', 'site_base_map_id'], 'cone_map_cones_tt_map_idx');
            $table->index('site_base_map_id');
            $table->index('group_key');
        });

        // 2. コーン間連結コーンバーテーブル（単独配置不可・必ず2本のコーン間を連結）
        Schema::create('cone_map_bars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->foreignId('site_base_map_id')->constrained('site_base_maps')->restrictOnDelete();
            $table->foreignId('start_cone_id')->constrained('cone_map_cones')->cascadeOnDelete();
            $table->foreignId('end_cone_id')->constrained('cone_map_cones')->cascadeOnDelete();
            $table->string('bar_style', 30)->default('yellow_black')->comment('バー種別 (yellow_black, red_white, blue_white, green_white)');
            $table->decimal('length_cm', 6, 2)->default(200.00)->comment('物理長さ (cm)');
            $table->decimal('diameter_cm', 5, 2)->default(3.40)->comment('物理太さ (cm)');
            $table->string('group_key', 36)->nullable()->comment('連結グループUUID');
            $table->text('notes')->nullable()->comment('個別メモ');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['timetable_id', 'site_base_map_id'], 'cone_map_bars_tt_map_idx');
            $table->index('start_cone_id');
            $table->index('end_cone_id');
        });

        // 3. バリケード配置テーブル（A型バリケード / 樹脂製単管バリケード）
        Schema::create('cone_map_barricades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->foreignId('site_base_map_id')->constrained('site_base_maps')->restrictOnDelete();
            $table->string('barricade_type', 30)->default('type_a')->comment('バリケード種別 (type_a, resin_pipe)');
            $table->decimal('pos_x', 10, 2)->default(0.00)->comment('中心X座標 (PDF pt)');
            $table->decimal('pos_y', 10, 2)->default(0.00)->comment('中心Y座標 (PDF pt)');
            $table->integer('rotation')->default(0)->comment('回転角度 (0-359度)');
            $table->string('label', 50)->nullable()->comment('任意識別ラベル');
            $table->text('notes')->nullable()->comment('個別メモ');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['timetable_id', 'site_base_map_id'], 'cone_map_barricades_tt_map_idx');
            $table->index('site_base_map_id');
        });

        // 4. 吹き出し（注意事項）テーブル
        Schema::create('cone_map_callouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->foreignId('site_base_map_id')->constrained('site_base_maps')->restrictOnDelete();
            $table->foreignId('target_cone_id')->nullable()->constrained('cone_map_cones')->nullOnDelete();
            $table->foreignId('target_barricade_id')->nullable()->constrained('cone_map_barricades')->nullOnDelete();
            $table->decimal('anchor_x', 10, 2)->default(0.00)->comment('引出線先端X座標 (PDF pt)');
            $table->decimal('anchor_y', 10, 2)->default(0.00)->comment('引出線先端Y座標 (PDF pt)');
            $table->decimal('box_x', 10, 2)->default(0.00)->comment('吹き出し枠左上X座標 (PDF pt)');
            $table->decimal('box_y', 10, 2)->default(0.00)->comment('吹き出し枠左上Y座標 (PDF pt)');
            $table->decimal('box_width', 10, 2)->default(76.00)->comment('吹き出し枠幅 (PDF pt)');
            $table->decimal('box_height', 10, 2)->default(28.00)->comment('吹き出し枠高さ (PDF pt)');
            $table->string('title', 100)->nullable()->comment('見出し');
            $table->text('body')->comment('注意事項本文');
            $table->string('color_theme', 20)->default('amber')->comment('配色テーマ (amber, red, blue, slate)');
            $table->decimal('font_size', 5, 2)->nullable()->comment('文字サイズpt (null時は自動)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['timetable_id', 'site_base_map_id'], 'cone_map_callouts_tt_map_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cone_map_callouts');
        Schema::dropIfExists('cone_map_barricades');
        Schema::dropIfExists('cone_map_bars');
        Schema::dropIfExists('cone_map_cones');
    }
};
