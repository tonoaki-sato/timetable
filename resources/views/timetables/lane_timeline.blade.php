<x-layouts.app :title="$timetable->name . ' - 班別タイムライン（担当班別ガントチャート）'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$timetable->name . ' 班別タイムライン'"
        icon="📊"
        description="担当班ごとに定常タスク（継続業務）とスポットタスク（単発作業）を2層ガントチャートで計画・調整します。">
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
            <span class="text-slate-700 font-bold truncate">📊 班別タイムライン</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 whitespace-nowrap">
                Step 3 班別ガント
            </span>
            <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200 whitespace-nowrap">
                📅 {{ $timetable->target_date?->format('Y年m月d日') }}
            </span>
            <span class="px-2 py-0.5 rounded text-xs font-mono text-slate-600 bg-slate-100 border border-slate-200 whitespace-nowrap">
                ⏱️ {{ substr($timetable->start_time, 0, 5) }}〜{{ substr($timetable->end_time, 0, 5) }}
            </span>
        </x-slot:badges>

        <x-slot:actions>
            @can('create', \App\Models\TimetableTask::class)
            <x-ui.button variant="header-action" id="btn-lane-timeline-common-create" data-action="open-common-task-create">
                <span>🌐</span>
                <span>共通タスク一括登録</span>
            </x-ui.button>
            @endcan

            <x-ui.button variant="header-action" :href="route('timetables.tasks.index', $timetable)">
                <span>📋</span>
                <span>タスク計画（一覧）</span>
            </x-ui.button>

            <x-ui.button variant="header-action" :href="route('timetables.staff-timeline', $timetable)">
                <span>👥</span>
                <span>スタッフタイムライン</span>
            </x-ui.button>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="lane-timeline" />

    <x-ui.validation-errors title="入力内容または時間帯にエラーがあります。" />

    <!-- サマリー & 操作ツールバー -->
    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-2xs space-y-2.5 print:hidden">
        <!-- 上段: KPI サマリー & ズーム・現在時刻操作 -->
        <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-100">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 font-bold border border-slate-200 font-mono">
                    🏷️ {{ $totalLanesCount }} 班
                </span>
                <span class="px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-800 font-bold border border-indigo-200 font-mono">
                    📋 全 {{ $totalTasksCount }} 件
                </span>
                <span class="px-2 py-0.5 rounded-lg bg-teal-50 text-teal-800 font-bold border border-teal-300 font-mono">
                    🔄 定常 {{ $totalRoutineCount }}
                </span>
                <span class="px-2 py-0.5 rounded-lg bg-amber-50 text-amber-800 font-bold border border-amber-300 font-mono">
                    ⚡ スポット {{ $totalSpotCount }}
                </span>
                @if($totalShortageTaskCount > 0)
                <span class="px-2 py-0.5 rounded-lg bg-red-50 text-red-700 font-bold border border-red-200 font-mono">
                    ⚠️ 人員不足 {{ $totalShortageTaskCount }} 件
                </span>
                @endif
                <span class="hidden lg:inline-block text-[11px] text-slate-500 ml-1">
                    💡 背景をドラッグしてタスク新規作成 ／ バーをドラッグして時間調整
                </span>
            </div>

            <!-- ズーム切替 & 現在時刻ジャンプ -->
            <div class="flex items-center gap-1.5 shrink-0">
                <span class="text-[11px] font-bold text-slate-500">時間軸:</span>
                <div class="inline-flex rounded-lg border border-slate-300 bg-slate-50 p-0.5 text-[11px]">
                    <button type="button" data-zoom-mode="15m" class="lane-zoom-btn px-2 py-0.5 rounded-md font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer">15分</button>
                    <button type="button" data-zoom-mode="30m" class="lane-zoom-btn px-2 py-0.5 rounded-md font-bold bg-indigo-600 text-white shadow-2xs transition cursor-pointer">30分</button>
                    <button type="button" data-zoom-mode="60m" class="lane-zoom-btn px-2 py-0.5 rounded-md font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer">60分</button>
                    <button type="button" data-zoom-mode="fit" class="lane-zoom-btn px-2 py-0.5 rounded-md font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer">フィット</button>
                </div>
                <button type="button" id="btn-lane-scroll-now"
                    class="px-2 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-red-50 text-red-600 border border-red-200 transition cursor-pointer">
                    ⏱️ 現在時刻
                </button>
            </div>
        </div>

        <!-- 下段: 検索・種別フィルター・充足状態フィルター・班チップ -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-2.5">
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" id="lane-timeline-search" placeholder="🔍 タスク名・班名・場所で検索..."
                    class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 w-52">

                <!-- 種別フィルター -->
                <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5 text-xs">
                    <button type="button" data-filter-task-type="all"
                        class="lane-type-btn px-2.5 py-1 rounded-md font-bold bg-indigo-600 text-white transition cursor-pointer">
                        すべて
                    </button>
                    <button type="button" data-filter-task-type="routine"
                        class="lane-type-btn px-2.5 py-1 rounded-md font-bold text-slate-600 hover:text-teal-800 transition cursor-pointer">
                        🔄 定常のみ
                    </button>
                    <button type="button" data-filter-task-type="spot"
                        class="lane-type-btn px-2.5 py-1 rounded-md font-bold text-slate-600 hover:text-amber-800 transition cursor-pointer">
                        ⚡ スポットのみ
                    </button>
                </div>

                <!-- 充足フィルター -->
                <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5 text-xs">
                    <button type="button" data-filter-shortage="all"
                        class="lane-shortage-btn px-2.5 py-1 rounded-md font-bold bg-slate-800 text-white transition cursor-pointer">
                        全状態
                    </button>
                    <button type="button" data-filter-shortage="shortage"
                        class="lane-shortage-btn px-2.5 py-1 rounded-md font-bold text-slate-600 hover:text-amber-800 transition cursor-pointer">
                        ⚠️ 不足あり
                    </button>
                    <button type="button" data-filter-shortage="satisfied"
                        class="lane-shortage-btn px-2.5 py-1 rounded-md font-bold text-slate-600 hover:text-emerald-800 transition cursor-pointer">
                        ✔ 充足済
                    </button>
                </div>
            </div>

            <!-- 担当班チップ（全選択/全解除） -->
            <div class="flex flex-wrap items-center gap-1.5">
                <div class="inline-flex rounded border border-slate-300 bg-white p-0.5 shrink-0">
                    <button type="button" data-action="lane-select-all" class="px-1.5 py-0.5 text-[10px] font-bold text-slate-600 hover:text-indigo-600 cursor-pointer">全班</button>
                    <button type="button" data-action="lane-deselect-all" class="px-1.5 py-0.5 text-[10px] font-bold text-slate-600 hover:text-indigo-600 cursor-pointer">解除</button>
                </div>
                @foreach($timetableLanes as $lane)
                <button type="button"
                    data-lane-chip="{{ $lane->id }}"
                    data-active="1"
                    class="lane-filter-chip inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border transition bg-white text-slate-800 border-slate-300 hover:border-indigo-400 cursor-pointer whitespace-nowrap">
                    <span class="mr-1 text-emerald-600 font-bold chip-check">✔</span>
                    <span>{{ $lane->presenter()->iconLabel() }} {{ $lane->name }}</span>
                </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 2ペイン・ガントチャート本体 -->
    <div id="lane-gantt-root"
        data-timetable-id="{{ $timetable->id }}"
        data-bound-start-min="{{ $bounds['start_min'] }}"
        data-bound-end-min="{{ $bounds['end_min'] }}"
        data-now-min="{{ $nowMin }}"
        data-can-create="{{ auth()->user()?->can('create', \App\Models\TimetableTask::class) ? '1' : '0' }}"
        data-can-update="{{ auth()->user()?->can('update', \App\Models\TimetableTask::class) ? '1' : '0' }}"
        class="bg-white border border-slate-300 rounded-xl shadow-xs overflow-hidden">

        @if(empty($laneRows))
        <div class="p-10 text-center text-slate-400 text-xs space-y-2">
            <span class="text-3xl block">🏷️</span>
            <p class="font-bold text-slate-600">この運行日にはまだ担当班が編成されていません。</p>
            <a href="{{ route('timetables.lanes.index', $timetable) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 transition">
                担当班編成を開く →
            </a>
        </div>
        @else
        <div id="lane-gantt-scroll" class="overflow-x-auto relative">
            <div class="inline-block min-w-full align-top">
                <!-- ヘッダー行 (Sticky Top) -->
                <div class="flex sticky top-0 z-30 bg-slate-900 text-white border-b border-slate-700 select-none">
                    <!-- 左固定カラム見出し -->
                    <div class="sticky left-0 z-40 w-[250px] min-w-[250px] shrink-0 bg-slate-900 border-r border-slate-700 px-3 py-2 flex flex-col justify-center">
                        <div class="text-xs font-bold flex items-center justify-between">
                            <span>🏷️ 担当班 ＆ トラック層</span>
                            <span class="text-[10px] font-normal text-slate-400">上:定常 / 下:スポット</span>
                        </div>
                    </div>

                    <!-- 右タイムラインルーラー & 規制帯 -->
                    <div class="flex-1 min-w-0">
                        <div id="lane-gantt-bands" class="hidden bg-white text-slate-800 border-b border-slate-300"></div>
                        <div id="lane-gantt-ruler" class="relative h-8 bg-slate-800"></div>
                    </div>
                </div>

                <!-- 担当班ごとの行 -->
                <div id="lane-gantt-rows" class="divide-y divide-slate-300">
                    @foreach($laneRows as $row)
                    @php
                        $lane = $row->timetableLane;
                        $laneColorClass = $lane->presenter()->colorClasses()['badge'];
                    @endphp
                    <div data-gantt-row
                        data-lane-id="{{ $lane->id }}"
                        data-lane-name="{{ mb_strtolower($lane->name) }}"
                        data-routine-count="{{ $row->routineCount }}"
                        data-spot-count="{{ $row->spotCount }}"
                        data-shortage-count="{{ $row->shortageTaskCount }}"
                        class="lane-gantt-row flex bg-white hover:bg-slate-50/40 transition">

                        <!-- 左固定セル (担当班情報) -->
                        <div class="sticky left-0 z-20 w-[250px] min-w-[250px] shrink-0 bg-white border-r border-slate-300 shadow-2xs flex flex-col">
                            <!-- 班ヘッダー小帯 -->
                            <div class="px-2.5 py-1.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-1.5">
                                <div class="min-w-0 flex items-center gap-1.5">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold border truncate {{ $laneColorClass }}" title="{{ $lane->name }}">
                                        <span class="mr-1">{{ $lane->presenter()->iconLabel() }}</span>
                                        <span class="truncate">{{ $lane->name }}</span>
                                    </span>
                                    @if($row->shortageTaskCount > 0)
                                    <span class="px-1 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-300 shrink-0" title="人員不足タスクあり">
                                        不足{{ $row->shortageTaskCount }}
                                    </span>
                                    @endif
                                </div>
                                @can('create', \App\Models\TimetableTask::class)
                                <button type="button"
                                    data-action="open-task-create"
                                    data-lane-id="{{ $lane->id }}"
                                    data-lane-name="{{ $lane->name }}"
                                    data-lane-icon="{{ $lane->presenter()->iconLabel() }}"
                                    data-suggested-start="{{ $row->suggestedStartTime }}"
                                    class="px-1.5 py-0.5 bg-white hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 rounded text-[10px] font-bold transition cursor-pointer shrink-0"
                                    title="{{ $lane->name }}にタスクを追加">
                                    + 追加
                                </button>
                                @endcan
                            </div>

                            <!-- 班長表示 + 上段(定常)・下段(スポット)ラベル -->
                            <div data-left-zone="routine" class="px-2.5 py-1 border-b border-dashed border-slate-200 flex items-center justify-between text-[10px] bg-teal-50/30">
                                <span class="font-bold text-teal-800 flex items-center gap-1">
                                    <span>🔄</span>
                                    <span>定常</span>
                                    <span class="font-mono text-teal-600">({{ $row->routineCount }})</span>
                                </span>
                                <span class="text-slate-500 truncate max-w-[130px]" title="班長: {{ $lane->leadStaff?->name ?: '未設定' }}">
                                    {{ $lane->leadStaff ? '👑 ' . $lane->leadStaff->name : '班長未定' }}
                                </span>
                            </div>

                            <div data-left-zone="spot" class="px-2.5 py-1 flex items-center justify-between text-[10px] bg-amber-50/20">
                                <span class="font-bold text-amber-800 flex items-center gap-1">
                                    <span>⚡</span>
                                    <span>スポット</span>
                                    <span class="font-mono text-amber-700">({{ $row->spotCount }})</span>
                                </span>
                                <span class="text-[9px] text-slate-400">ドラッグ追加可</span>
                            </div>
                        </div>

                        <!-- 右タイムラインキャンバス（左小帯の高さ分だけ上余白を揃える） -->
                        <div data-gantt-canvas class="flex-1 min-w-0 relative flex flex-col">
                            <!-- 班ヘッダー小帯に対応する薄い時間帯帯 -->
                            <div class="h-[29px] bg-slate-50/60 border-b border-slate-200 relative pointer-events-none">
                                <div class="gantt-grid-guides absolute inset-0"></div>
                            </div>

                            <!-- 上段: 🔄 定常タスクトラック -->
                            <div data-track-zone="routine"
                                class="relative border-b border-dashed border-slate-200 bg-teal-50/15 cursor-crosshair">
                                <div class="gantt-grid-guides absolute inset-0"></div>

                                @foreach($row->routineBars as $bar)
                                @include('timetables.partials.gantt-task-bar', ['bar' => $bar, 'mode' => 'lane'])
                                @endforeach
                            </div>

                            <!-- 下段: ⚡ スポットタスクトラック -->
                            <div data-track-zone="spot"
                                class="relative bg-white cursor-crosshair">
                                <div class="gantt-grid-guides absolute inset-0"></div>

                                @foreach($row->spotBars as $bar)
                                @include('timetables.partials.gantt-task-bar', ['bar' => $bar, 'mode' => 'lane'])
                                @endforeach
                            </div>
                        </div>

                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- バークリック時のタスク操作・詳細モーダル -->
    <div id="laneBarActionModal"
        class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
        role="dialog" aria-modal="true" aria-labelledby="lane-bar-modal-title">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-xl border border-slate-200 overflow-hidden text-left">
            <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5 text-[11px] mb-0.5">
                        <span id="laneBarTypeBadge" class="px-1.5 py-0.2 rounded font-bold"></span>
                        <span id="laneBarLaneBadge" class="px-1.5 py-0.2 rounded bg-slate-800 text-indigo-200 border border-slate-700 font-bold"></span>
                        <span id="laneBarTimeLabel" class="font-mono text-indigo-200 font-bold"></span>
                    </div>
                    <h3 id="lane-bar-modal-title" class="text-sm font-bold truncate"></h3>
                </div>
                <button type="button" data-action="close-bar-action-modal" class="text-slate-400 hover:text-white text-xl font-bold leading-none cursor-pointer">&times;</button>
            </div>

            <div class="p-5 space-y-3 text-xs">
                <div class="grid grid-cols-2 gap-2.5 bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div>
                        <span class="text-[10px] text-slate-500 block">👥 人員充足</span>
                        <strong id="laneBarPeopleLabel" class="text-slate-900 font-mono"></strong>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 block">📍 実施場所</span>
                        <strong id="laneBarLocationLabel" class="text-slate-900"></strong>
                    </div>
                </div>

                <div>
                    <span class="text-[11px] font-bold text-slate-600 block mb-1">配置済みスタッフ</span>
                    <div id="laneBarAssignees" class="flex flex-wrap gap-1.5"></div>
                </div>

                <div id="laneBarContentWrap" class="hidden">
                    <span class="text-[11px] font-bold text-slate-600 block mb-1">作業内容</span>
                    <p id="laneBarContent" class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-slate-700 whitespace-pre-line leading-relaxed"></p>
                </div>
            </div>

            <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs">
                <div class="flex items-center gap-1.5">
                    @can('update', \App\Models\TimetableTask::class)
                    <button type="button" id="laneBarQuickAssignBtn"
                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold transition cursor-pointer flex items-center gap-1">
                        <span>👥</span>
                        <span>人員配置</span>
                    </button>
                    <button type="button" id="laneBarEditBtn"
                        class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-lg font-bold transition cursor-pointer flex items-center gap-1">
                        <span>✏️</span>
                        <span>編集</span>
                    </button>
                    @endcan
                    @can('delete', \App\Models\TimetableTask::class)
                    <button type="button" id="laneBarDeleteBtn"
                        class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-lg font-bold transition cursor-pointer">
                        🗑️ 削除
                    </button>
                    @endcan
                </div>
                <button type="button" data-action="close-bar-action-modal"
                    class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg font-bold cursor-pointer">
                    閉じる
                </button>
            </div>
        </div>
    </div>

    <!-- クイックアサイン スライドオーバーモーダル (進行表と同一DOM構造・CSP完全準拠) -->
    <div id="quickAssignModal"
        class="hidden fixed inset-0 z-50 overflow-hidden bg-slate-900/40 backdrop-blur-xs flex justify-end"
        role="dialog" aria-modal="true" aria-labelledby="quickAssignTaskTitle">
        <div class="bg-white w-full max-w-2xl h-full shadow-xl flex flex-col border-l border-slate-200">
            <div class="px-4 py-3 bg-gradient-to-r from-slate-900 to-indigo-950 text-white shrink-0 space-y-1.5">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-1.5 text-xs">
                        <span id="quickAssignTaskTime" class="font-mono font-bold text-indigo-200 bg-slate-800/90 px-2 py-0.5 rounded border border-slate-700"></span>
                        <span id="quickAssignTaskLane" class="px-2 py-0.5 rounded bg-indigo-500/20 border border-indigo-400/30 text-[10px] font-bold text-indigo-200"></span>
                        <span id="quickAssignTaskLocation" class="hidden px-2 py-0.5 rounded bg-slate-800/80 border border-slate-700 text-[10px] text-slate-300"></span>
                        <span id="quickAssignHeaderStatus"></span>
                    </div>
                    <div class="flex items-center space-x-2 shrink-0">
                        <span id="quickAssignHeaderPoolBtn"></span>
                        <button type="button" data-action="close-quick-assign"
                            class="text-slate-400 hover:text-white text-2xl font-bold leading-none cursor-pointer px-1">&times;</button>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <h3 id="quickAssignTaskTitle" class="text-sm sm:text-base font-bold truncate text-white">作業タスク詳細</h3>
                </div>
            </div>
            <div id="quickAssignModalBody" class="flex-1 min-h-0 flex flex-col overflow-hidden"></div>
            <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs shrink-0">
                <a id="quickAssignDetailLink" href="#" target="_blank"
                    class="text-indigo-600 hover:underline font-bold flex items-center space-x-1">
                    <span>タスク詳細ページを開く</span>
                    <span>↗</span>
                </a>
                <button type="button" data-action="reload-matrix"
                    class="px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold transition cursor-pointer">
                    閉じて画面を更新
                </button>
            </div>
        </div>
    </div>

    <!-- 共通タスク操作モーダル群 -->
    <x-timetable-tasks.detail-modal />
    <x-timetable-tasks.create-modal :timetable="$timetable" :statuses="$statuses" />
    @can('create', \App\Models\TimetableTask::class)
    <x-timetable-tasks.common-create-modal :timetable="$timetable" :timetable-lanes="$timetableLanes" :statuses="$statuses" />
    @endcan
    <x-timetable-tasks.edit-modal :statuses="$statuses" />

    <!-- JSONデータ (CSP完全準拠・hiddenコンテナ) -->
    <div id="lane-timeline-tasks-data" class="hidden">{!! json_encode($allTaskPayloads, JSON_HEX_TAG | JSON_HEX_AMP) !!}</div>
    <div id="lane-timeline-periods-data" class="hidden">{!! json_encode(['road_bands' => $roadBands, 'festival_bands' => $festivalBands], JSON_HEX_TAG | JSON_HEX_AMP) !!}</div>
</div>

@vite(['resources/js/lane-timeline.js'])
</x-layouts.app>
