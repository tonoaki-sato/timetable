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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_type_id')->constrained('location_types')->restrictOnDelete();
            $table->string('name')->comment('拠点名');
            $table->text('notes')->nullable()->comment('メモ欄（フリーテキスト）');
            $table->boolean('is_all_day')->default(false)->comment('終日利用可能フラグ');
            $table->integer('display_order')->default(0)->comment('表示順');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
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
        Schema::dropIfExists('locations');
    }
};
