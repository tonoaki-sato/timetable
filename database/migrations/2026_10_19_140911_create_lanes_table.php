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
        Schema::create('lanes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('color', 30)->default('slate');
            $table->string('icon', 30)->nullable();
            $table->foreignId('lead_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);

            // 2038年問題対策として DATETIME 型を採用
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['timetable_id', 'display_order']);
            $table->unique(['timetable_id', 'name']);
        });

        Schema::table('timetable_tasks', function (Blueprint $table) {
            $table->foreignId('lane_id')->nullable()->after('timetable_id')->constrained('lanes')->restrictOnDelete();
        });

        // 既存データの移行処理
        $defaultLanes = [
            ['name' => 'カラーコーン', 'icon' => '🔺', 'color' => 'amber', 'description' => '路上規制バー・コーン搬入・本設置・撤去', 'display_order' => 10],
            ['name' => '給水設備', 'icon' => '🚰', 'color' => 'sky', 'description' => '給水タンク・仮設手洗い場・配管ホース設置', 'display_order' => 20],
            ['name' => '衛生', 'icon' => '🚻', 'color' => 'emerald', 'description' => '仮設トイレ・ごみステーション設置・回収袋交換', 'display_order' => 30],
            ['name' => '巡回', 'icon' => '🚨', 'color' => 'purple', 'description' => '会場警備・迷子対応・防犯パトロール・動線監視', 'display_order' => 40],
            ['name' => 'キッズ村', 'icon' => '🎈', 'color' => 'pink', 'description' => 'こども体験ブース設営・運営・安全管理', 'display_order' => 50],
            ['name' => 'ちょうちん', 'icon' => '🏮', 'color' => 'red', 'description' => '提灯取付・配線点検・点灯確認・翌日回収', 'display_order' => 60],
            ['name' => 'ござ市', 'icon' => '🧺', 'color' => 'orange', 'description' => 'ござ敷設・出店区画案内・境界管理・漆原さん連携', 'display_order' => 70],
            ['name' => '本陣', 'icon' => '🏛️', 'color' => 'indigo', 'description' => '本部運営・来賓応接・放送アナウンス・総合案内', 'display_order' => 80],
            ['name' => '道路清掃', 'icon' => '🧹', 'color' => 'teal', 'description' => '開催前清掃・巡回清掃・規制解除前の最終路面清掃', 'display_order' => 90],
            ['name' => 'ステカン', 'icon' => '🪧', 'color' => 'yellow', 'description' => '捨て看板（案内標識）の電柱・フェンス設置および完全回収', 'display_order' => 100],
        ];

        $now = now();
        $timetables = DB::table('timetables')->get();

        foreach ($timetables as $timetable) {
            $laneIdMap = [];

            // 1. デフォルト10レーンを作成
            foreach ($defaultLanes as $laneData) {
                $insertedId = DB::table('lanes')->insertGetId([
                    'timetable_id' => $timetable->id,
                    'name' => $laneData['name'],
                    'icon' => $laneData['icon'],
                    'color' => $laneData['color'],
                    'description' => $laneData['description'],
                    'display_order' => $laneData['display_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $laneIdMap[$laneData['name']] = $insertedId;
            }

            // 2. 既存タスクの lane 文字列に対応する lane_id をマッピング
            $tasks = DB::table('timetable_tasks')
                ->where('timetable_id', $timetable->id)
                ->get();

            foreach ($tasks as $task) {
                $targetLaneName = match ($task->lane) {
                    'カラーコーン班' => 'カラーコーン',
                    '給水設備班' => '給水設備',
                    '衛生班' => '衛生',
                    '誘導・警備班' => '巡回',
                    '救護班' => '本陣', // 救護または本陣
                    '本部連絡班' => '本陣',
                    default => $task->lane,
                };

                // もしマッピング先のレーンが存在しなければ新設
                if (! isset($laneIdMap[$targetLaneName])) {
                    $newLaneId = DB::table('lanes')->insertGetId([
                        'timetable_id' => $timetable->id,
                        'name' => $targetLaneName,
                        'icon' => '📋',
                        'color' => 'slate',
                        'display_order' => 110,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $laneIdMap[$targetLaneName] = $newLaneId;
                }

                DB::table('timetable_tasks')
                    ->where('id', $task->id)
                    ->update(['lane_id' => $laneIdMap[$targetLaneName]]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetable_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lane_id');
        });

        Schema::dropIfExists('lanes');
    }
};
