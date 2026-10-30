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
        Schema::create('logistics_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('拠点名（税務署前駐車場、番所地下倉庫等）');
            $table->string('type', 30)->default('parking')->comment('拠点種別: parking, facility, open_space, other');
            $table->boolean('is_all_day')->default(false)->comment('終日利用可能フラグ');
            $table->dateTime('available_from')->nullable()->comment('使用可能・貸切開始日時');
            $table->dateTime('available_to')->nullable()->comment('使用可能・貸切終了日時');
            $table->text('description')->nullable()->comment('備考・利用規約・鍵情報等');
            $table->integer('display_order')->default(0)->comment('表示順序');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_locations');
    }
};
