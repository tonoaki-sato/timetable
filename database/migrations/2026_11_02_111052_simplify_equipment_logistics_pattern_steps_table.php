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
        Schema::table('equipment_logistics_pattern_steps', function (Blueprint $table) {
            $table->dropColumn([
                'start_time',
                'end_time',
                'from_location_role',
                'to_location_role',
                'from_location',
                'to_location',
                'quantity_rule',
                'quantity_note',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_logistics_pattern_steps', function (Blueprint $table) {
            $table->string('quantity_rule', 30)->default('all')->comment('数量ルール: all(全量), half_1(前半分割), half_2(後半分割), custom(補足のみ)');
            $table->string('quantity_note')->nullable()->comment('数量補足テンプレート（『通行止め用分』等）');
            $table->string('start_time', 10)->nullable()->comment('標準開始時刻 (HH:mm)');
            $table->string('end_time', 10)->nullable()->comment('標準終了時刻 (HH:mm)');
            $table->string('from_location_role', 30)->default('text')->comment('fixed, equipment_storage, bansho_space, staging_parking, night_parking_1, night_parking_2, text');
            $table->string('to_location_role', 30)->default('text')->comment('fixed, equipment_storage, bansho_space, staging_parking, night_parking_1, night_parking_2, text');
            $table->string('from_location')->nullable()->comment('移動元テキスト（『会場設置』『イベント会社車両』等）');
            $table->string('to_location')->nullable()->comment('移動先テキスト（『会場設置』『クリティカルポイント付近』等）');
        });
    }
};
