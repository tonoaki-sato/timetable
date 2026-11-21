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
        Schema::create('site_base_maps', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('白地図名称（例: 商店街平面図 260813版 A3 1/400）');
            $table->string('cad_version', 50)->default('260813')->comment('CAD版数（例: 260813）');
            $table->string('paper_size', 20)->default('A3')->comment('用紙サイズ（A3）');
            $table->decimal('scale_ratio', 8, 4)->default(7.0866)->comment('縮尺比率（1mあたりのpx数: A3 1/400換算 7.0866px/m）');
            $table->decimal('width_meters', 8, 2)->default(118.80)->comment('実空間横幅目安（m）');
            $table->decimal('height_meters', 8, 2)->default(168.00)->comment('実空間縦幅目安（m）');
            $table->string('svg_path')->default('maps/shopping_street_base_map_260813.svg')->comment('SVG白地図ファイルパス');
            $table->string('pdf_path')->nullable()->default(null)->comment('PDF原本ファイルパス');
            $table->string('viewbox', 50)->default('0 0 842 1191')->comment('SVG viewBox');
            $table->boolean('is_default')->default(false)->comment('デフォルト白地図フラグ');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->text('notes')->nullable()->comment('特記事項・CADメモ');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['is_active', 'is_default', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_base_maps');
    }
};
