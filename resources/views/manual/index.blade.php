<x-layouts.app title="システム操作マニュアル">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- ページヘッダー -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-5 border-b border-slate-200">
            <div>
                <div class="flex items-center space-x-2.5">
                    <span class="text-3xl">📖</span>
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">システム操作マニュアル</h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                            まつり総合管理システム（運行本部・タイムテーブル・人員機材動態・年間工程）の公式操作ガイド
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-2.5">
                <!-- キーワードクイック検索 -->
                <div class="relative w-48 sm:w-64">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-xs">🔍</span>
                    <input type="text" id="manualSearchInput"
                        placeholder="キーワード検索 (例: アサイン)..."
                        class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-xs">
                </div>
                <!-- 印刷ボタン -->
                <button type="button" onclick="window.print()"
                    class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1.5 shadow-xs cursor-pointer print:hidden shrink-0">
                    <span>🖨️</span>
                    <span>印刷 / PDF出力</span>
                </button>
            </div>
        </div>

        <!-- メイングリッド (サイドバー目次 + コンテンツ) -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">

            <!-- 左側: 固定目次ナビゲーション (Desktop) -->
            <nav class="hidden lg:block lg:col-span-1 sticky top-20 bg-white border border-slate-200 rounded-2xl p-4 shadow-xs text-xs space-y-1 print:hidden" aria-label="マニュアル目次">
                <div class="font-extrabold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center justify-between">
                    <span class="flex items-center space-x-1.5">
                        <span>📑</span>
                        <span>目次・章一覧</span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-mono">全9章</span>
                </div>
                <div class="space-y-1 max-h-[calc(100vh-160px)] overflow-y-auto pr-1">
                    <a href="#sec-overview" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第1章</span>
                        <span class="truncate">概要・ログイン</span>
                    </a>
                    <a href="#sec-dashboard" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第2章</span>
                        <span class="truncate">本部ダッシュボード</span>
                    </a>
                    <a href="#sec-calendar" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第3章</span>
                        <span class="truncate">総合カレンダー & 年間工程</span>
                    </a>
                    <a href="#sec-timetable" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第4章</span>
                        <span class="truncate">運行タイムテーブルマトリクス</span>
                    </a>
                    <a href="#sec-tasks" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第5章</span>
                        <span class="truncate">計画台帳・レーン・アサイン</span>
                    </a>
                    <a href="#sec-staff" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第6章</span>
                        <span class="truncate">スタッフ名簿 & カード印刷</span>
                    </a>
                    <a href="#sec-myshift" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第7章</span>
                        <span class="truncate">現場マイシフト (スマホ)</span>
                    </a>
                    <a href="#sec-users" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">第8章</span>
                        <span class="truncate">ユーザー管理・権限設定</span>
                    </a>
                    <a href="#sec-faq" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px]">付録</span>
                        <span class="truncate">よくある質問 & FAQ</span>
                    </a>
                </div>
            </nav>

            <!-- 右側: マニュアル本文コンテンツ -->
            <div class="col-span-1 lg:col-span-3 space-y-12" id="manualContent">

                <!-- ========================================== -->
                <!-- 第1章: システム概要・権限・ログイン -->
                <!-- ========================================== -->
                <section id="sec-overview" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 01</span>
                        <span>•</span>
                        <span>Getting Started</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第1章 システム概要・利用権限・ログイン手順</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        本システムは、まつり・イベントの年間準備から前日準備・当日本番運行・翌日回収に至るまでの運行・人員・機材動態を一元管理するクラウド型総合管理システムです。
                    </p>

                    <!-- 権限ロール比較カード -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
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
                        <div class="p-4 bg-emerald-50/60 border border-emerald-200/80 rounded-xl">
                            <div class="flex items-center space-x-2">
                                <span class="bg-emerald-700 text-emerald-100 text-[10px] font-bold px-2 py-0.5 rounded">現場スタッフ</span>
                            </div>
                            <h3 class="font-bold text-sm text-emerald-950 mt-2">ボランティア・現場班員</h3>
                            <p class="text-xs text-emerald-800 mt-1 leading-normal">
                                ID・パスワード不要。配布される個別QRコード/URLからスマートフォンで「マイシフト」を閲覧・出勤報告。
                            </p>
                        </div>
                    </div>

                    <!-- 実際の画面画像: ログイン画面 -->
                    <div class="mt-8 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】ログイン認証画面 (https://timetable.tonoaki-sato.home/login)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/01_login.png') }}"
                                alt="本部ログイン画面の実画面キャプチャ"
                                onclick="openImageModal(this.src, '本部ログイン認証画面')"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- ログイン操作ステップ -->
                    <div class="mt-6 border-t border-slate-100 pt-6">
                        <h4 class="font-bold text-sm text-slate-900 flex items-center space-x-2 mb-3">
                            <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[11px] font-bold">1</span>
                            <span>本部スタッフのログイン手順</span>
                        </h4>
                        <ol class="space-y-3 text-xs sm:text-sm text-slate-600 list-decimal list-inside leading-relaxed pl-1">
                            <li>ブラウザで開発環境URL（<code class="px-1.5 py-0.5 bg-slate-100 rounded text-indigo-600 font-mono text-xs">https://timetable.tonoaki-sato.home/</code>）を開きます。</li>
                            <li>画面右上の「🔐 本部スタッフログイン」をクリックして上記ログイン画面を開きます。</li>
                            <li>事前に発行されたメールアドレスとパスワードを入力して「ログイン」を押します。</li>
                            <li>ログインが完了すると、自動的に「本部ダッシュボード」が表示されます。</li>
                        </ol>

                        <!-- ヒントボックス -->
                        <div class="mt-4 p-3.5 bg-amber-50/80 border-l-4 border-amber-400 rounded-r-xl text-xs text-amber-900">
                            <div class="font-bold flex items-center space-x-1.5 text-amber-950">
                                <span>💡</span>
                                <span>現場スタッフはログイン不要です</span>
                            </div>
                            <p class="mt-1 text-amber-800">
                                現場ボランティア等のスタッフは、第7章で案内する「専用QRコード」または「個別URL」を開くだけで自身のシフトが確認できます。ログインパスワードを管理する必要はありません。
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第2章: 本部ダッシュボード -->
                <!-- ========================================== -->
                <section id="sec-dashboard" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 02</span>
                        <span>•</span>
                        <span>HQ Dashboard</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第2章 本部ダッシュボードの見方と進捗管理</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        ログイン直後に表示される司令塔画面です。まつり本番までの残日数、本日の進行中タスク、人員過不足のアラート、現場スタッフの出勤状況が1画面に集約されています。
                    </p>

                    <!-- 実際の画面画像: 本部ダッシュボード -->
                    <div class="mt-6 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】本部ダッシュボード (/dashboard)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/02_dashboard.png') }}"
                                alt="本部ダッシュボード画面の実画面キャプチャ"
                                onclick="openImageModal(this.src, '本部ダッシュボード画面')"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- 解説項目 -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs sm:text-sm">
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <div class="font-bold text-slate-900 flex items-center space-x-2">
                                <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0">1</span>
                                <span>開催カウントダウン & 進行日選択</span>
                            </div>
                            <p class="text-slate-600 text-xs leading-relaxed">
                                まつり本番までのカウントダウンを表示。「前日準備」「Day 1 本番」「Day 2 最終日」等の運行日ボタンをクリックして、対象日の集計データを即座に切り替えられます。
                            </p>
                        </div>

                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <div class="font-bold text-slate-900 flex items-center space-x-2">
                                <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0">2</span>
                                <span>作業タスク進捗バー</span>
                            </div>
                            <p class="text-slate-600 text-xs leading-relaxed">
                                「未着手」「進行中」「完了」のタスク件数とパーセンテージをカラーバーで可視化。現場スタッフがスマホで完了報告するとリアルタイムに更新されます。
                            </p>
                        </div>

                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <div class="font-bold text-slate-900 flex items-center space-x-2">
                                <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0">3</span>
                                <span>直近・進行中タスク（Now & Next）</span>
                            </div>
                            <p class="text-slate-600 text-xs leading-relaxed">
                                「現在進行中のタスク」と「次の30分以内に開始するタスク」を自動抽出。遅れや担当者の過不足を逃さず察知できます。
                            </p>
                        </div>

                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <div class="font-bold text-slate-900 flex items-center space-x-2">
                                <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0">4</span>
                                <span>要対応アラート & チェックイン集計</span>
                            </div>
                            <p class="text-slate-600 text-xs leading-relaxed">
                                必要人数に達していない未割り当てタスクや重複アサインを赤・黄バッジで警告。クリックすると直接アサイン画面へジャンプできます。
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第3章: 総合カレンダー & 年間工程表 -->
                <!-- ========================================== -->
                <section id="sec-calendar" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 03</span>
                        <span>•</span>
                        <span>Calendar & Schedules</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第3章 総合カレンダー & 年間スケジュールの管理</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        4月から翌年3月までの12ヶ月運用サイクルを管理します。行政協議（警察の道路使用許可、消防・保健所届出）、業者機材早期予約、出店者募集、定期会議（総会・幹事会）の漏れを防ぎます。
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
                                onclick="openImageModal(this.src, '総合カレンダー画面')"
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
                                <span class="ml-2">【実画面】年間スケジュール一覧 (/annual-schedules)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/04_annual_schedules.png') }}"
                                alt="年間スケジュール画面の実画面キャプチャ"
                                onclick="openImageModal(this.src, '年間スケジュール一覧画面')"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第4章: 運行タイムテーブルマトリクス -->
                <!-- ========================================== -->
                <section id="sec-timetable" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 04</span>
                        <span>•</span>
                        <span>Matrix Timetable</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第4章 運行タイムテーブルマトリクス表示</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        運行本部の大型ディスプレイに投影して使用する中心的な画面です。<strong>横軸に担当班（レーン）、縦軸に時間スロット（15分単位）</strong>を配置し、全体の業務進捗・人員配置・優先順位を俯瞰できます。
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
                                onclick="openImageModal(this.src, '運行進行表マトリクス俯瞰画面')"
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
                                <span>最優先バッジ & 機材表示</span>
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
                </section>

                <!-- ========================================== -->
                <!-- 第5章: 計画台帳・レーン・アサイン -->
                <!-- ========================================== -->
                <section id="sec-tasks" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 05</span>
                        <span>•</span>
                        <span>Task & Lanes</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第5章 タイムテーブル台帳・担当班（レーン）編成・人員アサイン</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        まつり本番に先立ち、日程枠の登録、担当班（レーン）の編成、作業タスクの洗い出し、および各タスクへのスタッフアサインを行う手順です。
                    </p>

                    <!-- 実際の画面画像: タイムテーブル・タスク台帳 -->
                    <div class="mt-6 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】タイムテーブル・タスク台帳 (/timetables)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/06_timetables_plan.png') }}"
                                alt="タイムテーブル・タスク台帳画面の実画面キャプチャ"
                                onclick="openImageModal(this.src, 'タイムテーブル・タスク台帳画面')"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- 実際の画面画像: 担当班（レーン）管理 -->
                    <div class="mt-8 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】担当班（レーン）管理 (/lanes)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/07_lanes.png') }}"
                                alt="担当班管理画面の実画面キャプチャ"
                                onclick="openImageModal(this.src, '担当班（レーン）管理画面')"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- レーン編成の操作ポイント -->
                    <div class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm space-y-2">
                        <h4 class="font-bold text-slate-900">班編成と現場リーダー設定の手順</h4>
                        <ol class="list-decimal list-inside space-y-1.5 text-slate-600 text-xs leading-relaxed">
                            <li>「担当班管理」画面で、該当日（例: Day 1）を選択します。</li>
                            <li>「新規レーン追加」から班名（例: 道路警備班、衛生班）、表示色、種別を設定します。</li>
                            <li>各レーンに「現場リーダー（班長）」を任命すると、当日の緊急連絡網に反映されます。</li>
                            <li>ドラッグ操作でタイムテーブル上の左右の並び順を自在に並び替えできます。</li>
                        </ol>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第6章: スタッフ名簿 & 帳票印刷 -->
                <!-- ========================================== -->
                <section id="sec-staff" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 06</span>
                        <span>•</span>
                        <span>Staff & Cards</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第6章 スタッフ管理・名簿・カード印刷</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        現場ボランティアの名簿管理、CSV一括登録、および現場配布用の「名札・スタッフ証印刷」の操作手順です。
                    </p>

                    <!-- 実際の画面画像: スタッフ名簿 -->
                    <div class="mt-6 border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
                        <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between text-xs">
                            <span class="flex items-center space-x-2 text-slate-300 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                                <span class="ml-2">【実画面】スタッフ名簿一覧・管理 (/staff)</span>
                            </span>
                            <span class="text-[10px] text-slate-400">クリックで拡大</span>
                        </div>
                        <div class="p-2 sm:p-4 bg-slate-950/40">
                            <img src="{{ asset('images/manual/08_staff_index.png') }}"
                                alt="スタッフ名簿一覧画面の実画面キャプチャ"
                                onclick="openImageModal(this.src, 'スタッフ名簿一覧画面')"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- 実際の画面画像: スタッフカード印刷 -->
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
                                onclick="openImageModal(this.src, 'スタッフカード・名札印刷プレビュー画面')"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <!-- スタッフカードの現場運用フロー -->
                    <div class="mt-4 p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl text-xs sm:text-sm space-y-1.5">
                        <div class="font-bold text-indigo-950 flex items-center space-x-1.5">
                            <span>🪪</span>
                            <span>名札ケースに入れるだけの簡単運用</span>
                        </div>
                        <p class="text-indigo-900 text-xs leading-relaxed">
                            「スタッフカード一括印刷」を開き、市販のA4用紙に印刷して切り取るだけで、名刺ホルダーにそのまま入る名札が完成します。各スタッフ固有のQRコードが印字されており、現場で読み取るだけで自分のシフトがスマホに表示されます。
                        </p>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第7章: 現場マイシフト (スマホ操作) -->
                <!-- ========================================== -->
                <section id="sec-myshift" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 07</span>
                        <span>•</span>
                        <span>Field Mobile Shift</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第7章 現場スタッフ向けマイシフト（スマホ利用ガイド）</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        現場ボランティア・スタッフが自身のスマートフォンで担当タスクを確認し、出勤報告や進捗更新を行うためのガイドです。
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
                                    onclick="openImageModal(this.src, '現場マイシフト画面（スマホ表示）')"
                                    class="w-full cursor-zoom-in hover:opacity-95 transition">
                            </div>
                            <div class="text-center text-[10px] text-slate-400 mt-2 font-mono">iPhone 相当ビュー</div>
                        </div>

                        <!-- スマホでの操作手順ステップ -->
                        <div class="space-y-4 text-xs sm:text-sm text-slate-700">
                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0">1</span>
                                    <span>QRコードをカメラで読み取る</span>
                                </h4>
                                <p class="text-slate-600 text-xs leading-relaxed">
                                    スタッフ証に印字されたQRコードをスマホの標準カメラでスキャンするだけで、自身専用のマイシフト画面が開きます。ID・パスワード入力は一切不要です。
                                </p>
                            </div>

                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0">2</span>
                                    <span>現場到着時に「出勤チェックイン」をタップ</span>
                                </h4>
                                <p class="text-slate-600 text-xs leading-relaxed">
                                    本部に到着したら緑色の「出勤チェックイン」ボタンをタップ。本部ダッシュボードに出勤完了が即時連携されます。
                                </p>
                            </div>

                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0">3</span>
                                    <span>担当タスクの開始・完了をワンタップ報告</span>
                                </h4>
                                <p class="text-slate-600 text-xs leading-relaxed">
                                    担当する時間帯のタスクが表示され、作業開始時に「開始」、作業終了時に「完了」をタップするだけで、本部へスムーズに報告が伝達されます。
                                </p>
                            </div>

                            <div class="p-3.5 bg-amber-50/80 border border-amber-200 rounded-xl space-y-1">
                                <h4 class="font-bold text-amber-950 flex items-center space-x-1.5">
                                    <span>⚠️</span>
                                    <span>名札やURLを紛失したときは？（本部管理者が再発行）</span>
                                </h4>
                                <p class="text-amber-900 text-xs leading-relaxed">
                                    名札やリンクを紛失した場合は、本部受付にお越しください。管理者が台帳からURLを確認・再送するか、落とした名札の古いQRコードを即時無効化して新しい名札カードを再発行します。
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第8章: ユーザー管理・権限設定 -->
                <!-- ========================================== -->
                <section id="sec-users" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 08</span>
                        <span>•</span>
                        <span>Administration</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第8章 ユーザー管理・権限設定（管理者向け）</h2>
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
                                onclick="openImageModal(this.src, 'ユーザー管理画面')"
                                class="w-full rounded-xl border border-slate-700/80 shadow-md cursor-zoom-in hover:opacity-95 transition">
                        </div>
                    </div>

                    <div class="mt-6 space-y-4 text-xs sm:text-sm text-slate-700">
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <h4 class="font-bold text-slate-900 text-xs">ユーザーの追加とロール指定</h4>
                            <ol class="list-decimal list-inside space-y-1.5 text-slate-600 text-xs leading-relaxed">
                                <li>ヘッダーの「設定・管理」→「ユーザー管理」をクリックします。</li>
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
                        <span>Troubleshooting</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">付録 よくある質問（FAQ）& トラブル対応</h2>
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
                                    <span>印刷したときに画面のボタンやサイドバーが写り込んでしまいませんか？</span>
                                </span>
                                <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                            </summary>
                            <p class="mt-3 text-slate-600 leading-relaxed text-xs border-t border-slate-200 pt-3">
                                本システムは全画面でPrint CSSが組み込まれており、ブラウザの「印刷」を実行すると自動的にナビゲーションやボタン等の不要な要素が非表示になり、整然としたA4用紙向け帳票として印刷されます。
                            </p>
                        </details>
                    </div>
                </section>

            </div>
        </div>

    </div>

    <!-- 画像拡大表示モーダル (ライトボックス) -->
    <div id="imageModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs z-50 hidden flex flex-col items-center justify-center p-4" onclick="closeImageModal()">
        <div class="max-w-6xl w-full bg-slate-900 rounded-2xl overflow-hidden border border-slate-700 shadow-2xl flex flex-col max-h-[92vh]" onclick="event.stopPropagation()">
            <div class="px-4 py-2.5 bg-slate-800 border-b border-slate-700 flex items-center justify-between text-xs text-white">
                <span id="modalCaption" class="font-bold flex items-center space-x-2">
                    <span>🖼️</span>
                    <span>画像プレビュー</span>
                </span>
                <button type="button" onclick="closeImageModal()" class="text-slate-400 hover:text-white text-lg p-1 cursor-pointer">✕ 閉じる</button>
            </div>
            <div class="p-2 sm:p-4 overflow-auto flex-1 flex items-center justify-center bg-slate-950/60">
                <img id="modalImage" src="" alt="拡大画像" class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-lg">
            </div>
        </div>
    </div>

    <!-- キーワードリアルタイム検索スクリプト & 目次スムーススクロール & 画像モーダル制御（CSP準拠） -->
    <script src="{{ asset('js/manual-helper.js') }}" defer></script>
</x-layouts.app>
