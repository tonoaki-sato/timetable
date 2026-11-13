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
        Schema::create('organization_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('organization_units')->cascadeOnDelete();
            $table->string('name')->comment('名前');
            $table->string('short_description')->nullable()->comment('説明（一覧用）');
            $table->text('description')->nullable()->comment('詳細説明');
            $table->integer('display_order')->default(0)->comment('表示順');

            // 2038年問題対策
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index('display_order');
            $table->index(['parent_id', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_units');
    }
};

