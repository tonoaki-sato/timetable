<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feature_settings', function (Blueprint $table) {
            $table->id();
            $table->string('feature_key')->unique()->comment('機能識別キー');
            $table->string('name')->comment('機能名称');
            $table->text('description')->nullable()->comment('機能説明');
            $table->boolean('is_enabled')->default(true)->comment('有効フラグ');
            $table->dateTime('opened_at')->nullable()->comment('利用可能開始日時');
            $table->dateTime('closed_at')->nullable()->comment('利用可能終了日時');
            $table->text('notice_message')->nullable()->comment('停止時・期間外の特記事項');
            $table->dateTime('created_at')->nullable()->comment('作成日時');
            $table->dateTime('updated_at')->nullable()->comment('更新日時');
        });

        // 初期データとして「スタッフセルフ登録」を登録
        DB::table('feature_settings')->insert([
            'feature_key' => 'staff_self_registration',
            'name' => 'スタッフセルフ登録',
            'description' => 'ボランティア・現場スタッフによるWebセルフ登録受付機能',
            'is_enabled' => true,
            'opened_at' => null,
            'closed_at' => null,
            'notice_message' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_settings');
    }
};
