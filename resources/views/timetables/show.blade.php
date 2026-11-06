<x-layouts.app :title="$timetable->name . ' - 運行基本情報'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$timetable->name"
        icon="⏱️"
        :description="$timetable->description ?: '「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）の運行基本情報・規制時間帯および担当班サマリーです。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>運行情報一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">📅 {{ $timetable->name }}</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <!-- 運行情報一覧に戻る -->
            <a href="{{ route('timetables.index') }}"
                class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-2xs whitespace-nowrap shrink-0">
                <span>←</span>
                <span>運行情報一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー (日程切替・サブナビ) -->
    <x-timetable-context-bar :timetable="$timetable" active="show" />

    @php
        $weekdayNames = ['日', '月', '火', '水', '木', '金', '土'];
        $weekday = $timetable->target_date ? $weekdayNames[$timetable->target_date->dayOfWeek] : '';
        $isWeekend = in_array($timetable->target_date?->dayOfWeek, [0, 6], true);

        // 運行時間計算
        $startCarbon = \Carbon\Carbon::createFromFormat('H:i:s', strlen($timetable->start_time) === 5 ? $timetable->start_time . ':00' : $timetable->start_time);
        $endCarbon = \Carbon\Carbon::createFromFormat('H:i:s', strlen($timetable->end_time) === 5 ? $timetable->end_time . ':00' : $timetable->end_time);
        $diffMinutes = $startCarbon && $endCarbon ? $startCarbon->diffInMinutes($endCarbon) : 0;
        $hours = floor($diffMinutes / 60);
        $mins = $diffMinutes % 60;
        $durationLabel = $hours . '時間' . ($mins > 0 ? " {$mins}分" : '');

        // タスク集計
        $totalTasks = $timetable->tasks->count();
        $satisfiedTasks = $timetable->tasks->filter(fn($t) => $t->is_satisfied)->count();
        $shortageTasks = $totalTasks - $satisfiedTasks;
        $totalRequired = $timetable->tasks->sum('required_people_count');
        $totalAssigned = $timetable->tasks->sum(fn($t) => $t->assigned_count);
    @endphp

    <!-- 1. 運行基本枠カード -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 sm:p-5 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-100 gap-2">
            <div class="flex items-center space-x-2.5">
                <span class="text-xl">📅</span>
                <div>
                    <h2 class="text-base font-bold text-slate-900">運行基本情報</h2>
                    <p class="text-xs text-slate-500">まつり開催日の基本的な運用枠と設定ステータスです。</p>
                </div>
            </div>
            <div class="flex items-center space-x-2 shrink-0">
                @can('update', $timetable)
                <a href="{{ route('timetables.edit', $timetable) }}"
                    class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-2xs whitespace-nowrap shrink-0">
                    <span>✏️</span>
                    <span>基本情報を編集</span>
                </a>
                @endcan

                @php
                    $statusBadge = match($timetable->status) {
                        'active' => 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold',
                        'completed' => 'bg-slate-100 text-slate-600 border-slate-300',
                        default => 'bg-blue-50 text-blue-700 border-blue-300',
                    };
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold border {{ $statusBadge }}">
                    <span class="w-2 h-2 rounded-full mr-1.5 {{ $timetable->status === 'active' ? 'bg-emerald-500 animate-pulse' : ($timetable->status === 'completed' ? 'bg-slate-400' : 'bg-blue-500') }}"></span>
                    {{ $timetable->status_label }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 開催日 -->
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                <span class="text-[11px] font-bold text-slate-500">開催年月日</span>
                <div class="flex items-baseline space-x-1.5">
                    <span class="font-mono font-bold text-slate-900 text-base">
                        {{ $timetable->target_date?->format('Y年m月d日') }}
                    </span>
                    <span class="text-xs font-bold {{ $isWeekend ? 'text-rose-600' : 'text-slate-600' }}">
                        ({{ $weekday }})
                    </span>
                </div>
            </div>

            <!-- 運行時間帯 -->
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                <span class="text-[11px] font-bold text-slate-500">運行時間帯 (予定)</span>
                <div class="flex items-baseline space-x-1.5">
                    <span class="font-mono font-bold text-slate-900 text-base">
                        {{ substr($timetable->start_time, 0, 5) }} 〜 {{ substr($timetable->end_time, 0, 5) }}
                    </span>
                    <span class="text-[11px] text-slate-500 font-mono">
                        ({{ $durationLabel }})
                    </span>
                </div>
            </div>

            <!-- 登録担当班数 -->
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                <span class="text-[11px] font-bold text-slate-500">編成担当班数</span>
                <div>
                    <span class="font-mono font-bold text-slate-900 text-base">
                        {{ $timetable->lanes->count() }}
                    </span>
                    <span class="text-xs text-slate-600">班</span>
                    <span class="text-[11px] text-slate-400 ml-1">
                        (班長設定済: {{ $timetable->lanes->filter(fn($l) => $l->lead_staff_id !== null)->count() }}班)
                    </span>
                </div>
            </div>

            <!-- 登録者・日時 -->
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                <span class="text-[11px] font-bold text-slate-500">作成者・作成日時</span>
                <div class="text-xs text-slate-700 truncate">
                    👤 {{ $timetable->creator?->name ?? 'システム管理者' }}
                </div>
                <div class="text-[11px] text-slate-400 font-mono">
                    {{ $timetable->created_at?->format('Y/m/d H:i') }}
                </div>
            </div>
        </div>

        @if($timetable->description)
        <div class="pt-2">
            <span class="text-xs font-bold text-slate-700 block mb-1">概要・全体連絡事項</span>
            <div class="px-3 py-2 bg-slate-50/70 border border-slate-200 rounded-xl text-xs text-slate-700 whitespace-pre-wrap leading-relaxed">
                {{ $timetable->description }}
            </div>
        </div>
        @endif

        <!-- 道路の状況 & まつりの状況 (運行基本情報カード内に統合) -->
        <div class="pt-3 border-t border-slate-100">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- 道路の状況 -->
                <div class="bg-slate-50/70 rounded-xl border border-slate-200/80 p-4 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                        <h3 class="font-bold text-xs text-slate-900 flex items-center space-x-1.5">
                            <span>🚧</span>
                            <span>道路の状況（交通規制・通行止め）</span>
                        </h3>
                        <span class="text-[10px] px-2 py-0.5 rounded font-mono font-bold {{ !empty($timetable->road_periods) ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-500' }}">
                            {{ !empty($timetable->road_periods) ? count($timetable->road_periods) . '件の設定' : '終日規制なし' }}
                        </span>
                    </div>

                    @if(!empty($timetable->road_periods))
                    <div class="space-y-1.5">
                        @foreach($timetable->road_periods as $rp)
                        <div class="flex items-center justify-between p-2.5 bg-white rounded-lg border border-rose-100 text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="px-1.5 py-0.5 rounded font-mono font-bold bg-rose-100 text-rose-800 text-[10px]">
                                    {{ substr($rp['start_time'] ?? '', 0, 5) }} 〜 {{ substr($rp['end_time'] ?? '', 0, 5) }}
                                </span>
                                <strong class="text-rose-950 font-bold">{{ $rp['label'] ?? '通行止め' }}</strong>
                            </div>
                            <span class="text-[10px] text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                                {{ ($rp['status'] ?? 'closed') === 'closed' ? '車両通行止め' : $rp['status'] }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="py-4 text-center text-slate-400 text-xs">
                        <span>終日、車両通行止め規制はありません（通常交通）。</span>
                    </div>
                    @endif
                </div>

                <!-- まつりの状況 -->
                <div class="bg-slate-50/70 rounded-xl border border-slate-200/80 p-4 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                        <h3 class="font-bold text-xs text-slate-900 flex items-center space-x-1.5">
                            <span>🏮</span>
                            <span>まつりの状況（イベント進行状況）</span>
                        </h3>
                        <span class="text-[10px] px-2 py-0.5 rounded font-mono font-bold {{ !empty($timetable->festival_periods) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                            {{ !empty($timetable->festival_periods) ? count($timetable->festival_periods) . '件の設定' : '終日準備・待機' }}
                        </span>
                    </div>

                    @if(!empty($timetable->festival_periods))
                    <div class="space-y-1.5">
                        @foreach($timetable->festival_periods as $fp)
                        <div class="flex items-center justify-between p-2.5 bg-white rounded-lg border border-emerald-100 text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="px-1.5 py-0.5 rounded font-mono font-bold bg-emerald-100 text-emerald-800 text-[10px]">
                                    {{ substr($fp['start_time'] ?? '', 0, 5) }} 〜 {{ substr($fp['end_time'] ?? '', 0, 5) }}
                                </span>
                                <strong class="text-emerald-950 font-bold">{{ $fp['label'] ?? '開催中' }}</strong>
                            </div>
                            <span class="text-[10px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                {{ ($fp['status'] ?? 'active') === 'active' ? 'イベント進行中' : $fp['status'] }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="py-4 text-center text-slate-400 text-xs">
                        <span>終日、まつり本祭運行はありません（準備・片付け日等）。</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 3. 編成 & タスクサマリーカード (2カラム) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 担当班（レーン）編成サマリー -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-bold text-sm text-slate-900 flex items-center space-x-1.5">
                        <span>🏷️</span>
                        <span>担当班（10レーン）編成状況</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">班長および固定配置スタッフの編成状態です。</p>
                </div>
                <a href="{{ route('timetables.lanes.index', $timetable) }}"
                    class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition flex items-center space-x-1">
                    <span>編成を開く</span>
                    <span>→</span>
                </a>
            </div>

            <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto pr-1">
                @forelse($timetable->lanes as $lane)
                <div class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/70 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center text-xs font-bold text-slate-800">
                            <span class="mr-1">{{ $lane->icon ?: '📋' }}</span>
                            <span class="truncate">{{ $lane->name }}</span>
                        </span>
                        <span class="text-[10px] font-mono text-slate-500">
                            {{ $lane->fixedStaff->count() }}名固定
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-600 truncate flex items-center space-x-1">
                        <span class="text-slate-400 text-[10px]">班長:</span>
                        <strong class="font-medium {{ $lane->leadStaff ? 'text-indigo-700' : 'text-slate-400' }}">
                            {{ $lane->leadStaff ? $lane->leadStaff->name : '未設定' }}
                        </strong>
                    </div>
                </div>
                @empty
                <div class="col-span-2 py-6 text-center text-slate-400 text-xs">
                    担当班が登録されていません。「編成を開く」から標準10班を作成してください。
                </div>
                @endforelse
            </div>
        </div>

        <!-- タスク進捗 & 人員充足サマリー -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-bold text-sm text-slate-900 flex items-center space-x-1.5">
                        <span>📋</span>
                        <span>作業タスク・人員充足サマリー</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">登録作業タスクの人員アサイン状況です。</p>
                </div>
                <a href="{{ route('timetables.tasks.index', $timetable) }}"
                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                    <span>タスク管理へ</span>
                    <span>→</span>
                </a>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-500 block">登録タスク総数</span>
                    <strong class="text-xl font-bold font-mono text-slate-900">{{ $totalTasks }}</strong>
                    <span class="text-xs text-slate-500 ml-0.5">件</span>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-500 block">人員充足タスク</span>
                    <strong class="text-xl font-bold font-mono {{ $shortageTasks === 0 && $totalTasks > 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ $satisfiedTasks }} / {{ $totalTasks }}
                    </strong>
                    <span class="text-xs text-slate-500 ml-0.5">充足</span>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-500 block">必要人員数 (延べ)</span>
                    <strong class="text-xl font-bold font-mono text-slate-800">{{ $totalRequired }}</strong>
                    <span class="text-xs text-slate-500 ml-0.5">人</span>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-500 block">アサイン済人員数 (延べ)</span>
                    <strong class="text-xl font-bold font-mono text-indigo-700">{{ $totalAssigned }}</strong>
                    <span class="text-xs text-slate-500 ml-0.5">人</span>
                </div>
            </div>

            @if($shortageTasks > 0)
            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 flex items-center justify-between">
                <span class="font-bold flex items-center space-x-1">
                    <span>⚠️</span>
                    <span>{{ $shortageTasks }}件のタスクで人員が不足しています</span>
                </span>
                <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}" class="text-indigo-700 font-bold underline">
                    進行表で配置 →
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- 4. フッターエリア（削除等の管理者操作） -->
    @can('delete', $timetable)
    <div class="pt-4 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
        <div>
            <span>この運行タイムテーブルを削除すると、紐づくすべての作業タスクおよびレーン編成も削除されます。</span>
        </div>
        <form action="{{ route('timetables.destroy', $timetable) }}" method="POST"
            data-confirm="タイムテーブル「{{ $timetable->name }}」を削除しますか？&#10;（関連するすべての作業タスク、アサイン、レーンも削除されます）">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="px-4 py-2 bg-white hover:bg-rose-50 border border-rose-200 text-rose-600 rounded-xl text-xs font-bold transition cursor-pointer">
                🗑️ このタイムテーブルを削除する
            </button>
        </form>
    </div>
    @endcan

</div>
</x-layouts.app>
