<x-layouts.app :title="$staff->name . ' - スタッフ詳細'">
<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$staff->name"
        icon="👷"
        :description="($staff->kana ? 'フリガナ: ' . $staff->kana . ' ｜ ' : '') . '所属: ' . ($staff->affiliation ?: '一般・ボランティア')">
        <x-slot:breadcrumbs>
            <a href="{{ route('staff.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1">
                <span>👷</span>
                <span>スタッフ一覧</span>
            </a>
            <span>/</span>
            <span class="text-slate-700 font-bold">{{ $staff->name }}</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            @if($staff->is_active)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                ● 稼働可能
            </span>
            @else
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                休止中
            </span>
            @endif

            @if($staff->is_reissued)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                ⚠️ 再発行 {{ $staff->reissue_count }}回目
            </span>
            @endif
        </x-slot:badges>

        <x-slot:actions>
            <a href="{{ route('staff.print-card', $staff) }}" target="_blank"
                class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>🖨️</span>
                <span>名札印刷</span>
            </a>

            @can('update', $staff)
            <a href="{{ route('staff.edit', $staff) }}"
                class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
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
                    class="px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs cursor-pointer">
                    <span>🗑️</span>
                    <span>削除</span>
                </button>
            </form>
            @endcan

            <a href="{{ route('staff.index') }}"
                class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>←</span>
                <span>スタッフ一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- セキュリティ再発行完了バナー -->
    @if(session('reissued_success'))
    <div class="p-4 bg-amber-50 border-2 border-amber-300 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
        <div class="flex items-start space-x-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <h4 class="text-sm font-bold text-amber-900">名札トークンのセキュリティ再発行が完了しました</h4>
                <p class="text-xs text-amber-800 mt-0.5">
                    以前の古いQRコード・URLはすべて無効化（404）されました。新しく発行された名札カードを印刷してスタッフにお渡しください。
                </p>
            </div>
        </div>
        <a href="{{ route('staff.print-card', $staff) }}" target="_blank"
           class="inline-flex items-center justify-center px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-sm transition whitespace-nowrap space-x-1.5 cursor-pointer">
            <span>🖨️</span>
            <span>新名札カードを印刷する</span>
            <span>↗</span>
        </a>
    </div>
    @endif

    <!-- スタッフ基本情報カード -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="flex items-center space-x-2">
                @if($staff->is_reissued)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                    ⚠️ 再発行 {{ $staff->reissue_count }}回目 ({{ $staff->token_reissued_at?->format('m/d H:i') }})
                </span>
                @endif

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

                <!-- 登録経路情報 -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">🏷️ 登録情報</span>
                    <div class="text-sm font-bold text-slate-900">
                        {{ $staff->registered_via === 'self' ? 'Webセルフ登録' : '本部登録' }}
                    </div>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">
                        登録日: {{ $staff->created_at?->format('Y/m/d') ?? '-' }}
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

            <!-- 専用マイタイムテーブルURL & セキュリティ再発行 -->
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

                    @can('update', $staff)
                    <div class="flex items-center space-x-2">
                        <form action="{{ route('staff.regenerate-token', $staff) }}" method="POST"
                              data-confirm="スタッフ「{{ $staff->name }}」の名札紛失・セキュリティ再発行を実行しますか？&#10;&#10;【注意】落とした名札の古いQRコードは即時に無効化（404）され、新しい名札が発行されます。">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 cursor-pointer">
                                <span>⚠️</span>
                                <span>名札紛失・セキュリティ再発行</span>
                            </button>
                        </form>
                    </div>
                    @endcan
                </div>

                <div class="flex items-center space-x-2">
                    <input type="text" id="staffShiftUrlInput" readonly value="{{ $staff->my_shift_url }}"
                           class="flex-1 px-3 py-1.5 bg-white border border-indigo-200 rounded-lg text-xs font-mono text-slate-700">
                    <button type="button" id="copyShiftUrlBtn"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 cursor-pointer">
                        <span>📋</span>
                        <span id="copyShiftUrlBtnText">URLコピー</span>
                    </button>
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

<!-- 外部JS（CSP準拠） -->
<script src="{{ asset('js/staff-management.js') }}" defer></script>
</x-layouts.app>
