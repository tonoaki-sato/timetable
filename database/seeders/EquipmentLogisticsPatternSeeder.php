<?php

namespace Database\Seeders;

use App\Models\EquipmentLogisticsPattern;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class EquipmentLogisticsPatternSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();
        $adminId = $admin?->id;

        // 主要拠点の取得
        $banshoUnderground = Location::where('name', '番所地下倉庫')->first();
        $banshoSpace = Location::where('name', '番所前スペース')->first();
        $taxOfficeParking = Location::where('name', '税務署前駐車場')->first();
        $communityRoom = Location::where('name', 'コミュニティルーム')->first();

        $patterns = [
            // ① カラーコーン型（交通規制連動・9ステップ）
            [
                'name' => 'カラーコーン型（交通規制連動・9ステップ）',
                'target_type' => 'committee',
                'description' => '番所地下倉庫から前日搬出、通行止め開始前のクリティカルポイント仮設置、解除後の最優先撤去・集約・倉庫格納を行う9段階標準パターン。',
                'display_order' => 10,
                'steps' => [
                    [
                        'day_phase' => 'pre_day',
                        'action_type' => 'storage_out',
                        'title' => '前日倉庫出し（地下倉庫➔番所前スペース）',
                        'from_location_id' => $banshoUnderground?->id,
                        'to_location_id' => $banshoSpace?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '地下倉庫から地上の番所前スペースへ台車等で一旦すべて運び出す',
                        'display_order' => 10,
                    ],
                    [
                        'day_phase' => 'pre_day',
                        'action_type' => 'staging_move',
                        'title' => '中継待機移動（番所前スペース➔貸切駐車場）',
                        'from_location_id' => $banshoSpace?->id,
                        'to_location_id' => $taxOfficeParking?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '駐車場が12:00以降貸切利用可能になり次第、台車で番所前スペースから移動',
                        'display_order' => 20,
                    ],
                    [
                        'day_phase' => 'day1_morning',
                        'action_type' => 'staging_move',
                        'title' => '1日目 通行止め用仮設置（事前配備）',
                        'from_location_id' => $taxOfficeParking?->id,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '支道出入口等のクリティカルポイント用コーンを交通の妨げにならない場所へ仮置き',
                        'display_order' => 30,
                    ],
                    [
                        'day_phase' => 'day1_morning',
                        'action_type' => 'setup',
                        'title' => '1日目 通行止め開始・本設置',
                        'from_location_id' => null,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '通行止め開始直後にクリティカルポイントを本配置し、私有地進入禁止用等も順次本設置',
                        'display_order' => 40,
                    ],
                    [
                        'day_phase' => 'day1_evening',
                        'action_type' => 'night_storage',
                        'title' => '1日目 通行止め解除後 夜間保管移動',
                        'from_location_id' => null,
                        'to_location_id' => $taxOfficeParking?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => 'クリティカルポイントから順に撤去し、2日目の設置場所に近い駐車場へ移動保管',
                        'display_order' => 50,
                    ],
                    [
                        'day_phase' => 'day2_morning',
                        'action_type' => 'staging_move',
                        'title' => '2日目 通行止め用仮設置（事前配備）',
                        'from_location_id' => $taxOfficeParking?->id,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '2日目も通行止め開始前にクリティカルポイント付近の邪魔にならない場所へ仮置き',
                        'display_order' => 60,
                    ],
                    [
                        'day_phase' => 'day2_morning',
                        'action_type' => 'setup',
                        'title' => '2日目 通行止め開始・本設置',
                        'from_location_id' => null,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '通行止め開始直後にクリティカルポイントを本配置、私有地進入禁止用等も順次設置',
                        'display_order' => 70,
                    ],
                    [
                        'day_phase' => 'day2_evening',
                        'action_type' => 'staging_move',
                        'title' => '2日目 通行止め解除後 撤去・集約移動',
                        'from_location_id' => null,
                        'to_location_id' => $banshoSpace?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '規制解除直後にクリティカルポイントから最優先で撤去（業者トラック進入路の開放）し番所前スペースへ集約',
                        'display_order' => 80,
                    ],
                    [
                        'day_phase' => 'day2_evening',
                        'action_type' => 'return_storage',
                        'title' => '番所地下倉庫格納完了',
                        'from_location_id' => $banshoSpace?->id,
                        'to_location_id' => $banshoUnderground?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '清掃・数量確認の上、番所前スペースから番所地下倉庫の定位置へ格納して完了',
                        'display_order' => 90,
                    ],
                ],
            ],

            // ② 一般自前資材型（2日間設置サイクル）
            [
                'name' => '一般自前資材型（2日間設置サイクル）',
                'target_type' => 'committee',
                'description' => 'ござ市用テントや看板等、事前仮置きを行わない標準的な2日間設置・夜間保管・倉庫返還サイクル。',
                'display_order' => 20,
                'steps' => [
                    [
                        'day_phase' => 'pre_day',
                        'action_type' => 'storage_out',
                        'title' => '前日倉庫出し・中継移動',
                        'from_location_id' => $banshoUnderground?->id,
                        'to_location_id' => $taxOfficeParking?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '倉庫から搬出し、番所前スペース経由で中継駐車場へ移動',
                        'display_order' => 10,
                    ],
                    [
                        'day_phase' => 'day1_morning',
                        'action_type' => 'setup',
                        'title' => '1日目 会場各ブース設置',
                        'from_location_id' => $taxOfficeParking?->id,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '朝の規定時間内に担当ブース・設置ポイントへ運搬し設置',
                        'display_order' => 20,
                    ],
                    [
                        'day_phase' => 'day1_evening',
                        'action_type' => 'night_storage',
                        'title' => '1日目 夜間保管移動',
                        'from_location_id' => null,
                        'to_location_id' => $taxOfficeParking?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '1日目終了後、近隣駐車場またはコミュニティルームへ夜間保管',
                        'display_order' => 30,
                    ],
                    [
                        'day_phase' => 'day2_morning',
                        'action_type' => 'setup',
                        'title' => '2日目 会場各ブース再設置',
                        'from_location_id' => $taxOfficeParking?->id,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '2日目朝、保管場所から会場ブースへ再配備',
                        'display_order' => 40,
                    ],
                    [
                        'day_phase' => 'day2_evening',
                        'action_type' => 'staging_move',
                        'title' => '2日目 撤収・番所前スペース集約',
                        'from_location_id' => null,
                        'to_location_id' => $banshoSpace?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => 'ブース終了後に撤収し、番所前スペースへ一時集約・点検',
                        'display_order' => 50,
                    ],
                    [
                        'day_phase' => 'day2_evening',
                        'action_type' => 'return_storage',
                        'title' => '倉庫返還・格納完了',
                        'from_location_id' => $banshoSpace?->id,
                        'to_location_id' => $banshoUnderground?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '清掃・点検の上、定常保管場所へ格納して完了',
                        'display_order' => 60,
                    ],
                ],
            ],

            // ③ 私物持ち込み・持ち帰り型
            [
                'name' => '私物持ち込み・持ち帰り型',
                'target_type' => 'personal',
                'description' => '個人持ち込み工具・特殊備品等。紛失や委員会倉庫への誤格納を防ぐため、夜間施錠保管と所有者持ち帰りを明確化。',
                'display_order' => 30,
                'steps' => [
                    [
                        'day_phase' => 'day1_morning',
                        'action_type' => 'setup',
                        'title' => '所有者持込・会場配備',
                        'from_location_id' => null,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '朝、所有者が会場または本陣へ持参し配備',
                        'display_order' => 10,
                    ],
                    [
                        'day_phase' => 'day1_evening',
                        'action_type' => 'night_storage',
                        'title' => '夜間施錠保管（コミュニティルーム）',
                        'from_location_id' => null,
                        'to_location_id' => $communityRoom?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '盗難・紛失防止のため施錠可能なコミュニティルームへ保管（または所有者一時持ち帰り）',
                        'display_order' => 20,
                    ],
                    [
                        'day_phase' => 'day2_morning',
                        'action_type' => 'setup',
                        'title' => '会場再配備',
                        'from_location_id' => $communityRoom?->id,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '朝、コミュニティルームから取り出して会場へ再配備',
                        'display_order' => 30,
                    ],
                    [
                        'day_phase' => 'day2_evening',
                        'action_type' => 'return_storage',
                        'title' => '所有者返却・持帰完了',
                        'from_location_id' => null,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '委員会倉庫への誤格納を防止し、所有者へ確実に引き渡し完了',
                        'display_order' => 40,
                    ],
                ],
            ],

            // ④ レンタル機材・委員会設置（受取・回収立会要）
            [
                'name' => 'レンタル機材・委員会設置（受取・回収立会要）',
                'target_type' => 'rental',
                'description' => '足踏み式給水設備等、前日業者受取および翌日業者回収時に幹事立会を行い、会場内での移動・保管は委員会スタッフが実施するパターン。',
                'display_order' => 40,
                'steps' => [
                    [
                        'day_phase' => 'pre_day',
                        'action_type' => 'vendor_receive',
                        'title' => '業者納品受取・検品立会',
                        'from_location_id' => null,
                        'to_location_id' => $banshoSpace?->id,
                        'operator_type' => 'committee',
                        'witness_required' => true,
                        'notes' => 'ペダル動作・付属品員数等を確認し受領サイン（立会必須）',
                        'display_order' => 10,
                    ],
                    [
                        'day_phase' => 'pre_day',
                        'action_type' => 'staging_move',
                        'title' => '貸切駐車場へ中継移動',
                        'from_location_id' => $banshoSpace?->id,
                        'to_location_id' => $taxOfficeParking?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '番所前スペースが翌日ブース化するため、12:00以降駐車場へ移動',
                        'display_order' => 20,
                    ],
                    [
                        'day_phase' => 'day1_morning',
                        'action_type' => 'setup',
                        'title' => '1日目 会場設置・通水テスト',
                        'from_location_id' => $taxOfficeParking?->id,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '会場各ポイントへ配備し、水漏れ・ペダル動作確認を実施',
                        'display_order' => 30,
                    ],
                    [
                        'day_phase' => 'day1_evening',
                        'action_type' => 'night_storage',
                        'title' => '1日目 夜間保管移動',
                        'from_location_id' => null,
                        'to_location_id' => $taxOfficeParking?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '2日目の再設置に近い近隣駐車場へ夜間保管',
                        'display_order' => 40,
                    ],
                    [
                        'day_phase' => 'day2_morning',
                        'action_type' => 'setup',
                        'title' => '2日目 会場再設置・注水',
                        'from_location_id' => $taxOfficeParking?->id,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '各ポイントへ再配備し、注水・稼働準備',
                        'display_order' => 50,
                    ],
                    [
                        'day_phase' => 'day2_evening',
                        'action_type' => 'collection_staging',
                        'title' => '水抜き・番所前回収待ち集約',
                        'from_location_id' => null,
                        'to_location_id' => $banshoSpace?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '水抜き・乾燥清掃の上、イベントブース終了後の番所前スペースへ集約',
                        'display_order' => 60,
                    ],
                    [
                        'day_phase' => 'post_day',
                        'action_type' => 'vendor_pickup',
                        'title' => '業者回収・引渡立会',
                        'from_location_id' => $banshoSpace?->id,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => true,
                        'notes' => '業者トラック積み込みに立ち会い、員数確認の上で返却サイン（立会必須）',
                        'display_order' => 70,
                    ],
                ],
            ],

            // ⑤ イベント会社一任型（設営・解体搬出）
            [
                'name' => 'イベント会社一任型（設営・解体搬出）',
                'target_type' => 'rental',
                'description' => '大型パイプテント等、イベント会社が搬入設営から歩行者規制解除後の解体搬出まで一貫して請け負うパターン。',
                'display_order' => 50,
                'steps' => [
                    [
                        'day_phase' => 'day1_morning',
                        'action_type' => 'vendor_setup',
                        'title' => 'イベント会社搬入・設営',
                        'from_location_id' => null,
                        'to_location_id' => null,
                        'operator_type' => 'event_company',
                        'witness_required' => false,
                        'notes' => '1日目早朝に業者が搬入・設営完了。幹事は開始・完了状況を把握',
                        'display_order' => 10,
                    ],
                    [
                        'day_phase' => 'day2_evening',
                        'action_type' => 'vendor_pickup',
                        'title' => '解体・トラック搬出',
                        'from_location_id' => null,
                        'to_location_id' => null,
                        'operator_type' => 'event_company',
                        'witness_required' => false,
                        'notes' => '歩行者規制解除・コーン班によるゲート開放後に業者トラックが進入し解体搬出',
                        'display_order' => 20,
                    ],
                ],
            ],

            // ⑥ 放送設備型（事前配線・現地テント内保管）
            [
                'name' => '放送設備型（事前配線・現地テント内保管）',
                'target_type' => 'rental',
                'description' => '事前配線工事（otherフェーズ）、本陣テント内での夜間そのまま保管（進入禁止コーン・横幕天幕・シート被覆の三重防犯）、解除後の配線解除・回収を行う5ステップ。',
                'display_order' => 60,
                'steps' => [
                    [
                        'day_phase' => 'other',
                        'action_type' => 'vendor_setup',
                        'title' => '事前配線工事',
                        'from_location_id' => null,
                        'to_location_id' => null,
                        'operator_type' => 'event_company',
                        'witness_required' => false,
                        'notes' => 'イベント会社と事前調整した日時に長尺ケーブル等の配線工事を実施',
                        'display_order' => 10,
                    ],
                    [
                        'day_phase' => 'day1_morning',
                        'action_type' => 'setup',
                        'title' => '放送設備設置・テスト',
                        'from_location_id' => null,
                        'to_location_id' => null,
                        'operator_type' => 'event_company',
                        'witness_required' => false,
                        'notes' => '通行止め開始前に本陣テントへ機材本設置・音響テスト完了',
                        'display_order' => 20,
                    ],
                    [
                        'day_phase' => 'day1_evening',
                        'action_type' => 'night_storage',
                        'title' => 'テント内保管（三重防犯対策）',
                        'from_location_id' => null,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '本陣裏手駐車場進入禁止コーン＋テント四方天幕覆い＋シート被覆（※荒天予測時はコミュニティルームへ退避）',
                        'display_order' => 30,
                    ],
                    [
                        'day_phase' => 'day2_morning',
                        'action_type' => 'other',
                        'title' => '稼働前設備確認',
                        'from_location_id' => null,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => 'シートを外し、稼働前の音響・通電チェックを実施',
                        'display_order' => 40,
                    ],
                    [
                        'day_phase' => 'day2_evening',
                        'action_type' => 'vendor_pickup',
                        'title' => '機材回収・配線解除',
                        'from_location_id' => null,
                        'to_location_id' => null,
                        'operator_type' => 'event_company',
                        'witness_required' => false,
                        'notes' => '通行止め解除後、イベント会社による機材回収および配線解除作業を実施して完了',
                        'display_order' => 50,
                    ],
                ],
            ],
            // ⑦ 足踏み式給水設備型パターン
            [
                'name' => '足踏み式給水設備型パターン',
                'target_type' => 'rental',
                'description' => '足踏み式給水設備 の搬出入ステップから作成されたパターン',
                'display_order' => 70,
                'steps' => [
                    [
                        'day_phase' => 'pre_day',
                        'action_type' => 'vendor_receive',
                        'title' => '業者納品受取・検品立会',
                        'from_location_id' => $banshoSpace?->id,
                        'to_location_id' => $banshoSpace?->id,
                        'operator_type' => 'committee',
                        'witness_required' => true,
                        'notes' => 'ペダル動作・付属品員数等を確認',
                        'display_order' => 10,
                    ],
                    [
                        'day_phase' => 'pre_day',
                        'action_type' => 'staging_move',
                        'title' => '貸切駐車場へ中継移動',
                        'from_location_id' => $banshoSpace?->id,
                        'to_location_id' => $taxOfficeParking?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '番所前スペースが翌日ブース化するため、12:00以降駐車場へ移動',
                        'display_order' => 20,
                    ],
                    [
                        'day_phase' => 'day1_morning',
                        'action_type' => 'setup',
                        'title' => '1日目 会場設置・通水テスト',
                        'from_location_id' => $taxOfficeParking?->id,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '会場各ポイントへ配備し、水漏れ・ペダル動作確認を実施',
                        'display_order' => 30,
                    ],
                    [
                        'day_phase' => 'day1_evening',
                        'action_type' => 'night_storage',
                        'title' => '1日目 夜間保管移動',
                        'from_location_id' => null,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '2日目の再設置に近い近隣駐車場へ夜間保管',
                        'display_order' => 40,
                    ],
                    [
                        'day_phase' => 'day2_morning',
                        'action_type' => 'setup',
                        'title' => '2日目 会場再設置・注水',
                        'from_location_id' => null,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => '各ポイントへ再配備し、注水・稼働準備',
                        'display_order' => 50,
                    ],
                    [
                        'day_phase' => 'day2_evening',
                        'action_type' => 'collection_staging',
                        'title' => '番所前回収待ち集約',
                        'from_location_id' => null,
                        'to_location_id' => $banshoSpace?->id,
                        'operator_type' => 'committee',
                        'witness_required' => false,
                        'notes' => 'イベントブース終了後の番所前スペースへ集約',
                        'display_order' => 60,
                    ],
                    [
                        'day_phase' => 'post_day',
                        'action_type' => 'vendor_pickup',
                        'title' => '業者回収・引渡立会',
                        'from_location_id' => $banshoSpace?->id,
                        'to_location_id' => null,
                        'operator_type' => 'committee',
                        'witness_required' => true,
                        'notes' => '業者トラック積み込みに立ち会い、員数確認の上で返却サイン（立会必須）',
                        'display_order' => 70,
                    ],
                ],
            ],
        ];

        foreach ($patterns as $patternData) {
            $steps = $patternData['steps'];
            unset($patternData['steps']);

            $pattern = EquipmentLogisticsPattern::updateOrCreate(
                ['name' => $patternData['name']],
                [
                    'target_type' => $patternData['target_type'],
                    'description' => $patternData['description'],
                    'display_order' => $patternData['display_order'],
                    'created_by' => $adminId,
                ]
            );

            // 既存ステップを再作成
            $pattern->steps()->delete();

            foreach ($steps as $stepData) {
                $pattern->steps()->create($stepData);
            }
        }
    }
}
