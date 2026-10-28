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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('氏名');
            $table->string('kana')->nullable()->comment('フリガナ');
            $table->string('affiliation', 100)->nullable()->comment('所属団体・地域会等');
            $table->boolean('is_active')->default(true)->comment('稼働可能フラグ');

            // 個別アクセス用カラム
            $table->string('access_token', 64)->unique()->comment('個別マイタイムテーブル用アクセストークン');
            $table->enum('registered_via', ['admin', 'self'])->default('admin')->comment('登録経路（admin:本部, self:セルフ登録）');

            // 現場アクセス・受付用日時
            $table->dateTime('last_accessed_at')->nullable()->comment('最終確認日時');
            $table->dateTime('checked_in_at')->nullable()->comment('当日受付・現場到着日時');
            $table->dateTime('token_reissued_at')->nullable()->comment('トークン再発行日時');
            $table->unsignedSmallInteger('reissue_count')->default(0)->comment('名札・トークン再発行回数');

            // 2038年問題対策として DATETIME 型を採用
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // 検索最適化インデックス
            $table->index(['name', 'kana']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
