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
        // 1. アイコン種別定義マスター (site_map_icon_definitions)
        Schema::create('site_map_icon_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('icon_key', 50)->unique()->comment('アイコン識別キー (例: general_tent, stall_open 等)');
            $table->string('name', 100)->comment('アイコン名称 (例: 一般テント, 露店 等)');
            $table->string('category', 30)->default('tent')->comment('分類: tent, stall, free_area, facility');
            $table->decimal('default_width', 10, 2)->default(78.00)->comment('既定幅 (px: A3 1/400 1m=26px)');
            $table->decimal('default_height', 10, 2)->default(78.00)->comment('既定高さ (px: A3 1/400 1m=26px)');
            $table->boolean('is_resizable')->default(false)->comment('縦横可変フラグ (free_area のみ true)');
            $table->boolean('is_rotatable')->default(true)->comment('回転可能フラグ');
            $table->unsignedInteger('rotation_step')->default(45)->comment('回転単位 (45度)');
            $table->json('supported_colors')->nullable()->comment('対応カラーパレット JSON');
            $table->boolean('has_hygiene_circle')->default(false)->comment('給水20m円自動展開フラグ');
            $table->boolean('is_hygiene_target')->default(false)->comment('衛生チェック対象フラグ');
            $table->string('icon_asset_path')->nullable()->comment('アイコン画像アセットパス');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index('category');
        });

        // 2. マッピング配置テーブル (site_map_placements)
        Schema::create('site_map_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_base_map_id')->constrained('site_base_maps')->cascadeOnDelete();
            $table->foreignId('layout_item_id')->nullable()->constrained('layout_items')->nullOnDelete();
            $table->string('shape_type', 50)->default('general_tent')->comment('形状種別キー');
            $table->string('display_text', 255)->default('テキスト')->comment('中央表示テキスト');
            $table->string('color_theme', 30)->default('red')->comment('カラーテーマ (red, green, black, blue, orange)');
            $table->decimal('pos_x', 10, 2)->default(0.00)->comment('白地図上X座標 (px)');
            $table->decimal('pos_y', 10, 2)->default(0.00)->comment('白地図上Y座標 (px)');
            $table->decimal('width', 10, 2)->default(78.00)->comment('幅 (px)');
            $table->decimal('height', 10, 2)->default(78.00)->comment('高さ (px)');
            $table->integer('rotation')->default(0)->comment('回転角度 (0, 45, 90, 135, 180, 225, 270, 315)');
            $table->boolean('is_rotation_locked')->default(false)->comment('通り沿い配置による回転ロック');
            $table->text('notes')->nullable()->comment('特記事項・個別メモ');
            $table->json('meta_data')->nullable()->comment('拡張描画属性JSON');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['site_base_map_id', 'shape_type']);
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
