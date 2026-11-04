<x-layouts.app :title="$schedule->title . ' - スケジュール詳細'">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-5 space-y-3 sm:space-y-4">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$schedule->title"
        icon="📅"
        :description="$schedule->formatted_datetime . ($schedule->location ? ' ｜ 場所: ' . $schedule->location : '')">
        <x-slot:breadcrumbs>
            <a href="{{ route('schedules.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1">
                <span>📅</span>
                <span>スケジュール管理一覧</span>
            </a>
            <span>/</span>
            <span class="text-slate-700 font-bold truncate max-w-xs">{{ $schedule->title }}</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            @php
                $badgeMeta = match($schedule->category) {
                    'committee' => ['icon' => '🤝', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
                    'permit' => ['icon' => '📑', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
                    'briefing' => ['icon' => '📢', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
                    'operation' => ['icon' => '🎪', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                    default => ['icon' => '📌', 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
                };

                $statusClass = match($schedule->status) {
                    'planned' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'in_progress' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'cancelled' => 'bg-slate-100 text-slate-500 border-slate-200',
                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                };
            @endphp
            <span class="px-2.5 py-0.5 rounded text-xs font-bold border flex items-center space-x-1 {{ $badgeMeta['class'] }}">
                <span>{{ $badgeMeta['icon'] }}</span>
                <span>{{ $schedule->category_label }}</span>
            </span>

            <span class="px-2.5 py-0.5 rounded text-xs font-bold border {{ $statusClass }}">
                {{ $schedule->status_label }}
            </span>

            @if($schedule->is_today)
            <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                本日開催
            </span>
            @endif

            <span class="text-xs text-slate-400 font-mono">
                ID: #{{ $schedule->id }}
            </span>
        </x-slot:badges>

        <x-slot:actions>
            @can('update', $schedule)
            <a href="{{ route('schedules.edit', $schedule) }}"
                class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>✏️</span>
                <span>編集する</span>
            </a>
            @endcan

            @if(! $schedule->is_linked_to_timetable)
            @can('delete', $schedule)
            <form action="{{ route('schedules.destroy', $schedule) }}" method="POST" class="inline"
                data-confirm="予定「{{ $schedule->title }}」を削除しますか？&#10;この操作は取り消せません。">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-2xs cursor-pointer">
                    <span>🗑️</span>
                    <span>削除</span>
                </button>
            </form>
            @endcan
            @endif
        </x-slot:actions>
    </x-page-header>

    <!-- 2カラムメイングリッド（PC表示でスクロールフリー配置） -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">

        <!-- 左カラム: 予定詳細情報（col-span-7） -->
        <div class="lg:col-span-7 space-y-3">
            @if($schedule->is_linked_to_timetable)
            <div class="bg-gradient-to-r from-rose-50 to-indigo-50 border border-rose-200/80 rounded-xl p-3 sm:p-3.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                <div class="flex items-center space-x-2.5">
                    <span class="text-lg p-1 bg-white rounded-lg shadow-2xs border border-rose-200 shrink-0">🏮</span>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-xs font-bold text-slate-900">タイムテーブル連動</h3>
                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-100 text-rose-800">公式運行枠</span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-0.5">
                            運行表と自動同期されています。班編成や削除は運行管理から行えます。
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-1.5 shrink-0">
                    @if($schedule->timetable_id)
                    <a href="{{ route('timetables.show', $schedule->timetable_id) }}"
                        class="px-2.5 py-1 bg-white hover:bg-slate-50 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition shadow-2xs flex items-center space-x-1">
                        <span>⏱️</span>
                        <span>運行表</span>
                    </a>
                    <a href="{{ route('timetable.index', ['timetable_id' => $schedule->timetable_id]) }}"
                        class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition shadow-2xs flex items-center space-x-1">
                        <span>📊</span>
                        <span>進行マトリクス</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- メイン情報カード -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 space-y-3.5">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <!-- 日時・期間 -->
                        <div class="bg-slate-50 px-2.5 py-2 rounded-lg border border-slate-100">
                            <span class="text-[11px] font-bold text-slate-500 block mb-0.5">📅 開催日時・期間</span>
                            <div class="text-xs sm:text-sm font-bold text-slate-900 font-mono">
                                {{ $schedule->formatted_datetime }}
                            </div>
                        </div>

                        <!-- 場所 -->
                        <div class="bg-slate-50 px-2.5 py-2 rounded-lg border border-slate-100">
                            <span class="text-[11px] font-bold text-slate-500 block mb-0.5">📍 開催場所</span>
                            <div class="text-xs sm:text-sm font-bold text-slate-900 truncate">
                                {{ $schedule->location ?: '未定' }}
                            </div>
                        </div>

                        <!-- 担当スタッフ -->
                        <div class="bg-slate-50 px-2.5 py-2 rounded-lg border border-slate-100 flex flex-col justify-between">
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 block mb-0.5">👤 担当・主催スタッフ</span>
                                @if($schedule->assignee)
                                <div class="text-xs sm:text-sm font-bold text-slate-900">
                                    {{ $schedule->assignee->name }}
                                </div>
                                @if($schedule->assignee->affiliation)
                                <span class="text-[10px] text-slate-500 block truncate">{{ $schedule->assignee->affiliation }}</span>
                                @endif
                                @else
                                <span class="text-xs text-slate-400 italic">未設定</span>
                                @can('update', $schedule)
                                <a href="{{ route('schedules.edit', $schedule) }}" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-bold block mt-0.5">
                                    ✏️ 設定する
                                </a>
                                @endcan
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- 議題・内容 -->
                    <div>
                        <h3 class="text-xs font-bold text-slate-700 mb-1">📝 詳細説明・議題・アジェンダ</h3>
                        <div class="px-2.5 py-2 bg-slate-50/70 border border-slate-200 rounded-lg text-xs sm:text-sm text-slate-800 leading-relaxed whitespace-pre-wrap">
                            {{ $schedule->description ?: '詳細説明の登録はありません。' }}
                        </div>
                    </div>

                    <!-- メタフッター -->
                    <div class="pt-2.5 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
                        <div>
                            <span>登録ユーザー: </span>
                            <span class="font-medium text-slate-600">{{ $schedule->creator?->name ?? 'システム' }}</span>
                        </div>
                        <div>
                            <span>登録日時: </span>
                            <span class="font-mono text-slate-600">{{ $schedule->created_at?->format('Y/m/d H:i') ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 右カラム: LINE告知用テキストボックス（col-span-5） -->
        <div class="lg:col-span-5">
            <div class="bg-slate-900 text-white rounded-xl shadow-md border border-slate-800 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800/80 flex items-center justify-between gap-3">
                    <div class="flex items-center space-x-2">
                        <span class="text-base sm:text-lg">💬</span>
                        <div>
                            <h2 class="text-xs sm:text-sm font-bold text-white tracking-tight flex items-center space-x-1.5">
                                <span>共有告知テキスト</span>
                                <span class="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-1.5 py-0.2 rounded font-mono">LINE告知用</span>
                            </h2>
                        </div>
                    </div>

                    <button type="button" data-action="copy-current-line"
                        id="copyLineBtn"
                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5 cursor-pointer shrink-0">
                        <span>📋</span>
                        <span id="copyBtnLabel">文面をコピー</span>
                    </button>
                </div>

                <div class="p-3.5 sm:p-4">
                    <div class="bg-slate-950/90 px-3 py-2.5 rounded-lg border border-slate-800/80 font-mono text-xs sm:text-sm text-slate-200 whitespace-pre-wrap leading-relaxed select-all" id="lineTextBox">{{ $schedule->share_text }}</div>
                    <p class="text-[11px] text-slate-400 mt-2 flex items-center space-x-1">
                        <span>💡</span>
                        <span>文面をクリックで全選択、または右上のボタンでコピーできます</span>
                    </p>
                </div>
            </div>
        </div>

    </div>

</div>

@vite(['resources/js/schedules.js'])
</x-layouts.app>
