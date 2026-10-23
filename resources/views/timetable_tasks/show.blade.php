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
                    @if($task->is_satisfied)
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

    <!-- スタッフアサイン＆アテンド管理セクション (Phase 5) -->
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

        <!-- スタッフ新規割り当てフォーム -->
        @can('update', $task)
        <div class="p-5 bg-slate-50 border-b border-slate-200">
            <form action="{{ route('timetable-tasks.assign', $task) }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label for="staff_id" class="block text-xs font-bold text-slate-700 mb-1">
                        スタッフを選択 <span class="text-red-500">*</span>
                    </label>
                    <select id="staff_id" name="staff_id" required
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- スタッフを選択してください --</option>
                        @foreach($activeStaff as $staff)
                        <option value="{{ $staff->id }}">
                            {{ $staff->name }} ({{ $staff->kana }}) - {{ $staff->affiliation ?: '所属なし' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:w-64">
                    <label for="role_description" class="block text-xs font-bold text-slate-700 mb-1">
                        担当役割（任意）
                    </label>
                    <input type="text" id="role_description" name="role_description"
                        placeholder="例: チーフ、カラーコーン運搬、案内"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <button type="submit"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow transition cursor-pointer shrink-0">
                    ➕ タスクに配置する
                </button>
            </form>
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
                            <form action="{{ route('timetable-tasks.remove-staff', ['task' => $task, 'staff' => $assignment->staff_id]) }}"
                                method="POST" class="inline"
                                data-confirm="スタッフ「{{ $assignment->staff?->name }}」の割り当てを解除しますか？">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-2.5 py-1 bg-white hover:bg-red-50 text-red-600 border border-slate-200 hover:border-red-200 rounded text-[11px] font-medium transition cursor-pointer">
                                    アサイン解除
                                </button>
                            </form>
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
    </div>

</div>
</x-layouts.app>
