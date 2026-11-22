<?php

namespace Database\Seeders;

use App\Models\Lane;
use Illuminate\Database\Seeder;

class LaneSeeder extends Seeder
{
    /**
     * 担当班マスターを登録する。
     */
    public function run(): void
    {
        $masters = [
            [
                'name' => 'カラーコーン',
                'color' => 'amber',
                'icon' => '🔺',
                'display_order' => 10,
                'description' => '路上規制バー・コーン搬入・本設置・撤去',
                'is_active' => true,
            ],
            [
                'name' => '給水設備',
                'color' => 'sky',
                'icon' => '🚰',
                'display_order' => 20,
                'description' => '給水タンク・仮設手洗い場設置',
                'is_active' => true,
            ],
            [
                'name' => '衛生',
                'color' => 'emerald',
                'icon' => '🚻',
                'display_order' => 30,
                'description' => 'ごみ箱設置・回収袋交換',
                'is_active' => true,
            ],
            [
                'name' => '巡回',
                'color' => 'purple',
                'icon' => '🚨',
                'display_order' => 40,
                'description' => '会場巡回・迷子、落とし物、ポイ捨て、私有地進入対応、来場者棄権行動の監視・注意喚起',
                'is_active' => true,
            ],
            [
                'name' => 'キッズ村',
                'color' => 'pink',
                'icon' => '🎈',
                'display_order' => 50,
                'description' => 'キッズ村エリア全体の管理',
                'is_active' => true,
            ],
            [
                'name' => 'ちょうちん',
                'color' => 'red',
                'icon' => '🏮',
                'display_order' => 60,
                'description' => '提灯取付・回収',
                'is_active' => true,
            ],
            [
                'name' => 'ござ市',
                'color' => 'orange',
                'icon' => '🧺',
                'display_order' => 70,
                'description' => '出店者受付・貸出資材（テント・ウェイトなど）管理、危険行為、違反行為（特に行政指導要綱違反）の監視と指導。',
                'is_active' => true,
            ],
            [
                'name' => '本陣',
                'color' => 'indigo',
                'icon' => '🏛️',
                'display_order' => 80,
                'description' => '本部拠点運営・放送アナウンス・総合案内・ござ市受付（連携）',
                'is_active' => true,
            ],
            [
                'name' => '道路清掃',
                'color' => 'teal',
                'icon' => '🧹',
                'display_order' => 90,
                'description' => '1日目、2日目終了後の道路清掃。特に2日目は油汚れの洗浄など念入りに清掃',
                'is_active' => true,
            ],
            [
                'name' => 'ステカン',
                'color' => 'yellow',
                'icon' => '🪧',
                'display_order' => 100,
                'description' => '捨て看板の設置・回収（内回りと外回り）',
                'is_active' => true,
            ],
            [
                'name' => '直営店',
                'color' => 'indigo',
                'icon' => '🛒',
                'display_order' => 110,
                'description' => '直営店の運営・管理など',
                'is_active' => true,
            ],
            [
                'name' => '税務署ステージ',
                'color' => 'orange',
                'icon' => '🎤',
                'display_order' => 120,
                'description' => '税務署ステージのイベント運行管理',
                'is_active' => true,
            ],
            [
                'name' => '宿場くん/宿場ちゃん',
                'color' => 'red',
                'icon' => '🏃‍♂️',
                'display_order' => 130,
                'description' => 'マスコットキャラのマネジメント、パフォーマンス、アテンドなど',
                'is_active' => true,
            ],
            [
                'name' => 'IT',
                'color' => 'emerald',
                'icon' => '💻',
                'display_order' => 140,
                'description' => 'SNS更新、ホームページ更新、記録用写真撮影など',
                'is_active' => true,
            ],
            [
                'name' => 'S&S',
                'color' => 'orange',
                'icon' => '🏛️',
                'display_order' => 150,
                'description' => 'S&Sエリアの管理など',
                'is_active' => true,
            ],
            [
                'name' => '企業ブース',
                'color' => 'pink',
                'icon' => '🏢',
                'display_order' => 160,
                'description' => '企業ブース出展者の対応とエリアの管理。代替駐車場（主に横浜銀行駐車場）の監視、対象者対応など',
                'is_active' => true,
            ],
            [
                'name' => '幹事会',
                'color' => 'amber',
                'icon' => '📋',
                'display_order' => 170,
                'description' => null,
                'is_active' => true,
            ],
        ];

        foreach ($masters as $master) {
            Lane::updateOrCreate(
                ['name' => $master['name']],
                $master
            );
        }
    }
}
