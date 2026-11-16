<?php

namespace Database\Seeders;

use App\Enums\BoothCategory;
use App\Enums\FacilityType;
use App\Enums\LayoutItemType;
use App\Models\LayoutArea;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Location;
use App\Models\Timetable;
use App\Models\Vendor;
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
            'display_order' => 10,
        ]);

        $areaSS = LayoutArea::create([
            'layout_map_id' => $map->id,
            'location_id' => $locations->get('S&S駐車場')?->id,
            'zone_code' => 'AREA_SS',
            'name' => 'S&S前駐車場内',
            'display_order' => 20,
        ]);

        $areaTax = LayoutArea::create([
            'layout_map_id' => $map->id,
            'location_id' => $locations->get('税務署前駐車場')?->id,
            'zone_code' => 'AREA_TAX',
            'name' => '税務署前駐車場内',
            'display_order' => 30,
        ]);

        $areaKidsVillage = LayoutArea::create([
            'layout_map_id' => $map->id,
            'location_id' => null,
            'zone_code' => 'KIDS_VILLAGE',
            'name' => 'キッズ村',
            'display_order' => 35,
        ]);

        $areaA = LayoutArea::create([
            'layout_map_id' => $map->id,
            'location_id' => null,
            'zone_code' => 'ZONE_A',
            'name' => 'メイン通りA列（東側歩道）',
            'display_order' => 40,
        ]);

        $areaB = LayoutArea::create([
            'layout_map_id' => $map->id,
            'location_id' => null,
            'zone_code' => 'ZONE_B',
            'name' => 'メイン通りB列（西側歩道）',
            'display_order' => 50,
        ]);

        // 3. 現場設備・インフラ（給水5ヵ所、トイレ、授乳室、救護所、本陣、待機所）
        $facilitiesData = [
            [
                'code' => 'W-01',
                'name' => '給水設備①（手洗い）',
                'type' => FacilityType::WaterSupply,
                'area_id' => $areaKitagawa->id,
                'loc_desc' => '北川駐車場内 奥',
                'status' => 'confirmed',
                'order' => 10,
            ],
            [
                'code' => 'W-02',
                'name' => '給水設備②（手洗い）',
                'type' => FacilityType::WaterSupply,
                'area_id' => $areaA->id,
                'loc_desc' => 'メイン通り南側（ヴィラソレイユ付近）',
                'status' => 'confirmed',
                'order' => 20,
            ],
            [
                'code' => 'W-03',
                'name' => '給水設備③（手洗い）',
                'type' => FacilityType::WaterSupply,
                'area_id' => $areaB->id,
                'loc_desc' => 'メイン通り中央（フローレンスパレス向かい・服部邸前）',
                'status' => 'confirmed',
                'order' => 30,
            ],
            [
                'code' => 'W-04',
                'name' => '給水設備④（手洗い）',
                'type' => FacilityType::WaterSupply,
                'area_id' => $areaB->id,
                'loc_desc' => 'メイン通り中央北（甲陽保土ヶ谷ビル/ガスト前）',
                'status' => 'confirmed',
                'order' => 40,
            ],
            [
                'code' => 'W-05',
                'name' => '給水設備⑤（手洗い）',
                'type' => FacilityType::WaterSupply,
                'area_id' => $areaB->id,
                'loc_desc' => 'メイン通り北側（丸山浴槽設備・藤巻ビル間）',
                'status' => 'confirmed',
                'order' => 50,
            ],
            [
                'code' => 'FA-01',
                'name' => '救護所',
                'type' => FacilityType::FirstAid,
                'area_id' => $areaB->id,
                'loc_desc' => 'リサイクル歌留多・作田邸間（B06-B08裏）',
                'status' => 'confirmed',
                'order' => 60,
            ],
            [
                'code' => 'HQ-01',
                'name' => '本陣・運営本部',
                'type' => FacilityType::Headquarters,
                'area_id' => $areaB->id,
                'loc_desc' => 'おもちゃのうえの向かい・本陣スペース',
                'status' => 'confirmed',
                'order' => 70,
            ],
            [
                'code' => 'STBY-01',
                'name' => 'スタッフ控室',
                'type' => FacilityType::StaffRoom,
                'area_id' => $areaB->id,
                'loc_desc' => '本陣裏・西村アパート付近',
                'status' => 'confirmed',
                'order' => 80,
            ],
            [
                'code' => 'TOILET-01',
                'name' => '仮設トイレ（無料）',
                'type' => FacilityType::ToiletFree,
                'area_id' => $areaKitagawa->id,
                'loc_desc' => '北川駐車場内 手前',
                'status' => 'confirmed',
                'order' => 90,
            ],
            [
                'code' => 'TOILET-02',
                'name' => '仮設トイレ（無料）',
                'type' => FacilityType::ToiletFree,
                'area_id' => $areaSS->id,
                'loc_desc' => 'S&S前駐車場内 奥',
                'status' => 'confirmed',
                'order' => 100,
            ],
            [
                'code' => 'TOILET-03',
                'name' => '公衆トイレ（有料）',
                'type' => FacilityType::ToiletPaid,
                'area_id' => $areaTax->id,
                'loc_desc' => '税務署前駐車場内 奥',
                'status' => 'confirmed',
                'order' => 110,
            ],
            [
                'code' => 'NURSE-01',
                'name' => '授乳室テント',
                'type' => FacilityType::NursingRoom,
                'area_id' => $areaSS->id,
                'loc_desc' => 'S&S前駐車場内 店舗横',
                'status' => 'confirmed',
                'order' => 120,
            ],
        ];

        foreach ($facilitiesData as $f) {
            LayoutItem::create([
                'layout_map_id' => $map->id,
                'layout_area_id' => $f['area_id'],
                'item_type' => LayoutItemType::Facility,
                'code' => $f['code'],
                'name' => $f['name'],
                'category' => $f['type']->value,
                'booth_type' => in_array($f['type'], [FacilityType::StaffRoom, FacilityType::FirstAid], true) ? 'partner_building' : 'tent',
                'location_description' => $f['loc_desc'],
                'status' => $f['status'],
                'display_order' => $f['order'],
            ]);
        }

        // 4. 特設・企業ブース
        // 複数ブース出店者（水浅青果: KC-03, A01, A03 の3ブースを出店）
        $mizuasaVendor = Vendor::create([
            'name' => '水浅青果',
            'contact_person' => '水浅 太郎',
            'contact' => '090-1234-1001',
            'notes' => '北川Pでキッチンカー、メイン通りA列で青果直売および生搾り加工品の計3コマ出店。',
        ]);

        // 北川駐車場（キッチンカー）
        $kitchenCars = [
            ['code' => 'KC-01', 'name' => 'ハングリータイガー', 'items' => 'ハンバーグ・特製弁当', 'vendor' => null],
            ['code' => 'KC-02', 'name' => '横浜屋', 'items' => '横浜ラーメン・餃子', 'vendor' => null],
            ['code' => 'KC-03', 'name' => '水浅青果 (生搾りジュース)', 'items' => 'フレッシュフルーツジュース・生搾りドリンク', 'vendor' => $mizuasaVendor],
            ['code' => 'KC-04', 'name' => '牽引車カフェ', 'items' => 'カフェラテ・焼き菓子', 'vendor' => null],
        ];
        foreach ($kitchenCars as $idx => $kc) {
            $vendorId = null;
            if ($kc['vendor']) {
                $vendorId = $kc['vendor']->id;
            } else {
                $vendor = Vendor::create([
                    'name' => $kc['name'],
                    'contact_person' => '企業出店担当者',
                    'contact' => '090-0000-00'.str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT),
                ]);
                $vendorId = $vendor->id;
            }

            LayoutItem::create([
                'layout_map_id' => $map->id,
                'layout_area_id' => $areaKitagawa->id,
                'item_type' => LayoutItemType::Booth,
                'vendor_id' => $vendorId,
                'code' => $kc['code'],
                'name' => $kc['name'],
                'category' => BoothCategory::CorporateBooth->value,
                'booth_type' => 'kitchen_car',
                'status' => 'confirmed',
                'notes' => $kc['items'],
                'location_description' => '北川駐車場内',
                'display_order' => 100 + $idx,
            ]);
        }

        // S&S前駐車場（体験・企業ブース）
        $ssBooths = [
            ['code' => 'SS-01', 'name' => 'ミニバス体験スペース', 'category' => BoothCategory::SpecialEvent, 'store' => null, 'items' => 'フリースロー体験コーナー'],
            ['code' => 'SS-02', 'name' => '横浜エデンの園', 'category' => BoothCategory::CorporateBooth, 'store' => '横浜エデンの園', 'items' => '福祉施設PR・オリジナルグッズ配布'],
            ['code' => 'SS-03', 'name' => 'S&Sブース', 'category' => BoothCategory::CorporateBooth, 'store' => 'S&S', 'items' => '地域協賛企業PR・抽選会'],
        ];
        foreach ($ssBooths as $idx => $sb) {
            $vendorId = null;
            if ($sb['store']) {
                $vendor = Vendor::create([
                    'name' => $sb['store'],
                    'contact_person' => '協賛企業責任者',
                    'contact' => '090-0000-01'.str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT),
                ]);
                $vendorId = $vendor->id;
            }

            LayoutItem::create([
                'layout_map_id' => $map->id,
                'layout_area_id' => $areaSS->id,
                'item_type' => LayoutItemType::Booth,
                'vendor_id' => $vendorId,
                'code' => $sb['code'],
                'name' => $sb['name'],
                'category' => $sb['category']->value,
                'booth_type' => 'tent',
                'status' => 'confirmed',
                'notes' => $sb['items'],
                'location_description' => 'S&S前駐車場内',
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
            LayoutItem::create([
                'layout_map_id' => $map->id,
                'layout_area_id' => $areaTax->id,
                'item_type' => LayoutItemType::Booth,
                'vendor_id' => null,
                'code' => $tb['code'],
                'name' => $tb['name'],
                'category' => $tb['category']->value,
                'booth_type' => 'tent',
                'status' => 'confirmed',
                'location_description' => '税務署前駐車場内',
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
            16 => 'タカオ前',
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
            $category = $isCooking ? BoothCategory::GozaOutdoorCooking : BoothCategory::GozaCookedFood;
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

            $vendorId = null;
            $items = match ($code) {
                'A01' => '新鮮野菜・旬の果物直売',
                'A03' => '生搾りジュース・フルーツパフェ',
                'A04' => '炭火焼き鳥・つくね串',
                'A05' => '下町風もんじゃ焼き・鉄板スナック',
                'A06' => '宿場特製大福・和スイーツ',
                'A07' => '手作り革小物・和小物雑貨',
                'A09' => '特製味噌ホルモン焼き',
                'A11' => '富士宮焼きそば・富士山麓ソーセージ',
                'A12' => '秘伝タレ焼き鳥・串焼き盛り合わせ',
                'A14' => '焼きたてクレープ各種',
                'A17' => '大玉たこ焼き',
                'A19' => '大阪名物浪花焼きそば',
                'A25' => 'メガネクリーニング・フレーム調整無料体験',
                'A35' => '血圧測定・健康相談',
                'A39' => 'スパイシースープカレー',
                'A41' => '高級座布団・寝具特価フェア',
                'A42' => '懐かしの駄菓子・昭和レトロ玩具',
                'A47' => '地域物産品・特産加工品',
                'A48' => '手作りアクセサリー・ワークショップ',
                default => ($isCooking ? '温かい調理品・鉄板フード' : '加工食品または手芸品・物販'),
            };

            if ($code === 'A01' || $code === 'A03') {
                $vendorId = $mizuasaVendor->id;
            } elseif ($storeName) {
                $vendor = Vendor::create([
                    'name' => $storeName,
                    'contact_person' => '出店担当 '.$code,
                    'contact' => '090-1234-'.str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT),
                ]);
                $vendorId = $vendor->id;
            }

            LayoutItem::create([
                'layout_map_id' => $map->id,
                'layout_area_id' => $areaA->id,
                'item_type' => LayoutItemType::Booth,
                'vendor_id' => $vendorId,
                'code' => $code,
                'name' => $storeName,
                'category' => $category->value,
                'booth_type' => 'tent',
                'status' => 'confirmed',
                'notes' => $storeName ? $items : null,
                'location_description' => $landmark,
                'display_order' => 1000 + $i,
            ]);
        }

        // B列 (B01〜B30)
        for ($i = 1; $i <= 30; $i++) {
            $code = 'B'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $isCooking = in_array($i, $cookingB, true);
            $category = $isCooking ? BoothCategory::GozaOutdoorCooking : BoothCategory::GozaCookedFood;
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

            $vendorId = null;
            $items = match ($code) {
                'B01' => '案内パンフレット配布・総合案内',
                'B04' => '伝統和菓子・どら焼き・草餅',
                'B05' => '厚切り牛串・豚バラ串焼き',
                'B06' => '広島風お好み焼き',
                'B07' => '特製欧風カレー・ナン',
                'B08' => '昭和レトロ古着・リサイクル雑貨',
                'B09' => 'ジャンボ宿場フランクフルト',
                'B10' => '特製ソースお好み焼き・焼きそば',
                'B12' => '特製発酵食品・味噌・甘酒',
                'B13' => '自家焙煎ドリップ珈琲・珈琲豆販売',
                'B14' => '秘伝タレ漬け込み鶏唐揚げ',
                'B15' => '本格炭火地鶏焼き・手羽先',
                'B20' => '交通安全グッズ配布・バス模型展示',
                'B22' => '秘伝ソース焼きそば',
                'B26' => 'ハンドケア体験・リラクゼーション',
                'B30' => '季節のお惣菜・手作り弁当',
                default => ($isCooking ? '温かい調理品・鉄板焼き' : '日用品・和菓子・珈琲豆'),
            };

            if ($storeName) {
                $vendor = Vendor::create([
                    'name' => $storeName,
                    'contact_person' => '出店代表 '.$code,
                    'contact' => '080-5678-'.str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT),
                ]);
                $vendorId = $vendor->id;
            }

            LayoutItem::create([
                'layout_map_id' => $map->id,
                'layout_area_id' => $areaB->id,
                'item_type' => LayoutItemType::Booth,
                'vendor_id' => $vendorId,
                'code' => $code,
                'name' => $storeName,
                'category' => $category->value,
                'booth_type' => 'tent',
                'status' => 'confirmed',
                'notes' => $storeName ? $items : null,
                'location_description' => $landmark,
                'display_order' => 2000 + $i,
            ]);
        }
    }
}
