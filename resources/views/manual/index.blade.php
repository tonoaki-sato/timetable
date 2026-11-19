<x-layouts.app title="システム操作マニュアル">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- ページヘッダー -->
        <x-page-header
            title="システム操作マニュアル"
            icon="📖"
            description="全機能一覧・シナリオ別操作手順・本部統括・会場白地図・資材物流・現場マイシフトの公式操作ガイド"
            class="pb-4 border-b border-slate-200">
            <x-slot:breadcrumbs>
                <span class="text-slate-500">⚙️ システム・ガイド</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">📖 操作マニュアル</span>
            </x-slot:breadcrumbs>

            <x-slot:actions>
                <!-- キーワードクイック検索 -->
                <div class="relative w-56 sm:w-72 print:hidden">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-xs">🔍</span>
                    <input type="text" id="manualSearchInput"
                        placeholder="キーワード検索 (例: 白地図, 出展者, シナリオ)..."
                        aria-label="マニュアル内キーワード検索"
                        class="w-full pl-8 pr-8 py-1.5 bg-white border border-slate-300 rounded-xl text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs">
                    <button type="button" id="manualSearchClear"
                        aria-label="検索をクリア"
                        class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-700 text-xs font-bold cursor-pointer">
                        ✕
                    </button>
                </div>
                <span id="manualSearchCount" class="hidden sm:inline-flex items-center px-2 py-1 rounded-lg bg-slate-100 text-slate-600 border border-slate-200 text-[11px] font-mono font-bold print:hidden">
                    全 12 セクション
                </span>
                <!-- 印刷ボタン -->
                <x-ui.button variant="header-action" data-action="print" class="print:hidden">
                    <span>🖨️</span>
                    <span>印刷 / PDF出力</span>
                </x-ui.button>
            </x-slot:actions>
        </x-page-header>

        <!-- クイックジャンプインデックスバー (デスクトップ・モバイル共通) -->
        <div class="bg-gradient-to-r from-indigo-50 via-white to-slate-50 border border-indigo-200/80 rounded-2xl p-3 shadow-2xs flex flex-wrap items-center gap-2 text-xs print:hidden">
            <span class="font-bold text-indigo-950 flex items-center space-x-1 mr-1">
                <span>⚡</span>
                <span>クイックガイド:</span>
            </span>
            <a href="#sec-feature-catalog" class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition">
                🌟 全機能一覧カタログ
            </a>
            <a href="#sec-scenarios" class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white font-bold hover:bg-emerald-700 transition">
                🧭 シナリオ別操作手順 (全8選)
            </a>
            <a href="#sec-tasks" class="px-2.5 py-1 rounded-lg bg-white border border-slate-300 text-slate-700 font-bold hover:border-indigo-400 hover:text-indigo-700 transition">
                🚀 運行準備ナビ・班・アサイン
            </a>
            <a href="#sec-staff" class="px-2.5 py-1 rounded-lg bg-white border border-slate-300 text-slate-700 font-bold hover:border-indigo-400 hover:text-indigo-700 transition">
                🏛️ 組織・スタッフ・QR名札
            </a>
            <a href="#sec-logistics" class="px-2.5 py-1 rounded-lg bg-white border border-slate-300 text-slate-700 font-bold hover:border-indigo-400 hover:text-indigo-700 transition">
                🗺️ 会場配置・A3白地図・物流
            </a>
            <a href="#sec-myshift" class="px-2.5 py-1 rounded-lg bg-white border border-slate-300 text-slate-700 font-bold hover:border-indigo-400 hover:text-indigo-700 transition">
                📱 現場マイシフト
            </a>
            <a href="#sec-reports" class="px-2.5 py-1 rounded-lg bg-white border border-slate-300 text-slate-700 font-bold hover:border-indigo-400 hover:text-indigo-700 transition">
                🖨️ 帳票・データ出力一覧
            </a>
            <a href="#sec-faq" class="px-2.5 py-1 rounded-lg bg-white border border-slate-300 text-slate-700 font-bold hover:border-indigo-400 hover:text-indigo-700 transition">
                ❓ FAQ
            </a>
        </div>

        <!-- 検索結果0件時のエンプティステート -->
        <div id="manualSearchEmpty" class="hidden bg-white border border-slate-200 rounded-2xl p-8 text-center space-y-3 shadow-xs">
            <div class="text-3xl">🔍</div>
            <h3 class="text-sm font-bold text-slate-900">検索条件に一致するマニュアル項目が見つかりませんでした</h3>
            <p class="text-xs text-slate-500">別のキーワード（例: 白地図、出展者、組織、LINE、QR、スワップ）でお試しください。</p>
            <button type="button" data-action="reset-manual-search"
                class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition cursor-pointer">
                すべての章を表示する
            </button>
        </div>

        <!-- メイングリッド (サイドバー目次 + コンテンツ) -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">

            <!-- 左側: 固定目次ナビゲーション (Desktop) -->
            <nav class="hidden lg:block lg:col-span-1 sticky top-20 bg-white border border-slate-200 rounded-2xl p-4 shadow-xs text-xs space-y-1 print:hidden" aria-label="マニュアル目次">
                <div class="font-extrabold text-slate-900 mb-3 pb-2 border-b border-slate-100 flex items-center justify-between">
                    <span class="flex items-center space-x-1.5">
                        <span>📑</span>
                        <span>目次・ガイド一覧</span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-mono">全12節</span>
                </div>
                <div class="space-y-1 max-h-[calc(100vh-160px)] overflow-y-auto pr-1">
                    <a href="#sec-feature-catalog" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-indigo-500 font-mono text-[10px] shrink-0">一覧</span>
                        <span class="truncate">全機能一覧・画面カタログ</span>
                    </a>
                    <a href="#sec-scenarios" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-emerald-600 font-mono text-[10px] shrink-0">実践</span>
                        <span class="truncate">シナリオ別操作手順 (8選)</span>
                    </a>
                    <div class="border-t border-slate-100 my-1.5"></div>
                    <a href="#sec-overview" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px] shrink-0">第1章</span>
                        <span class="truncate">概要・権限・共通機能・プレゼン</span>
                    </a>
                    <a href="#sec-dashboard" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px] shrink-0">第2章</span>
                        <span class="truncate">本部ダッシュボード</span>
                    </a>
                    <a href="#sec-calendar" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px] shrink-0">第3章</span>
                        <span class="truncate">年間工程・会議・LINE・iCal</span>
                    </a>
                    <a href="#sec-timetable" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px] shrink-0">第4章</span>
                        <span class="truncate">進行表マトリクス・印刷</span>
                    </a>
                    <a href="#sec-tasks" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px] shrink-0">第5章</span>
                        <span class="truncate">運行準備ナビ &amp; 編成アサイン</span>
                    </a>
                    <a href="#sec-staff" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px] shrink-0">第6章</span>
                        <span class="truncate">組織管理・スタッフ &amp; QR名札</span>
                    </a>
                    <a href="#sec-logistics" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px] shrink-0">第7章</span>
                        <span class="truncate">資材物流・拠点・出展者・白地図</span>
                    </a>
                    <a href="#sec-myshift" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px] shrink-0">第8章</span>
                        <span class="truncate">現場マイシフト &amp; 自律運行</span>
                    </a>
                    <a href="#sec-users" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px] shrink-0">第9章</span>
                        <span class="truncate">ユーザー権限 &amp; 帳票総覧</span>
                    </a>
                    <a href="#sec-faq" class="manual-nav-link flex items-center space-x-2 px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 font-medium transition">
                        <span class="text-slate-400 font-mono text-[10px] shrink-0">付録</span>
                        <span class="truncate">よくある質問 &amp; FAQ</span>
                    </a>
                </div>
            </nav>

            <!-- 右側: マニュアル本文コンテンツ -->
            <div class="col-span-1 lg:col-span-3 space-y-10" id="manualContent">

                <!-- ========================================== -->
                <!-- 特集1: 全機能一覧・画面カタログ (ナビゲーション完全対応) -->
                <!-- ========================================== -->
                <section id="sec-feature-catalog" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs space-y-6">
                    <div>
                        <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                            <span>Feature Directory</span>
                            <span>•</span>
                            <span>All Modules &amp; Navigation Map</span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">🌟 全機能一覧・画面カタログ（5大メニュー＆共通・モバイル機能）</h2>
                        <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                            上部ナビゲーションバーの5大カテゴリ（「当日運行」「準備・計画」「マスター管理」「帳票・印刷」「システム・ガイド」）およびヘッダー共通機能・現場モバイル機能の全機能を網羅しています。各機能名をクリックすると直接画面へ移動できます。
                        </p>
                    </div>

                    <!-- 1. 当日運行 & 2. システム・ガイド & 共通機能 -->
                    <div class="space-y-5">
                        <!-- カテゴリ1: 当日運行 -->
                        <div class="border border-slate-200 rounded-2xl overflow-hidden">
                            <div class="bg-slate-900 text-white px-4 py-2.5 flex items-center justify-between text-xs font-bold">
                                <span class="flex items-center space-x-2">
                                    <span>⏱️</span>
                                    <span>1. 当日運行（Operations）— 運行当日の監視・リアルタイム調整</span>
                                </span>
                                <span class="text-[10px] font-mono text-emerald-300 bg-emerald-500/20 px-2 py-0.5 rounded">当日・リアルタイム</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                        <tr>
                                            <th class="py-2 px-3.5 w-48">機能・画面名</th>
                                            <th class="py-2 px-3.5 w-40">URL / 経路</th>
                                            <th class="py-2 px-3.5 w-32">対象ロール</th>
                                            <th class="py-2 px-3.5">主な機能・できること</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:underline">📊 本部ダッシュボード</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/dashboard</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">年間フェーズ・カウントダウン、4大KPI、直近会議・許認可、運行日程ゲートウェイ、組織・資機材サマリーを一望する司令塔ポータル。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('timetable.index') }}" class="text-indigo-600 hover:underline">⏱️ 当日進行表マトリクス</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/timetable</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">縦軸＝時刻（15分単位）×横軸＝担当班で全タスクと人員充足、道路規制・まつり開催時間帯バー、現在時刻Nowバーを表示。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('timetables.index') }}" class="text-indigo-600 hover:underline">👥 スタッフタイムライン</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/timetables/{id}/staff-timeline</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">スタッフ軸のガントチャートで稼働時間・過密疲労・空き要員を特定し、緊急応援投入やシフト交代を調整。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('logistics-timeline.index') }}" class="text-indigo-600 hover:underline">🚚 搬出入タイムライン</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/logistics-timeline</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">資材・機材の搬入・中継・夜間保管・撤収ステップを日付別ガントチャートで一覧表示。所有区分・進捗・キーワードで絞り込み可能。</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- カテゴリ2: 準備・計画 -->
                        <div class="border border-slate-200 rounded-2xl overflow-hidden">
                            <div class="bg-indigo-950 text-white px-4 py-2.5 flex items-center justify-between text-xs font-bold">
                                <span class="flex items-center space-x-2">
                                    <span>📋</span>
                                    <span>2. 準備・計画（Planning）— 運行編成・会場レイアウト・物流・年間日程</span>
                                </span>
                                <span class="text-[10px] font-mono text-indigo-200 bg-indigo-500/20 px-2 py-0.5 rounded">事前準備</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                        <tr>
                                            <th class="py-2 px-3.5 w-48">機能・画面名</th>
                                            <th class="py-2 px-3.5 w-44">URL / 経路</th>
                                            <th class="py-2 px-3.5 w-32">対象ロール</th>
                                            <th class="py-2 px-3.5">主な機能・できること</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('preparation-navigator.index') }}" class="text-indigo-600 hover:underline">🚀 運行準備ナビ</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/preparation-navigator</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">Step 1（運行日程）→ Step 2（担当班）→ Step 3（タスク）→ Step 4（人員配置）を迷わず順に進める4ステップ導入ウィザード。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('timetables.index') }}" class="text-indigo-600 hover:underline">📅 運行日程管理</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/timetables</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-purple-100 text-purple-800 font-bold text-[10px]">管理者 (登録/編集)</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">開催日別のタイムテーブル枠、道路交通規制時間帯、まつり開催時間帯の設定。詳細画面は運行日別コックピット＆帳票出力ハブ。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('lanes.index') }}" class="text-indigo-600 hover:underline">🏷️ 担当班編成</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/lanes</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-purple-100 text-purple-800 font-bold text-[10px]">管理者 (編成)</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">班の登録、テーマカラー・アイコン設定、👑班長の任命、📌固定スタッフの紐付け、進行表の左右表示順ドラッグ変更。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('timetable-tasks.index') }}" class="text-indigo-600 hover:underline">📝 作業タスク台帳</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/timetable-tasks</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">班別の作業タスク（開始/終了時刻、場所、必要人数、重要フラグ、使用機材、手順メモ）および全班一括の「共通タスク」登録。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('assignments.index') }}" class="text-indigo-600 hover:underline">👥 人員事前配置</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/assignments</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">左ペインでタスクを選び右ペインでスタッフを割当。個別・複数一括・班プール一括・交代(スワップ)と重複自動検知に対応。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('logistics.index') }}" class="text-indigo-600 hover:underline">🚚 搬出入計画</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/logistics</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">資材ごとに事前準備・前日・1日目朝/夜・2日目朝/夜・翌日回収のステップを管理。定番パターンのワンクリック適用も対応。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('timetables.index') }}" class="text-indigo-600 hover:underline">🗺️ 配置計画・配置一覧</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/timetables/{id}/layouts</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">ござ市・屋台ブース・現場設備（発電機・給水・救護所等）のエリア別サマリーおよびコマ割り台帳を管理。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('timetables.index') }}" class="text-indigo-600 hover:underline">🎨 白地図配置エディタ</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/timetables/{id}/site-maps/editor</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">A3 1/400 CADベクター図面にブース・設備をドラッグ配置。自動吸着・一括整列・20m衛生圏表示・PNGアイコン生成・A3 PDF出力に対応。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('timetables.index') }}" class="text-indigo-600 hover:underline">📍 拠点・規制スケジュール</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/timetables/{id}/locations</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">本部テント・神輿番所・臨時駐車場・給水所などの住所/連絡先と、利用可能時間帯・車両通行止め規制枠を管理。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('calendar.index') }}" class="text-indigo-600 hover:underline">📅 総合カレンダー</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/calendar</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">月間カレンダーで会議・許認可・募集・運行日を俯瞰。上部パレットからドラッグ＆ドロップで予定を直感登録・移動。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('schedules.index') }}" class="text-indigo-600 hover:underline">📋 スケジュール管理</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/schedules</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">年間行事・総会・幹事会・行政申請を時系列管理。LINE告知文のワンクリックコピー、iCal（.ics）出力、決定事項メモ記録に対応。</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- カテゴリ3: マスター管理 -->
                        <div class="border border-slate-200 rounded-2xl overflow-hidden">
                            <div class="bg-slate-800 text-white px-4 py-2.5 flex items-center justify-between text-xs font-bold">
                                <span class="flex items-center space-x-2">
                                    <span>🗂️</span>
                                    <span>3. マスター管理（Master Data）— 組織・人員・出展者・資材・テンプレート台帳</span>
                                </span>
                                <span class="text-[10px] font-mono text-amber-300 bg-amber-500/20 px-2 py-0.5 rounded">年間共通基盤</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                        <tr>
                                            <th class="py-2 px-3.5 w-48">機能・画面名</th>
                                            <th class="py-2 px-3.5 w-40">URL / 経路</th>
                                            <th class="py-2 px-3.5 w-32">対象ロール</th>
                                            <th class="py-2 px-3.5">主な機能・できること</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('organizations.index') }}" class="text-indigo-600 hover:underline">🏛️ 組織管理</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/organizations</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">実行委員会の部門・係・役職・担当スタッフ体制を管理。一覧表と組織図ツリービューの切替、並び替え、組織図PDF・所属スタッフPDF出力に対応。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('staff.index') }}" class="text-indigo-600 hover:underline">👷 スタッフ名簿</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/staff</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">ボランティア・幹事の台帳管理。CSV一括取込/出力、QR名札一括/単一印刷、アクセストークン再発行、セルフ登録設定に対応。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('vendors.index') }}" class="text-indigo-600 hover:underline">🎪 出展・出店者マスター</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/vendors</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">ござ市・キッチンカー・企業協賛等の出展者連絡先・品目・電気/火気要件等を管理。CSV一括取込/出力および配置一覧との連携に対応。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('equipments.index') }}" class="text-indigo-600 hover:underline">📦 機材・備品一覧</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/equipments</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">実行委員会保有・提携レンタル・私物の資材を一元台帳化。数量・保管場所・単位の管理とCSV一括取込/出力、表示順変更に対応。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('location-types.index') }}" class="text-indigo-600 hover:underline">🏷️ 拠点タイプ管理</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/location-types</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">番所・駐車場・詰所・救護所などの拠点種別マスタ、表示アイコン・カラーテーマを設定。</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="py-2.5 px-3.5 font-bold">
                                                <a href="{{ route('equipment-patterns.index') }}" class="text-indigo-600 hover:underline">🧩 搬出入パターン</a>
                                            </td>
                                            <td class="py-2.5 px-3.5 font-mono text-[11px] text-slate-500">/equipment-patterns</td>
                                            <td class="py-2.5 px-3.5"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">管理者 / 本部</span></td>
                                            <td class="py-2.5 px-3.5 text-slate-600">全運行日で再利用できる「前日倉庫出し→会場設置→夜間保管→翌日回収」等の標準ステップテンプレートを作成・編集。</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- カテゴリ4 & 5 & 6: システム・ガイド / 共通ユーティリティ / 現場モバイル / 帳票一覧サマリー -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- システム・ガイド & 共通機能 -->
                            <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/40">
                                <div class="bg-slate-900 text-white px-4 py-2.5 text-xs font-bold flex items-center justify-between">
                                    <span>⚙️ 4. システム・ガイド &amp; 共通機能</span>
                                    <span class="text-[10px] text-indigo-300 font-mono">全画面共通</span>
                                </div>
                                <div class="p-3.5 space-y-2.5 text-xs">
                                    <div class="p-2.5 bg-white border border-slate-200 rounded-xl">
                                        <div class="font-bold text-slate-900 flex items-center justify-between">
                                            <a href="{{ route('presentation.index') }}" class="text-indigo-600 hover:underline">📽️ 幹事会プレゼン</a>
                                            <span class="font-mono text-[10px] text-slate-400">/presentation</span>
                                        </div>
                                        <p class="text-[11px] text-slate-600 mt-0.5">実行委員会や幹事会での合意形成用フルスクリーンスライド（キーボード操作対応）。</p>
                                    </div>
                                    <div class="p-2.5 bg-white border border-slate-200 rounded-xl">
                                        <div class="font-bold text-slate-900 flex items-center justify-between">
                                            <a href="{{ route('users.index') }}" class="text-indigo-600 hover:underline">👤 ユーザー管理（管理者専用）</a>
                                            <span class="font-mono text-[10px] text-slate-400">/users</span>
                                        </div>
                                        <p class="text-[11px] text-slate-600 mt-0.5">本部アカウントの追加、管理者/オペレーター権限変更、アカウント有効/無効切替。</p>
                                    </div>
                                    <div class="p-2.5 bg-white border border-slate-200 rounded-xl">
                                        <div class="font-bold text-slate-900 flex items-center justify-between">
                                            <span>🔍 グローバル横断クイック検索</span>
                                            <span class="font-mono text-[10px] text-indigo-600 bg-indigo-50 px-1.5 rounded">Ctrl+K / Cmd+K</span>
                                        </div>
                                        <p class="text-[11px] text-slate-600 mt-0.5">スタッフ・出展者・機材・予定・タスクを全画面どこからでも即座に横断検索して直接移動。</p>
                                    </div>
                                    <div class="p-2.5 bg-white border border-slate-200 rounded-xl">
                                        <div class="font-bold text-slate-900 flex items-center justify-between">
                                            <span>🔔 ヘッダー現場要請通知ベル &amp; コンテキストバー</span>
                                            <span class="font-mono text-[10px] text-amber-700 bg-amber-50 px-1.5 rounded">リアルタイム</span>
                                        </div>
                                        <p class="text-[11px] text-slate-600 mt-0.5">未対応の班長応援・機材要請をバッジ通知しその場で手配/完了処理。コンテキストバーで同一運行日の10機能間を1クリック移動。</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 現場モバイル機能 & 帳票出力サマリー -->
                            <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/40">
                                <div class="bg-emerald-900 text-white px-4 py-2.5 text-xs font-bold flex items-center justify-between">
                                    <span>📱 5. 現場モバイル &amp; 🖨️ 帳票・データ出力</span>
                                    <span class="text-[10px] text-emerald-200 font-mono">現場・印刷</span>
                                </div>
                                <div class="p-3.5 space-y-2.5 text-xs">
                                    <div class="p-2.5 bg-white border border-slate-200 rounded-xl">
                                        <div class="font-bold text-slate-900 flex items-center justify-between">
                                            <a href="{{ route('staff.join') }}" target="_blank" class="text-emerald-700 hover:underline">🌐 ボランティアWebセルフ登録 ↗</a>
                                            <span class="font-mono text-[10px] text-slate-400">/join</span>
                                        </div>
                                        <p class="text-[11px] text-slate-600 mt-0.5">ログイン不要の公募登録フォーム。氏名＋カナの自動名寄せと本部側での受付開始/終了・締切日時予約に対応。</p>
                                    </div>
                                    <div class="p-2.5 bg-white border border-slate-200 rounded-xl">
                                        <div class="font-bold text-slate-900 flex items-center justify-between">
                                            <span>📱 現場マイシフト（マイタイムテーブル）</span>
                                            <span class="font-mono text-[10px] text-slate-400">/my/shift/{token}</span>
                                        </div>
                                        <p class="text-[11px] text-slate-600 mt-0.5">名札QRで起動。到着チェックイン、Now &amp; Next自律誘導、着手/完了報告、iCal保存、👑班長コンソール（要請送信）を搭載。</p>
                                    </div>
                                    <div class="p-2.5 bg-white border border-indigo-200 rounded-xl">
                                        <div class="font-bold text-indigo-950 flex items-center justify-between">
                                            <span>🖨️ 帳票・PDF・CSV・iCal 出力 (区分A / 区分B)</span>
                                            <a href="#sec-reports" class="text-[10px] text-indigo-600 hover:underline font-bold">詳細一覧へ ↓</a>
                                        </div>
                                        <p class="text-[11px] text-slate-600 mt-0.5">
                                            <strong>区分A（年間共通）:</strong> 組織図PDF、所属スタッフPDF、名札印刷、年間工程PDF、スタッフ名簿PDF、各種CSV/iCal。<br>
                                            <strong>区分B（運行日別）:</strong> 運行マトリクスPDF、担当班編成表PDF、搬出入指示書PDF、人員配置表PDF、A3 1/400白地図PDF。
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 特集2: シナリオ別操作手順ガイド (全8実践シナリオ) -->
                <!-- ========================================== -->
                <section id="sec-scenarios" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                        <div>
                            <div class="flex items-center space-x-2 text-emerald-600 font-bold text-xs uppercase tracking-wider mb-1">
                                <span>Playbooks &amp; Workflows</span>
                                <span>•</span>
                                <span>Scenario-Based Step-by-Step Guides</span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">🧭 シナリオ別操作手順ガイド（時期・役割別の実践ワークフロー）</h2>
                            <p class="text-xs sm:text-sm text-slate-600 mt-1.5 leading-relaxed">
                                「何から操作すればよいか分からない」という場面でも迷わないよう、まつりの準備フェーズから当日現場・緊急トラブル対応までを8つの実践シナリオに整理しました。
                            </p>
                        </div>
                        <span id="scenarioCountBadge" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold font-mono shrink-0">
                            8 シナリオ表示中
                        </span>
                    </div>

                    <!-- シナリオ絞り込みフィルターチップ -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-2.5 flex flex-wrap items-center gap-1.5 text-xs print:hidden">
                        <span class="font-bold text-slate-600 mr-1">🎯 シナリオ絞り込み:</span>
                        <button type="button" data-scenario-filter="all" aria-pressed="true"
                            class="px-3 py-1 rounded-lg font-bold border transition cursor-pointer bg-indigo-600 text-white border-indigo-600 shadow-2xs">
                            すべて (8)
                        </button>
                        <button type="button" data-scenario-filter="planning" aria-pressed="false"
                            class="px-3 py-1 rounded-lg font-bold border transition cursor-pointer bg-white text-slate-700 border-slate-300 hover:bg-slate-50">
                            📋 事前準備・計画 (3)
                        </button>
                        <button type="button" data-scenario-filter="venue" aria-pressed="false"
                            class="px-3 py-1 rounded-lg font-bold border transition cursor-pointer bg-white text-slate-700 border-slate-300 hover:bg-slate-50">
                            🗺️ 会場配置・物流 (2)
                        </button>
                        <button type="button" data-scenario-filter="operation" aria-pressed="false"
                            class="px-3 py-1 rounded-lg font-bold border transition cursor-pointer bg-white text-slate-700 border-slate-300 hover:bg-slate-50">
                            ⏱️ 当日運行・現場 (2)
                        </button>
                        <button type="button" data-scenario-filter="trouble" aria-pressed="false"
                            class="px-3 py-1 rounded-lg font-bold border transition cursor-pointer bg-white text-slate-700 border-slate-300 hover:bg-slate-50">
                            🚨 緊急トラブル対応 (1)
                        </button>
                    </div>

                    <!-- シナリオカード一覧 -->
                    <div class="grid grid-cols-1 gap-5">

                        <!-- シナリオ 01 -->
                        <article class="scenario-card border border-slate-200 rounded-2xl p-5 bg-slate-50/50 space-y-3" data-scenario-category="planning">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-2.5">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded-lg bg-indigo-600 text-white font-mono font-bold text-[11px]">Scenario 01</span>
                                    <h3 class="text-sm sm:text-base font-black text-slate-900">年間立ち上げ・実行委員会の組織構築と会議・LINE告知</h3>
                                </div>
                                <div class="flex items-center gap-1.5 text-[10px] font-bold">
                                    <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-800">実行委員長・管理者</span>
                                    <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-700">開催3〜12ヶ月前</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 text-xs">
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                                    <div class="font-bold text-indigo-700">Step 1. 組織体制を定義</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('organizations.index') }}" class="text-indigo-600 font-bold hover:underline">組織管理</a>で部門・係・役職と担当者を登録し、ドラッグで序列を整え「組織図PDF」を出力します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                                    <div class="font-bold text-indigo-700">Step 2. 年間工程・会議を登録</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('calendar.index') }}" class="text-indigo-600 font-bold hover:underline">総合カレンダー</a>のパレットからドラッグ＆ドロップで総会・幹事会・道路使用許可申請日を登録します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                                    <div class="font-bold text-indigo-700">Step 3. LINE告知 &amp; iCal連携</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('schedules.index') }}" class="text-indigo-600 font-bold hover:underline">スケジュール管理</a>の詳細から「LINE告知文をコピー」で役員グループへ配信し、.icsでスマホ登録します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                                    <div class="font-bold text-indigo-700">Step 4. 幹事会で合意形成</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        会議当日は<a href="{{ route('presentation.index') }}" class="text-indigo-600 font-bold hover:underline">幹事会プレゼン</a>を全画面投影（[F]キー）し、システム運用の全体方針を共有します。
                                    </p>
                                </div>
                            </div>
                        </article>

                        <!-- シナリオ 02 -->
                        <article class="scenario-card border border-slate-200 rounded-2xl p-5 bg-slate-50/50 space-y-3" data-scenario-category="planning">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-2.5">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded-lg bg-indigo-600 text-white font-mono font-bold text-[11px]">Scenario 02</span>
                                    <h3 class="text-sm sm:text-base font-black text-slate-900">ボランティア公募・前年度名簿の取り込みとQR名札印刷</h3>
                                </div>
                                <div class="flex items-center gap-1.5 text-[10px] font-bold">
                                    <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800">本部オペレーター</span>
                                    <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-700">開催1〜2ヶ月前</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 text-xs">
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                                    <div class="font-bold text-indigo-700">Step 1. セルフ登録の受付開始</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('staff.index') }}" class="text-indigo-600 font-bold hover:underline">スタッフ名簿</a>またはヘッダー「システム・ガイド」からWebセルフ登録を受付中にし、募集締切日時を設定します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                                    <div class="font-bold text-indigo-700">Step 2. 案内文コピーで公募</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        「登録案内文コピー」で <code class="font-mono text-indigo-600">/join</code> URL入り定型文を取得し、町会・大学・SNS等へ一斉案内します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                                    <div class="font-bold text-indigo-700">Step 3. CSV一括登録・自動名寄せ</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        前年度名簿はCSV一括登録で取り込みます。セルフ登録と重複しても氏名＋カナによるスマート名寄せで自動統合されます。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                                    <div class="font-bold text-indigo-700">Step 4. 名簿 &amp; QR名札印刷</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('staff.print-cards') }}" target="_blank" class="text-indigo-600 font-bold hover:underline">名札カード一括印刷</a>でA4出力し、カットして首掛け名札ケースに封入します。
                                    </p>
                                </div>
                            </div>
                        </article>

                        <!-- シナリオ 03 -->
                        <article class="scenario-card border border-slate-200 rounded-2xl p-5 bg-slate-50/50 space-y-3" data-scenario-category="planning">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-2.5">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded-lg bg-indigo-600 text-white font-mono font-bold text-[11px]">Scenario 03</span>
                                    <h3 class="text-sm sm:text-base font-black text-slate-900">運行準備ナビ（4ステップ）でタイムテーブル・班・人員配置を完成</h3>
                                </div>
                                <div class="flex items-center gap-1.5 text-[10px] font-bold">
                                    <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-800">管理者 / 本部</span>
                                    <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-700">開催2週間〜1ヶ月前</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 text-xs">
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                                    <div class="font-bold text-indigo-700">Step 1. 運行日程＆規制時間作成</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('preparation-navigator.index') }}" class="text-indigo-600 font-bold hover:underline">運行準備ナビ</a>から<a href="{{ route('timetables.index') }}" class="text-indigo-600 font-bold hover:underline">運行日程管理</a>を開き、日付・開始終了時刻・道路規制時間・まつり開催時間を設定します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                                    <div class="font-bold text-indigo-700">Step 2. 担当班・班長・固定人員</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('lanes.index') }}" class="text-indigo-600 font-bold hover:underline">担当班編成</a>で班を作成し、👑班長と📌固定スタッフを指定すると、その班の全タスクに自動基本配置されます。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                                    <div class="font-bold text-indigo-700">Step 3. 個別・共通タスク登録</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('timetable-tasks.index') }}" class="text-indigo-600 font-bold hover:underline">作業タスク台帳</a>で班ごとのタスクと、朝礼や撤収など複数班へ一括登録できる「共通タスク」を作成します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-1">
                                    <div class="font-bold text-indigo-700">Step 4. 人員配置＆タイムライン点検</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('assignments.index') }}" class="text-indigo-600 font-bold hover:underline">人員事前配置</a>で充足スタッフを割当（コンフリクト警告で重複防止）し、スタッフタイムラインで過密を確認します。
                                    </p>
                                </div>
                            </div>
                        </article>

                        <!-- シナリオ 04 -->
                        <article class="scenario-card border border-slate-200 rounded-2xl p-5 bg-emerald-50/40 space-y-3" data-scenario-category="venue">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-200/80 pb-2.5">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded-lg bg-emerald-600 text-white font-mono font-bold text-[11px]">Scenario 04</span>
                                    <h3 class="text-sm sm:text-base font-black text-slate-900">出展・出店者登録から屋台ブース割当・A3 1/400 白地図図面作成</h3>
                                </div>
                                <div class="flex items-center gap-1.5 text-[10px] font-bold">
                                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">会場・出店担当</span>
                                    <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-700">開催2〜4週間前</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 text-xs">
                                <div class="p-3 bg-white rounded-xl border border-emerald-200 space-y-1">
                                    <div class="font-bold text-emerald-800">Step 1. 出展・出店者台帳登録</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('vendors.index') }}" class="text-indigo-600 font-bold hover:underline">出展・出店者マスター</a>でござ市・キッチンカー・協賛ブースの団体名・担当者・電力を登録（CSV取込可）。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-emerald-200 space-y-1">
                                    <div class="font-bold text-emerald-800">Step 2. 配置計画・ブース割当</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        運行日のコンテキストバー「🗺️ 配置計画」から配置一覧を開き、メイン通りA列（東側歩道）や北川駐車場内、キッズ村等へブース・設備を登録します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-emerald-200 space-y-1">
                                    <div class="font-bold text-emerald-800">Step 3. 白地図エディタで作図</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        「🎨 白地図エディタ」を開き、図形パレットや台帳アイテムを1/400 CAD図面へドラッグ配置。自動吸着・整列・45°回転・20m衛生圏チェックを行います。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-emerald-200 space-y-1">
                                    <div class="font-bold text-emerald-800">Step 4. A3 1/400 PDF出力</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        「PDFダウンロード (A3 1/400)」を押すと印刷用の高精細ベクターPDFが生成され、消防・保健所届出や当日設営図として配布できます。
                                    </p>
                                </div>
                            </div>
                        </article>

                        <!-- シナリオ 05 -->
                        <article class="scenario-card border border-slate-200 rounded-2xl p-5 bg-emerald-50/40 space-y-3" data-scenario-category="venue">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-200/80 pb-2.5">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded-lg bg-emerald-600 text-white font-mono font-bold text-[11px]">Scenario 05</span>
                                    <h3 class="text-sm sm:text-base font-black text-slate-900">資材・機材手配と6大フェーズ搬出入計画・トラック指示書発行</h3>
                                </div>
                                <div class="flex items-center gap-1.5 text-[10px] font-bold">
                                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">資材・物流担当</span>
                                    <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-700">開催1〜3週間前</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 text-xs">
                                <div class="p-3 bg-white rounded-xl border border-emerald-200 space-y-1">
                                    <div class="font-bold text-emerald-800">Step 1. 機材＆搬出入パターン登録</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('equipments.index') }}" class="text-indigo-600 font-bold hover:underline">機材・備品一覧</a>に保有/レンタル/私物を登録し、<a href="{{ route('equipment-patterns.index') }}" class="text-indigo-600 font-bold hover:underline">搬出入パターン</a>で標準工程を作ります。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-emerald-200 space-y-1">
                                    <div class="font-bold text-emerald-800">Step 2. 拠点と利用時間帯を設定</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        運行日のコンテキストバー「📍 拠点」で番所・倉庫・臨時駐車場と利用可能時間・車両通行止め規制時間を設定します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-emerald-200 space-y-1">
                                    <div class="font-bold text-emerald-800">Step 3. 搬出入計画へテンプレート適用</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('logistics.index') }}" class="text-indigo-600 font-bold hover:underline">搬出入計画</a>で資材にパターンを一括適用し、前日〜翌日回収までの各移動元→移動先・立会要否を調整します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-emerald-200 space-y-1">
                                    <div class="font-bold text-emerald-800">Step 4. タイムライン確認＆指示書PDF</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('logistics-timeline.index') }}" class="text-indigo-600 font-bold hover:underline">搬出入タイムライン</a>で車両混雑を点検し、「搬出入指示書PDF」を印刷して運転班へ渡します。
                                    </p>
                                </div>
                            </div>
                        </article>

                        <!-- シナリオ 06 -->
                        <article class="scenario-card border border-slate-200 rounded-2xl p-5 bg-amber-50/50 space-y-3" data-scenario-category="operation">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-amber-200/80 pb-2.5">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded-lg bg-amber-600 text-white font-mono font-bold text-[11px]">Scenario 06</span>
                                    <h3 class="text-sm sm:text-base font-black text-slate-900">まつり当日・運行本部オペレーション（監視・要請対応・飛び入り受付）</h3>
                                </div>
                                <div class="flex items-center gap-1.5 text-[10px] font-bold">
                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900">本部オペレーター</span>
                                    <span class="px-2 py-0.5 rounded bg-red-100 text-red-700">まつり当日</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 text-xs">
                                <div class="p-3 bg-white rounded-xl border border-amber-200 space-y-1">
                                    <div class="font-bold text-amber-900">Step 1. 司令塔画面を常時投影</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        本部モニターに<a href="{{ route('dashboard') }}" class="text-indigo-600 font-bold hover:underline">本部ダッシュボード</a>と<a href="{{ route('timetable.index') }}" class="text-indigo-600 font-bold hover:underline">当日進行表マトリクス</a>を開き、赤いNowバーと各班の進捗を監視します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-amber-200 space-y-1">
                                    <div class="font-bold text-amber-900">Step 2. 通知ベル🔔で現場要請対応</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        班長から応援・機材要請が届くとヘッダーの🔔に赤バッジが点灯。クリックして中身を確認し「手配」→「完了」へ更新します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-amber-200 space-y-1">
                                    <div class="font-bold text-amber-900">Step 3. 待機要員の迅速投入</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <code class="font-mono text-indigo-600">Ctrl+K</code> でスタッフを検索するか、スタッフタイムラインで空き時間の要員を特定し、応援タスクへ即追加します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-amber-200 space-y-1">
                                    <div class="font-bold text-amber-900">Step 4. 当日飛び入りスタッフ受付</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('staff.index') }}" class="text-indigo-600 font-bold hover:underline">スタッフ名簿</a>から即座に登録し（固定または充足）、詳細画面の「🖨️ 名札単一印刷」でその場でQR名札を発行します。
                                    </p>
                                </div>
                            </div>
                        </article>

                        <!-- シナリオ 07 -->
                        <article class="scenario-card border border-slate-200 rounded-2xl p-5 bg-amber-50/50 space-y-3" data-scenario-category="operation">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-amber-200/80 pb-2.5">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded-lg bg-amber-600 text-white font-mono font-bold text-[11px]">Scenario 07</span>
                                    <h3 class="text-sm sm:text-base font-black text-slate-900">まつり当日・👑班長＆現場ボランティアのスマホ自律運行（マイシフト）</h3>
                                </div>
                                <div class="flex items-center gap-1.5 text-[10px] font-bold">
                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900">👑班長・ボランティア</span>
                                    <span class="px-2 py-0.5 rounded bg-red-100 text-red-700">まつり当日</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 text-xs">
                                <div class="p-3 bg-white rounded-xl border border-amber-200 space-y-1">
                                    <div class="font-bold text-amber-900">Step 1. QRスキャン＆事前確認</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        名札QRをスマホカメラで読み込み、マイシフトを起動。運行日前は閲覧専用モードで予定確認や「カレンダー保存 (.ics)」を行えます。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-amber-200 space-y-1">
                                    <div class="font-bold text-amber-900">Step 2. 朝の「現場到着（受付）」</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        当日現場に着いたら上部の「📍 現場到着（受付）」をタップ。第1タスクが自動的に「現場着」へ連動し本部へ到着が伝わります。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-amber-200 space-y-1">
                                    <div class="font-bold text-amber-900">Step 3. Now &amp; Nextで報告</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        「NOW 進行中」「NEXT 次の予定」を確認して現地へ移動し、「着手」「完了」をタップすると本部マトリクスの色が即座に更新されます。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-amber-200 space-y-1">
                                    <div class="font-bold text-amber-900">Step 4. 👑班長コンソール活用</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        班長は「👑 班長コンソール」タブから班全体のタスク状態更新や、人員不足・機材追加の要請（レーンリクエスト）を本部へ送信します。
                                    </p>
                                </div>
                            </div>
                        </article>

                        <!-- シナリオ 08 -->
                        <article class="scenario-card border-2 border-red-200 rounded-2xl p-5 bg-red-50/40 space-y-3" data-scenario-category="trouble">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-red-200 pb-2.5">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded-lg bg-red-600 text-white font-mono font-bold text-[11px]">Scenario 08</span>
                                    <h3 class="text-sm sm:text-base font-black text-red-950">緊急トラブル・例外対処（雨天順延・急な欠席・名札紛失・通信不良）</h3>
                                </div>
                                <div class="flex items-center gap-1.5 text-[10px] font-bold">
                                    <span class="px-2 py-0.5 rounded bg-red-100 text-red-800">本部・管理者</span>
                                    <span class="px-2 py-0.5 rounded bg-white text-red-700 border border-red-200">緊急時</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 text-xs">
                                <div class="p-3 bg-white rounded-xl border border-red-200 space-y-1">
                                    <div class="font-bold text-red-900">A. 雨天順延・時間変更</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('timetables.index') }}" class="text-indigo-600 font-bold hover:underline">運行日程管理</a>で開始時刻を変更、またはタスク台帳で時間枠を修正。全スタッフのマイシフトに即時反映されます。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-red-200 space-y-1">
                                    <div class="font-bold text-red-900">B. 急な欠席・体調不良交代</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('assignments.index') }}" class="text-indigo-600 font-bold hover:underline">人員事前配置</a>または進行表から該当タスクを開き、「スタッフ交代（スワップ）」で待機要員へ即入れ替えします。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-red-200 space-y-1">
                                    <div class="font-bold text-red-900">C. 名札QRの紛失・盗難</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        <a href="{{ route('staff.index') }}" class="text-indigo-600 font-bold hover:underline">スタッフ名簿</a>で本人を開き「アクセストークン再発行」を実行（旧QRは即404無効）。続けて「単一カード印刷」で再発行します。
                                    </p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-red-200 space-y-1">
                                    <div class="font-bold text-red-900">D. 現場スマホの圏外・電池切れ</div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        本部が無線や口頭連絡を受け、スタッフ詳細画面またはタスクアサインモーダルから代理で出欠（「現場着」）やタスク完了を更新します。
                                    </p>
                                </div>
                            </div>
                        </article>

                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第1章: システム概要・権限・共通機能 & プレゼン -->
                <!-- ========================================== -->
                <section id="sec-overview" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 01</span>
                        <span>•</span>
                        <span>Getting Started, Roles &amp; Common UI</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第1章 システム概要・利用権限・ログイン手順 &amp; 幹事会プレゼン</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        本システムは、まつり・イベントの年間準備から前日準備・当日本番運行・翌日回収に至るまでの運行・人員・会場配置・機材動態を一元管理するクラウド型総合管理システムです。
                    </p>

                    <!-- 権限ロール比較カード -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="p-4 bg-purple-50/60 border border-purple-200/80 rounded-xl">
                            <div class="flex items-center space-x-2">
                                <span class="bg-purple-900 text-purple-200 text-[10px] font-bold px-2 py-0.5 rounded">管理者 (Admin)</span>
                            </div>
                            <h3 class="font-bold text-sm text-purple-950 mt-2">実行委員長・システム責任者</h3>
                            <p class="text-xs text-purple-800 mt-1 leading-normal">
                                全ての機能（ユーザー登録・権限変更、日程・レーン基本設計、タイムテーブル編集、会場白地図作成、全帳票出力）を利用可能。
                            </p>
                        </div>
                        <div class="p-4 bg-blue-50/60 border border-blue-200/80 rounded-xl">
                            <div class="flex items-center space-x-2">
                                <span class="bg-slate-800 text-slate-200 text-[10px] font-bold px-2 py-0.5 rounded">本部オペレーター</span>
                            </div>
                            <h3 class="font-bold text-sm text-blue-950 mt-2">本部当番・進行管理担当</h3>
                            <p class="text-xs text-blue-800 mt-1 leading-normal">
                                タイムテーブル運行、タスク進捗更新、人員アサイン調整、スタッフ証印刷、出欠管理、出展者・搬出入運用などの日常運用を担当。
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

                    <!-- ヘッダー共通便利機能（検索・通知・コンテキストバー） -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <h4 class="font-bold text-slate-900 flex items-center justify-between">
                                <span class="flex items-center space-x-1.5"><span>🔍</span><span>横断クイック検索</span></span>
                                <kbd class="px-1.5 py-0.5 bg-white border border-slate-300 rounded font-mono text-[10px]">Ctrl+K</kbd>
                            </h4>
                            <p class="text-[11px] text-slate-600 leading-relaxed">
                                ヘッダーの「🔍 検索」または <code class="font-mono">Ctrl+K</code>（Macは <code class="font-mono">Cmd+K</code>）で、スタッフ・出展者・機材・予定・タスクをシステム横断で高速検索し、矢印キー＋Enterで直接開けます。
                            </p>
                        </div>
                        <div class="p-4 bg-amber-50/70 border border-amber-200 rounded-xl space-y-1.5">
                            <h4 class="font-bold text-amber-950 flex items-center space-x-1.5">
                                <span>🔔</span>
                                <span>現場要請通知ベル</span>
                            </h4>
                            <p class="text-[11px] text-amber-900 leading-relaxed">
                                班長から人員・機材の応援要請が届くとヘッダー右上のベルに赤バッジが点灯します。ドロップダウン内で該当運行日を確認し、ワンクリックで「手配」「完了」へ処理できます。
                            </p>
                        </div>
                        <div class="p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-1.5">
                            <h4 class="font-bold text-indigo-950 flex items-center space-x-1.5">
                                <span>🧭</span>
                                <span>運行日コンテキストバー</span>
                            </h4>
                            <p class="text-[11px] text-indigo-900 leading-relaxed">
                                運行日配下の画面上部に常に表示され、対象運行日の切り替えと「運行情報 / 担当班 / タスク台帳 / 人員配置 / 拠点 / 搬出入 / 配置計画 / 白地図 / 進行表 / 配置表PDF」の10機能間を1クリックで横断できます。
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
                        本部ダッシュボード（<code class="font-mono text-indigo-600">/dashboard</code>）は、システムログイン直後に表示される司令塔ポータルです。年間フェーズと本番カウントダウン、全体KPIストリップ、直近の会議・許認可、各運行日のゲートウェイ、組織・資機材サマリーを一目で俯瞰できます。
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

                    <!-- ダッシュボード主要構成ブロックの解説 -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs sm:text-sm">
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <div class="font-bold text-slate-900 flex items-center space-x-1.5">
                                <span class="text-indigo-600 font-extrabold text-sm">01.</span>
                                <span>年間フェーズ &amp; 本番カウントダウン</span>
                            </div>
                            <p class="text-slate-600 text-xs leading-relaxed">
                                現在のまつり準備フェーズと本祭までの残日数、年間スケジュール消化率（完了件数/全件数）を上段バナーに表示します。
                            </p>
                        </div>
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <div class="font-bold text-slate-900 flex items-center space-x-1.5">
                                <span class="text-indigo-600 font-extrabold text-sm">02.</span>
                                <span>会議 &amp; 許認可・申請監視</span>
                            </div>
                            <p class="text-slate-600 text-xs leading-relaxed">
                                左カラムでは直近に控える総会・幹事会および道路使用許可・消防・保健所等の行政申請予定を通知。クリックで直接詳細・LINE告知へ遷移します。
                            </p>
                        </div>
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <div class="font-bold text-slate-900 flex items-center space-x-1.5">
                                <span class="text-indigo-600 font-extrabold text-sm">03.</span>
                                <span>運行日程ゲートウェイ &amp; 進捗</span>
                            </div>
                            <p class="text-slate-600 text-xs leading-relaxed">
                                中央カラムでは運行日ごとの進行ステータス、人員充足率、交通規制・駐車場稼働状態を表示し、進行表・人員配置・搬出入へ即ジャンプできます。
                            </p>
                        </div>
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <div class="font-bold text-slate-900 flex items-center space-x-1.5">
                                <span class="text-indigo-600 font-extrabold text-sm">04.</span>
                                <span>リアルタイム時計 &amp; リソース要約</span>
                            </div>
                            <p class="text-slate-600 text-xs leading-relaxed">
                                サーバー時刻と同期したリアルタイム時計に加え、右カラムで組織・スタッフ・出展者・機材・拠点マスタの登録数とクイックランチャーを提供します。
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
                        4月から翌年3月までの12ヶ月運用サイクルを管理します。行政協議（警察の道路使用許可、消防・保健所届出）、業者機材早期予約、出店者募集、定期会議（総会・幹事会）の漏れを防ぎ、LINE告知およびiCalカレンダー連携まで一元化します。
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

                    <!-- 会議・打ち合わせ管理 & LINE告知文自動生成機能 & iCal/PDF出力 -->
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

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
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
                                    📋 議題: 道路使用許可申請、神輿渡御タイムテーブル確認
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

                            <div class="p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-2">
                                <div class="font-bold text-indigo-950 flex items-center space-x-1.5 text-sm">
                                    <span>📆</span>
                                    <span>iCal (.ics) 連携 &amp; 年間工程PDF</span>
                                </div>
                                <p class="text-indigo-900 leading-relaxed text-[11px]">
                                    各予定または年間スケジュール全体（<a href="{{ route('schedules.ics.all') }}" class="underline font-bold">/schedules/ics</a>）をiCalendar形式でダウンロードし、GoogleカレンダーやiPhone標準カレンダーへ取り込めます。また<a href="{{ route('schedules.pdf') }}" class="underline font-bold">年間スケジュール表PDF</a>のA4縦出力にも対応しています。
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
                        運行本部の大型ディスプレイに投影して使用する中心的な画面です。<strong>横軸に担当班、縦軸に時間スロット（15分単位）</strong>を配置し、全体の業務進捗・人員配置・交通規制時間帯・優先順位を俯瞰できます。
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

                    <!-- マトリクス画面の4大機能 -->
                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
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
                        <div class="p-4 bg-sky-50/80 border border-sky-200 rounded-xl">
                            <div class="font-bold text-sky-900 flex items-center space-x-1.5">
                                <span>🚧</span>
                                <span>道路規制・まつり時間帯帯表示</span>
                            </div>
                            <p class="text-sky-800 mt-1.5 leading-relaxed">
                                運行日程で設定した道路通行止め時間帯やござ市受付・開催時間帯が時間軸に沿って帯表示され、規制タイミングと作業の整合を確認できます。
                            </p>
                        </div>
                    </div>

                    <!-- 人員配置表・進行表マトリクスPDFの印刷機能案内 -->
                    <div class="mt-6 p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                        <div>
                            <span class="font-bold text-indigo-950 flex items-center space-x-1.5">
                                <span>🖨️</span>
                                <span>当日の人員配置表（A4横・班別タスク一覧）の印刷出力 &amp; 進行表PDF</span>
                            </span>
                            <p class="text-indigo-900 mt-1 text-[11px]">
                                本部指揮官や各班長へ紙で配布するための「人員配置表（<code class="px-1 py-0.2 bg-white rounded font-mono text-indigo-700">/timetables/{id}/print-assignments</code>）」および「運行タイムテーブルマトリクスPDF（<code class="px-1 py-0.2 bg-white rounded font-mono text-indigo-700">/timetables/{id}/print-matrix</code>）」をA4横向きで出力できます。
                            </p>
                        </div>
                        <span class="px-2.5 py-1 bg-indigo-600 text-white rounded-lg text-[10px] font-bold shrink-0">運行情報詳細画面から出力可能</span>
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
                        まつり本番に先立ち、迷わず準備を進められる4ステップの「準備ナビゲーター」、日程枠台帳、担当班編成、作業タスク・共通タスク登録、および重複検知付き人員アサインを行う手順です。
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
                            <a href="{{ route('preparation-navigator.index') }}" class="text-xs font-mono text-indigo-600 bg-white px-2.5 py-1 rounded-lg border border-indigo-200 font-bold hover:underline">/preparation-navigator ↗</a>
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
                                    運行日（前日準備、本宮、片付け等）の名称、日付、開始・終了時刻、道路規制・まつり開催時間帯を設定して枠を作成します。
                                </p>
                            </div>
                            <div class="p-3.5 bg-white rounded-xl border border-indigo-100 shadow-2xs space-y-1.5">
                                <div class="font-bold text-indigo-950 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[10px]">2</span>
                                    <span>担当班編成</span>
                                </div>
                                <p class="text-slate-600 text-[11px] leading-relaxed">
                                    道路警備班、衛生班、設営班などを登録し、テーマカラーの設定と班長（現場主担当）・固定スタッフを任命します。
                                </p>
                            </div>
                            <div class="p-3.5 bg-white rounded-xl border border-indigo-100 shadow-2xs space-y-1.5">
                                <div class="font-bold text-indigo-950 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[10px]">3</span>
                                    <span>作業タスク洗い出し</span>
                                </div>
                                <p class="text-slate-600 text-[11px] leading-relaxed">
                                    各班の具体的な業務（バリケード設置、給水所運営等）や全班一括の「共通タスク」の時間枠、必要人数、優先度を登録します。
                                </p>
                            </div>
                            <div class="p-3.5 bg-white rounded-xl border border-indigo-100 shadow-2xs space-y-1.5">
                                <div class="font-bold text-indigo-950 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-[10px]">4</span>
                                    <span>人員アサイン・配置確認</span>
                                </div>
                                <p class="text-slate-600 text-[11px] leading-relaxed">
                                    人員事前配置ワークスペースでスタッフを割り当て、過不足や時間帯重複がないかをスタッフタイムラインで点検します。
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

                    <!-- レーン編成＆共通タスク一括登録の操作ポイント -->
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <h4 class="font-bold text-slate-900">班編成と班長・固定スタッフ設定の手順</h4>
                            <ol class="list-decimal list-inside space-y-1.5 text-slate-600 leading-relaxed">
                                <li>「担当班管理」画面（<code class="px-1 py-0.2 bg-white rounded text-indigo-600 font-mono">/lanes</code>）で、該当日を選択します。</li>
                                <li>「新規レーン追加」から班名（例: 道路警備班）、表示色、無線チャンネル等を設定します。</li>
                                <li>各レーンに「👑 班長」および「📌 固定スタッフ」を設定すると、全タスクへ自動基本配置されます。</li>
                                <li>ドラッグ操作で進行表上の並び順変更や、「担当班編成表PDF」の出力も行えます。</li>
                            </ol>
                        </div>

                        <div class="p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-2">
                            <h4 class="font-bold text-indigo-950 flex items-center space-x-1.5">
                                <span>🌐</span>
                                <span>共通タスク一括登録（全班・複数班共通業務）</span>
                            </h4>
                            <p class="text-indigo-900 leading-relaxed">
                                作業タスク台帳（<code class="px-1 py-0.2 bg-white rounded text-indigo-600 font-mono">/timetable-tasks</code>）の「🌐 共通タスク一括登録」ボタンから、朝礼・開会式・一斉清掃・撤収確認など<strong>複数班にまたがる共通タスクを1回の入力で全対象班へ同時生成</strong>できます。
                            </p>
                        </div>
                    </div>

                    <!-- タスクへの人員アサイン操作 -->
                    <div class="mt-8 border-t border-slate-100 pt-6 space-y-4">
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 flex items-center space-x-2">
                            <span>👥</span>
                            <span>タスクへの人員割り当て（個別・一括・レーンプール・事前配置ワークスペース）</span>
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            <a href="{{ route('assignments.index') }}" class="text-indigo-600 font-bold hover:underline">人員事前配置ワークスペース（/assignments）</a>または各タスクのアサインモーダルから必要なスタッフを割り当てます。左ペインで不足タスクを確認しながら右ペインで即時配置でき、手作業による1名ずつの追加だけでなく、部会全員の一括配置や交代（スワップ）にも対応しています。
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
                            スタッフをアサインする際、システムが自動的に同一運行日の時間帯を照合します。すでに別レーンのタスクに割り当てられている場合は、即座に<strong>コンフリクト警告モーダル</strong>が表示され、ダブルブッキングを未然に防止します。
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
                            運行情報や人員配置画面の「📊 スタッフタイムライン」をクリックすると、<strong>横軸に時刻スロット、縦軸にスタッフ名が並ぶガントチャート</strong>が開きます。
                        </p>
                        <ul class="list-disc list-inside text-xs text-indigo-950 space-y-1.5 pl-1 leading-relaxed">
                            <li><strong>過密稼働の早期発見:</strong> 特定のスタッフが連続して重労働タスクに入っていないか、熱中症リスクや疲労集中を視覚的にチェックできます。</li>
                            <li><strong>フリー要員の可視化:</strong> 同一時間帯にタスクが割り当てられていない「待機・空きスタッフ」が一目で分かります。</li>
                            <li><strong>ワンクリック・スワップ（交代）:</strong> 過密なスタッフと空いているスタッフの間でタスクを交代し、無理のない最適なシフトバランスへ調整できます。</li>
                        </ul>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第6章: 組織管理 & スタッフ管理・名簿・Webセルフ登録 & QRカード印刷 -->
                <!-- ========================================== -->
                <section id="sec-staff" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                        <span>Chapter 06</span>
                        <span>•</span>
                        <span>Organizations, Staff Directory &amp; Self Registration</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第6章 スタッフ管理・名簿・シフト編成・カード印刷（＆組織体制管理）</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        実行委員会の組織体制管理から、ボランティアの公募・セルフ登録、本部側での受付設定、前年度名簿のCSV一括取り込み、スマート名寄せ、QRコード付き名札印刷、および紛失時のトークン再発行に至る組織・人員管理の中核機能です。
                    </p>

                    <!-- 新機能: 組織管理 -->
                    <div class="mt-6 p-5 bg-indigo-50/70 border border-indigo-200 rounded-2xl space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-indigo-200 pb-2.5">
                            <h3 class="text-base sm:text-lg font-black text-slate-900 flex items-center space-x-2">
                                <span>🏛️</span>
                                <span>実行委員会の組織管理（部門・係・組織図・PDF出力）</span>
                            </h3>
                            <a href="{{ route('organizations.index') }}" class="text-xs font-mono font-bold text-indigo-700 bg-white px-2.5 py-1 rounded-lg border border-indigo-200 hover:underline">/organizations ↗</a>
                        </div>
                        <p class="text-xs text-slate-700 leading-relaxed">
                            まつり実行委員会の部門・係・役職および各部署の担当スタッフを階層管理します。画面上部のタブで「📋 組織一覧（ドラッグ＆ドロップ並び替え対応）」と「🗺️ 組織図ビュー（ツリー構造表示）」を瞬時に切り替えられます。
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="p-3 bg-white rounded-xl border border-indigo-200/80 space-y-1">
                                <div class="font-bold text-indigo-950">📄 組織図PDF出力</div>
                                <p class="text-[11px] text-slate-600">実行委員会全体の組織体制図をA4縦帳票PDF（<a href="{{ route('organizations.print') }}" class="text-indigo-600 underline font-mono">/organizations/print</a>）としてダウンロードできます。</p>
                            </div>
                            <div class="p-3 bg-white rounded-xl border border-indigo-200/80 space-y-1">
                                <div class="font-bold text-indigo-950">📋 所属スタッフ一覧PDF出力</div>
                                <p class="text-[11px] text-slate-600">部署・係ごとの所属スタッフと役割連絡先をまとめたA4縦帳票PDF（<a href="{{ route('organizations.staff-list-pdf') }}" class="text-indigo-600 underline font-mono">/organizations/staff-list-pdf</a>）を出力できます。</p>
                            </div>
                        </div>
                    </div>

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

                    <!-- スタッフ台帳の検索・絞り込み & 名簿PDF -->
                    <div class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm space-y-1.5">
                        <h4 class="font-bold text-slate-900 flex items-center space-x-1.5">
                            <span>🔍</span>
                            <span>高速台帳検索 &amp; 状態フィルタ &amp; 名簿一覧PDF</span>
                        </h4>
                        <p class="text-slate-600 text-xs leading-relaxed">
                            スタッフ台帳（<code class="px-1 py-0.2 bg-slate-100 rounded text-indigo-600 font-mono">/staff</code>）では、氏名・フリガナ・所属団体（商店街、大学、消防団等）でのあいまい検索が可能です。さらに「稼働可能フラグ」や「当日の出勤状況（未到着／到着済）」でワンクリック絞り込みできるほか、A4縦・2段組の<a href="{{ route('staff.pdf') }}" class="text-indigo-600 font-bold hover:underline">スタッフ名簿一覧PDF（/staff/pdf）</a>も出力できます。
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
                                    スタッフ管理画面またはヘッダーの「システム・ガイド ▾」内トグルスイッチにより、ボランティア受付を即座に「受付中 / 停止中」に切り替えられます。
                                </p>
                            </div>
                            <div class="p-3.5 bg-white rounded-xl border border-emerald-200 space-y-1.5">
                                <h5 class="font-bold text-emerald-950 flex items-center space-x-1.5">
                                    <span>⏰</span>
                                    <span>募集締切日時の指定</span>
                                </h5>
                                <p class="text-slate-600 text-[11px] leading-relaxed">
                                    開始日時・締切日時（例: 前日18:00）をセットしておくと、期間前は「受付前(予約)」、期限到来後は自動的に受付終了画面へ切り替わります。
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

                    <!-- 4大管理機能 + 当日飛び入りフローグリッド -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <!-- 機能A: CSV一括インポート・エクスポート -->
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="font-bold text-slate-900 flex items-center space-x-1.5 text-sm">
                                <span>📁</span>
                                <span>前年度Excel名簿のCSV一括登録</span>
                            </div>
                            <p class="text-slate-600 leading-relaxed text-[11px]">
                                前年度の実行委員会で作成されたExcel名簿をCSV保存し、「スタッフ管理 &gt; CSV一括登録」からアップロードするだけで、数十名規模のスタッフが一瞬で登録されます。
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

                        <!-- 機能E: 当日紹介・飛び入りスタッフ即時受付 & 個別名札手渡しフロー -->
                        <div class="col-span-1 md:col-span-2 p-4 bg-emerald-50/90 border-2 border-emerald-300 rounded-xl space-y-3">
                            <div class="font-bold text-emerald-950 flex items-center justify-between text-sm">
                                <span class="flex items-center space-x-1.5">
                                    <span>⚡</span>
                                    <span>まつり当日の紹介・飛び入り参加者受付 &amp; 即時配置フロー（3ステップ）</span>
                                </span>
                                <span class="text-[10px] px-2 py-0.5 rounded font-mono font-bold bg-emerald-600 text-white">当日現場</span>
                            </div>
                            <p class="text-emerald-900 leading-relaxed text-[11px]">
                                まつり当日に既存スタッフや班長から友人・知人の応援参加者が連れてこられた場合、本部で直ちに受付・配置・名札手渡しが完了するシームレスな導線を用意しています。
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1">
                                <div class="bg-white p-3 rounded-lg border border-emerald-200 space-y-1">
                                    <div class="text-xs font-bold text-emerald-900 flex items-center space-x-1">
                                        <span class="w-4 h-4 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-mono">1</span>
                                        <span>当日紹介される</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        既存スタッフ・班長からボランティア希望者が本部に連れてこられます。
                                    </p>
                                </div>
                                <div class="bg-white p-3 rounded-lg border border-emerald-200 space-y-1">
                                    <div class="text-xs font-bold text-emerald-900 flex items-center space-x-1">
                                        <span class="w-4 h-4 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-mono">2</span>
                                        <span>登録 &amp; 状況に応じた配置</span>
                                    </div>
                                    <div class="text-[10px] text-slate-600 space-y-1">
                                        <div><strong>a. 紹介者と同班</strong>: 「固定スタッフ」として登録し、紹介者の班へ配属（知人と一緒で安心・即戦力）。</div>
                                        <div><strong>b. 不足タスク充当</strong>: 「充足スタッフ」として登録し、タイムラインで不足枠・要請枠へ割り当て。</div>
                                    </div>
                                </div>
                                <div class="bg-white p-3 rounded-lg border border-emerald-200 space-y-1">
                                    <div class="text-xs font-bold text-emerald-900 flex items-center space-x-1">
                                        <span class="w-4 h-4 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-mono">3</span>
                                        <span>名札印刷 &amp; 手渡し</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed">
                                        登録完了画面や一覧から「🖨️ 名札」を押して1枚出力。ホルダーに入れて手渡し、スマホでQRを読めば現場マイシフトが即時稼働します。
                                    </p>
                                </div>
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
                <!-- 第7章: 資材・機材物流管理 & 拠点・会場配置・出展者・白地図 -->
                <!-- ========================================== -->
                <section id="sec-logistics" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs space-y-6">
                    <div>
                        <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                            <span>Chapter 07</span>
                            <span>•</span>
                            <span>Logistics, Locations, Vendors &amp; Site Maps</span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第7章 資材・機材物流管理 &amp; 拠点・会場管理（出展者・配置計画・A3白地図エディタ）</h2>
                        <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                            パイプ椅子・テント・バリケード等の機材台帳、搬出入パターンマスター、6大フェーズ搬出入計画とタイムライン、神輿番所・駐車場・交通規制時間管理に加え、出展・出店者マスター、屋台・設備の配置計画、およびA3 1/400 白地図配置エディタまでを一元管理します。
                        </p>
                    </div>

                    <!-- 機材物流の4大管理フロー + パターン & タイムライン -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <!-- 1. 機材台帳と所有区分 -->
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5 text-sm">
                                    <span>📦</span>
                                    <span>機材台帳と所有区分管理（CSV対応）</span>
                                </h4>
                                <a href="{{ route('equipments.index') }}" class="font-mono text-indigo-600 bg-white px-1.5 py-0.5 rounded border border-slate-200 text-[10px] hover:underline">/equipments ↗</a>
                            </div>
                            <p class="text-slate-600 leading-relaxed text-[11px]">
                                実行委員会管理（自前保有）、イベント会社提携レンタル、私物（個人持ち込み）の3区分で資材を登録。管理番号、保管場所、総数量を管理し、CSV一括インポート・エクスポートや並び順変更も可能です。
                            </p>
                            <div class="p-2.5 bg-white rounded-lg border border-slate-200 space-y-1">
                                <span class="font-bold text-slate-900 text-[11px]">💡 定番セットテンプレート適用 &amp; 搬出入パターン:</span>
                                <p class="text-slate-500 text-[10px] leading-relaxed">
                                    <a href="{{ route('equipment-patterns.index') }}" class="text-indigo-600 underline font-bold">搬出入パターン（/equipment-patterns）</a>で「倉庫出し→会場設置→夜間保管→回収」等の手順を定義しておき、各資材へワンクリック適用できます。
                                </p>
                            </div>
                        </div>

                        <!-- 2. 搬出入計画・夜間保管 & タイムライン -->
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5 text-sm">
                                    <span>🚚</span>
                                    <span>日別・拠点別の搬出入 &amp; 搬出入タイムライン</span>
                                </h4>
                                <a href="{{ route('logistics.index') }}" class="font-mono text-indigo-600 bg-white px-1.5 py-0.5 rounded border border-slate-200 text-[10px] hover:underline">/logistics ↗</a>
                            </div>
                            <p class="text-slate-600 leading-relaxed text-[11px]">
                                「前日・1日目朝/夜・2日目朝/夜・翌日回収・事前準備」の時系列フェーズごとに「いつ、誰が（委員会スタッフ／イベント会社）、どの拠点からどの拠点へ運ぶか（立会要否含む）」を計画し、<a href="{{ route('logistics-timeline.index') }}" class="text-indigo-600 font-bold underline">搬出入タイムライン（/logistics-timeline）</a>でガントチャート確認します。
                            </p>
                            <div class="p-2.5 bg-white rounded-lg border border-slate-200 space-y-1">
                                <span class="font-bold text-slate-900 text-[11px]">進捗ステータス管理:</span>
                                <div class="flex flex-wrap gap-1 text-[9px] font-bold">
                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700">計画中</span>
                                    <span>→</span>
                                    <span class="px-1.5 py-0.5 rounded bg-sky-100 text-sky-700">進行中</span>
                                    <span>→</span>
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">完了</span>
                                    <span class="text-slate-400">（必要に応じて 中止 も記録）</span>
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
                                <span class="font-mono text-indigo-600 bg-white px-1.5 py-0.5 rounded border border-indigo-200 text-[10px]">/timetables/{id}/logistics/print</span>
                            </div>
                            <p class="text-indigo-900 leading-relaxed text-[11px]">
                                運搬トラックの運転手や各神輿番所の資材受け取り担当者向けに、表示フィルターで選択した資材を反映した指示書兼チェックリストをA4縦向きでPDFダウンロードできます。
                            </p>
                            <p class="text-[10px] text-indigo-700 leading-relaxed">
                                現場でチェックボックス（☑）をチェックしながら確実に受け渡し・回収作業を行えます。
                            </p>
                        </div>

                        <!-- 4. 拠点・会場管理 & 交通規制 & 拠点タイプ -->
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5 text-sm">
                                    <span>📍</span>
                                    <span>拠点管理 &amp; 利用時間帯・交通規制スケジュール</span>
                                </h4>
                                <span class="font-mono text-indigo-600 bg-white px-1.5 py-0.5 rounded border border-slate-200 text-[10px]">/timetables/{id}/locations</span>
                            </div>
                            <p class="text-slate-600 leading-relaxed text-[11px]">
                                本部テント、神輿番所（各町会詰所）、臨時駐車場、給水ポイント、救護所を住所や連絡先とともに登録します。拠点種別・アイコンは<a href="{{ route('location-types.index') }}" class="text-indigo-600 font-bold underline">拠点タイプ管理（/location-types）</a>で定義します。
                            </p>
                            <div class="p-2.5 bg-white rounded-lg border border-slate-200 space-y-1">
                                <span class="font-bold text-slate-900 text-[11px]">利用・交通規制・駐車場稼働連携:</span>
                                <p class="text-slate-500 text-[10px] leading-relaxed">
                                    各拠点の利用可能時間帯や車両通行止め・交通規制時間を紐づけることで、運行日程・ダッシュボードから稼働状態や搬出入時間との整合を監視できます。
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 新設ブロック: 出展・出店者マスター & 配置計画 & A3 1/400 白地図エディタ -->
                    <div class="p-5 bg-gradient-to-br from-emerald-50/70 via-white to-indigo-50/60 border-2 border-emerald-200 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-200 pb-3">
                            <div>
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-emerald-600 text-white uppercase tracking-wider">Site Layout &amp; CAD Map</span>
                                <h3 class="text-base sm:text-lg font-black text-slate-900 mt-1 flex items-center space-x-2">
                                    <span>🗺️</span>
                                    <span>出展・出店者マスター &amp; 現場配置計画・A3 1/400 白地図エディタ</span>
                                </h3>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs font-mono">
                                <a href="{{ route('vendors.index') }}" class="px-2 py-0.5 bg-white text-indigo-700 border border-indigo-200 rounded font-bold hover:underline">/vendors ↗</a>
                                <span class="px-2 py-0.5 bg-white text-slate-600 border border-slate-200 rounded">/site-maps/editor</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                            <div class="p-3.5 bg-white rounded-xl border border-emerald-200 space-y-1.5">
                                <h4 class="font-bold text-emerald-950 flex items-center space-x-1.5">
                                    <span>🏪</span>
                                    <span>1. 出展・出店者マスター</span>
                                </h4>
                                <p class="text-[11px] text-slate-600 leading-relaxed">
                                    ござ市出店者、キッチンカー、飲食屋台、企業協賛ブース、特設企画の出展者台帳を管理します。連絡先・販売品目・必要電力等を登録し、<strong>CSV出力・CSV一括取込</strong>にも対応しています。
                                </p>
                            </div>

                            <div class="p-3.5 bg-white rounded-xl border border-emerald-200 space-y-1.5">
                                <h4 class="font-bold text-emerald-950 flex items-center space-x-1.5">
                                    <span>📋</span>
                                    <span>2. 配置計画 &amp; 配置一覧</span>
                                </h4>
                                <p class="text-[11px] text-slate-600 leading-relaxed">
                                    運行日ごとに「出展ブース」と「現場設備（給水設備・発電機・本部・救護等）」をエリア別（例: メイン通りA列（東側歩道）、北川駐車場内、キッズ村）に整理・登録します。区分別・ブース形態別の件数サマリーも自動集計されます。
                                </p>
                            </div>

                            <div class="p-3.5 bg-white rounded-xl border border-indigo-200 space-y-1.5">
                                <h4 class="font-bold text-indigo-950 flex items-center space-x-1.5">
                                    <span>🎨</span>
                                    <span>3. 白地図配置エディタ (A3 1/400)</span>
                                </h4>
                                <p class="text-[11px] text-slate-600 leading-relaxed">
                                    1/400 CADベクター白地図上に、図形パレットまたは台帳アイテムをドラッグ＆ドロップで精密配置します。
                                </p>
                            </div>
                        </div>

                        <!-- 白地図エディタの操作チートシート -->
                        <div class="p-4 bg-white rounded-xl border border-slate-200 space-y-2 text-xs">
                            <div class="font-bold text-slate-900 flex items-center space-x-1.5">
                                <span>⌨️</span>
                                <span>白地図配置エディタの便利ツール＆ショートカット</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 text-[11px] text-slate-600">
                                <div><strong>↖ 選択 / ✋ 手のひら切替:</strong> <code class="font-mono bg-slate-100 px-1 rounded">V</code> / <code class="font-mono bg-slate-100 px-1 rounded">H</code> キー（または <code class="font-mono bg-slate-100 px-1 rounded">Space</code> 押下）で地図移動と配置編集を素早く切替。</div>
                                <div><strong>↩ 戻す / やり直し:</strong> <code class="font-mono bg-slate-100 px-1 rounded">Ctrl+Z</code> / <code class="font-mono bg-slate-100 px-1 rounded">Ctrl+Y</code>、連番複製 <code class="font-mono bg-slate-100 px-1 rounded">Ctrl+D</code>、45°回転 <code class="font-mono bg-slate-100 px-1 rounded">R</code>。</div>
                                <div><strong>🧲 自動吸着 &amp; 一括整列:</strong> 歩道レールや隣接ブースへ自動吸着。複数選択で左揃・上揃・縦密着・横密着が可能。</div>
                                <div><strong>💧 20m衛生圏チェック:</strong> 給水設備を中心に半径20mの衛生確認円を表示し、保健所基準の配置チェックが可能。</div>
                                <div><strong>➕ PNGアイコン生成:</strong> 既存のCAD図形だけでなく、カスタムPNG画像をアップロードして独自アイコンを作成可能。</div>
                                <div><strong>📥 PDFダウンロード (A3 1/400):</strong> 編集内容をベクター解像度のままA3図面PDFとして即座にダウンロード。</div>
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
                        現場ボランティアや👑班長がスマホで担当タスクを確認し、自律的に移動・報告・要請を行う「マイシフト（マイタイムテーブル）」機能と、本部に現場の動態（出勤・進捗・トラブル）がリアルタイムに集約される仕組みの解説です。
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
                                    <span>朝の受付で名札QRをスキャン（運行日切替＆iCal保存）</span>
                                </h4>
                                <p class="text-slate-600 text-xs leading-relaxed">
                                    名札ケース内のQRコードをスマホの標準カメラで読み取るだけで、自身専用のマイシフトが開きます。複数日程に参加する場合は上部スイッチャーで運行日（「すべて」含む）を切り替えでき、「カレンダー保存 (.ics)」でスマホ標準カレンダーにも登録可能です。
                                </p>
                            </div>

                            <div class="p-3.5 bg-emerald-50/80 border border-emerald-200 rounded-xl space-y-1">
                                <h4 class="font-bold text-emerald-950 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0">2</span>
                                    <span>現場到着チェックイン（第1タスク自動連動）</span>
                                </h4>
                                <p class="text-emerald-900 text-xs leading-relaxed">
                                    運行日当日までは予定・担当内容の事前確認専用の「閲覧専用モード」となります。運行日当日以降に選択中の運行計画について画面上部の「📍 現場到着（受付）」をタップすると、その運行計画の到着日時を打刻。さらに<strong>対象運行計画における第1タスクの出欠ステータスが自動的に「現場着」に更新</strong>され、本部にリアルタイムで伝達されます。
                                </p>
                            </div>

                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5">
                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0">3</span>
                                    <span>「Now &amp; Next」による現場自律移動</span>
                                </h4>
                                <p class="text-slate-600 text-xs leading-relaxed">
                                    最上部に「NOW 進行中」と「NEXT 次の予定」が自動強調され、担当班・班長・場所・詳細手順・共同作業メンバーを確認できます。未完了のみ表示や担当班フィルタによる絞り込みもワンタップです。
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
                                    👑班長は「班長コンソール」タブから班全体のタスク進捗更新を行えるほか、「人手が足りない」「機材が破損・不足した」などの事態が生じた際、本部へ人員応援や必要機材を申請できます。送信と同時に本部ヘッダーの🔔通知ベルへリアルタイム通知されます。
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
                                <li><strong>ホーム画面への追加:</strong> スマホブラウザの「共有 &gt; ホーム画面に追加」を行うと、アプリ感覚でワンタップ起動できます。</li>
                                <li><strong>名札紛失時:</strong> 名札を紛失した場合は、本部受付で古いQRを即時無効化し、2分で新しい名札を再印刷できます。</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 第9章: ユーザー管理・権限設定 & 帳票・データ出力総覧 -->
                <!-- ========================================== -->
                <section id="sec-users" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs space-y-6">
                    <div>
                        <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                            <span>Chapter 09</span>
                            <span>•</span>
                            <span>Administration, Security &amp; Reports</span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">第9章 ユーザー管理・権限設定（管理者向け） &amp; 帳票・データ出力一覧</h2>
                        <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                            本部システムユーザーの追加、ロール権限の設定、アカウントの有効/無効化の手順（管理者専用）と、システムから出力できるすべてのPDF帳票・CSV・iCalデータの一覧です。
                        </p>
                    </div>

                    <!-- 実際の画面画像: ユーザー管理 -->
                    <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-900">
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

                    <div class="space-y-4 text-xs sm:text-sm text-slate-700">
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <h4 class="font-bold text-slate-900 text-xs">ユーザーの追加とロール指定</h4>
                            <ol class="list-decimal list-inside space-y-1.5 text-slate-600 text-xs leading-relaxed">
                                <li>ヘッダーの「⚙️ システム・ガイド」→「ユーザー管理」をクリックします。</li>
                                <li>「新規ユーザー登録」を押し、氏名、メールアドレス、初期パスワードを入力します。</li>
                                <li>ロールとして「システム管理者 (`admin`)」または「本部オペレーター (`operator`)」を選択して保存します。</li>
                            </ol>
                        </div>
                    </div>

                    <!-- 帳票・データ出力総覧テーブル (区分A・区分B・CSV/iCal) -->
                    <div id="sec-reports" class="pt-4 border-t border-slate-200 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base sm:text-lg font-bold text-slate-900 flex items-center space-x-2">
                                <span>🖨️</span>
                                <span>帳票・PDF・CSV・iCal 出力総覧（区分A / 区分B）</span>
                            </h3>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold">全15出力</span>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-xs">
                            <!-- 区分A: 全体・マスター帳票 -->
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <div class="bg-slate-800 text-white px-3.5 py-2 font-bold flex items-center justify-between">
                                    <span>🌐 区分A: 全体・マスター帳票 / CSV / iCal</span>
                                    <span class="text-[10px] font-mono text-indigo-300">年間・共通</span>
                                </div>
                                <div class="divide-y divide-slate-100 bg-white">
                                    <div class="p-2.5 flex items-center justify-between gap-2">
                                        <div>
                                            <a href="{{ route('organizations.print') }}" class="font-bold text-indigo-600 hover:underline">🏛️ 組織図 (PDF)</a>
                                            <p class="text-[10px] text-slate-500">実行委員会組織体制（A4縦）</p>
                                        </div>
                                        <span class="font-mono text-[10px] text-slate-400">/organizations/print</span>
                                    </div>
                                    <div class="p-2.5 flex items-center justify-between gap-2">
                                        <div>
                                            <a href="{{ route('organizations.staff-list-pdf') }}" class="font-bold text-indigo-600 hover:underline">📋 所属スタッフ一覧 (PDF)</a>
                                            <p class="text-[10px] text-slate-500">部署・係別の所属名簿（A4縦）</p>
                                        </div>
                                        <span class="font-mono text-[10px] text-slate-400">/organizations/staff-list-pdf</span>
                                    </div>
                                    <div class="p-2.5 flex items-center justify-between gap-2">
                                        <div>
                                            <a href="{{ route('staff.print-cards') }}" target="_blank" class="font-bold text-indigo-600 hover:underline">🪪 名札カード一括・単一印刷 ↗</a>
                                            <p class="text-[10px] text-slate-500">QRコード付き首掛けカード（A4面付け）</p>
                                        </div>
                                        <span class="font-mono text-[10px] text-slate-400">/staff/print-cards</span>
                                    </div>
                                    <div class="p-2.5 flex items-center justify-between gap-2">
                                        <div>
                                            <a href="{{ route('staff.pdf') }}" class="font-bold text-indigo-600 hover:underline">👥 スタッフ名簿一覧 (PDF)</a>
                                            <p class="text-[10px] text-slate-500">全登録スタッフ名簿（A4縦・2段組）</p>
                                        </div>
                                        <span class="font-mono text-[10px] text-slate-400">/staff/pdf</span>
                                    </div>
                                    <div class="p-2.5 flex items-center justify-between gap-2">
                                        <div>
                                            <a href="{{ route('schedules.pdf') }}" class="font-bold text-indigo-600 hover:underline">📅 年間スケジュール表 (PDF)</a>
                                            <p class="text-[10px] text-slate-500">年間工程・会議一覧（A4縦）</p>
                                        </div>
                                        <span class="font-mono text-[10px] text-slate-400">/schedules/pdf</span>
                                    </div>
                                    <div class="p-2.5 flex items-center justify-between gap-2">
                                        <div>
                                            <span class="font-bold text-slate-800">📥 各種CSVエクスポート / iCal (.ics)</span>
                                            <p class="text-[10px] text-slate-500">スタッフ・機材・出展者のCSV入出力／予定・シフトのiCal出力</p>
                                        </div>
                                        <span class="font-mono text-[10px] text-slate-400">CSV / .ics</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 区分B: 運行日別・現場帳票 -->
                            <div class="border border-indigo-200 rounded-xl overflow-hidden">
                                <div class="bg-indigo-950 text-white px-3.5 py-2 font-bold flex items-center justify-between">
                                    <span>🗓️ 区分B: 運行日別・現場帳票（PDF）</span>
                                    <span class="text-[10px] font-mono text-emerald-300">運行情報詳細から出力</span>
                                </div>
                                <div class="divide-y divide-slate-100 bg-white">
                                    <div class="p-2.5 flex items-center justify-between gap-2">
                                        <div>
                                            <span class="font-bold text-slate-900">⏱️ 運行タイムテーブル (A4横)</span>
                                            <p class="text-[10px] text-slate-500">時間軸×班軸の運行進行マトリクスPDF</p>
                                        </div>
                                        <span class="font-mono text-[10px] text-slate-400">/timetables/{id}/print-matrix</span>
                                    </div>
                                    <div class="p-2.5 flex items-center justify-between gap-2">
                                        <div>
                                            <span class="font-bold text-slate-900">🏷️ 担当班編成表 (A4縦)</span>
                                            <p class="text-[10px] text-slate-500">班長・メンバー・無線機一覧PDF</p>
                                        </div>
                                        <span class="font-mono text-[10px] text-slate-400">/timetables/{id}/lanes/print</span>
                                    </div>
                                    <div class="p-2.5 flex items-center justify-between gap-2">
                                        <div>
                                            <span class="font-bold text-slate-900">🚚 機材搬出入計画表 (A4縦)</span>
                                            <p class="text-[10px] text-slate-500">選択資材の搬出入指示書兼チェックリストPDF</p>
                                        </div>
                                        <span class="font-mono text-[10px] text-slate-400">/timetables/{id}/logistics/print</span>
                                    </div>
                                    <div class="p-2.5 flex items-center justify-between gap-2">
                                        <div>
                                            <span class="font-bold text-slate-900">📋 担当班別タスク一覧 / 人員配置表 (A4横)</span>
                                            <p class="text-[10px] text-slate-500">班ごとのタスクと配置スタッフ一覧PDF</p>
                                        </div>
                                        <span class="font-mono text-[10px] text-slate-400">/timetables/{id}/print-assignments</span>
                                    </div>
                                    <div class="p-2.5 flex items-center justify-between gap-2">
                                        <div>
                                            <span class="font-bold text-slate-900">🎨 白地図配置図面 (A3 1/400)</span>
                                            <p class="text-[10px] text-slate-500">ブース・設備・衛生圏を図示した高精細CAD図面PDF</p>
                                        </div>
                                        <span class="font-mono text-[10px] text-slate-400">/timetables/{id}/site-maps/pdf</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ========================================== -->
                <!-- 付録: FAQ & トラブルシューティング -->
                <!-- ========================================== -->
                <section id="sec-faq" class="manual-section bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center space-x-2 text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1">
                                <span>Appendix</span>
                                <span>•</span>
                                <span>Troubleshooting &amp; FAQ</span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">付録 よくある質問（FAQ）&amp; トラブル対応</h2>
                            <p class="text-xs sm:text-sm text-slate-600 mt-1.5 leading-relaxed">
                                現場運行中や準備期間中によく発生する疑問点とその対処法です。
                            </p>
                        </div>
                        <button type="button"
                            data-action="toggle-accordions"
                            data-target="#manual-faq-accordions"
                            data-state="closed"
                            class="inline-flex items-center space-x-1 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer shrink-0 print:hidden">
                            <span data-icon>🔽</span>
                            <span data-label>すべて開く</span>
                        </button>
                    </div>

                    <div id="manual-faq-accordions" class="mt-6 space-y-3">
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
                                該当タスクの人員アサインモーダル（または人員事前配置画面）を開き、「スタッフ交代（スワップ）」をクリックして代替スタッフを選択するか、一度解除してフリーの待機スタッフを再割り当てしてください。
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
                                現場ボランティア・班長はスマホのマイシフト画面から「応援要請（レーンリクエスト）」を送信してください。本部側ではヘッダーの通知ベル（🔔）で受信し、「搬出入計画（`/logistics`）および資材管理（`/equipments`）」の予備在庫状況を確認して代替機材を運搬スタッフへ指示します。
                            </p>
                        </details>

                        <details class="group p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm transition cursor-pointer">
                            <summary class="font-bold text-slate-900 flex items-center justify-between list-none">
                                <span class="flex items-center space-x-2">
                                    <span class="text-indigo-600 font-bold">Q.</span>
                                    <span>白地図エディタでブースを置いても、別の運行日の配置に影響しませんか？</span>
                                </span>
                                <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                            </summary>
                            <p class="mt-3 text-slate-600 leading-relaxed text-xs border-t border-slate-200 pt-3">
                                はい、配置計画・白地図配置データは運行日（タイムテーブル）ごとに独立して保存されます。ただし「出展・出店者マスター（<code class="font-mono text-indigo-600">/vendors</code>）」や「カスタムPNGアイコン」は全運行日で共通利用されるマスターデータです。
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
    <div id="imageModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 hidden flex flex-col items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="modalCaption">
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

    <!-- キーワードリアルタイム検索・シナリオフィルタ・目次追従・画像モーダル制御（CSP準拠） -->
    <script src="{{ asset('js/manual-helper.js') }}" defer></script>
</x-layouts.app>
