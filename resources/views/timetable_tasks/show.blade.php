<x-layouts.app :title="$task->title . ' - 作業タスク詳細・人員アサイン'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

    @php
        $laneRel = $task->laneRelation;
        $laneBadgeClass = $laneRel ? $laneRel->color_classes['badge'] : match($task->lane) {
            'カラーコーン', 'カラーコーン班' => 'bg-amber-100 text-amber-800 border-amber-300',
            '給水設備', '給水設備班' => 'bg-sky-100 text-sky-800 border-sky-300',
            '衛生', '衛生班' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            '巡回', '誘導・警備班' => 'bg-purple-100 text-purple-800 border-purple-300',
            'キッズ村' => 'bg-pink-100 text-pink-800 border-pink-300',
            'ちょうちん' => 'bg-rose-100 text-rose-800 border-rose-300',
            'ござ市' => 'bg-orange-100 text-orange-800 border-orange-300',
            '本陣', '本部連絡班' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
            '道路清掃' => 'bg-teal-100 text-teal-800 border-teal-300',
            'ステカン' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
            default => 'bg-slate-100 text-slate-800 border-slate-200',
        };
        $laneIcon = $laneRel ? ($laneRel->icon ?: '🏷️') : '🏷️';

        $taskStatusClass = match($task->status) {
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'in_progress' => 'bg-amber-50 text-amber-800 border-amber-300 font-bold',
            'cancelled' => 'bg-slate-100 text-slate-400 border-slate-200 line-through',
            default => 'bg-slate-100 text-slate-600 border-slate-200',
        };

        $affiliations = $prioritizedStaff->pluck('affiliation')->filter()->unique()->values();
    @endphp

    <!-- ナビゲーション & ヘッダー (共通 x-page-header) -->
    <x-page-header
        :title="$task->title"
        icon="📋"
        :description="'「' . ($task->timetable?->name ?? '運行タイムテーブル') . '」（' . ($task->timetable?->target_date?->format('Y年m月d日') ?? '-') . '）の人員配置・出欠アテンド管理'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1">
                <span>⏱️</span>
                <span>タイムテーブル一覧</span>
            </a>
            <span>/</span>
            <a href="{{ route('timetables.show', $task->timetable_id) }}" class="hover:text-indigo-600 transition">
                <span>📅 {{ $task->timetable?->name }}</span>
            </a>
            <span>/</span>
            <span class="text-slate-700 font-bold">📋 {{ $task->title }}</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            <!-- 担当班バッジ -->
            @if($task->lane)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold border {{ $laneBadgeClass }}">
                <span class="mr-1">{{ $laneIcon }}</span>
                <span>{{ $task->lane }}</span>
            </span>
            @endif

            <!-- 時間帯バッジ -->
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-slate-100 text-slate-800 border border-slate-200">
                ⏱️ {{ substr($task->start_time, 0, 5) }} 〜 {{ substr($task->end_time, 0, 5) }}
            </span>

            <!-- 実施場所バッジ -->
            @if($task->location)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-slate-50 text-slate-700 border border-slate-200">
                📍 {{ $task->location }}
            </span>
            @endif

            <!-- 進行ステータスバッジ -->
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs border {{ $taskStatusClass }}">
                {{ $task->status_label }}
            </span>

            <!-- 最優先・本部確認バッジ -->
            @if($task->is_critical)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                🚨 最優先
            </span>
            @endif
            @if($task->requires_review)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                📋 本部確認要
            </span>
            @endif

            <!-- 人員充足状況バッジ -->
            @if($task->assigned_count > $task->required_people_count && !$task->is_at_least)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                ℹ 人員余剰: {{ $task->assigned_count }} / {{ $task->required_people_label }} (+{{ $task->assigned_count - $task->required_people_count }}名)
            </span>
            @elseif($task->is_satisfied)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                ✔ 人員充足: {{ $task->assigned_count }} / {{ $task->required_people_label }}
            </span>
            @else
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300">
                ⚠️ 人員不足: {{ $task->assigned_count }} / {{ $task->required_people_label }} (あと{{ $task->shortage_count }}名)
            </span>
            @endif
        </x-slot:badges>

        <x-slot:actions>
            @can('update', $task)
            <a href="{{ route('timetable-tasks.edit', $task) }}"
                class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>✏️</span>
                <span>タスク情報編集</span>
            </a>
            @endcan

            @can('delete', $task)
            <form action="{{ route('timetable-tasks.destroy', $task) }}" method="POST" class="inline"
                data-confirm="作業タスク「{{ $task->title }}」を削除しますか？">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs cursor-pointer">
                    <span>🗑️</span>
                    <span>削除</span>
                </button>
            </form>
            @endcan

            <a href="{{ route('timetables.show', $task->timetable_id) }}"
                class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>←</span>
                <span>「{{ $task->timetable?->name }}」に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 作業内容・手順指示（コンパクト表示） -->
    @if($task->content)
        @php
            $isMultilineContent = str_contains($task->content, "\n") || mb_strlen($task->content) > 80;
        @endphp
        @if($isMultilineContent)
        <details class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden group">
            <summary class="px-4 py-2.5 flex items-center justify-between gap-3 cursor-pointer hover:bg-slate-50/80 transition select-none text-xs">
                <div class="flex items-center space-x-2 min-w-0 flex-1">
                    <span class="font-bold text-slate-600 shrink-0 flex items-center space-x-1">
                        <span>📝</span>
                        <span>作業内容・手順指示:</span>
                    </span>
                    <span class="text-slate-700 truncate group-open:hidden">{{ strtok($task->content, "\r\n") }}</span>
                </div>
                <span class="text-[11px] text-indigo-600 font-bold shrink-0 group-open:hidden">全文を表示 ▼</span>
                <span class="text-[11px] text-slate-400 font-medium shrink-0 hidden group-open:inline">閉じる ▲</span>
            </summary>
            <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/70 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                {{ $task->content }}
            </div>
        </details>
        @else
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-2.5 shadow-2xs flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
            <span class="font-bold text-slate-600 shrink-0 flex items-center space-x-1">
                <span>📝</span>
                <span>作業内容・手順指示:</span>
            </span>
            <span class="text-slate-800">{{ $task->content }}</span>
        </div>
        @endif
    @endif

    <!-- メインセクション: 配置スタッフ・出欠アテンド管理 -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- セクションヘッダー & 人員充足サマリー -->
        <div class="p-4 sm:p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-r from-slate-50/70 to-white">
            <div class="flex flex-wrap items-center gap-3">
                <div>
                    <h2 class="font-bold text-base text-slate-900 flex items-center space-x-2">
                        <span>👥</span>
                        <span>配置スタッフ・出欠アテンド管理</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        スタッフの一括配置・スマートアサインおよび当日の出欠・役割変更・代替交代を管理します。
                    </p>
                </div>
            </div>

            <!-- 人員充足カウンター & 自動配置アクション -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <div class="flex items-center space-x-3 px-3.5 py-2 rounded-xl border {{ $task->is_satisfied ? 'bg-emerald-50/70 border-emerald-200' : 'bg-amber-50/80 border-amber-300' }}">
                    <div class="text-xs">
                        <span class="text-slate-500 font-medium block text-[10px]">配置人数 / 必要人員</span>
                        <div class="font-mono font-black text-base leading-tight {{ $task->is_satisfied ? 'text-emerald-700' : 'text-amber-800' }}">
                            {{ $task->assigned_count }} <span class="text-xs font-normal text-slate-500">/</span> {{ $task->required_people_label }}
                        </div>
                    </div>
                    <div>
                        @if($task->assigned_count > $task->required_people_count && !$task->is_at_least)
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-sky-100 text-sky-800 border border-sky-200">
                            余剰 +{{ $task->assigned_count - $task->required_people_count }}名
                        </span>
                        @elseif($task->is_satisfied)
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            ✔ 充足完了
                        </span>
                        @else
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                            ⚠️ あと{{ $task->shortage_count }}名不足
                        </span>
                        @endif
                    </div>
                </div>

                @can('update', $task)
                    @if($task->shortage_count > 0 && $task->lane)
                    <form action="{{ route('timetable-tasks.assign-lane-pool', $task) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            title="この班に所属する空きスタッフを不足人数（{{ $task->shortage_count }}名）分自動配置します"
                            class="px-3.5 py-2.5 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs cursor-pointer">
                            <span>⚡</span>
                            <span>「{{ $task->lane }}」から不足分を自動配置</span>
                        </button>
                    </form>
                    @endif
                @endcan
            </div>
        </div>

        <!-- 1. 配置済みスタッフ・出欠アテンド一覧テーブル -->
        <div class="overflow-x-auto border-b border-slate-200">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-4 py-2.5 font-bold">配置スタッフ氏名（フリガナ）</th>
                        <th class="px-4 py-2.5 font-bold">所属団体</th>
                        <th class="px-4 py-2.5 font-bold">担当役割</th>
                        <th class="px-4 py-2.5 font-bold">出欠ステータス</th>
                        <th class="px-4 py-2.5 font-bold text-right">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($task->assignments as $assignment)
                    <tr class="hover:bg-slate-50/70 transition">
                        <!-- 氏名 -->
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('staff.show', $assignment->staff_id) }}"
                                    class="font-bold text-indigo-600 hover:underline">
                                    {{ $assignment->staff?->name }}
                                </a>
                                @if($assignment->staff?->kana)
                                <span class="text-[10px] text-slate-400 font-normal">
                                    ({{ $assignment->staff->kana }})
                                </span>
                                @endif
                            </div>
                        </td>

                        <!-- 所属 -->
                        <td class="px-4 py-2.5 text-slate-600 whitespace-nowrap">
                            {{ $assignment->staff?->affiliation ?: '-' }}
                        </td>

                        <!-- 担当役割 -->
                        <td class="px-4 py-2.5 text-slate-800">
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-medium text-[11px]">
                                {{ $assignment->role_description ?: '一般担当' }}
                            </span>
                        </td>

                        <!-- 出欠ステータス & 変更フォーム -->
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            @can('update', $task)
                            <form action="{{ route('timetable-tasks.update-attendance', ['task' => $task, 'assignment' => $assignment]) }}"
                                method="POST" class="inline-flex items-center space-x-1">
                                @csrf
                                @method('PATCH')
                                <select name="attendance_status" data-auto-submit
                                    class="text-xs px-2.5 py-1 rounded-lg border border-slate-300 bg-white font-medium focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                                    @foreach($attendanceStatuses as $statusCode => $statusLabel)
                                    <option value="{{ $statusCode }}" {{ $assignment->attendance_status === $statusCode ? 'selected' : '' }}>
                                        {{ $statusLabel }}
                                    </option>
                                    @endforeach
                                </select>
                            </form>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700">
                                {{ $assignment->attendance_status_label }}
                            </span>
                            @endcan
                        </td>

                        <!-- 操作 -->
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            @can('update', $task)
                            <div class="inline-flex items-center space-x-1.5">
                                <button type="button"
                                    data-action="open-swap-modal"
                                    data-staff-id="{{ $assignment->staff_id }}"
                                    data-staff-name="{{ $assignment->staff?->name ?? '不明' }}"
                                    data-staff-role="{{ $assignment->role_description ?? '担当' }}"
                                    class="px-2.5 py-1 {{ $assignment->attendance_status === 'absent' ? 'bg-amber-100 hover:bg-amber-200 text-amber-800 border-amber-300 font-bold animate-pulse' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200' }} border rounded-lg text-[11px] transition flex items-center space-x-1 cursor-pointer"
                                    title="別の空きスタッフとワンタップで交代・補充します">
                                    <span>🔄</span>
                                    <span>{{ $assignment->attendance_status === 'absent' ? '代替補充' : '交代' }}</span>
                                </button>

                                <form action="{{ route('timetable-tasks.remove-staff', ['task' => $task, 'staff' => $assignment->staff_id]) }}"
                                    method="POST" class="inline"
                                    data-confirm="スタッフ「{{ $assignment->staff?->name }}」の割り当てを解除しますか？">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-2.5 py-1 bg-white hover:bg-red-50 text-red-600 border border-slate-200 hover:border-red-200 rounded-lg text-[11px] font-medium transition cursor-pointer">
                                        解除
                                    </button>
                                </form>
                            </div>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                            <span class="text-2xl block mb-1">👥</span>
                            まだスタッフが配置されていません。下の「スタッフ一括配置・スマートアサイン」から選択して配置してください。
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 2. スタッフ新規割り当て & スマートアサイン（高密度マルチカラム一覧） -->
        @can('update', $task)
        <div class="p-4 sm:p-5 bg-slate-50/70 space-y-4">
            <!-- 単一アサイン時の重複警告バナー -->
            @if(session('conflict_warning'))
            <div class="p-4 bg-amber-50 border-2 border-amber-300 rounded-xl space-y-3 shadow-sm">
                <div class="flex items-start space-x-3">
                    <span class="text-2xl">⚠️</span>
                    <div class="flex-1">
                        <h4 class="text-xs font-bold text-amber-900 uppercase tracking-wider">同一時間帯のアサイン重複警告</h4>
                        <p class="text-xs text-amber-800 font-medium mt-1">
                            {{ session('conflict_warning') }}
                        </p>
                        <p class="text-[11px] text-amber-700 mt-1">
                            業務上必要な場合、重複を承認してそのまま強制配置することができます。
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-3 pt-2 border-t border-amber-200">
                    <a href="{{ route('timetable-tasks.show', $task) }}"
                        class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition">
                        キャンセル
                    </a>
                    <form action="{{ route('timetable-tasks.assign', $task) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="staff_id" value="{{ session('conflict_staff_id') }}">
                        <input type="hidden" name="role_description" value="{{ session('conflict_role_description') }}">
                        <input type="hidden" name="force" value="1">
                        <button type="submit"
                            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold shadow transition cursor-pointer">
                            ⚠️ 重複を承認して配置する
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- 一括アサイン時の重複警告バナー -->
            @if(session('bulk_conflict_warning'))
            <div class="p-4 bg-amber-50 border-2 border-amber-300 rounded-xl space-y-3 shadow-sm">
                <div class="flex items-start space-x-3">
                    <span class="text-2xl">⚠️</span>
                    <div class="flex-1">
                        <h4 class="text-xs font-bold text-amber-900 uppercase tracking-wider">同一時間帯の一括アサイン重複警告</h4>
                        <p class="text-xs text-amber-800 font-medium mt-1">
                            {{ session('bulk_conflict_warning') }}
                        </p>
                        @if(session('bulk_conflict_list'))
                        <div class="mt-2 space-y-1">
                            @foreach(session('bulk_conflict_list') as $item)
                            <div class="text-[11px] text-amber-800 bg-amber-100/70 px-2 py-1 rounded">
                                ・<strong>{{ $item['staff_name'] }}</strong>: {{ $item['task_title'] }} ({{ $item['lane'] }} / {{ $item['time'] }})
                            </div>
                            @endforeach
                        </div>
                        @endif
                        <p class="text-[11px] text-amber-700 mt-2">
                            業務上必要な場合、重複を承認して選択した全員をそのまま強制配置することができます。
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-3 pt-2 border-t border-amber-200">
                    <a href="{{ route('timetable-tasks.show', $task) }}"
                        class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition">
                        キャンセル
                    </a>
                    <form action="{{ route('timetable-tasks.assign-bulk', $task) }}" method="POST" class="inline">
                        @csrf
                        @foreach(session('bulk_conflict_staff_ids', []) as $sId)
                        <input type="hidden" name="staff_ids[]" value="{{ $sId }}">
                        @endforeach
                        <input type="hidden" name="role_description" value="{{ session('bulk_conflict_role_description') }}">
                        <input type="hidden" name="force" value="1">
                        <button type="submit"
                            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold shadow transition cursor-pointer">
                            ⚠️ 重複を承認して一括配置する
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- 交代時の重複警告バナー -->
            @if(session('swap_conflict_warning'))
            <div class="p-4 bg-amber-50 border-2 border-amber-300 rounded-xl space-y-3 shadow-sm">
                <div class="flex items-start space-x-3">
                    <span class="text-2xl">⚠️</span>
                    <div class="flex-1">
                        <h4 class="text-xs font-bold text-amber-900 uppercase tracking-wider">交代候補の同一時間帯アサイン重複警告</h4>
                        <p class="text-xs text-amber-800 font-medium mt-1">
                            {{ session('swap_conflict_warning') }}
                        </p>
                        <p class="text-[11px] text-amber-700 mt-1">
                            業務上必要な場合、重複を承認してそのまま交代配置を実行することができます。
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-3 pt-2 border-t border-amber-200">
                    <a href="{{ route('timetable-tasks.show', $task) }}"
                        class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition">
                        キャンセル
                    </a>
                    <form action="{{ route('timetable-tasks.swap-staff', $task) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="old_staff_id" value="{{ session('swap_old_staff_id') }}">
                        <input type="hidden" name="new_staff_id" value="{{ session('swap_new_staff_id') }}">
                        <input type="hidden" name="role_description" value="{{ session('swap_role_description') }}">
                        <input type="hidden" name="force" value="1">
                        <button type="submit"
                            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold shadow transition cursor-pointer">
                            ⚠️ 重複を承認して交代する
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- 一括選択アサインUI（マルチカラム・高密度表示） -->
            <div class="space-y-3 bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                    <div>
                        <h3 class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                            <span>➕</span>
                            <span>スタッフ一括配置・スマートアサイン</span>
                            <span class="text-[11px] font-normal text-slate-400">（全 {{ $prioritizedStaff->count() }} 名）</span>
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            空き状況・所属班・現場受付状況・累計稼働時間に基づいて最適順に自動並び替えられています。カードをクリックして複数選択できます。
                        </p>
                    </div>

                    <!-- 選択件数バッジ & アクション -->
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <span class="text-xs text-slate-600 font-medium bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200">
                            選択中: <strong id="selected-count-label" class="text-indigo-600 font-mono text-sm">0</strong> 名
                        </span>
                        <button type="button" id="btn-select-all-filtered"
                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition cursor-pointer">
                            表示中を全選択
                        </button>
                        <button type="button" id="btn-clear-selection"
                            class="hidden px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-lg text-xs font-medium transition cursor-pointer">
                            選択解除
                        </button>
                    </div>
                </div>

                <!-- フィルターバー -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <!-- 検索ボックス -->
                    <div>
                        <input type="text" id="staff-search-input" placeholder="🔍 氏名・カナ・所属で検索..."
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <!-- 状態絞り込み -->
                    <div>
                        <select id="staff-filter-status"
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="all">すべてのスタッフを表示</option>
                            <option value="recommended">🌟 推奨スタッフのみ（同班所属＆空き）</option>
                            <option value="available">🟢 空きスタッフのみ（重複なし）</option>
                            <option value="checked_in">📍 現場到着（チェックイン）済みのみ</option>
                        </select>
                    </div>

                    <!-- 所属絞り込み -->
                    <div>
                        <select id="staff-filter-affiliation"
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">すべての所属団体</option>
                            @foreach($affiliations as $aff)
                            <option value="{{ $aff }}">{{ $aff }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- スタッフ候補マルチカラムグリッド（PC 3列 / タブレット 2列 / スマホ 1列） -->
                <form action="{{ route('timetable-tasks.assign-bulk', $task) }}" method="POST" id="bulk-assign-form" class="space-y-3">
                    @csrf

                    <div class="max-h-[480px] overflow-y-auto border border-slate-200 rounded-xl bg-slate-50/50 p-2.5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($prioritizedStaff as $s)
                            @php
                                $isAssigned = $task->assignments->contains('staff_id', $s->id);
                                $isConflicted = $s->conflict_task !== null;
                                $isSameLane = (bool) $s->is_same_lane;
                                $isCheckedIn = (bool) $s->is_checked_in;
                            @endphp
                            <div class="bulk-candidate-row px-3 py-2 rounded-lg border text-xs transition select-none flex flex-col justify-between gap-1 {{ $isAssigned ? 'opacity-55 bg-slate-100 border-slate-200 cursor-not-allowed' : ($isConflicted ? 'bg-amber-50/60 border-amber-200/80 hover:border-amber-300 cursor-pointer' : 'bg-white border-slate-200 hover:border-indigo-300 hover:shadow-2xs cursor-pointer') }}"
                                data-name="{{ $s->name }}"
                                data-kana="{{ $s->kana ?? '' }}"
                                data-affiliation="{{ $s->affiliation ?? '' }}"
                                data-is-conflicted="{{ $isConflicted ? '1' : '0' }}"
                                data-is-same-lane="{{ $isSameLane ? '1' : '0' }}"
                                data-is-checked-in="{{ $isCheckedIn ? '1' : '0' }}"
                                data-is-assigned="{{ $isAssigned ? '1' : '0' }}">

                                <!-- 上段: チェックボックス & 氏名・カナ & 受付・配置済バッジ -->
                                <div class="flex items-center justify-between gap-1.5 min-w-0">
                                    <div class="flex items-center space-x-2 min-w-0">
                                        <input type="checkbox" name="staff_ids[]" value="{{ $s->id }}"
                                            {{ $isAssigned ? 'disabled' : '' }}
                                            class="bulk-candidate-checkbox h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer shrink-0">

                                        <span class="font-bold text-slate-900 truncate" title="{{ $s->name }}">{{ $s->name }}</span>
                                        @if($s->kana)
                                        <span class="text-[10px] text-slate-400 font-normal truncate hidden xl:inline">({{ $s->kana }})</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center space-x-1 shrink-0">
                                        @if($isCheckedIn)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-sky-100 text-sky-800 border border-sky-300" title="現場到着受付済み">
                                            着
                                        </span>
                                        @endif
                                        @if($isAssigned)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-200 text-slate-600">
                                            配置済
                                        </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- 下段: 所属団体 & 適合度バッジ & 当日稼働時間 -->
                                <div class="flex items-center justify-between gap-1.5 pl-5 text-[10px]">
                                    <span class="text-slate-500 truncate" title="{{ $s->affiliation ?: '所属なし' }}">
                                        {{ $s->affiliation ?: '所属なし' }}
                                    </span>

                                    <div class="flex items-center space-x-1.5 shrink-0">
                                        @if($isConflicted)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-300 truncate max-w-[120px]"
                                            title="重複中: {{ $s->conflict_task?->title }}">
                                            ⚠️ {{ $s->conflict_task?->title }}
                                        </span>
                                        @elseif($isSameLane)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            🌟 同班推奨
                                        </span>
                                        @else
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                            🟢 空き
                                        </span>
                                        @endif

                                        <span class="font-mono text-slate-400">
                                            {{ round(($s->today_assigned_minutes ?? 0) / 60, 1) }}h
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div id="candidate-empty-notice" class="hidden p-6 text-center text-xs text-slate-400">
                            該当するスタッフが見つかりません。
                        </div>
                    </div>

                    <!-- アサイン実行フッター -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-1">
                        <div class="flex-1 sm:max-w-xs">
                            <input type="text" name="role_description" value="{{ old('role_description') }}"
                                placeholder="担当役割（任意、例: チーフ、案内、運搬）"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>

                        <div class="flex items-center space-x-2 justify-end">
                            <button type="submit" id="btn-submit-bulk-assign" disabled
                                class="px-5 py-2 rounded-lg text-xs font-bold shadow-sm transition flex items-center space-x-1.5 bg-slate-200 text-slate-400 cursor-not-allowed">
                                <span>➕</span>
                                <span>選択した <strong id="selected-count-submit-label">0</strong> 名をタスクに配置する</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endcan

        <!-- スタッフ交代・ワンタップ代替補充モーダル (CSP完全準拠・バニラJS) -->
        <div id="swapModal"
            class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-5 space-y-4 shadow-2xl border border-slate-200 text-left">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                        <span>🔄</span>
                        <span>スタッフ交代・ワンタップ代替補充</span>
                    </h3>
                    <button type="button" data-action="close-swap-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">✕</button>
                </div>

                <!-- 交代対象の表示 -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs flex items-center justify-between">
                    <span class="text-slate-500 font-medium">外れるスタッフ（交代元）:</span>
                    <div class="font-bold text-slate-800 text-sm flex items-center space-x-1.5">
                        <span id="swapOldStaffName"></span>
                        <span id="swapOldStaffRole" class="text-xs text-slate-500 font-normal"></span>
                    </div>
                </div>

                <!-- 交代先スタッフの選択フォーム -->
                <form action="{{ route('timetable-tasks.swap-staff', $task) }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" id="swapOldStaffIdInput" name="old_staff_id" value="">

                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700">新しく入る代替スタッフを選択:</label>
                        <input type="text" id="swapSearchInput" placeholder="🔍 氏名・カナ・所属で候補者を検索..."
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">

                        <div class="max-h-80 overflow-y-auto border border-slate-200 rounded-xl bg-slate-50/50 p-2">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                @foreach($prioritizedStaff as $st)
                                @php
                                    $stIsAssigned = $task->assignments->contains('staff_id', $st->id);
                                @endphp
                                <label class="swap-candidate-row flex items-center justify-between px-3 py-2 rounded-lg border border-slate-200 bg-white hover:border-indigo-300 cursor-pointer transition text-xs {{ $stIsAssigned ? 'hidden' : '' }}"
                                    data-staff-id="{{ $st->id }}"
                                    data-name="{{ $st->name }}"
                                    data-kana="{{ $st->kana ?? '' }}"
                                    data-affiliation="{{ $st->affiliation ?? '' }}"
                                    data-is-assigned="{{ $stIsAssigned ? '1' : '0' }}">
                                    <div class="flex items-center space-x-2 min-w-0">
                                        <input type="radio" name="new_staff_id" value="{{ $st->id }}" class="text-indigo-600 focus:ring-indigo-500 cursor-pointer shrink-0">
                                        <div class="truncate">
                                            <span class="text-slate-900 font-bold">{{ $st->name }}</span>
                                            @if($st->affiliation)
                                            <span class="text-[10px] text-slate-400 ml-1">({{ $st->affiliation }})</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-1 shrink-0 ml-1">
                                        @if($st->is_checked_in)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-sky-100 text-sky-800 border border-sky-300">着</span>
                                        @endif
                                        @if($st->priority_tier === 1)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">班推奨</span>
                                        @elseif($st->conflict_task !== null)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-300 truncate max-w-[90px]" title="重複中: {{ $st->conflict_task->title }}">⚠️ 重複</span>
                                        @else
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-medium bg-slate-100 text-slate-600 border border-slate-200">空き</span>
                                        @endif
                                    </div>
                                </label>
                                @endforeach
                            </div>

                            <div id="swap-candidate-empty-notice" class="hidden p-6 text-center text-xs text-slate-400">
                                交代可能な候補スタッフが見つかりません。
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">担当役割（任意）:</label>
                        <input type="text" id="swapRoleInput" name="role_description" placeholder="例: 案内、誘導、チーフ"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                        <button type="button" data-action="close-swap-modal"
                            class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition cursor-pointer">
                            キャンセル
                        </button>
                        <button type="submit" id="swapSubmitBtn" disabled
                            class="px-4 py-2 rounded-xl text-xs font-bold shadow transition flex items-center space-x-1.5 bg-slate-200 text-slate-400 cursor-not-allowed">
                            <span>🔄</span>
                            <span>交代を実行する</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@vite(['resources/js/timetable-task-show.js'])
</x-layouts.app>
