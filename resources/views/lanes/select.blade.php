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

        <div class="flex flex-wrap items-center gap-2">
            @can('create', App\Models\Timetable::class)
            <a href="{{ route('timetables.create') }}"
                class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1">
                <span>➕</span>
                <span>新規タイムテーブルを登録</span>
            </a>
            @endcan
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

    <!-- タイムテーブル選択カードグリッド -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($timetables as $timetable)
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
            <div class="bg-white rounded-2xl border {{ $timetable->status === 'active' ? 'border-indigo-400 ring-2 ring-indigo-500/20' : 'border-slate-200' }} shadow-sm overflow-hidden flex flex-col hover:border-indigo-400 hover:shadow-md transition group">
                <!-- カードヘッダー -->
                <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-mono font-bold text-indigo-600">
                                📅 {{ $timetable->target_date?->format('Y年m月d日') }}
                            </span>
                            @if($timetable->target_date)
                                <span class="text-[11px] font-bold text-slate-500">
                                    ({{ ['日','月','火','水','木','金','土'][$timetable->target_date->dayOfWeek] }})
                                </span>
                            @endif
                        </div>
                        <h2 class="font-bold text-base text-slate-900 mt-1 truncate group-hover:text-indigo-600 transition">
                            <a href="{{ route('timetables.lanes.index', $timetable) }}">
                                {{ $timetable->name }}
                            </a>
                        </h2>
                    </div>

                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] border {{ $statusBadge }} shrink-0">
                        @if($timetable->status === 'active')
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse mr-1"></span>
                        @endif
                        {{ $timetable->status_label }}
                    </span>
                </div>

                <!-- カードボディ -->
                <div class="p-5 flex-1 space-y-4">
                    <!-- 時間枠 -->
                    <div class="flex items-center justify-between text-xs text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                        <span class="text-[11px] text-slate-500 font-medium">⏰ 運行時間枠</span>
                        <span class="font-mono font-bold text-slate-800">
                            {{ substr($timetable->start_time, 0, 5) }} 〜 {{ substr($timetable->end_time, 0, 5) }}
                        </span>
                    </div>

                    <!-- レーン編成状況 -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">編成レーン数</span>
                            <span class="font-bold text-slate-800">
                                {{ $totalLanes }} <span class="text-[11px] font-normal text-slate-500">班</span>
                                @if($coreLanes > 0)
                                    <span class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.2 rounded ml-1 font-bold">
                                        主要 {{ $coreLanes }}
                                    </span>
                                @endif
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">現場リーダー設定</span>
                            @if($totalLanes === 0)
                                <span class="text-slate-400 text-xs">未編成</span>
                            @elseif($leadUnassignedCount === 0)
                                <span class="text-emerald-700 font-bold flex items-center space-x-1">
                                    <span>✅</span>
                                    <span>全 {{ $leadAssignedCount }} 班 確定</span>
                                </span>
                            @else
                                <span class="text-amber-700 font-bold flex items-center space-x-1">
                                    <span>⚠️</span>
                                    <span>{{ $leadAssignedCount }}/{{ $totalLanes }} 班 確定</span>
                                    <span class="text-[10px] text-slate-500 font-normal">({{ $leadUnassignedCount }}班 未設定)</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- 主要レーンのプレビューチップ -->
                    @if($lanes->isNotEmpty())
                    <div class="pt-2 border-t border-slate-100">
                        <div class="text-[10px] text-slate-400 font-bold mb-1.5">編成レーン例</div>
                        <div class="flex flex-wrap gap-1">
                            @foreach($lanes->take(5) as $l)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-700 border border-slate-200">
                                    <span class="mr-1">{{ $l->icon ?: '📋' }}</span>
                                    <span class="truncate max-w-[80px]">{{ $l->name }}</span>
                                </span>
                            @endforeach
                            @if($lanes->count() > 5)
                                <span class="text-[10px] text-slate-400 px-1 py-0.5">他 {{ $lanes->count() - 5 }} 班</span>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="pt-2 border-t border-slate-100 text-center py-2">
                        <span class="text-xs text-slate-400">担当班がまだ登録されていません</span>
                    </div>
                    @endif
                </div>

                <!-- カードフッター -->
                <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between gap-2">
                    <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}"
                        class="text-[11px] font-bold text-slate-600 hover:text-indigo-600 transition flex items-center space-x-1">
                        <span>⏱️</span>
                        <span>運行マトリクス</span>
                    </a>

                    <a href="{{ route('timetables.lanes.index', $timetable) }}"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1">
                        <span>担当班を管理する</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
        @endforeach
    </div>

</div>
</x-layouts.app>
