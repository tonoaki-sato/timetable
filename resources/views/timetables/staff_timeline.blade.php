<x-layouts.app :title="$timetable->name . ' - スタッフ別リソースタイムライン'">
<div class="max-w-[1700px] mx-auto px-2 sm:px-4 lg:px-6 py-4 space-y-4">

    <!-- ナビゲーション & アクションバー -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-2xs print:hidden">
        <div class="flex items-center space-x-2 text-xs text-slate-500">
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1">
                <span>⏱️</span>
                <span>タイムテーブル一覧</span>
            </a>
            <span>/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition">
                <span>📅 {{ $timetable->name }}</span>
            </a>
            <span>/</span>
            <span class="text-slate-700 font-bold">👥 スタッフ稼働タイムライン</span>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}"
                class="px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>⏱️</span>
                <span>全体マトリクス進行表へ</span>
            </a>

            <button type="button" data-action="print"
                class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 cursor-pointer shadow-2xs">
                <span>🖨️</span>
                <span>A4横 印刷</span>
            </button>

            <a href="{{ route('timetables.show', $timetable) }}"
                class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>←</span>
                <span>タイムテーブル詳細へ戻る</span>
            </a>
        </div>
    </div>

    <!-- ヘッダーカード -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 rounded-2xl p-5 text-white shadow-md border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-1.5">
                <span class="px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                    📅 {{ $timetable->target_date?->format('Y年m月d日') }}
                </span>
                <span class="px-2.5 py-0.5 rounded text-xs font-mono text-slate-300 bg-slate-800 border border-slate-700">
                    ⏱️ {{ substr($timetable->start_time, 0, 5) }} 〜 {{ substr($timetable->end_time, 0, 5) }}
                </span>
                <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-purple-500/20 text-purple-300 border border-purple-400/30">
                    👥 スタッフ稼働ガントチャート
                </span>
            </div>

            <h1 class="text-xl sm:text-2xl font-bold tracking-tight">
                {{ $timetable->name }} - スタッフ稼働タイムライン
            </h1>
            <p class="text-xs text-slate-300 mt-1">
                全スタッフのタスク配置状況と空き枠を時系列で俯瞰できます。空き枠をクリックすると不足タスクへ直接アサインできます。
            </p>
        </div>

        <!-- 統計サマリーウィジェット -->
        <div class="grid grid-cols-4 gap-2 shrink-0">
            <div class="bg-slate-800/80 backdrop-blur p-2.5 rounded-xl border border-slate-700 text-center min-w-[70px]">
                <span class="text-[10px] text-slate-400 block font-bold">全スタッフ</span>
                <span class="text-lg font-bold font-mono text-white">{{ $totalStaffCount }}</span>
            </div>
            <div class="bg-slate-800/80 backdrop-blur p-2.5 rounded-xl border border-slate-700 text-center min-w-[70px]">
                <span class="text-[10px] text-emerald-400 block font-bold">稼働配置中</span>
                <span class="text-lg font-bold font-mono text-emerald-400">{{ $assignedStaffCount }}</span>
            </div>
            <div class="bg-slate-800/80 backdrop-blur p-2.5 rounded-xl border border-slate-700 text-center min-w-[70px]">
                <span class="text-[10px] text-amber-400 block font-bold">待機・未配置</span>
                <span class="text-lg font-bold font-mono text-amber-400">{{ $unassignedStaffCount }}</span>
            </div>
            <div class="bg-slate-800/80 backdrop-blur p-2.5 rounded-xl border border-slate-700 text-center min-w-[70px]">
                <span class="text-[10px] text-sky-400 block font-bold">現場受付済</span>
                <span class="text-lg font-bold font-mono text-sky-400">{{ $checkedInCount }}</span>
            </div>
        </div>
    </div>

    <!-- フィルターバー -->
    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3 print:hidden">
        <div class="flex flex-wrap items-center gap-2 flex-1">
            <span class="text-xs font-bold text-slate-700">🔍 絞り込み:</span>

            <!-- キーワード検索 -->
            <input type="text" id="timeline-search-input" placeholder="氏名・カナ・所属で検索..."
                class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 w-48">

            <!-- 状態フィルターボタン -->
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

            <!-- 所属フィルター -->
            <select id="timeline-affiliation-select"
                class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option value="">すべての所属団体</option>
                @foreach($affiliations as $aff)
                <option value="{{ $aff }}">{{ $aff }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- ガントチャートテーブル -->
    <div class="bg-white border border-slate-300 rounded-xl shadow-xs overflow-x-auto">
        <table class="w-full border-collapse text-left text-xs">
            <thead>
                <tr class="bg-slate-800 text-white select-none">
                    <!-- スタッフ情報列（固定） -->
                    <th scope="col" class="sticky left-0 z-20 bg-slate-900 border-r border-slate-700 p-2.5 w-[220px] min-w-[220px]">
                        スタッフ情報
                    </th>

                    <!-- 30分スロット列 -->
                    @foreach($timeSlots as $slot)
                    <th scope="col" class="border-r border-slate-700 p-2 text-center font-mono text-[11px] min-w-[70px] w-[70px]">
                        {{ $slot['time'] }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($activeStaff as $staff)
                @php
                    $staffAssignments = $assignmentsByStaff[$staff->id] ?? collect();
                    $assignedCount = $staffAssignments->count();
                    $isCheckedIn = (bool) $staff->is_checked_in;
                    $staffGrid = $timelineGrid[$staff->id] ?? [];

                    // 当日累計稼働時間の算出
                    $totalMinutes = 0;
                    foreach($staffAssignments as $item) {
                        $t = $item['task'];
                        if ($t->start_time && $t->end_time) {
                            $sC = \Carbon\Carbon::parse($t->start_time);
                            $eC = \Carbon\Carbon::parse($t->end_time);
                            $totalMinutes += max(0, $sC->diffInMinutes($eC));
                        }
                    }
                    $totalHoursFormatted = round($totalMinutes / 60, 1) . 'h';
                @endphp
                <tr class="staff-row hover:bg-slate-50/80 transition"
                    data-staff-name="{{ $staff->name }}"
                    data-staff-kana="{{ $staff->kana ?? '' }}"
                    data-staff-affiliation="{{ $staff->affiliation ?? '' }}"
                    data-assigned-count="{{ $assignedCount }}"
                    data-checked-in="{{ $isCheckedIn ? '1' : '0' }}">

                    <!-- スタッフ情報セル (Sticky) -->
                    <td class="sticky left-0 z-10 bg-white border-r border-slate-300 p-2.5 align-middle shadow-xs">
                        <div class="space-y-0.5">
                            <div class="flex items-center justify-between">
                                <a href="{{ route('staff.show', $staff) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition truncate max-w-[130px]" title="{{ $staff->name }}">
                                    {{ $staff->name }}
                                </a>
                                @if($isCheckedIn)
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-sky-100 text-sky-800 border border-sky-300 shrink-0" title="現場到着受付済み">
                                    着
                                </span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between text-[10px] text-slate-500">
                                <span class="truncate max-w-[120px]" title="{{ $staff->affiliation }}">
                                    {{ $staff->affiliation ?: '所属なし' }}
                                </span>
                                <span class="font-mono text-slate-600 shrink-0 font-medium">
                                    {{ $totalHoursFormatted }}
                                </span>
                            </div>
                        </div>
                    </td>

                    <!-- 各スロットセル -->
                    @foreach($timeSlots as $sIdx => $slot)
                    @php
                        $cell = $staffGrid[$sIdx] ?? ['type' => 'empty', 'colspan' => 1];
                    @endphp

                    @if($cell['type'] === 'task')
                    @php
                        $task = $cell['task'];
                        $assignment = $cell['assignment'];
                        $laneRel = $task->laneRelation;
                        $badgeBg = match($laneRel?->color ?? 'indigo') {
                            'amber' => 'bg-amber-100 border-amber-300 text-amber-900',
                            'sky' => 'bg-sky-100 border-sky-300 text-sky-900',
                            'emerald' => 'bg-emerald-100 border-emerald-300 text-emerald-900',
                            'purple' => 'bg-purple-100 border-purple-400/30 text-purple-900',
                            'pink' => 'bg-pink-100 border-pink-300 text-pink-900',
                            'red' => 'bg-rose-100 border-rose-300 text-rose-900',
                            'orange' => 'bg-orange-100 border-orange-300 text-orange-900',
                            'teal' => 'bg-teal-100 border-teal-300 text-teal-900',
                            default => 'bg-indigo-100 border-indigo-300 text-indigo-900',
                        };
                    @endphp
                    <td colspan="{{ $cell['colspan'] }}"
                        class="p-1 border-r border-slate-300 align-middle">
                        <div class="p-1.5 rounded-lg border {{ $badgeBg }} text-xs shadow-2xs space-y-0.5">
                            <div class="font-bold truncate leading-tight flex items-center justify-between">
                                <a href="{{ route('timetable-tasks.show', $task) }}" class="hover:underline truncate" title="{{ $task->title }}">
                                    {{ $task->title }}
                                </a>
                                <span class="text-[9px] font-mono shrink-0 ml-1 opacity-75">
                                    {{ substr($task->start_time, 0, 5) }}〜{{ substr($task->end_time, 0, 5) }}
                                </span>
                            </div>
                            <div class="text-[10px] flex items-center justify-between opacity-80">
                                <span>{{ $task->lane }}</span>
                                <span class="font-medium">{{ $assignment->role_description ?: '担当' }}</span>
                            </div>
                        </div>
                    </td>

                    @elseif($cell['type'] === 'empty')
                    <td data-action="open-timeline-quick-assign"
                        data-staff-id="{{ $staff->id }}"
                        data-staff-name="{{ $staff->name }}"
                        data-slot-time="{{ $slot['time'] }}"
                        class="p-1 border-r border-slate-200 text-center align-middle hover:bg-indigo-50/70 group cursor-pointer transition relative"
                        title="クリックしてこの時間枠（{{ $slot['time'] }}）にタスクを配置">
                        <span class="text-slate-300 font-mono text-[10px] group-hover:hidden select-none">-</span>
                        <span class="hidden group-hover:inline-flex items-center justify-center text-[10px] font-bold text-indigo-600 bg-indigo-100/90 rounded px-1 py-0.2 shadow-2xs">
                            + 配置
                        </span>
                    </td>

                    @elseif($cell['type'] === 'skip')
                    <!-- colspan により結合されているためスキップ -->
                    @endif

                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- 空き枠からのタスク配置モーダル (CSP完全準拠・バニラJS) -->
    <div id="timelineQuickAssignModal"
        class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-5 space-y-4 shadow-2xl border border-slate-200 text-left">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                    <span>⏱️</span>
                    <span>空き時間枠へのタスク配置</span>
                </h3>
                <button type="button" data-action="close-timeline-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">×</button>
            </div>

            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs flex items-center justify-between">
                <div>
                    <span class="text-slate-500 block">対象スタッフ:</span>
                    <strong id="timelineStaffName" class="text-slate-800 text-sm"></strong>
                </div>
                <div class="text-right">
                    <span class="text-slate-500 block">対象時間枠:</span>
                    <span id="timelineSlotTime" class="font-mono font-bold text-indigo-600 text-sm"></span>
                </div>
            </div>

            <!-- エラーメッセージ -->
            <div id="timelineErrorMessage" class="hidden p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700"></div>

            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-700">この時間帯に人員不足のタスクを選択:</label>
                <div id="timelineTaskList" class="max-h-60 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100 bg-slate-50/50">
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700">担当役割（任意）:</label>
                <input type="text" id="timelineRoleInput" placeholder="例: 一般担当、案内、チーフ" value="担当"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>

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
    </div>

    <!-- 不足タスクデータ (CSP完全準拠・hiddenコンテナ) -->
    <div id="timeline-tasks-data" class="hidden">{!! json_encode($tasksWithShortage, JSON_HEX_TAG | JSON_HEX_AMP) !!}</div>

</div>

@vite(['resources/js/staff-timeline.js'])
</x-layouts.app>
