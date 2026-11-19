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
        // 1. 現場配置図面マスター
        Schema::create('layout_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->string('name')->comment('図面名称（例: メイン通り商店街配置図）');
            $table->string('version_tag', 50)->default('初版')->comment('版数タグ（例: 20260927版）');
            $table->text('notes')->nullable()->comment('図面全体の特記事項・運用メモ');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index('timetable_id');
        });

        // 2. 特設エリア・ゾーン（北川P, S&S前P, 税務署前P, 通りゾーン, KIDS_VILLAGE）
        Schema::create('layout_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_map_id')->constrained('layout_maps')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('zone_code', 50)->comment('識別コード（ZONE_A, ZONE_B, AREA_KITAGAWA, AREA_SS, AREA_TAX, KIDS_VILLAGE）');
            $table->string('name')->comment('エリア名（例: 北川駐車場内, メイン通りA列（東側）, キッズ村）');
            $table->integer('display_order')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['layout_map_id', 'display_order']);
        });

        // 3. 出展・出店者マスターテーブル（timetablesから独立）
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('出展・出店者名・屋号（事業者名・店舗名・団体名）');
            $table->string('contact_person')->nullable()->comment('窓口担当者名・代表者名');
            $table->string('contact', 50)->nullable()->comment('連絡先（電話番号等）');
            $table->text('notes')->nullable()->comment('出店者に関する特記事項・備考');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        // 4. 現場配置アイテム（出店ブース・衛生現場インフラ設備 統合テーブル）
        Schema::create('layout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_map_id')->constrained('layout_maps')->cascadeOnDelete();
            $table->foreignId('layout_area_id')->nullable()->constrained('layout_areas')->nullOnDelete();
            $table->string('item_type', 30)->default('booth')->comment('種別: booth(出店ブース), facility(現場設備)');
            $table->string('code', 30)->comment('識別コード（コマ番号 A01〜 または 設備コード W-01〜）');
            $table->string('name')->nullable()->comment('名称（ブース個別表示名または設備名。未入力時ブースはvendor名を表示）');
            $table->string('status', 30)->default('confirmed')->comment('状態: tentative(未定), confirmed(確定)');
            $table->string('category', 50)->nullable()->comment('出店区分(goza_cooked_food等) または 設備種別(water_supply, first_aid等)');
            $table->string('booth_type', 50)->nullable()->comment('ブース形態(tent, pipe_tent, open_stall, kitchen_car, partner_building)');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('location_description')->nullable()->comment('近接店舗・建物・設置場所の目安');
            $table->text('notes')->nullable()->comment('取扱品目・展示内容・特記事項・メモ（product_items統合）');
            $table->integer('display_order')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['layout_map_id', 'item_type']);
            $table->index(['layout_map_id', 'code']);
            $table->index(['layout_map_id', 'layout_area_id', 'display_order', 'code'], 'layout_items_map_area_order_code_idx');
            $table->index(['vendor_id', 'item_type', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layout_items');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('layout_areas');
        Schema::dropIfExists('layout_maps');
    }
};
