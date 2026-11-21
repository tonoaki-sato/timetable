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
        // 1. CADシンボル種別定義マスター (site_map_icon_definitions)
        Schema::create('site_map_icon_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('icon_key', 50)->unique()->comment('シンボル識別キー (booth_standard, tent_2x15_horiz 等)');
            $table->string('name', 100)->comment('シンボル名称');
            $table->string('category', 30)->default('booth')->comment('分類: booth, tent, vehicle, area, facility, note');
            $table->string('render_style', 30)->default('cad_tent')->comment('描画スタイル: cad_tent, cad_car, cad_hatch, cad_water, cad_note');
            $table->decimal('default_width', 10, 2)->default(13.86)->comment('規定幅 (PDF pt: A3 1/400)');
            $table->decimal('default_height', 10, 2)->default(13.86)->comment('規定高さ (PDF pt: A3 1/400)');
            $table->boolean('is_resizable')->default(false)->comment('縦横リサイズ可変フラグ');
            $table->boolean('is_rotatable')->default(true)->comment('回転可能フラグ');
            $table->unsignedInteger('rotation_step')->default(45)->comment('回転刻み角度 (45度)');
            $table->json('supported_colors')->nullable()->comment('対応カラーパレット JSON');
            $table->string('default_color', 30)->default('red')->comment('初期配置カラー');
            $table->boolean('has_hygiene_circle')->default(false)->comment('給水20m衛生円自動展開フラグ');
            $table->decimal('hygiene_radius', 10, 2)->default(138.00)->comment('衛生円半径 (PDF pt: 20m相当)');
            $table->boolean('is_hygiene_target')->default(false)->comment('衛生チェック対象フラグ');
            $table->boolean('is_preset')->default(false)->comment('プリセット初期定義フラグ（削除保護）');
            $table->string('icon_asset_path')->nullable()->comment('互換用アセットパス');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index('category');
        });

        // 2. マッピング配置テーブル (site_map_placements)
        Schema::create('site_map_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->foreignId('site_base_map_id')->constrained('site_base_maps')->restrictOnDelete();
            $table->foreignId('layout_item_id')->nullable()->constrained('layout_items')->nullOnDelete();
            $table->string('shape_type', 50)->default('booth_standard')->comment('シンボル種別キー');
            $table->string('display_text', 255)->default('')->comment('中央表示テキスト (改行可)');
            $table->decimal('font_size', 5, 2)->nullable()->comment('文字サイズpt (null時は自動最適化)');
            $table->string('color_theme', 30)->default('red')->comment('カラーテーマ (red, green, black, cyan, orange, blue)');
            $table->decimal('pos_x', 10, 2)->default(0.00)->comment('図面上X座標 (PDF pt)');
            $table->decimal('pos_y', 10, 2)->default(0.00)->comment('図面上Y座標 (PDF pt)');
            $table->decimal('width', 10, 2)->default(13.86)->comment('幅 (PDF pt)');
            $table->decimal('height', 10, 2)->default(13.86)->comment('高さ (PDF pt)');
            $table->integer('rotation')->default(0)->comment('回転角度 (0〜315)');
            $table->boolean('is_rotation_locked')->default(false)->comment('通り沿い配置による回転ロック');
            $table->boolean('show_hygiene_circle')->default(true)->comment('給水20m衛生円表示フラグ');
            $table->integer('arc_start_angle')->default(0)->comment('衛生円弧開始角度 (度)');
            $table->integer('arc_end_angle')->default(360)->comment('衛生円弧終了角度 (度)');
            $table->integer('z_index')->default(10)->comment('重なり順');
            $table->text('notes')->nullable()->comment('特記事項・個別メモ');
            $table->json('meta_data')->nullable()->comment('拡張描画属性JSON');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['timetable_id', 'site_base_map_id', 'shape_type'], 'site_map_placements_tt_map_shape_idx');
            $table->index(['timetable_id', 'site_base_map_id', 'z_index', 'id'], 'site_map_placements_tt_map_z_id_idx');
            $table->index('site_base_map_id');
            $table->index('shape_type');
            $table->index('layout_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_map_placements');
        Schema::dropIfExists('site_map_icon_definitions');
    }
};
