<x-layouts.app :title="$schedule->title . ' - 年間スケジュール詳細'">
<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

    @php
        $categoryBadgeClasses = match($schedule->category) {
            'committee' => 'bg-purple-50 text-purple-700 border-purple-200',
            'permit' => 'bg-amber-50 text-amber-700 border-amber-200',
            'briefing' => 'bg-sky-50 text-sky-700 border-sky-200',
            'operation' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };

        $statusBadgeClasses = match($schedule->status) {
            'planned' => 'bg-blue-50 text-blue-700 border-blue-200',
            'in_progress' => 'bg-amber-50 text-amber-800 border-amber-300 font-bold',
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'cancelled' => 'bg-slate-100 text-slate-500 border-slate-200 line-through',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    @endphp

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$schedule->title"
        icon="📋"
        :description="$schedule->start_date ? '実施日程: ' . $schedule->start_date->format('Y年m月d日') . ($schedule->end_date && !$schedule->end_date->isSameDay($schedule->start_date) ? ' 〜 ' . $schedule->end_date->format('Y年m月d日') : '') : null">
        <x-slot:breadcrumbs>
            <a href="{{ route('annual-schedules.index', ['year' => $schedule->target_year]) }}" class="hover:text-indigo-600 transition flex items-center space-x-1">
                <span>📋</span>
                <span>{{ $schedule->target_year }}年度 年間スケジュール一覧</span>
            </a>
            <span>/</span>
            <span class="text-slate-700 font-bold">{{ $schedule->title }}</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            <span class="bg-slate-900 text-white text-xs px-2.5 py-0.5 rounded font-mono font-bold">
                {{ $schedule->target_year }}年度
            </span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold border {{ $categoryBadgeClasses }}">
                {{ $schedule->category_label }}
            </span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium border {{ $statusBadgeClasses }}">
                {{ $schedule->status_label }}
            </span>
        </x-slot:badges>

        <x-slot:actions>
            @can('update', $schedule)
            <a href="{{ route('annual-schedules.edit', $schedule) }}"
                class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>✏️</span>
                <span>編集する</span>
            </a>
            @endcan

            @can('delete', $schedule)
            <form action="{{ route('annual-schedules.destroy', $schedule) }}" method="POST" class="inline"
                data-confirm="年間スケジュール「{{ $schedule->title }}」を削除しますか？&#10;この操作は取り消せません。">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs cursor-pointer">
                    <span>🗑️</span>
                    <span>削除</span>
                </button>
            </form>
            @endcan

            <a href="{{ route('annual-schedules.index', ['year' => $schedule->target_year]) }}"
                class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>←</span>
                <span>{{ $schedule->target_year }}年度 年間スケジュール一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- メイン詳細カード -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <!-- カードボディ -->
        <div class="p-6 sm:p-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- 実施日程 -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">📅 実施日程</span>
                    <div class="text-sm font-bold text-slate-900 font-mono">
                        {{ $schedule->start_date?->format('Y年m月d日') }}
                        @if($schedule->end_date && $schedule->end_date->ne($schedule->start_date))
                        <span class="text-slate-500 font-normal">〜</span>
                        {{ $schedule->end_date->format('Y年m月d日') }}
                        @endif
                    </div>
                </div>

                <!-- 実施場所 -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">📍 実施場所・会場</span>
                    <div class="text-sm font-bold text-slate-900">
                        {{ $schedule->location ?? '未指定' }}
                    </div>
                </div>

                <!-- 担当者 -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">👤 担当者</span>
                    @if($schedule->assignee)
                    <div class="text-sm font-bold text-slate-900">
                        {{ $schedule->assignee->name }}
                    </div>
                    @if($schedule->assignee->affiliation)
                    <span class="text-[11px] text-slate-500 block mt-0.5">{{ $schedule->assignee->affiliation }}</span>
                    @endif
                    @else
                    <div class="text-sm text-slate-400">未指定</div>
                    @endif
                </div>
            </div>

            <!-- 詳細内容 -->
            <div>
                <h3 class="text-xs font-bold text-slate-700 mb-2">📋 詳細内容・特記事項</h3>
                <div class="p-5 bg-slate-50/70 border border-slate-200 rounded-xl text-sm text-slate-800 leading-relaxed min-h-[100px] whitespace-pre-wrap">
                    {{ $schedule->description ?: '詳細情報・特記事項の登録はありません。' }}
                </div>
            </div>

            <!-- メタ情報フッター -->
            <div class="pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs text-slate-500">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 block">登録者</span>
                    <span class="font-medium text-slate-700">{{ $schedule->creator?->name ?? '不明' }}</span>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-slate-400 block">初回登録日時</span>
                    <span class="font-mono text-slate-700">{{ $schedule->created_at?->format('Y/m/d H:i') ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-slate-400 block">最終更新日時</span>
                    <span class="font-mono text-slate-700">{{ $schedule->updated_at?->format('Y/m/d H:i') ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>

</div>
</x-layouts.app>
