<x-layouts.app :title="$task->title . ' - 作業タスク詳細'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    @php
        $laneRel = $task->laneRelation;
        $laneName = $laneRel?->name ?? $task->lane;
        $laneIcon = $laneRel ? ($laneRel->icon ?: '🏷️') : '🏷️';
        $fullTitle = ($laneName ? $laneName . ' ' : '') . $task->title;

        $taskStatusClass = match($task->status) {
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'in_progress' => 'bg-amber-50 text-amber-800 border-amber-300 font-bold',
            'cancelled' => 'bg-slate-100 text-slate-400 border-slate-200 line-through',
            default => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    @endphp

    <!-- ナビゲーション & ヘッダー (タイトル横バッジなし・戻るリンクのみ) -->
    <x-page-header
        :title="$fullTitle"
        icon="📋"
        :description="'「' . ($task->timetable?->name ?? '運行タイムテーブル') . '」（' . ($task->timetable?->target_date?->format('Y年m月d日') ?? '-') . '）の作業タスク詳細情報です。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>タイムテーブル一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.show', $task->timetable_id) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>📅</span>
                <span>{{ $task->timetable?->name }}</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.tasks.index', $task->timetable) }}" class="hover:text-indigo-600 transition truncate">
                📋 タスク一覧
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate max-w-xs">📋 {{ $fullTitle }}</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.tasks.index', $task->timetable) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>タスク一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー (日程切替・サブナビ) -->
    <x-timetable-context-bar :timetable="$task->timetable" active="tasks" />

    <!-- メインコンテンツ: 作業タスク詳細情報カード (コンテキストバーの幅に一致) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        <!-- カードヘッダー -->
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
            <h2 class="font-bold text-sm sm:text-base text-slate-900 flex items-center space-x-2">
                <span>📋</span>
                <span>タスク基本情報・作業指示</span>
            </h2>

            <!-- カードヘッダー内にステータス・特記バッジを集約 -->
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs border {{ $taskStatusClass }}">
                    {{ $task->status_label }}
                </span>
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
            </div>
        </div>

        <div class="p-5 sm:p-6 space-y-6">
            <!-- 属性スペックグリッド -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <!-- 担当班 -->
                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">🏷️ 担当班</span>
                    <span class="font-bold text-slate-800 flex items-center space-x-1 text-sm">
                        <span>{{ $laneIcon }}</span>
                        <span>{{ $task->lane ?: '未設定' }}</span>
                    </span>
                    @if($laneRel?->leadStaff)
                    <span class="text-[11px] text-slate-500 block mt-1">
                        班長: {{ $laneRel->leadStaff->name }}
                    </span>
                    @endif
                </div>

                <!-- 時間帯 -->
                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">⏱️ 実施時間帯</span>
                    <div class="font-bold text-slate-800 font-mono text-sm">
                        {{ substr($task->start_time, 0, 5) }} 〜 {{ substr($task->end_time, 0, 5) }}
                    </div>
                </div>

                <!-- 実施場所 -->
                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">📍 実施場所</span>
                    <div class="font-bold text-slate-800 text-sm truncate" title="{{ $task->location }}">
                        {{ $task->location ?: '未指定' }}
                    </div>
                </div>

                <!-- 必要人員 -->
                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">👥 必要人員</span>
                    <div class="font-bold text-slate-800 text-sm">
                        {{ $task->required_people_label }}
                    </div>
                </div>
            </div>

            <!-- 作業内容・手順指示エリア -->
            <div>
                <h3 class="text-xs font-bold text-slate-700 mb-2 flex items-center space-x-1">
                    <span>📝</span>
                    <span>作業内容・手順指示</span>
                </h3>
                @if($task->content)
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">
                    {{ $task->content }}
                </div>
                @else
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-400 italic">
                    作業内容の記載はありません。
                </div>
                @endif
            </div>
        </div>
    </div>

</div>
</x-layouts.app>
