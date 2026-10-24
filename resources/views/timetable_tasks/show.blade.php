<x-layouts.app :title="$task->title . ' - 作業タスク詳細・人員アサイン'">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ナビゲーション & 操作 -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('timetables.show', $task->timetable_id) }}"
            class="inline-flex items-center space-x-1 text-xs font-medium text-slate-500 hover:text-slate-800 transition">
            <span>←</span>
            <span>「{{ $task->timetable?->name }}」に戻る</span>
        </a>

        <div class="flex items-center space-x-2">
            @can('update', $task)
            <a href="{{ route('timetable-tasks.edit', $task) }}"
                class="px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1">
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
                    class="px-3 py-2 bg-white hover:bg-red-50 text-red-600 border border-slate-200 hover:border-red-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 cursor-pointer">
                    <span>🗑️</span>
                    <span>削除</span>
                </button>
            </form>
            @endcan
        </div>
    </div>

    <!-- タスク概要ヘッダーカード -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 rounded-2xl p-6 text-white shadow-lg border border-slate-800">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                        📅 {{ $task->timetable?->name }} ({{ $task->timetable?->target_date?->format('m/d') }})
                    </span>
                    <span class="px-2.5 py-0.5 rounded text-xs font-mono text-slate-300 bg-slate-800 border border-slate-700">
                        ⏱️ {{ substr($task->start_time, 0, 5) }} 〜 {{ substr($task->end_time, 0, 5) }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-slate-800 text-slate-200 border border-slate-700">
                        🏷️ {{ $task->lane }}
                    </span>
                    @if($task->is_critical)
                    <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-rose-500/30 text-rose-300 border border-rose-400/40">
                        🚨 最優先
                    </span>
                    @endif
                    @if($task->requires_review)
                    <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-amber-500/30 text-amber-300 border border-amber-400/40">
                        📋 本部確認要
                    </span>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">
                    {{ $task->title }}
                </h1>

                @if($task->location)
                <div class="text-xs text-slate-300 flex items-center space-x-1">
                    <span>📍 実施場所:</span>
                    <span class="font-medium text-white">{{ $task->location }}</span>
                </div>
                @endif
            </div>

            <!-- 人員充足ステータスウィジェット -->
            <div class="bg-slate-800/90 backdrop-blur p-4 rounded-xl border border-slate-700 text-center min-w-[200px] shrink-0">
                <div class="text-[10px] text-slate-400 uppercase tracking-wider font-bold mb-1">人員充足状況</div>
                <div class="text-2xl font-black font-mono {{ $task->is_satisfied ? 'text-emerald-400' : 'text-amber-400' }}">
                    {{ $task->assigned_count }} / {{ $task->required_people_label }}
                </div>
                <div class="mt-2">
                    @if($task->assigned_count > $task->required_people_count)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-sky-500/20 text-sky-300 border border-sky-400/30">
                        ℹ 余剰: +{{ $task->assigned_count - $task->required_people_count }}名
                    </span>
                    @elseif($task->is_satisfied)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                        ✔ 充足完了
                    </span>
                    @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30">
                        ⚠️ 不足: あと{{ $task->shortage_count }}名
                    </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 作業内容（手順指示）カード -->
    @if($task->content)
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-2">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center space-x-1.5">
            <span>📝</span>
            <span>作業内容・手順指示</span>
        </h3>
        <div class="text-sm text-slate-800 whitespace-pre-wrap leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100 font-sans">
            {{ $task->content }}
        </div>
    </div>
    @endif

    <!-- スタッフアサイン＆アテンド管理セクション (Phase 1, 2, 3) -->
    @php
        $affiliations = $prioritizedStaff->pluck('affiliation')->filter()->unique()->values();
    @endphp
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-base text-slate-900 flex items-center space-x-2">
                    <span>👥</span>
                    <span>配置スタッフ・出欠アテンド管理</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    必要人員: <strong>{{ $task->required_people_label }}</strong> ｜ 現在配置数: <strong>{{ $task->assigned_count }}名</strong>
                </p>
            </div>

            <!-- アサイン追加（権限がある場合） -->
            @can('update', $task)
            <div class="flex items-center space-x-2">
                <span class="text-xs text-slate-500">クイック配置:</span>
            </div>
            @endcan
        </div>

        <!-- スタッフ新規割り当てフォーム & 重複警告バナー -->
        @can('update', $task)
        <div class="p-5 bg-slate-50 border-b border-slate-200 space-y-4">
            <!-- 単一アサイン時の重複警告バナー -->
            @if(session('conflict_warning'))
            <div class="p-4 bg-amber-50 border-2 border-amber-300 rounded-xl space-y-3 shadow-sm animate-pulse">
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

            <!-- Alpine.js による一括選択アサインUI -->
            <div class="space-y-3 bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                            <span>👥</span>
                            <span>スタッフ一括配置・スマートアサイン</span>
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            空き状況・所属班・現場受付状況・累計稼働時間に基づいて最適順に自動並び替えられています。
                        </p>
                    </div>

                    <!-- 選択件数バッジ & アクション -->
                    <div class="flex flex-wrap items-center gap-2">
                        @if($task->shortage_count > 0 && $task->lane)
                        <form action="{{ route('timetable-tasks.assign-lane-pool', $task) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                title="この班に所属する空きスタッフを不足人数（{{ $task->shortage_count }}名）分自動配置します"
                                class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded text-xs font-bold transition flex items-center space-x-1 cursor-pointer">
                                <span>⚡</span>
                                <span>「{{ $task->lane }}」から自動配置</span>
                            </button>
                        </form>
                        @endif

                        <span class="text-xs text-slate-600 font-medium">
                            選択中: <strong id="selected-count-label" class="text-indigo-600 font-mono text-sm">0</strong> 名
                        </span>
                        <button type="button" id="btn-select-all-filtered"
                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-medium transition cursor-pointer">
                            表示中を全選択
                        </button>
                        <button type="button" id="btn-clear-selection"
                            class="hidden px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded text-xs font-medium transition cursor-pointer">
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

                <!-- スタッフ候補スクロールリスト -->
                <form action="{{ route('timetable-tasks.assign-bulk', $task) }}" method="POST" id="bulk-assign-form" class="space-y-3">
                    @csrf

                    <div class="max-h-64 overflow-y-auto border border-slate-200 rounded-lg divide-y divide-slate-100 bg-white">
                        @foreach($prioritizedStaff as $s)
                        @php
                            $isAssigned = $task->assignments->contains('staff_id', $s->id);
                            $isConflicted = $s->conflict_task !== null;
                            $isSameLane = (bool) $s->is_same_lane;
                            $isCheckedIn = (bool) $s->is_checked_in;
                        @endphp
                        <div class="bulk-candidate-row p-2.5 flex items-center justify-between text-xs transition select-none {{ $isAssigned ? 'opacity-60 bg-slate-50 cursor-not-allowed' : ($isConflicted ? 'bg-amber-50/50 hover:bg-slate-50 cursor-pointer' : 'hover:bg-slate-50 cursor-pointer') }}"
                            data-name="{{ $s->name }}"
                            data-kana="{{ $s->kana ?? '' }}"
                            data-affiliation="{{ $s->affiliation ?? '' }}"
                            data-is-conflicted="{{ $isConflicted ? '1' : '0' }}"
                            data-is-same-lane="{{ $isSameLane ? '1' : '0' }}"
                            data-is-checked-in="{{ $isCheckedIn ? '1' : '0' }}"
                            data-is-assigned="{{ $isAssigned ? '1' : '0' }}">

                            <!-- 左側: チェックボックス & 氏名・所属 -->
                            <div class="flex items-center space-x-3 min-w-0">
                                <input type="checkbox" name="staff_ids[]" value="{{ $s->id }}"
                                    {{ $isAssigned ? 'disabled' : '' }}
                                    class="bulk-candidate-checkbox h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">

                                <div class="min-w-0">
                                    <div class="flex items-center space-x-1.5">
                                        <span class="font-bold text-slate-900 truncate">{{ $s->name }}</span>
                                        @if($s->kana)
                                        <span class="text-[10px] text-slate-400 font-normal">({{ $s->kana }})</span>
                                        @endif
                                        @if($isCheckedIn)
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-300">
                                            着
                                        </span>
                                        @endif
                                        @if($isAssigned)
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-200 text-slate-600">
                                            配置済み
                                        </span>
                                        @endif
                                    </div>
                                    <div class="text-[10px] text-slate-500 truncate">{{ $s->affiliation ?: '所属なし' }}</div>
                                </div>
                            </div>

                            <!-- 右側: 適合度バッジ & 当日稼働時間 -->
                            <div class="flex items-center space-x-2 shrink-0">
                                @if($isConflicted)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300 flex items-center space-x-1">
                                    <span>⚠️</span>
                                    <span>重複中: {{ $s->conflict_task?->title }}</span>
                                </span>
                                @elseif($isSameLane)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    🌟 推奨（同班）
                                </span>
                                @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                    🟢 空き
                                </span>
                                @endif

                                <!-- 当日累計稼働時間 -->
                                <span class="text-[10px] font-mono text-slate-500 hidden sm:inline">
                                    本日計: {{ round(($s->today_assigned_minutes ?? 0) / 60, 1) }}h
                                </span>
                            </div>
                        </div>
                        @endforeach

                        <div id="candidate-empty-notice" class="hidden p-6 text-center text-xs text-slate-400">
                            該当するスタッフが見つかりません。
                        </div>
                    </div>

                    <!-- アサイン実行フッター -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2">
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

        <!-- アサイン済みスタッフ一覧テーブル -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-4 py-3 font-bold">氏名（フリガナ）</th>
                        <th class="px-4 py-3 font-bold">所属</th>
                        <th class="px-4 py-3 font-bold">担当役割</th>
                        <th class="px-4 py-3 font-bold">出欠ステータス</th>
                        <th class="px-4 py-3 font-bold text-right">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($task->assignments as $assignment)
                    <tr class="hover:bg-slate-50/70 transition">
                        <!-- 氏名 -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="{{ route('staff.show', $assignment->staff_id) }}"
                                class="font-bold text-indigo-600 hover:underline">
                                {{ $assignment->staff?->name }}
                            </a>
                            <span class="text-[10px] text-slate-400 block font-normal">
                                {{ $assignment->staff?->kana }}
                            </span>
                        </td>

                        <!-- 所属 -->
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                            {{ $assignment->staff?->affiliation ?: '-' }}
                        </td>

                        <!-- 担当役割 -->
                        <td class="px-4 py-3 text-slate-800">
                            {{ $assignment->role_description ?: '一般担当' }}
                        </td>

                        <!-- 出欠ステータス & 変更フォーム -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            @can('update', $task)
                            <form action="{{ route('timetable-tasks.update-attendance', ['task' => $task, 'assignment' => $assignment]) }}"
                                method="POST" class="inline-flex items-center space-x-1">
                                @csrf
                                @method('PATCH')
                                <select name="attendance_status" data-auto-submit
                                    class="text-xs px-2 py-1 rounded border border-slate-300 bg-white font-medium focus:ring-1 focus:ring-indigo-500 cursor-pointer">
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
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @can('update', $task)
                            <div class="inline-flex items-center space-x-1.5">
                                <button type="button"
                                    data-action="open-swap-modal"
                                    data-staff-id="{{ $assignment->staff_id }}"
                                    data-staff-name="{{ $assignment->staff?->name ?? '不明' }}"
                                    data-staff-role="{{ $assignment->role_description ?? '担当' }}"
                                    class="px-2 py-1 {{ $assignment->attendance_status === 'absent' ? 'bg-amber-100 hover:bg-amber-200 text-amber-800 border-amber-300 font-bold animate-pulse' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200' }} border rounded text-[11px] transition flex items-center space-x-1 cursor-pointer"
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
                                        class="px-2 py-1 bg-white hover:bg-red-50 text-red-600 border border-slate-200 hover:border-red-200 rounded text-[11px] font-medium transition cursor-pointer">
                                        解除
                                    </button>
                                </form>
                            </div>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-slate-400">
                            <span class="text-3xl block mb-2">👥</span>
                            まだスタッフがアサインされていません。<br>
                            上のフォームからスタッフを選択して配置してください。
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- スタッフ交代・ワンタップ代替補充モーダル (CSP完全準拠・バニラJS) -->
        <div id="swapModal"
            class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-5 space-y-4 shadow-2xl border border-slate-200 text-left">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                        <span>🔄</span>
                        <span>スタッフ交代・ワンタップ代替補充</span>
                    </h3>
                    <button type="button" data-action="close-swap-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">✕</button>
                </div>

                <!-- 交代対象の表示 -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1">
                    <span class="text-slate-500 font-medium block">外れるスタッフ（交代元）:</span>
                    <div class="font-bold text-slate-800 text-sm flex items-center justify-between">
                        <span id="swapOldStaffName"></span>
                        <span id="swapOldStaffRole" class="text-xs text-slate-500 font-normal"></span>
                    </div>
                </div>

                <!-- 交代先スタッフの選択フォーム -->
                <form action="{{ route('timetable-tasks.swap-staff', $task) }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" id="swapOldStaffIdInput" name="old_staff_id" value="">

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">新しく入る代替スタッフを選択:</label>
                        <input type="text" id="swapSearchInput" placeholder="候補者を検索..."
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">

                        <div class="max-h-56 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100 bg-slate-50/50 mt-1">
                            @foreach($prioritizedStaff as $st)
                            @php
                                $stIsAssigned = $task->assignments->contains('staff_id', $st->id);
                            @endphp
                            <label class="swap-candidate-row flex items-center justify-between p-2.5 hover:bg-white cursor-pointer transition text-xs {{ $stIsAssigned ? 'hidden' : '' }}"
                                data-staff-id="{{ $st->id }}"
                                data-name="{{ $st->name }}"
                                data-kana="{{ $st->kana ?? '' }}"
                                data-affiliation="{{ $st->affiliation ?? '' }}"
                                data-is-assigned="{{ $stIsAssigned ? '1' : '0' }}">
                                <div class="flex items-center space-x-2 min-w-0">
                                    <input type="radio" name="new_staff_id" value="{{ $st->id }}" class="text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                    <div class="truncate">
                                        <span class="text-slate-900 font-bold">{{ $st->name }}</span>
                                        @if($st->affiliation)
                                        <span class="text-[10px] text-slate-400 ml-1">({{ $st->affiliation }})</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center space-x-1.5 shrink-0">
                                    @if($st->is_checked_in)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-sky-100 text-sky-800 border border-sky-300">着</span>
                                    @endif
                                    @if($st->priority_tier === 1)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">班推奨</span>
                                    @elseif($st->conflict_task !== null)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-300">⚠️ 重複中: {{ $st->conflict_task->title }}</span>
                                    @else
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-medium bg-slate-100 text-slate-600 border border-slate-200">空き</span>
                                    @endif
                                </div>
                            </label>
                            @endforeach

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
