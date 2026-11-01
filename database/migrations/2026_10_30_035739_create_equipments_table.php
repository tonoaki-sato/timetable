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
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('資材名（カラーコーン、給水設備、パイプテント等）');
            $table->string('category', 30)->default('heavy_equipment')->comment('資材カテゴリ: heavy_equipment, carrier, tool_consumable');
            $table->string('checkout_rule', 30)->default('free')->comment('持出ルール: free, permission_required, leader_only');
            $table->string('type', 30)->default('committee')->comment('資材区分: committee(自前), rental(イベント会社)');
            $table->integer('quantity')->default(1)->comment('総数量');
            $table->string('unit', 20)->default('個')->comment('単位');
            $table->string('storage_location')->nullable()->comment('自前機材の保管場所（番所地下倉庫、コミュニティルーム等）');
            $table->string('storage_detail')->nullable()->comment('保管場所詳細・定位置メモ（棚番・置場詳細）');
            $table->string('event_company_name')->nullable()->comment('イベント会社名（レンタル時）');
            $table->string('event_company_contact')->nullable()->comment('イベント会社連絡先・電話番号');
            $table->foreignId('lane_id')->nullable()->constrained('lanes')->nullOnDelete()->comment('主担当班');
            $table->text('description')->nullable()->comment('仕様・備考メモ');
            $table->integer('display_order')->default(0)->comment('表示順序');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipments');
    }
};
