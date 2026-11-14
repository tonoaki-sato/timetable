<?php

namespace Database\Seeders;

use App\Enums\BoothCategory;
use App\Enums\FacilityType;
use App\Models\GozaStore;
use App\Models\LayoutArea;
use App\Models\LayoutBooth;
use App\Models\LayoutFacility;
use App\Models\LayoutMap;
use App\Models\Location;
use App\Models\Timetable;
use Illuminate\Database\Seeder;

class SiteLayoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timetables = Timetable::all();
        if ($timetables->isEmpty()) {
            return;
        }

        foreach ($timetables as $timetable) {
            $this->seedLayoutForTimetable($timetable);
        }
    }

    /**
     * 特定のタイムテーブルに対する現場配置計画データを投入
     */
    private function seedLayoutForTimetable(Timetable $timetable): void
    {
        // 既存の図面があればスキップ
        if (LayoutMap::where('timetable_id', $timetable->id)->exists()) {
            return;
        }

        // 1. 図面マスター作成
        $map = LayoutMap::create([
            'timetable_id' => $timetable->id,
            'name' => 'メイン通り商店街配置図',
            'version_tag' => '20260927版',
            'notes' => '保土ヶ谷宿場まつり 食品衛生対応 平面図（1/400 A3）に基づく現場配置計画。手洗い設備5ヵ所設置。',
            'created_by' => $timetable->created_by,
        ]);

        // 既存拠点マップ（名前ベース）
        $locations = Location::where('timetable_id', $timetable->id)->get()->keyBy('name');

        // 2. 特設エリア・ゾーン作成
        $areaKitagawa = LayoutArea::create([
            'layout_map_id' => $map->id,
            'location_id' => $locations->get('長島駐車場（旧北川駐車場）')?->id,
            'zone_code' => 'AREA_KITAGAWA',
            'name' => '北川駐車場内',
            'area_type' => 'parking',
            'display_order' => 10,
        ]);

        $areaSS = LayoutArea::create([
            'layout_map_id' => $map->id,
            'location_id' => $locations->get('S&S駐車場')?->id,
            'zone_code' => 'AREA_SS',
            'name' => 'S&S前駐車場（日本パーキング保土ヶ谷第2）内',
            'area_type' => 'parking',
            'display_order' => 20,
        ]);

        $areaTax = LayoutArea::create([
            'layout_map_id' => $map->id,
            'location_id' => $locations->get('税務署前駐車場')?->id,
            'zone_code' => 'AREA_TAX',
            'name' => '税務署前駐車場（タイムス保土ヶ谷駅西口）内',
            'area_type' => 'parking',
            'display_order' => 30,
        ]);

        $areaTakao = LayoutArea::create([
            'layout_map_id' => $map->id,
            'location_id' => null,
            'zone_code' => 'STAGE_TAKAO',
            'name' => 'タカオ前ステージ',
            'area_type' => 'stage',
            'display_order' => 40,
        ]);

        $areaA = LayoutArea::create([
            'layout_map_id' => $map->id,
            'location_id' => null,
            'zone_code' => 'ZONE_A',
            'name' => 'メイン通りA列（東側歩道）',
            'area_type' => 'street',
            'display_order' => 50,
        ]);

        $areaB = LayoutArea::create([
            'layout_map_id' => $map->id,
            'location_id' => null,
            'zone_code' => 'ZONE_B',
            'name' => 'メイン通りB列（西側歩道）',
            'area_type' => 'street',
            'display_order' => 60,
        ]);

        // 3. 現場設備・インフラ（給水5ヵ所、トイレ、授乳室、救護所、本陣、待機所）
        $facilitiesData = [
            [
                'code' => 'W-01',
                'name' => '給水設備①（手洗い）',
                'type' => FacilityType::WaterSupply,
                'area_id' => $areaKitagawa->id,
                'loc_desc' => '北川駐車場内 奥',
                'status' => '確定',
                'order' => 10,
            ],
            [
                'code' => 'W-02',
                'name' => '給水設備②（手洗い）',
                'type' => FacilityType::WaterSupply,
                'area_id' => $areaA->id,
                'loc_desc' => 'メイン通り南側（ヴィラソレイユ付近）',
                'status' => '確定',
                'order' => 20,
            ],
            [
                'code' => 'W-03',
                'name' => '給水設備③（手洗い）',
                'type' => FacilityType::WaterSupply,
                'area_id' => $areaB->id,
                'loc_desc' => 'メイン通り中央（フローレンスパレス向かい・服部邸前）',
                'status' => '確定',
                'order' => 30,
            ],
            [
                'code' => 'W-04',
                'name' => '給水設備④（手洗い）',
                'type' => FacilityType::WaterSupply,
                'area_id' => $areaB->id,
                'loc_desc' => 'メイン通り中央北（甲陽保土ヶ谷ビル/ガスト前）',
                'status' => '確定',
                'order' => 40,
            ],
            [
                'code' => 'W-05',
                'name' => '給水設備⑤（手洗い）',
                'type' => FacilityType::WaterSupply,
                'area_id' => $areaB->id,
                'loc_desc' => 'メイン通り北側（丸山浴槽設備・藤巻ビル間）',
                'status' => '確定',
                'order' => 50,
            ],
            [
                'code' => 'FA-01',
                'name' => '救護所',
                'type' => FacilityType::FirstAid,
                'area_id' => $areaB->id,
                'loc_desc' => 'リサイクル歌留多・作田邸間（B06-B08裏）',
                'status' => '確定',
                'order' => 60,
            ],
            [
                'code' => 'HQ-01',
                'name' => '本陣・運営本部',
                'type' => FacilityType::Headquarters,
                'area_id' => $areaB->id,
                'loc_desc' => 'おもちゃのうえの向かい・本陣スペース',
                'status' => '確定',
                'order' => 70,
            ],
            [
                'code' => 'STBY-01',
                'name' => 'スタッフ待機場所・控室',
                'type' => FacilityType::StaffWaitingArea,
                'area_id' => $areaB->id,
                'loc_desc' => '本陣裏・西村アパート付近',
                'status' => '確定',
                'order' => 80,
            ],
            [
                'code' => 'TOILET-01',
                'name' => '仮設トイレ（無料）',
                'type' => FacilityType::ToiletFree,
                'area_id' => $areaKitagawa->id,
                'loc_desc' => '北川駐車場内 手前',
                'status' => '確定',
                'order' => 90,
            ],
            [
                'code' => 'TOILET-02',
                'name' => '仮設トイレ（無料）',
                'type' => FacilityType::ToiletFree,
                'area_id' => $areaSS->id,
                'loc_desc' => 'S&S前駐車場内 奥',
                'status' => '確定',
                'order' => 100,
            ],
            [
                'code' => 'TOILET-03',
                'name' => '公衆トイレ（有料）',
                'type' => FacilityType::ToiletPaid,
                'area_id' => $areaTax->id,
                'loc_desc' => '税務署前駐車場内 奥',
                'status' => '確定',
                'order' => 110,
            ],
            [
                'code' => 'NURSE-01',
                'name' => '授乳室テント',
                'type' => FacilityType::NursingRoom,
                'area_id' => $areaSS->id,
                'loc_desc' => 'S&S前駐車場内 店舗横',
                'status' => '確定',
                'order' => 120,
            ],
        ];

        foreach ($facilitiesData as $f) {
            LayoutFacility::create([
                'layout_map_id' => $map->id,
                'layout_area_id' => $f['area_id'],
                'facility_code' => $f['code'],
                'name' => $f['name'],
                'facility_type' => $f['type'],
                'location_description' => $f['loc_desc'],
                'status' => $f['status'],
                'display_order' => $f['order'],
            ]);
        }

        // 4. 特設・企業ブース
        // 北川駐車場（キッチンカー）
        $kitchenCars = [
            ['code' => 'KC-01', 'name' => 'ハングリータイガー', 'items' => 'ハンバーグ・特製弁当'],
            ['code' => 'KC-02', 'name' => '横浜屋', 'items' => '横浜ラーメン・餃子'],
            ['code' => 'KC-03', 'name' => '水浅青果 (生搾りジュース)', 'items' => 'フレッシュフルーツジュース'],
            ['code' => 'KC-04', 'name' => '牽引車カフェ', 'items' => 'カフェラテ・焼き菓子'],
        ];
        foreach ($kitchenCars as $idx => $kc) {
            $store = GozaStore::create([
                'timetable_id' => $timetable->id,
                'store_name' => $kc['name'],
                'representative_name' => '企業出店担当者',
                'emergency_contact' => '090-0000-00'.str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT),
                'product_items' => $kc['items'],
                'status' => 'confirmed',
            ]);

            LayoutBooth::create([
                'layout_map_id' => $map->id,
                'layout_area_id' => $areaKitagawa->id,
                'goza_store_id' => $store->id,
                'booth_code' => $kc['code'],
                'name' => $kc['name'],
                'category' => BoothCategory::CorporateKitchenCar,
                'adjacent_landmark' => '北川駐車場内',
                'display_order' => 100 + $idx,
            ]);
        }

        // S&S前駐車場（体験・企業ブース）
        $ssBooths = [
            ['code' => 'SS-01', 'name' => 'ミニバス体験スペース', 'category' => BoothCategory::SpecialEvent, 'store' => null],
            ['code' => 'SS-02', 'name' => '横浜エデンの園', 'category' => BoothCategory::CorporateTent, 'store' => '横浜エデンの園'],
            ['code' => 'SS-03', 'name' => 'S&Sブース', 'category' => BoothCategory::CorporateTent, 'store' => 'S&S'],
        ];
        foreach ($ssBooths as $idx => $sb) {
            $storeId = null;
            if ($sb['store']) {
                $store = GozaStore::create([
                    'timetable_id' => $timetable->id,
                    'store_name' => $sb['store'],
                    'representative_name' => '協賛企業責任者',
                    'emergency_contact' => '090-0000-01'.str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT),
                    'product_items' => '福祉施設PR・グッズ配布',
                    'status' => 'confirmed',
                ]);
                $storeId = $store->id;
            }

            LayoutBooth::create([
                'layout_map_id' => $map->id,
                'layout_area_id' => $areaSS->id,
                'goza_store_id' => $storeId,
                'booth_code' => $sb['code'],
                'name' => $sb['name'],
                'category' => $sb['category'],
                'adjacent_landmark' => 'S&S前駐車場内',
                'display_order' => 200 + $idx,
            ]);
        }

        // 税務署前駐車場（特設ブース）
        $taxBooths = [
            ['code' => 'TAX-01', 'name' => '防犯啓発ブース', 'category' => BoothCategory::SpecialEvent],
            ['code' => 'TAX-02', 'name' => '税理士会 無料相談', 'category' => BoothCategory::SpecialEvent],
            ['code' => 'TAX-03', 'name' => '白バイ・パトカー展示', 'category' => BoothCategory::SpecialEvent],
            ['code' => 'TAX-04', 'name' => 'フェイスペイントコーナー', 'category' => BoothCategory::SpecialEvent],
            ['code' => 'TAX-05', 'name' => '保土ヶ谷宿 歴史展示①', 'category' => BoothCategory::SpecialEvent],
            ['code' => 'TAX-06', 'name' => '保土ヶ谷宿 歴史展示②', 'category' => BoothCategory::SpecialEvent],
        ];
        foreach ($taxBooths as $idx => $tb) {
            LayoutBooth::create([
                'layout_map_id' => $map->id,
                'layout_area_id' => $areaTax->id,
                'goza_store_id' => null,
                'booth_code' => $tb['code'],
                'name' => $tb['name'],
                'category' => $tb['category'],
                'adjacent_landmark' => '税務署前駐車場内',
                'display_order' => 300 + $idx,
            ]);
        }

        // 5. ござ市 メイン通りコマ割り（A01〜A51, B01〜B30）
        // 図面の赤枠（調理）指定コマ
        $cookingA = [4, 5, 6, 9, 11, 12, 13, 14, 15, 16, 17, 19, 26, 27, 30, 31, 32, 34, 35, 36, 37, 38, 41, 43, 44, 45, 46, 48, 50, 51];
        $cookingB = [5, 6, 7, 9, 10, 14, 15, 22, 27];

        // ランドマーク・店舗マップ（目安）
        $landmarkA = [
            1 => '家本ビル/マイバスケット前',
            2 => 'ストークマンション前',
            3 => 'ヴィラソレイユ前',
            4 => '川むら前',
            5 => 'もんじゃ衛門前',
            8 => '保土ヶ谷税務署前',
            10 => '一戸建前',
            12 => 'Paraca駐車場前',
            15 => '佐野靴店前',
            18 => 'ないとうふとん店前',
            20 => 'かたびらスペースしばた前',
            21 => '斎藤邸前',
            23 => 'アパート前',
            25 => 'メガネのサイグサ前',
            30 => '堀江マンション前',
            35 => 'フローレンスパレス前',
            39 => '国際会館前',
            41 => 'ふとん池田屋前',
            42 => 'おもちゃのうえの前',
            46 => '甲陽ビル(ガスト)前',
            50 => '丸山浴槽設備前',
            51 => '北川ビル前',
        ];

        $landmarkB = [
            1 => 'ナイスアーバン前',
            2 => 'セブンイレブン前',
            3 => 'モンテグリシーヌ前',
            6 => '作田邸前',
            8 => 'リサイクル歌留多前',
            9 => 'パーセク三宅前',
            11 => '帷子珈琲前',
            12 => 'アパート前',
            14 => '金子クリーニング前',
            16 => 'タカオ前ステージ横',
            17 => 'NUCパーキング前',
            18 => '北川製粉所前',
            20 => 'ライオンズマンション前',
            23 => 'ウチコシナカムラ前',
            26 => 'はっこうショップ前',
            27 => 'ホワイト急便前',
            30 => 'わいず前',
        ];

        // A列 (A01〜A51)
        for ($i = 1; $i <= 51; $i++) {
            $code = 'A'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $isCooking = in_array($i, $cookingA, true);
            $category = $isCooking ? BoothCategory::FoodCooking : BoothCategory::FoodNonCooking;
            $landmark = $landmarkA[$i] ?? null;

            // 代表的な出店者名
            $storeName = match ($code) {
                'A01' => '水浅青果',
                'A03' => '水浅青果（加工品）',
                'A04' => '横浜本陣 焼き鳥',
                'A05' => 'もんじゃ衛門 屋台',
                'A06' => '宿場スイーツ',
                'A07' => 'クラフト工房 帷子',
                'A09' => 'ホルモン焼き 宿場亭',
                'A11' => '富士宮やきそば 遠州',
                'A12' => '焼き鳥 鳥久',
                'A14' => 'スイーツクレープ',
                'A17' => 'たこ焼き 太閤',
                'A19' => 'ソース焼きそば 浪花',
                'A25' => 'メガネのサイグサ (クリーニング&調整)',
                'A35' => '聖隷病院 健康相談ブース',
                'A39' => 'スープカレー サニー',
                'A41' => 'ふとん池田屋 (寝具フェア)',
                'A42' => 'おもちゃのうえの (駄菓子・玩具)',
                'A47' => '寺井ブース',
                'A48' => 'オーバーカム',
                default => ($i % 3 === 0 ? '宿場出店者 '.$code : null),
            };

            $storeId = null;
            if ($storeName) {
                $store = GozaStore::create([
                    'timetable_id' => $timetable->id,
                    'store_name' => $storeName,
                    'representative_name' => '出店担当 '.$code,
                    'emergency_contact' => '090-1234-'.str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT),
                    'product_items' => $isCooking ? '調理食品・ドリンク販売' : '加工食品または物販',
                    'status' => 'confirmed',
                ]);
                $storeId = $store->id;
            }

            LayoutBooth::create([
                'layout_map_id' => $map->id,
                'layout_area_id' => $areaA->id,
                'goza_store_id' => $storeId,
                'booth_code' => $code,
                'name' => $storeName,
                'category' => $category,
                'adjacent_landmark' => $landmark,
                'display_order' => 1000 + $i,
            ]);
        }

        // B列 (B01〜B30)
        for ($i = 1; $i <= 30; $i++) {
            $code = 'B'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $isCooking = in_array($i, $cookingB, true);
            $category = $isCooking ? BoothCategory::FoodCooking : BoothCategory::FoodNonCooking;
            $landmark = $landmarkB[$i] ?? null;

            $storeName = match ($code) {
                'B01' => '宿場まつり本部案内所',
                'B04' => '和菓子処 すずき',
                'B05' => '牛串・豚バラ串処',
                'B06' => '広島風お好み焼き',
                'B07' => 'カレーハウス サン',
                'B08' => 'リサイクル歌留多',
                'B09' => '宿場フランクフルト',
                'B10' => 'お好み焼き てっちゃん',
                'B12' => 'はっこうショップ',
                'B13' => '帷子珈琲',
                'B14' => 'からあげ処 宿場',
                'B15' => '炭火工房 鶏陣',
                'B20' => '交通局 PRブース',
                'B22' => '焼きそば 鈴屋',
                'B26' => 'サロン・ド・カーム',
                'B30' => 'わいず',
                default => ($i % 3 === 0 ? '宿場出店者 '.$code : null),
            };

            $storeId = null;
            if ($storeName) {
                $store = GozaStore::create([
                    'timetable_id' => $timetable->id,
                    'store_name' => $storeName,
                    'representative_name' => '出店代表 '.$code,
                    'emergency_contact' => '080-5678-'.str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT),
                    'product_items' => $isCooking ? '温かい調理品・鉄板焼き' : '日用品・和菓子・珈琲豆',
                    'status' => 'confirmed',
                ]);
                $storeId = $store->id;
            }

            LayoutBooth::create([
                'layout_map_id' => $map->id,
                'layout_area_id' => $areaB->id,
                'goza_store_id' => $storeId,
                'booth_code' => $code,
                'name' => $storeName,
                'category' => $category,
                'adjacent_landmark' => $landmark,
                'display_order' => 2000 + $i,
            ]);
        }
    }
}
