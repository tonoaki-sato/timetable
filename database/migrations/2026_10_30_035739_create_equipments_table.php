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
            $table->string('type', 30)->default('committee')->comment('資材区分: committee(委員会管理), rental(レンタル), personal(私物)');
            $table->string('checkout_rule', 30)->default('free')->comment('持出ルール: free, permission_required, leader_only');
            $table->integer('quantity')->default(1)->comment('数量（基準数値）');
            $table->boolean('is_approximate_quantity')->default(false)->comment('概数管理フラグ（true: 約○個として扱う）');
            $table->string('unit', 20)->default('個')->comment('単位（本・台・張・セット・一式等）');
            $table->string('storage_location')->nullable()->comment('定常保管場所（番所地下倉庫・番所倉庫・コミュニティルーム等）');
            $table->string('storage_detail')->nullable()->comment('棚番・置場・私物所有者詳細');
            $table->foreignId('lane_id')->nullable()->constrained('lanes')->nullOnDelete()->comment('主担当班ID');
            $table->text('description')->nullable()->comment('仕様・備考メモ');
            $table->integer('display_order')->default(0)->comment('表示順序');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
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
