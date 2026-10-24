<x-layouts.app :title="$staff->name . ' - スタッフ詳細'">
<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    <!-- ナビゲーション & 操作 -->
    <div class="flex items-center justify-between">
        <a href="{{ route('staff.index') }}"
            class="inline-flex items-center space-x-1 text-xs font-medium text-slate-500 hover:text-slate-800 transition">
            <span>←</span>
            <span>スタッフ一覧に戻る</span>
        </a>

        <div class="flex items-center space-x-2">
            <a href="{{ route('staff.print-card', $staff) }}" target="_blank"
                class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                <span>🖨️</span>
                <span>名札印刷</span>
            </a>

            @can('update', $staff)
            <a href="{{ route('staff.edit', $staff) }}"
                class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                <span>✏️</span>
                <span>編集する</span>
            </a>
            @endcan

            @can('delete', $staff)
            <form action="{{ route('staff.destroy', $staff) }}" method="POST" class="inline"
                data-confirm="スタッフ「{{ $staff->name }}」を削除しますか？&#10;（関連するタスクのアサインも解除されます）">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-xs font-bold transition flex items-center space-x-1 cursor-pointer">
                    <span>🗑️</span>
                    <span>削除</span>
                </button>
            </form>
            @endcan
        </div>
    </div>

    <!-- スタッフ基本情報カード -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 sm:p-8 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex items-center justify-between">
            <div>
                <span class="text-xs font-mono text-slate-400 block mb-1">
                    {{ $staff->kana ?: 'フリガナ未登録' }}
                </span>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    {{ $staff->name }}
                </h1>
            </div>

            <div>
                @if($staff->is_active)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    ● 稼働可能
                </span>
                @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200">
                    停止中
                </span>
                @endif
            </div>
        </div>

        <div class="p-6 sm:p-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- 所属団体 -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">🏢 所属団体・地域会等</span>
                    <div class="text-sm font-bold text-slate-900">
                        {{ $staff->affiliation ?: '未設定' }}
                    </div>
                </div>

                <!-- LINEアカウント情報 -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">💬 LINE連携</span>
                    <div class="flex items-center space-x-2">
                        @if($staff->line_picture_url)
                        <img src="{{ $staff->line_picture_url }}" class="w-6 h-6 rounded-full object-cover border border-slate-200" alt="">
                        @endif
                        <span class="text-sm font-bold text-slate-900">
                            {{ $staff->line_display_name ?: '未連携' }}
                        </span>
                    </div>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">
                        登録経路: {{ $staff->registered_via === 'self' ? 'LINEセルフ登録' : '本部登録' }}
                    </span>
                </div>

                <!-- 割当タスク数 & 受付状況 -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">📋 タスク & 当日受付</span>
                    <div class="text-sm font-bold text-slate-900">
                        {{ $staff->assignments->count() }} 件
                        @if($staff->is_checked_in)
                        <span class="ml-2 text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                            受付済 ({{ $staff->checked_in_at->format('H:i') }})
                        </span>
                        @else
                        <span class="ml-2 text-xs font-normal text-slate-400">未受付</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 専用マイタイムテーブルURL & QR -->
            <div class="p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h4 class="text-xs font-bold text-indigo-900 flex items-center space-x-1.5">
                            <span>📱</span>
                            <span>本人専用 マイタイムテーブル確認URL</span>
                        </h4>
                        <p class="text-[11px] text-indigo-700/80 mt-0.5">
                            パスワード不要でスタッフ本人がスマホから直接確認できる個別URLです。
                        </p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <form action="{{ route('staff.regenerate-token', $staff) }}" method="POST"
                              data-confirm="アクセストークンを再生成しますか？&#10;（古いURLは無効化されます）">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-600 border border-slate-300 rounded text-[11px] font-medium transition cursor-pointer">
                                🔄 URL再発行
                            </button>
                        </form>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <input type="text" readonly value="{{ $staff->my_shift_url }}"
                           class="flex-1 px-3 py-1.5 bg-white border border-indigo-200 rounded-lg text-xs font-mono text-slate-700">
                    <a href="{{ $staff->my_shift_url }}" target="_blank" rel="noopener noreferrer"
                       class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1">
                        <span>開く</span>
                        <span>↗</span>
                    </a>
                </div>
            </div>

            <!-- アサイン済みタスク一覧 -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center space-x-1.5">
                    <span>📅</span>
                    <span>担当タスク・運行シフト一覧</span>
                </h3>

                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                            <tr>
                                <th class="px-4 py-2.5 font-bold">運行日</th>
                                <th class="px-4 py-2.5 font-bold">時間帯</th>
                                <th class="px-4 py-2.5 font-bold">担当班</th>
                                <th class="px-4 py-2.5 font-bold">タスク名</th>
                                <th class="px-4 py-2.5 font-bold">役割</th>
                                <th class="px-4 py-2.5 font-bold">出欠状態</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($staff->assignments as $assignment)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-2.5 font-mono text-slate-700 whitespace-nowrap">
                                    {{ $assignment->task?->timetable?->target_date?->format('Y/m/d') ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 font-mono text-slate-600 whitespace-nowrap">
                                    {{ substr($assignment->task?->start_time ?? '', 0, 5) }} 〜 {{ substr($assignment->task?->end_time ?? '', 0, 5) }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        {{ $assignment->task?->lane ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 font-bold text-slate-900">
                                    {{ $assignment->task?->title ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 text-slate-600">
                                    {{ $assignment->role_description ?: '一般担当' }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    @php
                                        $statusClass = match($assignment->attendance_status) {
                                            'checked_in' => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold',
                                            'completed' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            'absent' => 'bg-red-50 text-red-600 border-red-200 line-through',
                                            default => 'bg-slate-100 text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] border {{ $statusClass }}">
                                        {{ $assignment->attendance_status_label }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                    現在アサインされているタスクはありません。
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
</x-layouts.app>
