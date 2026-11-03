<x-layouts.app title="システム操作マニュアル">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- ページヘッダー -->
        <x-page-header
            title="システム操作マニュアル"
            icon="📖"
            description="まつり総合管理システム（運行本部・タイムテーブル・人員機材動態・年間工程）の公式操作ガイド"
            class="mb-6 pb-5 border-b border-slate-200">
            <x-slot:actions>
                <!-- キーワードクイック検索 -->
                <div class="relative w-48 sm:w-64">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-xs">🔍</span>
                    <input type="text" id="manualSearchInput"
                        placeholder="キーワード検索 (例: 機材, アサイン)..."
                        class="w-full pl-8 pr-3 py-2 bg-white border border-slate-300 rounded-xl text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                </div>
                <!-- 印刷ボタン -->
                <button type="button" data-action="print"
                    class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs cursor-pointer print:hidden shrink-0">
                    <span>🖨️</span>
                    <span>印刷 / PDF出力</span>
                </button>
            </x-slot:actions>
        </x-page-header>

        <!-- メイングリッド (サイドバー目次 + コンテンツ) -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">

            <!-- 左側: 固定目次ナビゲーション (Desktop) -->
            <nav class="hidden lg:block lg:col-span-1 sticky top-20 bg-white border border-slate-200 rounded-2xl p-4 shadow-xs text-xs space-y-1 print:hidden" aria-label="マニュアル目次">
                <div class="font-extrabold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center justify-between">
                    <span class="flex items-center space-x-1.5">
                        <span>📑</span>
                        <span>目次・章一覧</span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-mono">全10章</span>
                </div>
                <div class="space-y-1 max-h-[calc(100vh-160px)] overflow-y-auto pr-1">
                    <a href="#sec-overview" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第1章</span>
                        <span class="truncate">概要・権限・プレゼン</span>
                    </a>
                    <a href="#sec-dashboard" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第2章</span>
                        <span class="truncate">本部ダッシュボード</span>
                    </a>
                    <a href="#sec-calendar" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第3章</span>
                        <span class="truncate">年間工程・会議・LINE告知</span>
                    </a>
                    <a href="#sec-timetable" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第4章</span>
                        <span class="truncate">進行表マトリクス</span>
                    </a>
                    <a href="#sec-tasks" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第5章</span>
                        <span class="truncate">運行準備ナビ &amp; 編成アサイン</span>
                    </a>
                    <a href="#sec-staff" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第6章</span>
                        <span class="truncate">スタッフ管理 &amp; Webセルフ登録</span>
                    </a>
                    <a href="#sec-logistics" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第7章</span>
                        <span class="truncate">資材機材物流 &amp; 拠点会場</span>
                    </a>
                    <a href="#sec-myshift" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第8章</span>
                        <span class="truncate">現場マイシフト &amp; 自律運行</span>
                    </a>
                    <a href="#sec-users" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第9章</span>
                        <span class="truncate">ユーザー管理・権限設定</span>
                    </a>
                    <a href="#sec-faq" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">付録</span>
                        <span class="truncate">よくある質問 &amp; FAQ</span>
                    </a>
                </div>
            </nav>

            <!-- 右側: マニュアル本文コンテンツ -->
            <div class="col-span-1 lg:col-span-3 space-y-12" id="manualContent">

                <!-- ========================================== -->
                <!-- 第1章: システム概要・権限・ログイン & プレゼン -->
                <!-- ========================================== -->
                <section id="sec-overview" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 01</span>
                        <span>•</span>
                        <span>Getting Started &amp; Overview</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第1章 システム概要・利用権限・ログイン手順 &amp; 幹事会プレゼン</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        本システムは、まつり・イベントの年間準備から前日準備・当日本番運行・翌日回収に至るまでの運行・人員・機材動態を一元管理するクラウド型総合管理システムです。
                    </p>

                    <!-- 権限ロール比較カード -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="p-4 bg-purple-50/60 border border-purple-200/80 rounded-xl">
                            <div class="flex items-center space-x-2">
                                <span class="bg-purple-900 text-purple-200 text-[10px] font-bold px-2 py-0.5 rounded">管理者 (Admin)</span>
                            </div>
                            <h3 class="font-bold text-sm text-purple-950 mt-2">実行委員長・システム責任者</h3>
                            <p class="text-xs text-purple-800 mt-1 leading-normal">
                                全ての機能（ユーザー登録・権限変更、日程・レーン基本設計、タイムテーブル編集、全帳票出力）を利用可能。
                            </p>
                        </div>
                        <div class="p-4 bg-blue-50/60 border border-blue-200/80 rounded-xl">
                            <div class="flex items-center space-x-2">
                                <span class="bg-slate-800 text-slate-200 text-[10px] font-bold px-2 py-0.5 rounded">本部オペレーター</span>
                            </div>
                            <h3 class="font-bold text-sm text-blue-950 mt-2">本部当番・進行管理担当</h3>
                            <p class="text-xs text-blue-800 mt-1 leading-normal">
                                タイムテーブル運行、タスク進捗更新、人員アサイン調整、スタッフ証印刷、出欠管理などの日常運用を担当。
                            </p>
                        </div>
                        <div class="p-4 bg-amber-50/60 border border-amber-200/80 rounded-xl">
                            <div class="flex items-center space-x-2">
                                <span class="bg-amber-700 text-amber-100 text-[10px] font-bold px-2 py-0.5 rounded">👑 班長 (担当レーン主担当)</span>
                            </div>
                            <h3 class="font-bold text-sm text-amber-950 mt-2">ボランティアと管理者の中間管理・班責任者</h3>
                            <p class="text-xs text-amber-900 mt-1 leading-normal">
                                班（レーン）に紐づく主担当を「班長」と呼称。ボランティアスタッフと管理者との中間に位置し、班のタスク管理、人員不足時の管理者への要請、現場での関係班長を集めた打ち合わせ参加、必要機材の申請を担います。
                            </p>
                        </div>
                        <div class="p-4 bg-emerald-50/60 border border-emerald-200/80 rounded-xl">
                            <div class="flex items-center space-x-2">
                                <span class="bg-emerald-800 text-emerald-100 text-[10px] font-bold px-2 py-0.5 rounded">現場スタッフ・ボランティア</span>
                            </div>
                            <h3 class="font-bold text-sm text-emerald-950 mt-2">固定スタッフ &amp; 充足スタッフ</h3>
                            <p class="text-xs text-emerald-900 mt-1 leading-normal">
                                個別パスワード不要。名札の専用QRコードからスマホ専用の「マイシフト画面」へアクセスし、出勤打刻、作業着手・完了報告、応援要請を行います。
                            </p>
                            <div class="mt-2 pt-2 border-t border-emerald-200/60 text-[11px] text-emerald-950">
                                💡 <strong>Webセルフ登録:</strong>
                                一般公募や学生ボランティアは、本部が発行する募集URL（<code class="px-1 py-0.2 bg-emerald-100 text-emerald-900 rounded font-mono">/join</code>）からアカウント作成なしに即座に事前セルフ登録できます（第6章参照）。
                            </div>
                        </div>
                    </div>

                    <!-- 実際の画面画像: ログイン画面 -->
                    <div class="mt-8 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】ログイン画面 (/login)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/01_login.png') }}"
                                alt="ログイン画面の実画面キャプチャ"
                                data-modal-image data-caption="本部システムログイン画面"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- ログイン手順とセキュリティ方針 -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2 text-xs">
                            <h4 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                                <span>🔑</span>
                                <span>本部ログイン手順</span>
                            </h4>
                            <ol class="list-decimal list-inside space-y-1 text-slate-600 leading-relaxed">
                                <li>ブラウザでシステムのトップURLへアクセスします。</li>
                                <li>メールアドレスとパスワードを入力します。</li>
                                <li>「ログイン」ボタンをクリックすると本部ダッシュボードへ遷移します。</li>
                            </ol>
                        </div>

                        <!-- 幹事会プレゼンスライド連携 -->
                        <div class="p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-sm text-indigo-950 flex items-center space-x-2">
                                    <span>📽️</span>
                                    <span>幹事会プレゼン・合意形成スライド</span>
                                </h4>
                                <a href="{{ route('presentation.index') }}" class="text-[10px] text-indigo-600 hover:underline font-bold">/presentation ↗</a>
                            </div>
                            <p class="text-indigo-900 leading-relaxed text-[11px]">
                                まつり役員会や幹事会で「なぜシステム導入が必要なのか」「紙の進行表からの脱却メリット」「班長・ボランティアの役割」を説明するための全画面プレゼンテーションスライドを内蔵しています。
                            </p>
                            <div class="text-[10px] text-indigo-700 font-mono">
                                💡 キーボードの [←][→] または [Space] キーでスライド送り、[F] キーで全画面表示が可能です。
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第2章: 本部ダッシュボードの見方と進捗管理 -->
                <!-- ========================================== -->
                <section id="sec-dashboard" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 02</span>
                        <span>•</span>
                        <span>Dashboard Operations</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第2章 本部ダッシュボードの見方と進捗管理</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        本部ダッシュボードは、システムログイン直後に表示される司令塔画面です。まつり運行の全体進捗率、班別の遅延状況、現場ボランティアの出勤状況、直近の会議予定を一目で俯瞰できます。
                    </p>

                    <!-- 実際の画面画像: ダッシュボード -->
                    <div class="mt-6 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】本部ダッシュボード画面 (/dashboard)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/02_dashboard.png') }}"
                                alt="本部ダッシュボードの実画面キャプチャ"
                                data-modal-image data-caption="本部ダッシュボード画面"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- 3大情報ブロックの解説 -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs sm:text-sm">
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <div class="font-bold text-slate-900 flex items-center space-x-1.5">
                                <span class="text-indigo-600 font-extrabold text-sm">01.</span>
                                <span>運行ステータス &amp; リアルタイム自動更新</span>
                            </div>
                            <p class="text-slate-600 text-xs leading-relaxed">
                                現在運行中のタイムテーブル（例: Day 1 神輿渡御）の進行状況を表示。「リアルタイム更新」スイッチをONにすると、リロード不要で現場のタスク完了がリアルタイムに反映されます。
                            </p>
                        </div>
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <div class="font-bold text-slate-900 flex items-center space-x-1.5">
                                <span class="text-indigo-600 font-extrabold text-sm">02.</span>
                                <span>班別（レーン別）進捗バー</span>
                            </div>
                            <p class="text-slate-600 text-xs leading-relaxed">
                                警備班・神輿運行班・衛生班などの進捗率をプログレスバーで一覧化。遅延が発生している班はオレンジ・赤で強調され、重点的なフォローに入れます。
                            </p>
                        </div>
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <div class="font-bold text-slate-900 flex items-center space-x-1.5">
                                <span class="text-indigo-600 font-extrabold text-sm">03.</span>
                                <span>要対応アラート &amp; 近日開催会議</span>
                            </div>
                            <p class="text-slate-600 text-xs leading-relaxed">
                                未割り当てタスクや人員不足警告、直近に迫った総会・幹事会（アジェンダ・場所）をコンパクトに通知。クリックで直接対象画面へジャンプできます。
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第3章: まつり年間工程・総合カレンダー & 会議・LINE告知 -->
                <!-- ========================================== -->
                <section id="sec-calendar" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 03</span>
                        <span>•</span>
                        <span>Calendar, Schedules &amp; Meetings</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第3章 まつり年間工程・総合カレンダー &amp; 会議・LINE告知</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        4月から翌年3月までの12ヶ月運用サイクルを管理します。行政協議（警察の道路使用許可、消防・保健所届出）、業者機材早期予約、出店者募集、定期会議（総会・幹事会）の漏れを防ぎ、LINE告知まで一元化します。
                    </p>

                    <!-- 実際の画面画像: 総合カレンダー -->
                    <div class="mt-6 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】総合カレンダー画面 (/calendar)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/03_calendar.png') }}"
                                alt="総合カレンダー画面の実画面キャプチャ"
                                data-modal-image data-caption="総合カレンダー画面"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- カレンダーの使い方 -->
                    <div class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm">
                        <h4 class="font-bold text-slate-900 mb-2">💡 ドラッグ＆ドロップによる直感登録</h4>
                        <p class="text-slate-600 text-xs leading-relaxed">
                            画面上部の種別アイコンパレット（🤝会議、📑申請、📢募集、🎪運行）から該当日付のセルへ<strong>ドラッグ＆ドロップ</strong>するだけで、種別がプリセットされた登録モーダルが起動します。予定のリスケジュールも日付間でドラッグ移動するだけで完了します。
                        </p>
                    </div>

                    <!-- 実際の画面画像: 年間スケジュール -->
                    <div class="mt-8 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】スケジュール管理一覧 (/schedules)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/04_annual_schedules.png') }}"
                                alt="年間スケジュール画面の実画面キャプチャ"
                                data-modal-image data-caption="年間スケジュール一覧画面"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- 会議・打ち合わせ管理 & LINE告知文自動生成機能 -->
                    <div class="mt-8 border-t border-slate-200 pt-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base sm:text-lg font-bold text-slate-900 flex items-center space-x-2">
                                <span>🤝</span>
                                <span>会議・打ち合わせ管理 &amp; LINE告知文ワンクリック生成</span>
                            </h3>
                            <span class="text-xs font-mono text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">/schedules</span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            まつり実行委員会、総会、幹事会、町内会連絡会、現場リーダーミーティングの日程、開催場所、議題（アジェンダ）、決定事項メモを一元管理します。
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-xl space-y-2">
                                <div class="font-bold text-emerald-950 flex items-center space-x-1.5 text-sm">
                                    <span>💬</span>
                                    <span>ワンクリックLINE告知テキスト生成</span>
                                </div>
                                <p class="text-emerald-900 leading-relaxed text-[11px]">
                                    会議詳細画面にある<strong>「LINE告知文をコピー」</strong>ボタンを押すだけで、幹事会LINEグループや町内会連絡用LINEにそのまま貼り付け可能な整形テキストがクリップボードにコピーされます。
                                </p>
                                <div class="bg-white p-2.5 rounded-lg border border-emerald-200 text-[10px] font-mono text-emerald-900 leading-normal">
                                    【第2回 まつり幹事会のご案内】<br>
                                    📅 日時: 2026年10月15日(木) 19:00〜21:00<br>
                                    📍 場所: 青葉コミュニティセンター 2F大会議室<br>
                                    📋 議題: 道路使用許可申請、神輿渡御タイムテーブル確認、各町会機材手配状況<br>
                                    持ち物: 前期決算資料、筆記用具
                                </div>
                            </div>

                            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                                <div class="font-bold text-slate-900 flex items-center space-x-1.5 text-sm">
                                    <span>🎯</span>
                                    <span>会議と担当班の連動</span>
                                </div>
                                <p class="text-slate-600 leading-relaxed text-[11px]">
                                    会議ごとに対象となる班（警備班、衛生班、総務班等）を指定できるため、出席義務のある役員・班長へ確実に要請できます。終了後は決定事項メモを記入しておくことで、次年度への引き継ぎ台帳としても機能します。
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第4章: 運行タイムテーブルマトリクス表示 -->
                <!-- ========================================== -->
                <section id="sec-timetable" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 04</span>
                        <span>•</span>
                        <span>Matrix Timetable &amp; Assignments</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第4章 運行タイムテーブルマトリクス表示</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        運行本部の大型ディスプレイに投影して使用する中心的な画面です。<strong>横軸に担当班、縦軸に時間スロット（15分単位）</strong>を配置し、全体の業務進捗・人員配置・優先順位を俯瞰できます。
                    </p>

                    <!-- 実際の画面画像: タイムテーブルマトリクス -->
                    <div class="mt-6 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】運行進行表マトリクス俯瞰ビュー (/timetable)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/05_timetable_matrix.png') }}"
                                alt="タイムテーブルマトリクス画面の実画面キャプチャ"
                                data-modal-image data-caption="運行進行表マトリクス俯瞰画面"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- マトリクス画面の3大機能 -->
                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <div class="p-4 bg-red-50/80 border border-red-200 rounded-xl">
                            <div class="font-bold text-red-900 flex items-center space-x-1.5">
                                <span>🔴</span>
                                <span>現在時刻インジケーター (Now Bar)</span>
                            </div>
                            <p class="text-red-800 mt-1.5 leading-relaxed">
                                当日の現在時刻に合わせて赤い水平線がリアルタイムに降下。今行われるべき作業と完了状況が一目で判別できます。
                            </p>
                        </div>
                        <div class="p-4 bg-amber-50/80 border border-amber-200 rounded-xl">
                            <div class="font-bold text-amber-900 flex items-center space-x-1.5">
                                <span>⚡</span>
                                <span>最優先バッジ &amp; 機材表示</span>
                            </div>
                            <p class="text-amber-800 mt-1.5 leading-relaxed">
                                車両通行止め直後の最優先設置箇所や、外部業者立会タスクにバッジが表示され、属人化しがちな手順を標準化します。
                            </p>
                        </div>
                        <div class="p-4 bg-indigo-50/80 border border-indigo-200 rounded-xl">
                            <div class="font-bold text-indigo-900 flex items-center space-x-1.5">
                                <span>👥</span>
                                <span>人員アサイン状況の即時把握</span>
                            </div>
                            <p class="text-indigo-800 mt-1.5 leading-relaxed">
                                タスクカード内に【必要人数】と割り当てられたスタッフ名が表示され、不足タスクは黄色・赤色の警告色で一目瞭然です。
                            </p>
                        </div>
                    </div>

                    <!-- 人員配置表の印刷機能案内 -->
                    <div class="mt-6 p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                        <div>
                            <span class="font-bold text-indigo-950 flex items-center space-x-1.5">
                                <span>🖨️</span>
                                <span>当日の人員配置表（A4横・班別タスク一覧）の印刷出力</span>
                            </span>
                            <p class="text-indigo-900 mt-1 text-[11px]">
                                本部指揮官や各班長へ紙で配布するための「人員配置表（<code class="px-1 py-0.2 bg-white rounded font-mono text-indigo-700">/timetables/{id}/print-assignments</code>）」をA4横向きで美しく印刷できます。
                            </p>
                        </div>
                        <span class="px-2.5 py-1 bg-indigo-600 text-white rounded-lg text-[10px] font-bold shrink-0">ヘッダー「まつり運行」▾ から出力可能</span>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第5章: 運行準備ナビゲーター & 台帳・班編成・アサイン -->
                <!-- ========================================== -->
                <section id="sec-tasks" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 05</span>
                        <span>•</span>
                        <span>Preparation Navigator &amp; Task Assignments</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第5章 運行準備ナビゲーター &amp; 台帳・担当班編成・人員アサイン</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        まつり本番に先立ち、迷わず準備を進められる4ステップの「準備ナビゲーター」、日程枠台帳、担当班編成、作業タスク登録、および重複検知付き人員アサインを行う手順です。
                    </p>

                    <!-- 新機能: まつり運行準備ナビゲーター -->
                    <div class="mt-6 p-5 bg-gradient-to-br from-indigo-50 to-slate-50 border-2 border-indigo-200 rounded-2xl space-y-4">
                        <div class="flex items-center justify-between border-b border-indigo-200/80 pb-3">
                            <div>
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-indigo-600 text-white uppercase tracking-wider">Guided Wizard</span>
                                <h3 class="text-base sm:text-lg font-black text-slate-900 mt-1 flex items-center space-x-2">
                                    <span>🚀</span>
                                    <span>まつり運行準備ナビゲーター（4ステップ準備ウィザード）</span>
                                </h3>
                            </div>
                            <span class="text-xs font-mono text-indigo-600 bg-white px-2.5 py-1 rounded-lg border border-indigo-200 font-bold">/preparation-navigator</span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            初めてまつりを担当する実行委員や次年度への引き継ぎ時でも、何から手をつけて良いか迷わないよう、手順通りにステップを進めるだけで抜け漏れなく準備が完了するガイドシステムです。
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                            <div class="p-3.5 bg-white rounded-xl border border-indigo-100 shadow-2xs space-y-1.5">
                                <div class="font-bold text-indigo-950 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[10px]">1</span>
                                    <span>タイムテーブル作成</span>
                                </div>
                                <p class="text-slate-600 text-[11px] leading-relaxed">
                                    運行日（前日準備、本宮、片付け等）の名称、日付、開始・終了時刻を設定して枠を作成します。
                                </p>
                            </div>
                            <div class="p-3.5 bg-white rounded-xl border border-indigo-100 shadow-2xs space-y-1.5">
                                <div class="font-bold text-indigo-950 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[10px]">2</span>
                                    <span>担当班編成</span>
                                </div>
                                <p class="text-slate-600 text-[11px] leading-relaxed">
                                    道路警備班、衛生班、設営班などを登録し、テーマカラーの設定と班長（現場主担当）を任命します。
                                </p>
                            </div>
                            <div class="p-3.5 bg-white rounded-xl border border-indigo-100 shadow-2xs space-y-1.5">
                                <div class="font-bold text-indigo-950 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[10px]">3</span>
                                    <span>作業タスク洗い出し</span>
                                </div>
                                <p class="text-slate-600 text-[11px] leading-relaxed">
                                    各班の具体的な業務（バリケード設置、給水所運営等）の時間枠、必要人数、優先度を登録します。
                                </p>
                            </div>
                            <div class="p-3.5 bg-white rounded-xl border border-indigo-100 shadow-2xs space-y-1.5">
                                <div class="font-bold text-indigo-950 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[10px]">4</span>
                                    <span>人員アサイン・配置確認</span>
                                </div>
                                <p class="text-slate-600 text-[11px] leading-relaxed">
                                    各タスクへスタッフを割り当て、過不足や時間帯重複がないかをタイムラインで点検します。
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 実際の画面画像: タイムテーブル・タスク台帳 -->
                    <div class="mt-8 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】タイムテーブル計画台帳一覧 (/timetables)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/06_timetables_plan.png') }}"
                                alt="タイムテーブル計画台帳画面の実画面キャプチャ"
                                data-modal-image data-caption="タイムテーブル計画台帳画面"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- 実際の画面画像: レーン編成 -->
                    <div class="mt-8 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】担当班管理 (/lanes)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/07_lanes.png') }}"
                                alt="担当班管理画面の実画面キャプチャ"
                                data-modal-image data-caption="担当班管理画面"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- 班（レーン）・班長・班員（固定スタッフ／充足スタッフ）運用仕様書セクション -->
                    <div id="sec-lane-spec" class="mt-6 p-5 sm:p-6 bg-amber-50/60 border-2 border-amber-200 rounded-2xl space-y-4">
                        <div class="flex items-center justify-between border-b border-amber-200 pb-3">
                            <div>
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-amber-600 text-white uppercase tracking-wider">Specification</span>
                                <h3 class="text-base sm:text-lg font-black text-slate-900 mt-1">
                                    👑 班（レーン）・班長・班員（固定スタッフ／充足スタッフ）運用仕様
                                </h3>
                            </div>
                        </div>

                        <div class="space-y-3 text-xs sm:text-sm text-slate-700 leading-relaxed">
                            <div class="p-3.5 bg-white rounded-xl border border-amber-200/80 space-y-1.5">
                                <h4 class="font-bold text-amber-950 flex items-center space-x-1.5">
                                    <span>1.</span>
                                    <span>班長の定義と位置づけ（ボランティアスタッフと管理者の中間層）</span>
                                </h4>
                                <p class="text-xs text-slate-600">
                                    班（レーン）に紐づく主担当を<strong>「班長」</strong>と呼称します。班長はボランティアスタッフ（班員）と管理者（本部）との中間に位置し、現場における班のタスク管理を担います。
                                </p>
                                <ul class="list-disc list-inside text-xs text-slate-700 space-y-1 pl-1 pt-1">
                                    <li><strong>班のタスク管理:</strong> 担当班の全タスクの進捗ステータス更新、班員の着任・出欠確認を行います。</li>
                                    <li><strong>人員が足りない場合の管理者への要請:</strong> 欠員や作業負荷増により人員が不足した場合、班長コンソール（マイタイムテーブル画面）から管理者へ増員・応援を要請します。</li>
                                    <li><strong>現場での関係班長を集めた打ち合わせの参加:</strong> 交通規制前後や工程切り替え時など、現場で関係する班長同士が集まる連絡調整打ち合わせに参加します。</li>
                                    <li><strong>必要機材の申請:</strong> 班のタスク遂行に必要な機材（カラーコーン、台車、給水タンク等）と数量を管理者へ申請します。</li>
                                </ul>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="p-3.5 bg-white rounded-xl border border-indigo-200 space-y-1.5">
                                    <h4 class="font-bold text-indigo-950 flex items-center space-x-1.5">
                                        <span>📌</span>
                                        <span>固定スタッフ（Fixed Staff）</span>
                                    </h4>
                                    <p class="text-xs text-slate-600">
                                        各班に専属で配置されるスタッフ（部会長、中心メンバー等）です。その班のすべての作業タスクに自動的に基本アサインされ、班長とともに業務の中核を支えます。
                                    </p>
                                </div>
                                <div class="p-3.5 bg-white rounded-xl border border-emerald-200 space-y-1.5">
                                    <h4 class="font-bold text-emerald-950 flex items-center space-x-1.5">
                                        <span>🤝</span>
                                        <span>充足スタッフ（Fulfillment Staff）</span>
                                    </h4>
                                    <p class="text-xs text-slate-600">
                                        特定のタスクで人手が必要な時間帯のみ応援として投入されるボランティアスタッフです。時間帯ごとに柔軟に配置されます。
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- レーン編成の操作ポイント -->
                    <div class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm space-y-2">
                        <h4 class="font-bold text-slate-900">班編成と班長・固定スタッフ設定の手順</h4>
                        <ol class="list-decimal list-inside space-y-1.5 text-slate-600 text-xs leading-relaxed">
                            <li>「担当班管理」画面（<code class="px-1 py-0.2 bg-slate-100 rounded text-indigo-600 font-mono">/lanes</code>）で、該当日（例: Day 1）を選択します。</li>
                            <li>「新規レーン追加」から班名（例: 道路警備班、衛生班）、表示色、種別を設定します。</li>
                            <li>各レーンに「👑 班長」および「📌 固定スタッフ」を設定すると、その班の全タスクへ自動的に基本アサインされます。</li>
                            <li>ドラッグ操作でタイムテーブル上の左右の並び順を自在に並び替えできます。</li>
                        </ol>
                    </div>

                    <!-- タスクへの人員アサイン操作 -->
                    <div class="mt-8 border-t border-slate-100 pt-6 space-y-4">
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 flex items-center space-x-2">
                            <span>👥</span>
                            <span>タスクへの人員割り当て（個別・一括・レーンプール）</span>
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            運行表上の各作業タスクに必要なスタッフをアサインします。手作業による1名ずつの追加だけでなく、部会全員の一括配置や交代（スワップ）にも対応しています。
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                                <h5 class="font-bold text-slate-900 flex items-center space-x-1.5">
                                    <span class="w-4 h-4 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-bold">1</span>
                                    <span>個別スタッフ選択</span>
                                </h5>
                                <p class="text-slate-600 leading-relaxed text-[11px]">
                                    アサインモーダルから候補スタッフを検索・選択。必要に応じて「役割名（例: 車両誘導係）」を入力してアサインします。
                                </p>
                            </div>
                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                                <h5 class="font-bold text-slate-900 flex items-center space-x-1.5">
                                    <span class="w-4 h-4 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-bold">2</span>
                                    <span>複数名の一括アサイン</span>
                                </h5>
                                <p class="text-slate-600 leading-relaxed text-[11px]">
                                    チェックボックスで複数名のスタッフを同時に選択し、ワンクリックでまとめてタスクへ投入できます。
                                </p>
                            </div>
                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                                <h5 class="font-bold text-slate-900 flex items-center space-x-1.5">
                                    <span class="w-4 h-4 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-bold">3</span>
                                    <span>レーンプール所属員一括</span>
                                </h5>
                                <p class="text-slate-600 leading-relaxed text-[11px]">
                                    該当レーンに所属登録されているスタッフ全員をワンタップで一括配置。開会式や一斉撤収などの大規模タスクに便利です。
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 同時刻重複アサイン自動検知（コンフリクト警告ダイアログ） -->
                    <div class="mt-6 p-4 sm:p-5 bg-red-50/70 border-2 border-red-200 rounded-2xl space-y-3">
                        <div class="flex items-center space-x-2 text-red-900 font-bold text-sm">
                            <span class="text-lg">⚠️</span>
                            <span>同一時間帯の重複アサイン自動検知（コンフリクト警告ダイアログ）</span>
                        </div>
                        <p class="text-xs text-red-800 leading-relaxed">
                            スタッフをアサインする際、システムが自動的に同一運行日の時間帯を照合します。すでに別レーンのタスクに割り当てられている場合は、即座に**コンフリクト警告モーダル**が表示され、ダブルブッキングを未然に防止します。
                        </p>
                        <div class="p-3 bg-white/90 border border-red-300 rounded-xl text-xs space-y-2">
                            <div class="font-mono text-red-700 font-bold flex items-center space-x-1">
                                <span>🚨</span>
                                <span>画面表示例: 「該当スタッフは同時刻（11:30〜12:30）の別タスク『本部給水所テント設営』（衛生班）に既にアサインされています。」</span>
                            </div>
                            <div class="text-[11px] text-slate-600 leading-relaxed">
                                <strong>対処手順:</strong> 警告内容（競合タスク名・時間・レーン）を確認し、誤りの場合は「キャンセル」を押して別の待機スタッフを選択してください。
                            </div>
                        </div>
                    </div>

                    <!-- スタッフタイムライン（ガントチャート）による過密防止と過不足調整 -->
                    <div class="mt-6 p-4 sm:p-5 bg-indigo-50/70 border border-indigo-200 rounded-2xl space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2 text-indigo-950 font-bold text-sm">
                                <span class="text-lg">📊</span>
                                <span>スタッフタイムライン（ガントチャート）による過密防止・過不足調整</span>
                            </div>
                            <span class="text-[11px] font-mono text-indigo-600 bg-white px-2 py-0.5 rounded border border-indigo-200">/timetables/{id}/staff-timeline</span>
                        </div>
                        <p class="text-xs text-indigo-900 leading-relaxed">
                            タイムテーブル詳細画面の「📈 スタッフタイムライン」をクリックすると、<strong>横軸に時刻スロット、縦軸にスタッフ名が並ぶガントチャート</strong>が開きます。
                        </p>
                        <ul class="list-disc list-inside text-xs text-indigo-950 space-y-1.5 pl-1 leading-relaxed">
                            <li><strong>過密稼働の早期発見:</strong> 特定のスタッフが連続して重労働タスクに入っていないか、熱中症リスクや疲労集中を視覚的にチェックできます。</li>
                            <li><strong>フリー要員の可視化:</strong> 同一時間帯にタスクが割り当てられていない「待機・空きスタッフ」が一目で分かります。</li>
                            <li><strong>ワンクリック・スワップ（交代）:</strong> 過密なスタッフと空いているスタッフの間でタスクを交代し、無理のない最適なシフトバランスへ調整できます。</li>
                        </ul>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第6章: スタッフ管理・名簿・Webセルフ登録 & QRカード印刷 -->
                <!-- ========================================== -->
                <section id="sec-staff" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 06</span>
                        <span>•</span>
                        <span>Staff Directory &amp; Self Registration</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第6章 スタッフ管理・名簿・シフト編成・カード印刷</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        ボランティアの公募・セルフ登録から、本部側での受付設定、前年度名簿のCSV一括取り込み、スマート名寄せ、QRコード付き名札印刷、および紛失時のトークン再発行に至るスタッフ管理の中核機能です。
                    </p>

                    <!-- 実際の画面画像: スタッフ管理 -->
                    <div class="mt-6 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】スタッフ管理一覧 (/staff)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/08_staff_index.png') }}"
                                alt="スタッフ管理画面の実画面キャプチャ"
                                data-modal-image data-caption="スタッフ管理画面"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- スタッフ台帳の検索・絞り込み -->
                    <div class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm space-y-1.5">
                        <h4 class="font-bold text-slate-900 flex items-center space-x-1.5">
                            <span>🔍</span>
                            <span>高速台帳検索 &amp; 状態フィルタ</span>
                        </h4>
                        <p class="text-slate-600 text-xs leading-relaxed">
                            スタッフ台帳（<code class="px-1 py-0.2 bg-slate-100 rounded text-indigo-600 font-mono">/staff</code>）では、氏名・フリガナ・所属団体（商店街、大学、消防団等）でのあいまい検索が可能です。さらに「稼働可能フラグ」や「当日の出勤状況（未到着／到着済）」でワンクリック絞り込みできます。
                        </p>
                    </div>

                    <!-- Webセルフ登録設定 & 案内文コピー（詳細） -->
                    <div class="mt-6 p-5 bg-emerald-50/80 border-2 border-emerald-300 rounded-2xl space-y-4">
                        <div class="flex items-center justify-between border-b border-emerald-200 pb-3">
                            <div>
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-emerald-600 text-white uppercase tracking-wider">Self Registration Management</span>
                                <h3 class="text-base sm:text-lg font-black text-slate-900 mt-1 flex items-center space-x-2">
                                    <span>💬</span>
                                    <span>Webセルフ登録（/join）と本部公開設定・締切管理</span>
                                </h3>
                            </div>
                            <span class="text-xs font-mono text-emerald-800 bg-white px-2.5 py-1 rounded-lg border border-emerald-200 font-bold">/join</span>
                        </div>
                        <p class="text-xs text-slate-700 leading-relaxed">
                            一般ボランティアや学生公募向けに、専用ログインID不要でスマートフォンから事前エントリーできる公開フォームを提供しています。本部側で募集期間や受付可否をワンタッチで管理できます。
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                            <div class="p-3.5 bg-white rounded-xl border border-emerald-200 space-y-1.5">
                                <h5 class="font-bold text-emerald-950 flex items-center space-x-1.5">
                                    <span>⚙️</span>
                                    <span>受付開始/終了トグル</span>
                                </h5>
                                <p class="text-slate-600 text-[11px] leading-relaxed">
                                    スタッフ管理画面右上のトグルスイッチにより、ボランティア受付を即座に「受付中 / 受付終了」に切り替えられます。
                                </p>
                            </div>
                            <div class="p-3.5 bg-white rounded-xl border border-emerald-200 space-y-1.5">
                                <h5 class="font-bold text-emerald-950 flex items-center space-x-1.5">
                                    <span>⏰</span>
                                    <span>募集締切日時の指定</span>
                                </h5>
                                <p class="text-slate-600 text-[11px] leading-relaxed">
                                    締切日時（例: 前日18:00）をセットしておくと、期限到来後に自動的に受付終了画面（`/join-closed`）へ遷移します。
                                </p>
                            </div>
                            <div class="p-3.5 bg-white rounded-xl border border-emerald-200 space-y-1.5">
                                <h5 class="font-bold text-emerald-950 flex items-center space-x-1.5">
                                    <span>📋</span>
                                    <span>登録案内文ワンクリックコピー</span>
                                </h5>
                                <p class="text-slate-600 text-[11px] leading-relaxed">
                                    「登録案内文コピー」ボタンを押すと、URL入りのSNS・メール用定型文がクリップボードにコピーされ、すぐに公募告知できます。
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 4大管理機能グリッド -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <!-- 機能A: CSV一括インポート・エクスポート -->
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="font-bold text-slate-900 flex items-center space-x-1.5 text-sm">
                                <span>📁</span>
                                <span>前年度Excel名簿のCSV一括登録</span>
                            </div>
                            <p class="text-slate-600 leading-relaxed text-[11px]">
                                前年度の実行委員会で作成されたExcel名簿をCSV保存し、「スタッフ管理 > CSV一括登録」からアップロードするだけで、数十名規模のスタッフが一瞬で登録されます。
                            </p>
                            <div class="bg-white p-2.5 rounded-lg border border-slate-200 text-[10px] font-mono text-slate-500">
                                氏名,フリガナ,所属,電話番号,メールアドレス<br>
                                山田 太郎,ヤマダ タロウ,青葉大学,090-XXXX-XXXX,yamada@example.com
                            </div>
                            <p class="text-slate-500 text-[10px] leading-relaxed">
                                ※インポート時に推測不可能な32桁の固有アクセストークンが自動採番されます。現在の台帳状況をCSVエクスポートすることも可能です。
                            </p>
                        </div>

                        <!-- 機能B: Web募集・自動名寄せ -->
                        <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-xl space-y-2">
                            <div class="font-bold text-emerald-950 flex items-center space-x-1.5 text-sm">
                                <span>💬</span>
                                <span>Webセルフ登録 &amp; スマート自動名寄せ</span>
                            </div>
                            <p class="text-emerald-900 leading-relaxed text-[11px]">
                                一般公募や学生ボランティアへ登録URL（<code class="px-1 py-0.2 bg-emerald-100 rounded font-mono text-emerald-950">/join</code>）を告知。ブラウザからID・パスワード不要で1分で登録完了します。
                            </p>
                            <div class="p-2 bg-white/90 border border-emerald-200 rounded-lg text-[11px] text-emerald-900 space-y-1">
                                <div class="font-bold text-emerald-950">二重登録を防ぐスマート名寄せ:</div>
                                <div class="text-[10px] leading-relaxed text-emerald-800">
                                    本部が事前にCSV等で登録していた名簿がある場合、「氏名＋カナ」で自動照合。二重レコードを作らず、既存台帳と自動統合します。
                                </div>
                            </div>
                        </div>

                        <!-- 機能C: 名札・スタッフ証印刷 -->
                        <div class="p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-2">
                            <div class="font-bold text-indigo-950 flex items-center space-x-1.5 text-sm">
                                <span>🪪</span>
                                <span>QRコード付き名札カードの一括印刷</span>
                            </div>
                            <p class="text-indigo-900 leading-relaxed text-[11px]">
                                「スタッフカード一括印刷（<code class="px-1 py-0.2 bg-indigo-100 rounded font-mono text-indigo-950">/staff/print-cards</code>）」から市販のA4用紙に印刷。専用のPrint CSSにより、余白・ヘッダー・フッターが自動調整され、ペーパーカッターで切るだけで市販の首掛け名刺ホルダーにぴったり収まります。
                            </p>
                            <p class="text-indigo-800 text-[10px] leading-relaxed">
                                ※各カードには、ベクターSVG形式の高精度QRコードが印字され、現場スマホの標準カメラで瞬時に読み取れます。
                            </p>
                        </div>

                        <!-- 機能D: セキュリティ・トークン再生成 -->
                        <div class="p-4 bg-amber-50/70 border border-amber-200 rounded-xl space-y-2">
                            <div class="font-bold text-amber-950 flex items-center space-x-1.5 text-sm">
                                <span>🛡️</span>
                                <span>名札紛失時の旧QR即時失効 &amp; 再発行</span>
                            </div>
                            <p class="text-amber-900 leading-relaxed text-[11px]">
                                まつり当日にスタッフが名札を落とした場合、管理画面で該当スタッフを開き「トークン再発行」をクリック。
                            </p>
                            <div class="p-2 bg-white/90 border border-amber-200 rounded-lg text-[10px] text-amber-900 leading-relaxed">
                                <strong>即時セキュリティ無効化:</strong> 古いQRコードは404失効となり第三者に運行情報を見られません。本部卓上プリンターで「単一カード印刷」を押し、わずか2分で新名札を手渡しできます。
                            </div>
                        </div>
                    </div>

                    <!-- 実際の画面画像: スタッフカード印刷プレビュー -->
                    <div class="mt-8 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】スタッフカード・QRコード名札印刷プレビュー (/staff/print-cards)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/09_staff_print_cards.png') }}"
                                alt="スタッフカード印刷プレビュー画面の実画面キャプチャ"
                                data-modal-image data-caption="スタッフカード・名札印刷プレビュー画面"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第7章: 資材・機材物流管理 & 拠点・会場管理（新設） -->
                <!-- ========================================== -->
                <section id="sec-logistics" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 07</span>
                        <span>•</span>
                        <span>Logistics, Equipments &amp; Locations</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第7章 資材・機材物流管理 &amp; 拠点・会場管理</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        まつり運行を支えるパイプ椅子・テント・バリケード・無線機等の機材台帳から、拠点ごとの搬入・夜間保管・返却計画、指示書印刷、神輿番所や臨時駐車場の利用スケジュール・交通規制時間帯までを一元管理します。
                    </p>

                    <!-- 機材物流の4大管理フロー -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <!-- 1. 機材台帳と所有区分 -->
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5 text-sm">
                                    <span>📦</span>
                                    <span>機材台帳と所有区分管理</span>
                                </h4>
                                <span class="font-mono text-indigo-600 bg-white px-1.5 py-0.5 rounded border border-slate-200 text-[10px]">/equipments</span>
                            </div>
                            <p class="text-slate-600 leading-relaxed text-[11px]">
                                保有備品（町内会・実行委員会所有）と、外部業者からのレンタル品、他団体からの借用品を区分して登録。管理番号、保管場所、総数量を正確に追跡します。
                            </p>
                            <div class="p-2.5 bg-white rounded-lg border border-slate-200 space-y-1">
                                <span class="font-bold text-slate-900 text-[11px]">💡 定番セットテンプレート適用:</span>
                                <p class="text-slate-500 text-[10px] leading-relaxed">
                                    「本部設営セット（パイプ長机4本、椅子12脚、ホワイトボード、給水タンク）」「救護所セット」「交通規制誘導セット」など定番機材の組み合わせをワンクリックで一括生成できます。
                                </p>
                            </div>
                        </div>

                        <!-- 2. 搬出入計画・夜間保管 -->
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5 text-sm">
                                    <span>🚚</span>
                                    <span>日別・拠点別の搬出入 &amp; 夜間保管計画</span>
                                </h4>
                                <span class="font-mono text-indigo-600 bg-white px-1.5 py-0.5 rounded border border-slate-200 text-[10px]">/logistics</span>
                            </div>
                            <p class="text-slate-600 leading-relaxed text-[11px]">
                                「いつ、誰が、どの拠点へ搬入し、夜間はどこに保管し、いつ回収・返却するか」をタイムライン連動で計画します。
                            </p>
                            <div class="p-2.5 bg-white rounded-lg border border-slate-200 space-y-1">
                                <span class="font-bold text-slate-900 text-[11px]">5段階のステータス遷移管理:</span>
                                <div class="flex flex-wrap gap-1 text-[9px] font-bold">
                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700">計画中 (planned)</span>
                                    <span>→</span>
                                    <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">搬入済 (delivered)</span>
                                    <span>→</span>
                                    <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">使用中 (in_use)</span>
                                    <span>→</span>
                                    <span class="px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700">回収済 (collected)</span>
                                    <span>→</span>
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">返却済 (returned)</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. 資材指示書・チェックリスト印刷 -->
                        <div class="p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-indigo-950 flex items-center space-x-1.5 text-sm">
                                    <span>🖨️</span>
                                    <span>資材搬出入計画書・チェックリストのA4印刷</span>
                                </h4>
                                <span class="font-mono text-indigo-600 bg-white px-1.5 py-0.5 rounded border border-indigo-200 text-[10px]">/logistics/print</span>
                            </div>
                            <p class="text-indigo-900 leading-relaxed text-[11px]">
                                運搬トラックの運転手や各神輿番所の資材受け取り担当者向けに、日時・拠点・機材名・数量・担当者を網羅した指示書兼チェックリストをA4横向きで印刷できます。
                            </p>
                            <p class="text-[10px] text-indigo-700 leading-relaxed">
                                現場でチェックボックス（☑）をチェックしながら確実に受け渡し・回収作業を行えます。
                            </p>
                        </div>

                        <!-- 4. 拠点・会場管理 & 交通規制 -->
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5 text-sm">
                                    <span>📍</span>
                                    <span>拠点管理 &amp; 利用時間帯・交通規制スケジュール</span>
                                </h4>
                                <span class="font-mono text-indigo-600 bg-white px-1.5 py-0.5 rounded border border-slate-200 text-[10px]">/locations</span>
                            </div>
                            <p class="text-slate-600 leading-relaxed text-[11px]">
                                本部テント、神輿番所（各町会詰所）、臨時駐車場、給水ポイント、救護所をマップ住所や連絡先とともに登録します。
                            </p>
                            <div class="p-2.5 bg-white rounded-lg border border-slate-200 space-y-1">
                                <span class="font-bold text-slate-900 text-[11px]">拠点タイプ &amp; 規制タイムテーブル:</span>
                                <p class="text-slate-500 text-[10px] leading-relaxed">
                                    拠点タイプ（番所、駐車場、詰所等）ごとにカラー・アイコンを設定。さらに各拠点の利用可能時間帯や車両通行止め・交通規制時間を紐づけ、ダッシュボードから稼働状態を監視できます。
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第8章: 現場動態管理・マイシフト運行・リアルタイム監視 -->
                <!-- ========================================== -->
                <section id="sec-myshift" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 08</span>
                        <span>•</span>
                        <span>Field Mobile Shift</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第8章 現場動態管理・マイシフト運行・リアルタイム監視</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        現場ボランティアがスマホで担当タスクを確認し、自律的に移動・報告を行う「マイシフト」機能と、本部に現場の動態（出勤・進捗・トラブル）がリアルタイムに集約される仕組みの解説です。
                    </p>

                    <!-- スマホ実画面と操作手順の2カラム -->
                    <div class="mt-6 flex flex-col md:flex-row items-center gap-8">
                        <!-- スマホフレーム内に実画面画像を配置 -->
                        <div class="w-72 bg-slate-900 p-3 rounded-[40px] shadow-2xl border-4 border-slate-700 shrink-0">
                            <!-- スピーカーバー -->
                            <div class="w-24 h-3 bg-slate-800 rounded-full mx-auto mb-3"></div>
                            <!-- 実画面画像 -->
                            <div class="rounded-[28px] overflow-hidden border border-slate-700 bg-white shadow-inner">
                                <img src="{{ asset('images/manual/11_myshift_mobile.png') }}"
                                    alt="現場マイシフト実画面キャプチャ（スマホ表示）"
                                    data-modal-image data-caption="現場マイシフト画面（スマホ表示）"
                                    class="w-full cursor-zoom-in hover:opacity-95 transition">
                            </div>
                            <div class="text-center text-[10px] text-slate-400 mt-2 font-mono">iPhone 相当ビュー</div>
                        </div>

                        <!-- スマホでの操作手順ステップ -->
                        <div class="space-y-3.5 text-xs sm:text-sm text-slate-700 flex-1">
                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0">1</span>
                                    <span>朝の受付で名札QRをスキャン</span>
                                </h4>
                                <p class="text-slate-600 text-xs leading-relaxed">
                                    名札ケース内のQRコードをスマホの標準カメラで読み取るだけで、自身専用のマイシフトが開きます。パスワードレス認証・Cookie自動認識により次回以降も即座に開けます。
                                </p>
                            </div>

                            <div class="p-3.5 bg-emerald-50/80 border border-emerald-200 rounded-xl space-y-1">
                                <h4 class="font-bold text-emerald-950 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0">2</span>
                                    <span>現場到着チェックイン（第1タスク自動連動）</span>
                                </h4>
                                <p class="text-emerald-900 text-xs leading-relaxed">
                                    画面上部の「📍 現場到着（受付）」をタップすると、出勤日時を打刻。さらに**当日第1タスクの出欠ステータスが自動的に「現場着」に更新**され、本部にリアルタイムで伝達されます。
                                </p>
                            </div>

                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0">3</span>
                                    <span>「Now &amp; Next」による現場自律移動</span>
                                </h4>
                                <p class="text-slate-600 text-xs leading-relaxed">
                                    最上部に「現在進行中のタスク（NOW）」と「次に控えるタスク（NEXT）」が自動表示されます。場所・使用機材・指示内容が明記されているため、本部に聞きに戻る必要がありません。
                                </p>
                            </div>

                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0">4</span>
                                    <span>ワンタップでの着手・完了報告</span>
                                </h4>
                                <p class="text-slate-600 text-xs leading-relaxed">
                                    作業開始時に「着手」、作業終了時に「完了」をタップ。本部モニターの全体運行マトリクス（<code class="px-1 py-0.2 bg-slate-100 rounded text-indigo-600 font-mono">/timetable</code>）上の該当タスクが即座に青色（進行中）・緑色（完了）へ変わります。
                                </p>
                            </div>

                            <div class="p-3.5 bg-amber-50/80 border border-amber-200 rounded-xl space-y-1">
                                <h4 class="font-bold text-amber-950 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-amber-600 text-white flex items-center justify-center font-bold text-xs shrink-0">5</span>
                                    <span>応援要請 &amp; トラブル報告（レーンリクエスト）</span>
                                </h4>
                                <p class="text-amber-900 text-xs leading-relaxed">
                                    「人手が足りない」「機材が破損した」「急病人発生」などの事態が生じた際、画面下のボタンから本部へ応援要請を発信。本部ダッシュボードへ直ちにアラート通知されます。
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 現場サポート機能 & 安全心得 -->
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <!-- 現場心得 & 熱中症対策 -->
                        <div class="p-4 bg-amber-50/80 border border-amber-200 rounded-xl space-y-2">
                            <div class="font-bold text-amber-950 flex items-center space-x-1.5 text-sm">
                                <span>☀️</span>
                                <span>ボランティア現場心得 &amp; 熱中症予防ルール</span>
                            </div>
                            <ul class="list-disc list-inside text-amber-900 space-y-1 leading-relaxed text-[11px] pl-1">
                                <li><strong>こまめな水分・塩分補給:</strong> 喉が渇く前に給水所テントで水分と塩分を補給してください。</li>
                                <li><strong>無理のない体調申告:</strong> 体調に少しでも異変を感じたら、我慢せずすぐに班長や本部テントへ申し出てください。</li>
                                <li><strong>気持ちの良い挨拶:</strong> 来場者や近隣住民には笑顔で元気よく挨拶し、まつりの安全運行をサポートしましょう。</li>
                                <li><strong>直射日光の回避:</strong> 待機時間はテント等の日陰を利用し、帽子やタオルで直射日光を防ぎましょう。</li>
                            </ul>
                        </div>

                        <!-- マイシフトURLの保存 -->
                        <div class="p-4 bg-indigo-50/80 border border-indigo-200 rounded-xl space-y-2">
                            <div class="font-bold text-indigo-950 flex items-center space-x-1.5 text-sm">
                                <span>📲</span>
                                <span>マイシフトURLの保存</span>
                            </div>
                            <ul class="list-disc list-inside text-indigo-950 space-y-1 leading-relaxed text-[11px] pl-1">
                                <li><strong>URLコピーボタン:</strong> 画面最下部の「このページのURLをコピーして保存」を押し、メモアプリ等に保存しておくと便利です。</li>
                                <li><strong>ホーム画面への追加:</strong> スマホブラウザの「共有 > ホーム画面に追加」を行うと、アプリ感覚でワンタップ起動できます。</li>
                                <li><strong>名札紛失時:</strong> 名札を紛失した場合は、本部受付で古いQRを即時無効化し、2分で新しい名札を再印刷できます。</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第9章: ユーザー管理・権限設定 -->
                <!-- ========================================== -->
                <section id="sec-users" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 09</span>
                        <span>•</span>
                        <span>Administration &amp; Security</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第9章 ユーザー管理・権限設定（管理者向け）</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        本部システムユーザーの追加、ロール権限の設定、アカウントの有効/無効化の手順です（管理者権限を持つユーザーのみ操作可能です）。
                    </p>

                    <!-- 実際の画面画像: ユーザー管理 -->
                    <div class="mt-6 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】ユーザー管理・権限一覧 (/users)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/10_users.png') }}"
                                alt="ユーザー管理画面の実画面キャプチャ"
                                data-modal-image data-caption="ユーザー管理画面"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <div class="mt-6 space-y-4 text-xs sm:text-sm text-slate-700">
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <h4 class="font-bold text-slate-900 text-xs">ユーザーの追加とロール指定</h4>
                            <ol class="list-decimal list-inside space-y-1.5 text-slate-600 text-xs leading-relaxed">
                                <li>ヘッダーの「設定・資料」→「ユーザー管理」をクリックします。</li>
                                <li>「新規ユーザー登録」を押し、氏名、メールアドレス、初期パスワードを入力します。</li>
                                <li>ロールとして「システム管理者 (`admin`)」または「本部オペレーター (`operator`)」を選択して保存します。</li>
                            </ol>
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 付録: FAQ & トラブルシューティング -->
                <!-- ========================================== -->
                <section id="sec-faq" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Appendix</span>
                        <span>•</span>
                        <span>Troubleshooting &amp; FAQ</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">付録 よくある質問（FAQ）&amp; トラブル対応</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        現場運行中や準備期間中によく発生する疑問点とその対処法です。
                    </p>

                    <div class="mt-6 space-y-3">
                        <details class="group p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm transition cursor-pointer">
                            <summary class="font-bold text-slate-900 flex items-center justify-between list-none">
                                <span class="flex items-center space-x-2">
                                    <span class="text-indigo-600 font-bold">Q.</span>
                                    <span>雨天順延や時間変更が発生した場合、どこを変更すればよいですか？</span>
                                </span>
                                <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                            </summary>
                            <p class="mt-3 text-slate-600 leading-relaxed text-xs border-t border-slate-200 pt-3">
                                タイムテーブル画面の「運行日程編集」から開始時刻を変更するか、該当するタスクの時間枠を編集してください。変更内容はリアルタイムに現場スタッフのマイシフト画面に反映されます。
                            </p>
                        </details>

                        <details class="group p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm transition cursor-pointer">
                            <summary class="font-bold text-slate-900 flex items-center justify-between list-none">
                                <span class="flex items-center space-x-2">
                                    <span class="text-indigo-600 font-bold">Q.</span>
                                    <span>急な欠席が出た場合、スタッフをその場で交代できますか？</span>
                                </span>
                                <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                            </summary>
                            <p class="mt-3 text-slate-600 leading-relaxed text-xs border-t border-slate-200 pt-3">
                                該当タスクの人員アサインモーダルを開き、「スタッフ交代（スワップ）」をクリックして代替スタッフを選択するか、一度解除してフリーの待機スタッフを再割り当てしてください。
                            </p>
                        </details>

                        <details class="group p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm transition cursor-pointer">
                            <summary class="font-bold text-slate-900 flex items-center justify-between list-none">
                                <span class="flex items-center space-x-2">
                                    <span class="text-indigo-600 font-bold">Q.</span>
                                    <span>印刷したときに画面のボタンやサイドバーが写り込んでしまいませんか？背景色が白く抜ける場合は？</span>
                                </span>
                                <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                            </summary>
                            <p class="mt-3 text-slate-600 leading-relaxed text-xs border-t border-slate-200 pt-3">
                                本システムは全画面でPrint CSSが組み込まれており、ブラウザの「印刷」を実行すると自動的にナビゲーションやボタン等の不要な要素が非表示になります。<br>
                                ※タイムテーブルや資材チェックリストの色分けバッジを綺麗に印刷するため、ブラウザの印刷ダイアログ詳細設定で<strong>「背景のグラフィック」にチェックを入れてください。</strong>
                            </p>
                        </details>

                        <details class="group p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm transition cursor-pointer">
                            <summary class="font-bold text-slate-900 flex items-center justify-between list-none">
                                <span class="flex items-center space-x-2">
                                    <span class="text-indigo-600 font-bold">Q.</span>
                                    <span>スタッフを割り当てた際に「同時刻の別タスクにアサインされています」と赤色警告が出ました。</span>
                                </span>
                                <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                            </summary>
                            <p class="mt-3 text-slate-600 leading-relaxed text-xs border-t border-slate-200 pt-3">
                                システムが同一時間帯の重複アサイン（ダブルブッキング）を自動検知した安全警告です。警告メッセージに競合しているタスク名・時間・レーン名が表示されますので、アサインをキャンセルして別の待機スタッフを選択してください。
                            </p>
                        </details>

                        <details class="group p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm transition cursor-pointer">
                            <summary class="font-bold text-slate-900 flex items-center justify-between list-none">
                                <span class="flex items-center space-x-2">
                                    <span class="text-indigo-600 font-bold">Q.</span>
                                    <span>ボランティアが「名札を落としてしまった」と本部に駆け込んできた場合は？</span>
                                </span>
                                <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                            </summary>
                            <p class="mt-3 text-slate-600 leading-relaxed text-xs border-t border-slate-200 pt-3">
                                本部PCの「スタッフ管理」から該当スタッフの詳細を開き、「アクセストークン再発行」をクリックしてください。落とした名札の旧QRコードは即座に無効化（404エラー）されます。その後「名札単一印刷」を押して卓上プリンターで1枚印刷し、予備ホルダーに入れて手渡せば完了です。
                            </p>
                        </details>

                        <details class="group p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm transition cursor-pointer">
                            <summary class="font-bold text-slate-900 flex items-center justify-between list-none">
                                <span class="flex items-center space-x-2">
                                    <span class="text-indigo-600 font-bold">Q.</span>
                                    <span>ボランティア自身が事前登録する方法はありますか？募集期間終了後はどうなりますか？</span>
                                </span>
                                <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                            </summary>
                            <p class="mt-3 text-slate-600 leading-relaxed text-xs border-t border-slate-200 pt-3">
                                本部が発行する登録URL（<code class="px-1 py-0.2 bg-slate-100 rounded text-indigo-600 font-mono">/join</code>）を案内することで、SafariやChrome等の通常ブラウザから氏名・カナ・所属・連絡先を入力して1分で事前セルフ登録できます。<br>
                                募集締切日時を過ぎた場合は自動で受付終了画面が表示されます。期限後に追加したい場合は、本部画面の「スタッフ管理」から手動追加するか、受付トグルを再有効化してください。
                            </p>
                        </details>

                        <details class="group p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm transition cursor-pointer">
                            <summary class="font-bold text-slate-900 flex items-center justify-between list-none">
                                <span class="flex items-center space-x-2">
                                    <span class="text-indigo-600 font-bold">Q.</span>
                                    <span>現場で資材が足りなくなったり、カラーコーンや無線機が破損した場合は？</span>
                                </span>
                                <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                            </summary>
                            <p class="mt-3 text-slate-600 leading-relaxed text-xs border-t border-slate-200 pt-3">
                                現場ボランティア・班長はスマホのマイシフト画面から「応援要請（レーンリクエスト）」を送信してください。本部側では「搬出入計画（`/logistics`）および資材管理（`/equipments`）」の予備在庫状況を確認し、近隣の番所や本部倉庫から代替機材を運搬スタッフへ指示します。
                            </p>
                        </details>

                        <details class="group p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm transition cursor-pointer">
                            <summary class="font-bold text-slate-900 flex items-center justify-between list-none">
                                <span class="flex items-center space-x-2">
                                    <span class="text-indigo-600 font-bold">Q.</span>
                                    <span>現場で「出勤チェックイン」ボタンを押したのに、本部画面で反映されない時は？</span>
                                </span>
                                <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                            </summary>
                            <p class="mt-3 text-slate-600 leading-relaxed text-xs border-t border-slate-200 pt-3">
                                端末の電波状況が一時的に不安定だった可能性があります。スマホ画面を再読み込み（リロード）し、ボタンが「受付済」に変わっているか確認してください。もし反映されない場合は、本部スタッフが「スタッフ管理」またはタスクアサイン画面から手動で出欠ステータスを「現場着」へ変更できます。
                            </p>
                        </details>
                    </div>
                </section>

            </div>
        </div>

    </div>

    <!-- 画像拡大表示モーダル (ライトボックス・CSP準拠) -->
    <div id="imageModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs z-50 hidden flex flex-col items-center justify-center p-4">
        <div class="max-w-6xl w-full bg-slate-900 rounded-2xl overflow-hidden border border-slate-700 shadow-2xl flex flex-col max-h-[92vh]">
            <div class="px-4 py-2.5 bg-slate-800 border-b border-slate-700 flex items-center justify-between text-xs text-white">
                <span id="modalCaption" class="font-bold flex items-center space-x-2">
                    <span>🖼️</span>
                    <span>画像プレビュー</span>
                </span>
                <button type="button" data-action="close-modal" class="text-slate-400 hover:text-white text-lg p-1 cursor-pointer">✕ 閉じる</button>
            </div>
            <div class="p-2 sm:p-4 overflow-auto flex-1 flex items-center justify-center bg-slate-950/60">
                <img id="modalImage" src="" alt="拡大画像" class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-lg">
            </div>
        </div>
    </div>

    <!-- キーワードリアルタイム検索スクリプト & 目次スムーススクロール & 画像モーダル制御（CSP準拠） -->
    <script src="{{ asset('js/manual-helper.js') }}" defer></script>
</x-layouts.app>