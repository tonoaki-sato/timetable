<x-layouts.app :title="$timetable->name . ' - 人員配置（スタッフ稼働タイムライン）'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$timetable->name . ' 人員配置'"
        icon="👥"
        description="全スタッフの定常業務・スポット作業・実拘束時間を2層ガントチャートで俯瞰し柔軟に配置調整します。">
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

        <x-slot:badges>
            <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 whitespace-nowrap">
                Step 4 人員配置
            </span>
            <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200 whitespace-nowrap">
                📅 {{ $timetable->target_date?->format('Y年m月d日') }}
            </span>
            <span class="px-2 py-0.5 rounded text-xs font-mono text-slate-600 bg-slate-100 border border-slate-200 whitespace-nowrap">
                ⏱️ {{ substr($timetable->start_time, 0, 5) }}〜{{ substr($timetable->end_time, 0, 5) }}
            </span>
        </x-slot:badges>

        <x-slot:actions>
            <x-ui.button variant="header-action" :href="route('timetables.lane-timeline', $timetable)" title="担当班別の班別タイムラインを表示">
                <span>📊</span>
                <span>班別タイムライン</span>
            </x-ui.button>

            <x-ui.button variant="header-action" :href="route('timetables.print-assignments', $timetable)" title="運行日の人員配置表をA4横PDFでダウンロード">
                <span>📄</span>
                <span>A4横 配置表PDF</span>
            </x-ui.button>

            <x-ui.button variant="header-action" :href="route('timetables.assignments', $timetable)">
                <span>🧩</span>
                <span>タスク別配置画面</span>
            </x-ui.button>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="timeline" />

    <!-- フィルター & ツールバー -->
    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-2xs space-y-2.5 print:hidden">
        <!-- 上段: 状態統計ボタン・班長トグル・検索 -->
        <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-100">
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <input type="text" id="timeline-search-input" placeholder="🔍 氏名・カナ・所属・タスクで検索..."
                    class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 w-56">

                <button type="button" data-filter-status="all"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer bg-indigo-600 text-white border-indigo-600">
                    全員 ({{ $totalStaffCount }})
                </button>
                <button type="button" data-filter-status="assigned"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
                    稼働配置中 ({{ $assignedStaffCount }})
                </button>
                <button type="button" data-filter-status="unassigned"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
                    未配置・待機 ({{ $unassignedStaffCount }})
                </button>
                <button type="button" data-filter-status="checked_in"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
                    現場受付済 ({{ $checkedInCount }})
                </button>

                <label class="inline-flex items-center space-x-1.5 px-2.5 py-1 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-xs font-bold text-slate-700 cursor-pointer select-none transition">
                    <input type="checkbox" id="timeline-leader-filter" checked
                        class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                    <span>班長を表示</span>
                    <span class="text-[11px] font-normal text-slate-500">({{ count($leadStaffIds) }})</span>
                </label>
            </div>

            <!-- ズーム切替 & 現在時刻 -->
            <div class="flex items-center gap-1.5 shrink-0">
                <span class="text-[11px] font-bold text-slate-500">時間軸:</span>
                <div class="inline-flex rounded-lg border border-slate-300 bg-slate-50 p-0.5 text-[11px]">
                    <button type="button" data-staff-zoom-mode="15m" class="staff-zoom-btn px-2 py-0.5 rounded-md font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer">15分</button>
                    <button type="button" data-staff-zoom-mode="30m" class="staff-zoom-btn px-2 py-0.5 rounded-md font-bold bg-indigo-600 text-white shadow-2xs transition cursor-pointer">30分</button>
                    <button type="button" data-staff-zoom-mode="60m" class="staff-zoom-btn px-2 py-0.5 rounded-md font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer">60分</button>
                    <button type="button" data-staff-zoom-mode="fit" class="staff-zoom-btn px-2 py-0.5 rounded-md font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer">フィット</button>
                </div>
                <button type="button" id="btn-staff-scroll-now"
                    class="px-2 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-red-50 text-red-600 border border-red-200 transition cursor-pointer">
                    ⏱️ 現在時刻
                </button>
            </div>
        </div>

        <!-- 下段: 種別フィルター・グルーピング・操作ヒント -->
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5">
                    <button type="button" data-staff-type-filter="all"
                        class="staff-type-btn px-2.5 py-1 rounded-md font-bold bg-indigo-600 text-white transition cursor-pointer">
                        すべて
                    </button>
                    <button type="button" data-staff-type-filter="routine"
                        class="staff-type-btn px-2.5 py-1 rounded-md font-bold text-slate-600 hover:text-teal-800 transition cursor-pointer">
                        🔄 定常のみ
                    </button>
                    <button type="button" data-staff-type-filter="spot"
                        class="staff-type-btn px-2.5 py-1 rounded-md font-bold text-slate-600 hover:text-amber-800 transition cursor-pointer">
                        ⚡ スポットのみ
                    </button>
                </div>

                <div class="flex items-center space-x-1.5">
                    <span class="text-[11px] font-bold text-slate-500">グループ:</span>
                    <select id="timeline-group-select"
                        class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-700 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                        <option value="flat">📋 全員フラット</option>
                        <option value="lane">🏷️ 担当班別</option>
                        <option value="affiliation">🏢 所属団体別</option>
                    </select>
                </div>
            </div>

            <div class="text-[11px] text-slate-500">
                💡 定常稼働中の時間帯もクリック・横ドラッグでスポットタスクを配置／新規作成できます
            </div>
        </div>
    </div>

    <!-- 2ペイン・スタッフガントチャート本体 -->
    <div id="staff-gantt-root"
        data-timetable-id="{{ $timetable->id }}"
        data-bound-start-min="{{ $bounds['start_min'] }}"
        data-bound-end-min="{{ $bounds['end_min'] }}"
        data-now-min="{{ $nowMin }}"
        data-can-create="{{ auth()->user()?->can('create', \App\Models\TimetableTask::class) ? '1' : '0' }}"
        data-can-update="{{ auth()->user()?->can('update', \App\Models\TimetableTask::class) ? '1' : '0' }}"
        class="bg-white border border-slate-300 rounded-xl shadow-xs overflow-hidden">

        <div id="staff-gantt-scroll" class="overflow-x-auto relative">
            <div class="inline-block min-w-full align-top">
                <!-- ヘッダー (Sticky Top) -->
                <div class="flex sticky top-0 z-30 bg-slate-900 text-white border-b border-slate-700 select-none">
                    <div class="sticky left-0 z-40 w-[260px] min-w-[260px] shrink-0 bg-slate-900 border-r border-slate-700 px-3 py-2 flex flex-col justify-center">
                        <div class="text-xs font-bold flex items-center justify-between">
                            <span>👷 スタッフ ＆ 稼働時間</span>
                            <span class="text-[10px] font-normal text-slate-400">上:定常 / 下:スポット</span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div id="staff-gantt-bands" class="hidden bg-white text-slate-800 border-b border-slate-300"></div>
                        <div id="staff-gantt-ruler" class="relative h-8 bg-slate-800"></div>
                    </div>
                </div>

                <!-- スタッフ行一覧 -->
                <div id="staff-gantt-rows" class="divide-y divide-slate-300">
                    @foreach($staffRows as $row)
                    @php
                        $staff = $row->staff;
                    @endphp
                    <div data-gantt-row
                        class="staff-row flex bg-white hover:bg-slate-50/40 transition"
                        data-staff-id="{{ $staff->id }}"
                        data-staff-name="{{ $staff->name }}"
                        data-staff-kana="{{ $staff->kana ?? '' }}"
                        data-staff-affiliation="{{ $staff->affiliation ?? '' }}"
                        data-group-lane="{{ $row->primaryGroupLane }}"
                        data-group-affiliation="{{ $row->affiliationLabel }}"
                        data-assigned-count="{{ $row->assignedCount }}"
                        data-checked-in="{{ $row->isCheckedIn ? '1' : '0' }}"
                        data-is-leader="{{ $row->isLeader ? '1' : '0' }}"
                        data-assignments-json='@json($row->assignmentSummary)'>

                        <!-- 左固定セル (スタッフ情報 + 定常/スポット層ラベル) -->
                        <div class="sticky left-0 z-20 w-[260px] min-w-[260px] shrink-0 bg-white border-r border-slate-300 shadow-2xs flex flex-col">
                            <div class="px-2.5 py-1.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-1.5">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    @if($row->isLeader)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300 shrink-0" title="班長（担当班: {{ $row->leadLaneNames }}）">
                                        班長
                                    </span>
                                    @else
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200 shrink-0" title="一般スタッフ">
                                        一般
                                    </span>
                                    @endif

                                    <a href="{{ route('staff.show', $staff) }}"
                                       class="font-bold text-xs text-slate-900 hover:text-indigo-600 transition truncate"
                                       title="{{ $staff->affiliation ?: '所属なし' }}">
                                        {{ $staff->name }}
                                    </a>
                                </div>

                                <div class="flex items-center gap-1 shrink-0">
                                    @if($row->hasSameTypeConflict)
                                    <span class="px-1 py-0.2 rounded text-[9px] font-bold bg-red-100 text-red-700 border border-red-300" title="同種別タスクの時間重複あり">
                                        重複
                                    </span>
                                    @endif
                                    @if($row->isCheckedIn)
                                    <span class="px-1 py-0.2 rounded text-[9px] font-bold bg-sky-100 text-sky-800 border border-sky-300" title="現場到着受付済み">
                                        着
                                    </span>
                                    @endif
                                    <span class="font-mono text-slate-700 text-[11px] font-bold"
                                          title="実拘束時間: {{ $row->actualHoursFormatted }} / 延べタスク時間: {{ $row->grossHoursFormatted }}（定常 {{ $row->routineCount }}件・スポット {{ $row->spotCount }}件）">
                                        {{ $row->actualHoursFormatted }}
                                    </span>
                                </div>
                            </div>

                            <!-- 上段(定常) / 下段(スポット) ラベル -->
                            <div data-left-zone="routine" class="px-2.5 py-1 border-b border-dashed border-slate-200 flex items-center justify-between text-[10px] bg-teal-50/30">
                                <span class="font-bold text-teal-800 flex items-center gap-1">
                                    <span>🔄</span>
                                    <span>定常</span>
                                    <span class="font-mono text-teal-600">({{ $row->routineCount }})</span>
                                </span>
                                <span class="text-[10px] text-slate-400 truncate max-w-[120px]">{{ $staff->affiliation ?: '所属なし' }}</span>
                            </div>

                            <div data-left-zone="spot" class="px-2.5 py-1 flex items-center justify-between text-[10px] bg-amber-50/20">
                                <span class="font-bold text-amber-800 flex items-center gap-1">
                                    <span>⚡</span>
                                    <span>スポット</span>
                                    <span class="font-mono text-amber-700">({{ $row->spotCount }})</span>
                                </span>
                                <button type="button"
                                    data-action="open-timeline-quick-assign"
                                    data-staff-id="{{ $staff->id }}"
                                    data-staff-name="{{ $staff->name }}"
                                    data-is-leader="{{ $row->isLeader ? '1' : '0' }}"
                                    class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline cursor-pointer">
                                    + 配置/作成
                                </button>
                            </div>
                        </div>

                        <!-- 右タイムラインキャンバス -->
                        <div data-gantt-canvas class="flex-1 min-w-0 relative flex flex-col">
                            <!-- 左小帯と高さを合わせるヘッダー帯 -->
                            <div class="h-[29px] bg-slate-50/60 border-b border-slate-200 relative pointer-events-none">
                                <div class="gantt-grid-guides absolute inset-0"></div>
                            </div>

                            <!-- 上段: 🔄 定常トラック -->
                            <div data-track-zone="routine"
                                class="relative border-b border-dashed border-slate-200 bg-teal-50/15 cursor-crosshair">
                                <div class="gantt-grid-guides absolute inset-0"></div>

                                @foreach($row->routineBars as $bar)
                                @include('timetables.partials.gantt-task-bar', ['bar' => $bar, 'mode' => 'staff'])
                                @endforeach
                            </div>

                            <!-- 下段: ⚡ スポットトラック -->
                            <div data-track-zone="spot"
                                class="relative bg-white cursor-crosshair">
                                <div class="gantt-grid-guides absolute inset-0"></div>

                                @foreach($row->spotBars as $bar)
                                @include('timetables.partials.gantt-task-bar', ['bar' => $bar, 'mode' => 'staff'])
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- 空き/指定時間枠からのタスク配置・新規作成 統合モーダル (2タブ・CSP完全準拠) -->
    <div id="timelineQuickAssignModal"
        class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
        role="dialog" aria-modal="true" aria-labelledby="timeline-quick-assign-title">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-xl border border-slate-200 text-left overflow-hidden flex flex-col max-h-[calc(100vh-2.5rem)]">
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between shrink-0">
                <h3 id="timeline-quick-assign-title" class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                    <span>⏱️</span>
                    <span>スタッフ配置 ＆ タスク登録</span>
                </h3>
                <button type="button" data-action="close-timeline-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">×</button>
            </div>

            <!-- 対象スタッフ・選択時間帯ヘッダー -->
            <div class="px-5 pt-3 shrink-0 space-y-2.5">
                <div class="px-3 py-2 bg-slate-50 rounded-xl border border-slate-200 text-xs flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="text-slate-500 shrink-0">対象スタッフ:</span>
                        <span id="timelineStaffBadge" class="shrink-0"></span>
                        <strong id="timelineStaffName" class="text-slate-900 font-bold truncate"></strong>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="text-slate-500">対象時間帯:</span>
                        <span id="timelineSlotTime" class="font-mono font-bold text-indigo-600"></span>
                    </div>
                </div>

                <!-- モード切替タブ -->
                <div class="grid grid-cols-2 gap-1 bg-slate-100 p-1 rounded-xl text-xs">
                    <button type="button" data-assign-tab="existing"
                        class="timeline-modal-tab py-1.5 px-3 rounded-lg font-bold bg-white text-indigo-700 shadow-2xs transition cursor-pointer">
                        📋 既存タスクに配置
                    </button>
                    <button type="button" data-assign-tab="create"
                        class="timeline-modal-tab py-1.5 px-3 rounded-lg font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer">
                        ➕ 新規タスクを作成して配置
                    </button>
                </div>

                <!-- エラー・同種重複承認エリア -->
                <div id="timelineErrorMessage" class="hidden p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-800 space-y-2">
                    <p id="timelineErrorText"></p>
                    <div id="timelineForceConfirmWrap" class="hidden flex items-center justify-end gap-2">
                        <button type="button" id="timelineForceSubmitBtn"
                            class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                            ⚠️ 同種重複を承認して登録する
                        </button>
                    </div>
                </div>
            </div>

            <!-- パネル1: 既存タスクに配置 -->
            <div id="timelinePanelExisting" class="p-5 space-y-3 overflow-y-auto flex-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-700">この時間帯と重なるタスクを選択:</span>
                    <label class="inline-flex items-center space-x-1.5 text-[11px] font-bold text-slate-600 cursor-pointer select-none">
                        <input type="checkbox" id="timelineShowSatisfiedToggle" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>充足済みタスクも表示</span>
                    </label>
                </div>

                <div id="timelineTaskList" class="max-h-64 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100 bg-slate-50/50"></div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" data-action="close-timeline-modal"
                        class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition cursor-pointer">
                        キャンセル
                    </button>
                    <button type="button" id="timelineSubmitBtn" disabled
                        class="px-4 py-2 rounded-xl text-xs font-bold shadow transition flex items-center space-x-1.5 bg-slate-200 text-slate-400 cursor-not-allowed">
                        <span>➕</span>
                        <span>タスクに配置する</span>
                    </button>
                </div>
            </div>

            <!-- パネル2: 新規タスクを作成して配置 -->
            <form id="timelineQuickCreateForm" class="hidden p-5 space-y-3 overflow-y-auto flex-1 text-xs">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">タスク種別 <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center space-x-2 p-2 rounded-xl border border-slate-200 bg-slate-50 has-[:checked]:bg-amber-50 has-[:checked]:border-amber-400 cursor-pointer">
                            <input type="radio" name="task_type" value="spot" id="timelineQuickCreateTypeSpot" checked class="text-amber-600">
                            <span class="font-bold text-slate-800">⚡ スポット（単発作業）</span>
                        </label>
                        <label class="flex items-center space-x-2 p-2 rounded-xl border border-slate-200 bg-slate-50 has-[:checked]:bg-teal-50 has-[:checked]:border-teal-400 cursor-pointer">
                            <input type="radio" name="task_type" value="routine" id="timelineQuickCreateTypeRoutine" class="text-teal-600">
                            <span class="font-bold text-slate-800">🔄 定常（継続業務）</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">担当班 <span class="text-red-500">*</span></label>
                        <select id="timelineQuickCreateLaneId" name="timetable_lane_id" required
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900">
                            @foreach($timetableLanes as $tl)
                            <option value="{{ $tl->id }}">{{ $tl->presenter()->iconLabel() }} {{ $tl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">タスク名 <span class="text-red-500">*</span></label>
                        <input type="text" id="timelineQuickCreateTitle" name="title" required placeholder="例: 交通誘導応援"
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">開始時刻</label>
                        <input type="time" id="timelineQuickCreateStart" name="start_time" required
                            class="w-full px-2 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">終了時刻</label>
                        <input type="time" id="timelineQuickCreateEnd" name="end_time" required
                            class="w-full px-2 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">必要人員</label>
                        <input type="number" id="timelineQuickCreatePeople" name="required_people_count" value="1" min="0" max="999" required
                            class="w-full px-2 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">実施場所（任意）</label>
                    <input type="text" id="timelineQuickCreateLocation" name="location" placeholder="例: 本陣前"
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900">
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" data-action="close-timeline-modal"
                        class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition cursor-pointer">
                        キャンセル
                    </button>
                    <button type="submit" id="timelineQuickCreateSubmitBtn"
                        class="px-4 py-2 rounded-xl text-xs font-bold shadow transition flex items-center space-x-1.5 bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer">
                        <span>➕</span>
                        <span>作成して配置する</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- バークリック時の詳細・配置解除モーダル -->
    <div id="staffBarActionModal"
        class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
        role="dialog" aria-modal="true" aria-labelledby="staff-bar-modal-title">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-xl border border-slate-200 overflow-hidden text-left">
            <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5 text-[11px] mb-0.5">
                        <span id="staffBarTypeBadge" class="px-1.5 py-0.2 rounded font-bold"></span>
                        <span id="staffBarLaneBadge" class="px-1.5 py-0.2 rounded bg-slate-800 text-indigo-200 border border-slate-700 font-bold"></span>
                        <span id="staffBarTimeLabel" class="font-mono text-indigo-200 font-bold"></span>
                    </div>
                    <h3 id="staff-bar-modal-title" class="text-sm font-bold truncate"></h3>
                </div>
                <button type="button" data-action="close-staff-bar-modal" class="text-slate-400 hover:text-white text-xl font-bold leading-none cursor-pointer">&times;</button>
            </div>

            <div class="p-5 space-y-3 text-xs">
                <div class="grid grid-cols-2 gap-2.5 bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div>
                        <span class="text-[10px] text-slate-500 block">対象スタッフ</span>
                        <strong id="staffBarStaffName" class="text-slate-900"></strong>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 block">📍 実施場所</span>
                        <strong id="staffBarLocation" class="text-slate-900"></strong>
                    </div>
                </div>

                <div>
                    <span class="text-[11px] font-bold text-slate-600 block mb-1">同時配置スタッフ</span>
                    <div id="staffBarAssignees" class="flex flex-wrap gap-1.5"></div>
                </div>
            </div>

            <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs">
                <div class="flex items-center gap-1.5">
                    @can('update', \App\Models\TimetableTask::class)
                    <button type="button" id="staffBarUnassignBtn"
                        class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-lg font-bold transition cursor-pointer">
                        ✖ このスタッフの配置を解除
                    </button>
                    @endcan
                    <a id="staffBarWorkspaceLink" href="{{ route('timetables.assignments', $timetable) }}"
                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg font-bold transition">
                        👥 人員配置へ
                    </a>
                </div>
                <button type="button" data-action="close-staff-bar-modal"
                    class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg font-bold cursor-pointer">
                    閉じる
                </button>
            </div>
        </div>
    </div>

    <!-- JSONデータ (CSP完全準拠・hiddenコンテナ) -->
    <div id="timeline-tasks-data" class="hidden">{!! json_encode($allTasksData, JSON_HEX_TAG | JSON_HEX_AMP) !!}</div>
    <div id="staff-timeline-periods-data" class="hidden">{!! json_encode(['road_bands' => $roadBands, 'festival_bands' => $festivalBands], JSON_HEX_TAG | JSON_HEX_AMP) !!}</div>
</div>

@vite(['resources/js/staff-timeline.js'])
</x-layouts.app>
