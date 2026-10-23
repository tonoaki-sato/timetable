<?php

namespace Database\Seeders;

use App\Models\AnnualSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnualScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        $schedules = [
            // =========================================================================
            // 2027年度（第35回 保土ケ谷宿場まつり 年間スケジュール）
            // =========================================================================

            // --- 1月：フェーズ1【設立準備】 ---

            // --- 2月：フェーズ1～2【設立準備～計画段階】 ---

            // --- 3月：フェーズ2【計画段階】 ---
            [
                'target_year' => 2027,
                'title' => '前年度会計年度末締め調整',
                'category' => 'other',
                'start_date' => '2027-03-31',
                'end_date' => null,
                'location' => '実行委員会事務局',
                'status' => 'planned',
                'description' => '前年度会計の年度末締め（3/31締め）処理完了、次年度繰越金の確定と口座管理手続き。',
                'created_by' => $admin->id,
            ],

            // --- 4月：フェーズ2【計画段階】 ---
            [
                'target_year' => 2027,
                'title' => 'キャッチフレーズ事前募集・タブロイド新聞準備',
                'category' => 'other',
                'start_date' => '2027-04-10',
                'end_date' => '2027-04-24',
                'location' => 'オンライン / 事務局',
                'status' => 'planned',
                'description' => 'Googleフォーム等を活用したキャッチフレーズの事前募集と投票実施、タブロイド新聞・広告募集チラシの版下準備。',
                'created_by' => $admin->id,
            ],

            // --- 5月：フェーズ2～3【計画段階～準備段階】 ---
            [
                'target_year' => 2027,
                'title' => '宿場まつり新聞 広告・協賛金募集開始',
                'category' => 'briefing',
                'start_date' => '2027-05-15',
                'end_date' => '2027-07-31',
                'location' => '商店街一帯 / 協賛企業窓口',
                'status' => 'planned',
                'description' => '宿場まつり新聞の広告枠（1枠1万円）・協賛金（1口5,000円）の近隣企業・商店街募集を開始。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '運行マニュアル改訂・行政関係機関事前相談',
                'category' => 'permit',
                'start_date' => '2027-05-20',
                'end_date' => null,
                'location' => '保土ケ谷警察署 / 保土ケ谷区役所 / 保土ケ谷消防署',
                'status' => 'planned',
                'description' => '運行ガイドライン・安全マニュアルの改訂作業、必要に応じた警察署・区役所・消防署への早期事前相談の実施。',
                'created_by' => $admin->id,
            ],

            // --- 6月：フェーズ3【準備段階】 ---
            [
                'target_year' => 2027,
                'title' => '行政補助金申請・後援名義申請手続き',
                'category' => 'permit',
                'start_date' => '2027-06-15',
                'end_date' => null,
                'location' => '保土ケ谷区役所 地域振興課',
                'status' => 'planned',
                'description' => '横浜市「商店街にぎわい促進事業補助金」および保土ケ谷区イベント活性化助成の交付申請書提出、行政・関係団体への後援名義使用申請。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => 'ポスター原案完成・入稿＆イベント保険見積検討',
                'category' => 'other',
                'start_date' => '2027-06-20',
                'end_date' => null,
                'location' => '事務局 / 印刷会社 / 保険代理店',
                'status' => 'planned',
                'description' => '公式ポスター原案の最終確認と印刷会社への入稿（6月下旬納品）、レクリエーション賠償責任保険の見積・プラン検討。',
                'created_by' => $admin->id,
            ],

            // --- 7月：フェーズ3【準備段階】 ---
            [
                'target_year' => 2027,
                'title' => 'ござ市出店者公募開始（街路灯パンフ掲示）',
                'category' => 'briefing',
                'start_date' => '2027-07-06',
                'end_date' => '2027-07-31',
                'location' => '商店街街路灯 / 事務局',
                'status' => 'planned',
                'description' => '街路灯パンフレット掲示およびWebサイトでの「ござ市」出店者公募開始（飲食・物販出店規約周知、7/31締切）。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => 'ステージ企画（カラオケ大会等）出演者募集',
                'category' => 'briefing',
                'start_date' => '2027-07-15',
                'end_date' => '2027-08-31',
                'location' => '事務局受付 / オンライン',
                'status' => 'planned',
                'description' => '税務署前ステージ・各会場での出演団体および名物カラオケ大会の一般参加者募集受付。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '自治会掲示板用ポスター区役所納品',
                'category' => 'other',
                'start_date' => '2027-07-24',
                'end_date' => null,
                'location' => '保土ケ谷区役所',
                'status' => 'planned',
                'description' => '地区連合町内会長会議（7/25頃）での配布に向け、地域自治会掲示板用ポスター（A4版約750部）を保土ケ谷区役所へ納品。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '新聞広告申込締切・臨時駐車場貸切申込',
                'category' => 'permit',
                'start_date' => '2027-07-31',
                'end_date' => null,
                'location' => '事務局 / タイムズ等',
                'status' => 'planned',
                'description' => '宿場まつり新聞広告の申込受付締め切り、本番時機材・スタッフ用臨時駐車場（タイムズ等）の貸切申込手続き完了。',
                'created_by' => $admin->id,
            ],

            // --- 8月：フェーズ3～4【準備段階～実施段階】 ---
            [
                'target_year' => 2027,
                'title' => '横浜市にぎわい促進事業補助金 交付決定',
                'category' => 'permit',
                'start_date' => '2027-08-05',
                'end_date' => null,
                'location' => '横浜市経済局 / 保土ケ谷区役所',
                'status' => 'planned',
                'description' => '横浜市「商店街にぎわい促進事業補助金」の交付決定通知を受領、助成額および対象経費の確認。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '宿場まつり新聞 原稿協賛締切・校正・入稿',
                'category' => 'other',
                'start_date' => '2027-08-10',
                'end_date' => '2027-08-20',
                'location' => '事務局 / 印刷会社',
                'status' => 'planned',
                'description' => '新聞掲載原稿・協賛者名簿の最終締切（8/7〜8/20頃）。最終校正、印刷代支払、印刷会社への正式入稿完了。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '当日ボランティア公募開始',
                'category' => 'briefing',
                'start_date' => '2027-08-15',
                'end_date' => '2027-09-15',
                'location' => 'オンライン / 公式サイト',
                'status' => 'planned',
                'description' => 'まつり当日の運営スタッフ・ボランティア（衛生・誘導・給水・ステージ補助）のWeb募集告知および受付開始。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '区長・警察署長・消防署長 表敬訪問',
                'category' => 'permit',
                'start_date' => '2027-08-25',
                'end_date' => null,
                'location' => '保土ケ谷区役所 / 保土ケ谷警察署 / 保土ケ谷消防署',
                'status' => 'planned',
                'description' => '保土ケ谷区長、保土ケ谷警察署長、保土ケ谷消防署長への表敬訪問と開催概要説明・協力要請挨拶。',
                'created_by' => $admin->id,
            ],

            // --- 9月：フェーズ4【実施段階】 ---
            [
                'target_year' => 2027,
                'title' => '宿場まつり新聞納品・近隣小中学校配布',
                'category' => 'other',
                'start_date' => '2027-09-04',
                'end_date' => null,
                'location' => '近隣小中学校（約25校） / 地域施設',
                'status' => 'planned',
                'description' => 'タブロイド新聞納品受領。保土ケ谷区内近隣小中学校（約25校）および地区センター等の地域公共施設へ配布。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '道路使用許可申請本提出・保健所届出協議',
                'category' => 'permit',
                'start_date' => '2027-09-08',
                'end_date' => null,
                'location' => '保土ケ谷警察署 / 保土ケ谷福祉保健センター',
                'status' => 'planned',
                'description' => '保土ケ谷警察署へ道路使用許可申請書（通行止め規制図・ござ市出店者名簿・配置図）を本提出。保健所へござ市食品販売および給水設備図面の届出・衛生協議。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '直前準備予備日（資材袋詰め・名札作成）',
                'category' => 'operation',
                'start_date' => '2027-09-17',
                'end_date' => null,
                'location' => 'コミュニティルーム',
                'status' => 'planned',
                'description' => '悪天候時のポスティング予備日および室内作業（スタッフ名札作成、案内資料袋詰め、資機材点検）。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => 'ござ市出店者配置確定連絡・代替駐車場手配',
                'category' => 'briefing',
                'start_date' => '2027-09-20',
                'end_date' => null,
                'location' => '実行委員会事務局',
                'status' => 'planned',
                'description' => 'ござ市出店者への小間位置決定連絡・出店許可証の送付、出店者用代替駐車場の割り当て・案内完了。',
                'created_by' => $admin->id,
            ],

            // --- 10月：フェーズ4【実施段階（まつり本番）】 ---
            [
                'target_year' => 2027,
                'title' => '道路使用許可証受領・イベント施工最終調整',
                'category' => 'permit',
                'start_date' => '2027-10-02',
                'end_date' => null,
                'location' => '保土ケ谷警察署 / 本部事務局',
                'status' => 'planned',
                'description' => '保土ケ谷警察署から道路使用許可証を受領。イベント施工会社（OverCom等）との最終搬入施工スケジュールの確認と確定。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '本番1週前会場整備（イコット広場草刈り・新聞駅配布・ポスティング）',
                'category' => 'operation',
                'start_date' => '2027-10-03',
                'end_date' => null,
                'location' => 'イコット広場 / コミュニティルーム / 保土ケ谷駅前',
                'status' => 'planned',
                'description' => '09:00〜 イコット広場草刈り（キッズ村会場整備、アネゴ依頼）、17:00〜 保土ケ谷駅前新聞配布、通行止めチラシのポスティング（2回目）。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '開催直前新聞折込・メディア告知',
                'category' => 'other',
                'start_date' => '2027-10-08',
                'end_date' => null,
                'location' => '読売・朝日新聞等折込 / 神奈川新聞',
                'status' => 'planned',
                'description' => '直前新聞折込（読売・朝日・毎日等約2万部折込）、神奈川新聞地域版イベント告知記事掲載。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '前日準備・設営・位置出し（隅出し）・資機材搬入',
                'category' => 'operation',
                'start_date' => '2027-10-09',
                'end_date' => null,
                'location' => 'コミュニティルーム / メイン通り会場一帯',
                'status' => 'planned',
                'description' => '日中〜夕方 新聞駅配布、17:00〜 コミュニティルーム集合、前日設営・道路位置出し（隅出し）・足踏み式給水設備・カラーコーン等資機材の搬入および駐車場への仮設置。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '保土ケ谷宿場まつり本番運行 1日目',
                'category' => 'operation',
                'start_date' => '2027-10-10',
                'end_date' => null,
                'location' => '本陣前 / 旧東海道特設メイン会場一帯',
                'status' => 'planned',
                'description' => '06:00 実施判断、07:00 本陣集合・全体ミーティング、10:00〜18:00 車両通行止め（直後にゴミ箱30個+イコット6個・給水設備5台・カラーコーン88個設置）、11:00〜17:00 まつり本番（ござ市・税務署ステージ・キッズ村・企業ブース、衛生・給水・巡回警備）、17:00〜 一部機材撤収・北川駐車場等へ仮保管。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '保土ケ谷宿場まつり本番運行 2日目＆当日打ち上げ',
                'category' => 'operation',
                'start_date' => '2027-10-11',
                'end_date' => null,
                'location' => '本陣前 / 運行エリア全域 / 吉祥楼',
                'status' => 'planned',
                'description' => '06:00 実施判断、08:00 本陣集合、10:00〜18:00 通行止め・再配置、11:00〜17:00 本番運行、17:00〜 完全撤収・ポスター剥がし・捨て看板外し・資機材番所地下格納、18:00 通行止め解除・清掃、19:00 ゴミ回収車積み込み、19:30〜 当日打ち上げ（吉祥楼等）。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => 'レンタル資機材回収',
                'category' => 'operation',
                'start_date' => '2027-10-12',
                'end_date' => null,
                'location' => '本陣 / 保土ケ谷警察署 / 保土ケ谷区役所',
                'status' => 'planned',
                'description' => 'レンタル機材（OverCom等）の回収立ち合い、まつり期間中の拾得物の保土ケ谷警察署届出、区役所・警察署・消防署への無事終了御礼連絡、警備費支払。',
                'created_by' => $admin->id,
            ],

            // --- 11月：フェーズ5【フォローアップ】 ---
            [
                'target_year' => 2027,
                'title' => '保土ケ谷区イベント活性化助成 実績報告書提出',
                'category' => 'permit',
                'start_date' => '2027-11-10',
                'end_date' => null,
                'location' => '保土ケ谷区役所 地域振興課',
                'status' => 'planned',
                'description' => '保土ケ谷区「商店街活性化イベント助成事業」実績報告書および収支報告書の提出（11/10頃締切）。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2027,
                'title' => '各業者請求書精算・支払処理完了',
                'category' => 'other',
                'start_date' => '2027-11-30',
                'end_date' => null,
                'location' => '実行委員会事務局',
                'status' => 'planned',
                'description' => 'イベント施工会社、ゴミ収集処分業者、印刷会社等への最終請求書精算および支払処理の完了。',
                'created_by' => $admin->id,
            ],
        ];

        foreach ($schedules as $schedule) {
            AnnualSchedule::updateOrCreate(
                [
                    'target_year' => $schedule['target_year'],
                    'title' => $schedule['title'],
                ],
                $schedule
            );
        }
    }
}
