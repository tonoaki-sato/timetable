<x-layouts.app title="担当班（レーン）管理 - タイムテーブルの選択">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ナビゲーション & ヘッダー -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <span>🏮 まつり運行</span>
                <span>/</span>
                <span>担当班（レーン）管理</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 flex items-center space-x-2">
                <span>🏷️</span>
                <span>担当班（レーン）管理</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                班編成や現場リーダー設定を行うタイムテーブル（日程）を選択してください。
            </p>
        </div>
    </div>

    <!-- アラートメッセージ -->
    @if(session('info'))
    <div class="p-4 bg-sky-50 border border-sky-200 text-sky-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>ℹ️</span>
        <span>{{ session('info') }}</span>
    </div>
    @endif

    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>✅</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- タイムテーブル一覧リスト（テーブル形式） -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- リストヘッダー -->
        <div class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-base">📋</span>
                <h2 class="font-bold text-sm text-slate-800">運行日別 担当班編成一覧</h2>
            </div>
            <span class="text-xs font-mono font-bold text-slate-600 bg-white px-2.5 py-1 rounded-lg border border-slate-200 shadow-2xs">
                全 {{ $timetables->count() }} 日程
            </span>
        </div>

        <!-- テーブル本体 -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-4 py-3.5 font-bold whitespace-nowrap">運行日程・名称</th>
                        <th class="px-4 py-3.5 font-bold whitespace-nowrap">運行時間枠</th>
                        <th class="px-4 py-3.5 font-bold whitespace-nowrap">編成レーン数</th>
                        <th class="px-4 py-3.5 font-bold whitespace-nowrap">現場リーダー設定状況</th>
                        <th class="px-4 py-3.5 font-bold whitespace-nowrap">編成レーン例</th>
                        <th class="px-4 py-3.5 font-bold text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($timetables as $timetable)
                    @php
                        $lanes = $timetable->lanes;
                        $totalLanes = $lanes->count();
                        $coreLanes = $lanes->where('is_core', true)->count();
                        $leadAssignedCount = $lanes->filter(fn($l) => $l->lead_staff_id !== null)->count();
                        $leadUnassignedCount = $totalLanes - $leadAssignedCount;

                        $statusBadge = match($timetable->status) {
                            'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold',
                            'completed' => 'bg-slate-100 text-slate-600 border-slate-200',
                            default => 'bg-blue-50 text-blue-700 border-blue-200',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition group">
                        <!-- 運行日程・名称 -->
                        <td class="px-4 py-3.5 min-w-[200px]">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="font-mono font-bold text-indigo-600 text-xs">
                                        📅 {{ $timetable->target_date?->format('Y年m月d日') }}
                                    </span>
                                    @if($timetable->target_date)
                                    <span class="text-[11px] font-bold text-slate-500">
                                        ({{ ['日','月','火','水','木','金','土'][$timetable->target_date->dayOfWeek] }})
                                    </span>
                                    @endif
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] border {{ $statusBadge }}">
                                        @if($timetable->status === 'active')
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse mr-1"></span>
                                        @endif
                                        {{ $timetable->status_label }}
                                    </span>
                                </div>
                                <div class="font-bold text-sm text-slate-900 group-hover:text-indigo-600 transition">
                                    <a href="{{ route('timetables.lanes.index', $timetable) }}">
                                        {{ $timetable->name }}
                                    </a>
                                </div>
                            </div>
                        </td>

                        <!-- 運行時間枠 -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <div class="inline-flex items-center space-x-1 text-slate-700 font-mono font-bold bg-slate-50 px-2 py-1 rounded-md border border-slate-100">
                                <span>⏰</span>
                                <span>{{ substr($timetable->start_time, 0, 5) }} 〜 {{ substr($timetable->end_time, 0, 5) }}</span>
                            </div>
                        </td>

                        <!-- 編成レーン数 -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <span class="font-bold text-slate-900 text-xs">
                                {{ $totalLanes }} <span class="font-normal text-slate-500 text-[11px]">班</span>
                            </span>
                            @if($coreLanes > 0)
                            <span class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded ml-1 font-bold">
                                主要 {{ $coreLanes }}
                            </span>
                            @endif
                        </td>

                        <!-- 現場リーダー設定状況 -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            @if($totalLanes === 0)
                                <span class="text-slate-400 text-xs">未編成</span>
                            @elseif($leadUnassignedCount === 0)
                                <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span>✅</span>
                                    <span>全 {{ $leadAssignedCount }} 班 確定</span>
                                </span>
                            @else
                                <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    <span>⚠️</span>
                                    <span>{{ $leadAssignedCount }}/{{ $totalLanes }} 班 確定</span>
                                    <span class="text-[10px] text-amber-600 font-normal">({{ $leadUnassignedCount }}班 未設定)</span>
                                </span>
                            @endif
                        </td>

                        <!-- 編成レーン例 -->
                        <td class="px-4 py-3.5">
                            @if($lanes->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-1 max-w-xs">
                                @foreach($lanes->take(4) as $l)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-700 border border-slate-200">
                                    <span class="mr-1">{{ $l->icon ?: '📋' }}</span>
                                    <span class="truncate max-w-[80px]">{{ $l->name }}</span>
                                </span>
                                @endforeach
                                @if($lanes->count() > 4)
                                <span class="text-[10px] text-slate-400 font-medium whitespace-nowrap">+他{{ $lanes->count() - 4 }}班</span>
                                @endif
                            </div>
                            @else
                            <span class="text-[11px] text-slate-400">担当班未登録</span>
                            @endif
                        </td>

                        <!-- 操作 -->
                        <td class="px-4 py-3.5 text-right whitespace-nowrap space-x-1.5">
                            <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}"
                                class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition inline-flex items-center space-x-1">
                                <span>⏱️</span>
                                <span>マトリクス</span>
                            </a>

                            <a href="{{ route('timetables.lanes.index', $timetable) }}"
                                class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-2xs transition inline-flex items-center space-x-1">
                                <span>担当班を管理</span>
                                <span>→</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-xs text-slate-500">
                            タイムテーブルが登録されていません。
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</x-layouts.app>
