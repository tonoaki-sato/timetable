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

        // 2. 特設エリア・ゾーン（北川P, S&S前P, 税務署前P, 通りゾーン）
        Schema::create('layout_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_map_id')->constrained('layout_maps')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('zone_code', 50)->comment('識別コード（ZONE_A, ZONE_B, AREA_KITAGAWA, AREA_SS, AREA_TAX）');
            $table->string('name')->comment('エリア名（例: 北川駐車場内, メイン通りA列（東側））');
            $table->string('area_type', 30)->default('street')->comment('種別: street(通り), parking(駐車場特設), stage(ステージ), hq(本部・待機エリア)');
            $table->integer('display_order')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['layout_map_id', 'display_order']);
        });

        // 3. 商業出店者管理マスターテーブル
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->string('name')->comment('出店者名・屋号（事業者名・店舗名・団体名）');
            $table->string('representative_name')->nullable()->comment('出店代表者氏名');
            $table->string('emergency_contact', 50)->nullable()->comment('緊急連絡先（携帯電話等）');
            $table->text('notes')->nullable()->comment('出店者に関する特記事項・搬入情報等');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index('timetable_id');
        });

        // 4. 現場配置コマ割り・出店ブース
        Schema::create('layout_booths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_map_id')->constrained('layout_maps')->cascadeOnDelete();
            $table->foreignId('layout_area_id')->nullable()->constrained('layout_areas')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('booth_code', 30)->comment('コマ番号（例: A01〜A51, B01〜B30, KC-01等）');
            $table->string('name')->nullable()->comment('表示名・屋号（出店者名、または企業名・特設企画名）');
            $table->string('category', 40)->default('food_non_cooking')->comment('出店区分: food_cooking, food_non_cooking, general_merchandise, special_event, corporate_kitchen_car, corporate_tent');
            $table->text('product_items')->nullable()->comment('このブースでの具体的な取扱品目・活動内容（例: 焼きそば, フランクフルト）');
            $table->string('adjacent_landmark')->nullable()->comment('近接店舗・建物名（例: おもちゃのうえの前, 西村アパート前）');
            $table->text('notes')->nullable()->comment('特記事項・メモ');
            $table->integer('display_order')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['layout_map_id', 'booth_code']);
            $table->index(['layout_map_id', 'category']);
        });

        // 5. 衛生・現場インフラ設備（給水設備、トイレ、授乳室、救護所、本陣、待機所）
        Schema::create('layout_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_map_id')->constrained('layout_maps')->cascadeOnDelete();
            $table->foreignId('layout_area_id')->nullable()->constrained('layout_areas')->nullOnDelete();
            $table->string('facility_code', 30)->comment('設備コード（例: W-01〜W-05, TOILET-01, FA-01等）');
            $table->string('name')->comment('設備名称（例: 給水設備①, 本陣, 救護所）');
            $table->string('facility_type', 40)->default('water_supply')->comment('種別: water_supply, toilet_paid, toilet_free, nursing_room, first_aid, headquarters, staff_waiting_area');
            $table->string('location_description')->nullable()->comment('設置場所の目安（例: 甲陽ビル(ガスト)向かい）');
            $table->string('status', 30)->default('予定')->comment('設置状況: 予定, 確定');
            $table->text('notes')->nullable()->comment('設備に関する特記事項');
            $table->integer('display_order')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['layout_map_id', 'facility_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layout_facilities');
        Schema::dropIfExists('layout_booths');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('layout_areas');
        Schema::dropIfExists('layout_maps');
    }
};
