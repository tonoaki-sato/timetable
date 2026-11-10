<x-layouts.app :title="$timetable->name . ' - 人員配置'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー (共通 x-page-header) -->
    <x-page-header
        :title="$timetable->name . ' 人員配置'"
        icon="👥"
        description="作業タスクを選択し、最適なスタッフを効率的かつ安全にアサイン（人員配置）します。">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>運行情報一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>📅</span>
                <span>{{ $timetable->name }}</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">👥 人員配置</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.staff-timeline', $timetable) }}"
                class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-2xs whitespace-nowrap shrink-0"
                title="スタッフ軸のガントチャートタイムラインを表示">
                <span>📊</span>
                <span>スタッフタイムライン</span>
            </a>

            <a href="{{ route('timetables.print-assignments', $timetable) }}"
                class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-2xs whitespace-nowrap shrink-0"
                title="運行日別人員配置一覧表をPDFでダウンロード">
                <span>📄</span>
                <span>配置表PDF</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー (日程切替・サブナビ) -->
    <x-timetable-context-bar :timetable="$timetable" active="assignments" />

    <!-- 全体サマリー統計バー -->
    <div class="bg-white border border-slate-200 rounded-xl px-4 py-2.5 shadow-2xs flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center space-x-1.5">
                <span class="text-slate-400 font-medium">📋 全タスク:</span>
                <span id="summary-total-tasks" class="font-bold text-slate-900 font-mono">{{ $totalTasks }} 件</span>
            </div>
            <div class="flex items-center space-x-1.5">
                <span class="text-slate-400 font-medium">⚠️ 要対応（不足）:</span>
                <span id="summary-shortage-tasks" class="font-bold {{ $shortageTasks > 0 ? 'text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded' : 'text-slate-600' }} font-mono">
                    {{ $shortageTasks }} 件
                </span>
            </div>
            <div class="flex items-center space-x-1.5">
                <span class="text-slate-400 font-medium">✔ 充足済:</span>
                <span id="summary-satisfied-tasks" class="font-bold text-emerald-700 font-mono">{{ $satisfiedTasks }} 件</span>
            </div>
            <div class="hidden sm:flex items-center space-x-1.5 text-slate-400 border-l border-slate-200 pl-4">
                <span>必要延べ人員:</span>
                <strong class="text-slate-700 font-mono">{{ $totalRequired }}名</strong>
                <span class="mx-0.5">/</span>
                <span>配置済:</span>
                <strong id="summary-total-assigned" class="text-indigo-600 font-mono">{{ $totalAssigned }}名</strong>
            </div>
        </div>

        <div class="flex items-center space-x-2 text-[11px] text-slate-500">
            <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-medium">
                💡 左でタスクを選択 → 右でスタッフをアサイン
            </span>
        </div>
    </div>

    <!-- メインワークスペース: スプリットビュー (左右2ペイン) -->
    <div id="assignment-workspace"
        data-timetable-id="{{ $timetable->id }}"
        data-initial-task-id="{{ $initialTaskId }}"
        class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 items-start">

        <!-- =================================================================== -->
        <!-- 左ペイン: タスク選択エリア (40% / 5カラム) -->
        <!-- =================================================================== -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col h-[750px] overflow-hidden">
            <!-- タスク検索・フィルターヘッダー (固定) -->
            <div class="p-3 bg-slate-50 border-b border-slate-200 space-y-2.5 shrink-0">
                <!-- 充足ステータスタブ -->
                <div class="flex items-center space-x-1 bg-slate-200/70 p-0.5 rounded-lg text-xs">
                    <button type="button" data-task-tab="shortage"
                        class="task-filter-tab flex-1 py-1 px-2 rounded-md font-bold transition text-center cursor-pointer bg-white text-indigo-700 shadow-2xs">
                        ⚠️ 不足あり (<span id="tab-shortage-count">{{ $shortageTasks }}</span>)
                    </button>
                    <button type="button" data-task-tab="all"
                        class="task-filter-tab flex-1 py-1 px-2 rounded-md font-bold transition text-center cursor-pointer text-slate-600 hover:text-slate-900">
                        すべて (<span id="tab-all-count">{{ $totalTasks }}</span>)
                    </button>
                    <button type="button" data-task-tab="satisfied"
                        class="task-filter-tab flex-1 py-1 px-2 rounded-md font-bold transition text-center cursor-pointer text-slate-600 hover:text-slate-900">
                        ✔ 充足済 (<span id="tab-satisfied-count">{{ $satisfiedTasks }}</span>)
                    </button>
                </div>

                <!-- 班別 & キーワード検索フィルター -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <select id="task-lane-filter"
                        class="px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 font-medium focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">🏷️ すべての担当班</option>
                        @foreach($lanes as $lane)
                        <option value="{{ $lane->id }}">{{ $lane->icon }} {{ $lane->name }}</option>
                        @endforeach
                    </select>

                    <div class="relative">
                        <input type="text" id="task-search-input" placeholder="🔍 タスク名・場所で絞込..."
                            class="w-full pl-2.5 pr-6 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <button type="button" id="task-search-clear" class="hidden absolute right-2 top-1.5 text-slate-400 hover:text-slate-600 text-xs">✕</button>
                    </div>
                </div>
            </div>

            <!-- タスクリストコンテナ (独立スクロール) -->
            <div id="task-list-container" class="flex-1 overflow-y-auto p-2.5 space-y-2">
                @forelse($tasks as $task)
                <div data-task-card
                    data-task-id="{{ $task->id }}"
                    data-lane-id="{{ $task->lane_id }}"
                    data-is-satisfied="{{ $task->is_satisfied ? '1' : '0' }}"
                    data-shortage-count="{{ $task->shortage_count }}"
                    data-search-text="{{ $task->assignment_search_text }}"
                    class="task-card group relative p-3 rounded-xl border transition cursor-pointer select-none {{ $task->id === $initialTaskId ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-slate-200 bg-white hover:border-indigo-300 hover:bg-slate-50/70' }}">

                    <!-- 時間帯 & 班名 & 最優先バッジ -->
                    <div class="flex items-center justify-between gap-1.5 mb-1.5">
                        <div class="flex items-center space-x-1.5">
                            <span class="font-mono font-bold text-xs text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                ⏱️ {{ substr($task->start_time, 0, 5) }}〜{{ substr($task->end_time, 0, 5) }}
                            </span>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold border truncate max-w-[120px] {{ $task->lane_badge_class }}" title="{{ $task->lane_name_label }}">
                                {{ $task->lane_icon_label }} {{ $task->lane_name_label }}
                            </span>
                        </div>

                        <div class="flex items-center space-x-1 shrink-0">
                            @if($task->is_critical)
                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                                🚨 最優先
                            </span>
                            @endif

                            <span data-task-status-badge
                                class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold font-mono border {{ $task->is_satisfied ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-300' }}">
                                {{ $task->is_satisfied ? "✔ 充足 ({$task->assigned_count})" : "⚠️ 不足 {$task->shortage_count}名" }}
                            </span>
                        </div>
                    </div>

                    <!-- タスク名 -->
                    <h4 class="font-bold text-sm text-slate-900 group-hover:text-indigo-600 transition truncate mb-1">
                        {{ $task->title }}
                    </h4>

                    <!-- 進捗プログレスミニバー & 充足カウント -->
                    <div class="flex items-center space-x-2 pt-1">
                        <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div data-task-progress-bar data-percent="{{ $task->fulfillment_percent }}" class="{{ $task->progress_color_class }} h-full transition-all duration-300"></div>
                        </div>
                        <span data-task-people-label class="text-[11px] font-mono text-slate-500 shrink-0 font-medium">
                            👥 {{ $task->assigned_count }}/{{ $task->required_people_label }}
                        </span>
                    </div>

                    <!-- 配置スタッフプレビューチップ -->
                    <div data-task-assignments-preview class="flex flex-wrap gap-1 mt-2">
                        @foreach($task->assignments as $assignment)
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] bg-slate-100 text-slate-700 border border-slate-200 truncate max-w-[90px]"
                            title="{{ $assignment->staff?->name }} ({{ $assignment->role_description ?: '担当' }})">
                            {{ $assignment->staff?->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-slate-400 text-xs">
                    タスクが登録されていません。
                </div>
                @endforelse

                <div id="task-filter-empty" class="hidden p-8 text-center text-slate-400 text-xs">
                    条件に一致するタスクが見つかりませんでした。
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- 右ペイン: 人員アサイン・配置管理エリア (60% / 7カラム) -->
        <!-- =================================================================== -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col h-[750px] overflow-hidden">

            <!-- 選択中タスクヘッダーバー (固定) -->
            <div id="detail-header" class="px-4 py-3 bg-gradient-to-r from-slate-900 to-indigo-950 text-white shrink-0 space-y-1.5">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-1.5 text-xs">
                        <span id="detail-task-time" class="font-mono font-bold text-indigo-200 bg-slate-800/90 px-2 py-0.5 rounded border border-slate-700">⏱️ --:-- 〜 --:--</span>
                        <span id="detail-task-location" class="hidden px-2 py-0.5 rounded bg-slate-800/80 border border-slate-700 text-[10px] text-slate-300">📍 場所</span>
                        <span id="detail-task-status"></span>
                    </div>

                    <div class="flex items-center space-x-2 shrink-0">
                        <span id="detail-pool-btn-wrapper"></span>
                        <a id="detail-task-link" href="#" target="_blank"
                            class="text-indigo-300 hover:text-white text-xs font-medium flex items-center space-x-0.5 transition"
                            title="別タブでタスク詳細を開く">
                            <span>タスク台帳</span>
                            <span>↗</span>
                        </a>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <h3 id="detail-task-title" class="text-base sm:text-lg font-bold truncate text-white">タスクを選択してください</h3>
                </div>
            </div>

            <!-- アサイン詳細ボディ (スクロール可能領域) -->
            <div id="detail-body-container" class="flex-1 min-h-0 flex flex-col overflow-hidden">
                <div id="detail-loading" class="py-24 text-center text-slate-400 text-xs">
                    <span class="inline-block animate-spin text-3xl mb-2">⏳</span>
                    <p>タスクデータを読み込み中...</p>
                </div>

                <div id="detail-content" class="hidden flex-1 min-h-0 flex flex-col overflow-hidden">

                    <!-- 1. アラート & 現在配置中のスタッフ領域 (固定) -->
                    <div class="px-4 pt-3 pb-2.5 border-b border-slate-200 space-y-2 shrink-0 bg-white">
                        <div id="assign-alert-area" class="hidden"></div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <h4 class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                                    <span>👥</span>
                                    <span>現在配置中のスタッフ</span>
                                    <span id="current-assignments-count-badge" class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700">0名</span>
                                </h4>
                            </div>
                            <!-- 現在配置スタッフ一覧コンテナ (2列グリッド) -->
                            <div id="current-assignments-list" class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-32 overflow-y-auto pr-0.5">
                            </div>
                        </div>
                    </div>

                    <!-- 2. スタッフ追加候補ヘッダー & フィルターバー (固定) -->
                    <div class="px-4 pt-2.5 pb-2 bg-slate-50/90 border-b border-slate-200 space-y-2 shrink-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="text-xs font-bold text-slate-800 flex items-center space-x-1">
                                <span>➕</span>
                                <span>スタッフを追加配置する</span>
                                <span id="candidates-total-label" class="text-[10px] font-normal text-slate-400">（全 0 名）</span>
                            </h4>

                            <!-- アシスト操作ボタン -->
                            <div class="flex items-center space-x-1.5">
                                <span class="text-[11px] text-slate-600 font-medium">
                                    選択中: <strong id="assign-selected-count" class="text-indigo-600 font-mono text-xs">0</strong> 名
                                </span>
                                <button type="button" id="btn-select-shortage-auto"
                                    class="px-2 py-0.5 bg-amber-100 hover:bg-amber-200 text-amber-900 border border-amber-300 rounded text-[11px] font-bold transition cursor-pointer">
                                    ⚡ 不足分を自動選択
                                </button>
                                <button type="button" id="btn-select-all-filtered"
                                    class="px-2 py-0.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded text-[11px] font-medium transition cursor-pointer">
                                    表示中を全選択
                                </button>
                                <button type="button" id="btn-clear-selection"
                                    class="hidden px-2 py-0.5 bg-white hover:bg-slate-100 text-slate-500 border border-slate-200 rounded text-[11px] font-medium transition cursor-pointer">
                                    解除
                                </button>
                            </div>
                        </div>

                        <!-- 候補スタッフ絞り込みタブ -->
                        <div class="flex items-center space-x-1 bg-white p-0.5 rounded-lg border border-slate-200 text-xs">
                            <button type="button" data-staff-tab="all"
                                class="staff-filter-tab flex-1 py-1 px-2 rounded-md font-bold transition text-center cursor-pointer bg-slate-100 text-slate-900">
                                全員
                            </button>
                            <button type="button" data-staff-tab="recommended"
                                class="staff-filter-tab flex-1 py-1 px-2 rounded-md font-bold transition text-center cursor-pointer text-slate-600 hover:text-slate-900">
                                🌟 推奨(同班・空き)
                            </button>
                            <button type="button" data-staff-tab="available"
                                class="staff-filter-tab flex-1 py-1 px-2 rounded-md font-bold transition text-center cursor-pointer text-slate-600 hover:text-slate-900">
                                🟢 空きのみ
                            </button>
                            <button type="button" data-staff-tab="checked_in"
                                class="staff-filter-tab flex-1 py-1 px-2 rounded-md font-bold transition text-center cursor-pointer text-slate-600 hover:text-slate-900">
                                📍 現場受付済
                            </button>
                        </div>

                        <!-- キーワード & 所属フィルター (2カラム) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <input type="text" id="staff-search-input" placeholder="🔍 氏名・カナ・所属で検索..."
                                class="px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">

                            <select id="staff-affiliation-filter"
                                class="px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="">🏢 すべての所属団体</option>
                                @foreach($affiliations as $aff)
                                <option value="{{ $aff }}">{{ $aff }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 3. スタッフ候補リスト (独立スクロール領域) -->
                    <div id="candidate-staff-list" class="flex-1 overflow-y-auto p-3 space-y-1.5">
                        <!-- JSで動的描画 -->
                    </div>

                    <!-- 4. 下部固定アクションバー (一括アサイン実行) -->
                    <div class="p-3 bg-white border-t border-slate-200 flex items-center justify-between gap-3 shrink-0">
                        <div class="flex items-center space-x-2 text-xs">
                            <span class="text-slate-500">アサイン時の役割:</span>
                            <input type="text" id="assign-role-description" value="一般担当" placeholder="一般担当"
                                class="px-2.5 py-1 bg-slate-50 border border-slate-300 rounded-lg text-xs font-medium w-28 sm:w-36 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>

                        <div class="flex items-center space-x-2">
                            <button type="button" id="btn-submit-bulk-assign" disabled
                                class="px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5 bg-slate-200 text-slate-400 cursor-not-allowed shrink-0">
                                <span>👥</span>
                                <span>選択した <strong id="assign-btn-count" class="font-mono">0</strong> 名をアサインする</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>

</div>

@vite(['resources/js/timetable-assignments.js'])
</x-layouts.app>

