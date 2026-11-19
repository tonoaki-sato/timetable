@if($timetable)
<div class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 overflow-x-auto scrollbar-none">
    <!-- 左側: 運行日切り替えセレクト -->
    <div class="flex items-center space-x-2 shrink-0">
        <span class="text-xs font-bold text-slate-700 whitespace-nowrap flex items-center space-x-1">
            <span>📅</span>
            <span>対象運行日:</span>
        </span>
        <select data-action="navigate-on-change"
            class="bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold py-1 pl-2 pr-7 text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer whitespace-nowrap">
            @if($timetable && $timetable->status === 'completed')
                <option value="{{ $targetRouteFor($timetable) }}" selected disabled>
                    {{ $timetable->target_date ? $timetable->target_date->format('m/d') . ' ' : '' }}{{ $timetable->name }} (運行完了)
                </option>
            @endif
            @forelse($timetablesList as $tt)
                <option value="{{ $targetRouteFor($tt) }}" {{ $timetable && $tt->id === $timetable->id ? 'selected' : '' }}>
                    {{ $tt->target_date ? $tt->target_date->format('m/d') . ' ' : '' }}{{ $tt->name }}
                    {{ $tt->status === 'active' ? '(運行中)' : '' }}
                </option>
            @empty
                @if(! $timetable || $timetable->status !== 'completed')
                    <option value="" disabled selected>選択可能な運行情報がありません</option>
                @endif
            @endforelse
        </select>
    </div>

    <!-- 右側: 5大機能タブ (横スクロール可能・各タブ whitespace-nowrap) -->
    <div class="flex items-center space-x-1 shrink-0 overflow-x-auto scrollbar-none flex-nowrap py-0.5">
        <a href="{{ route('timetables.show', $timetable) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ $active === 'show' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>ℹ️</span>
            <span>運行情報</span>
        </a>
        <a href="{{ route('timetables.lanes.index', $timetable) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ $active === 'lanes' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>🏷️</span>
            <span>担当班</span>
            @if(($timetable->lanes_count ?? 0) > 0)
                <span class="text-[10px] opacity-75 font-mono">({{ $timetable->lanes_count }})</span>
            @endif
        </a>
        <a href="{{ route('timetables.tasks.index', $timetable) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ $active === 'tasks' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>📋</span>
            <span>タスク台帳</span>
            @if(($timetable->tasks_count ?? 0) > 0)
                <span class="text-[10px] opacity-75 font-mono">({{ $timetable->tasks_count }})</span>
            @endif
        </a>
        <a href="{{ route('timetables.assignments', $timetable) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ in_array($active, ['assignments', 'timeline'], true) ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>👥</span>
            <span>人員配置</span>
        </a>
        <a href="{{ route('timetables.locations.index', $timetable) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ $active === 'locations' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>📍</span>
            <span>拠点</span>
            @if(($timetable->locations_count ?? 0) > 0)
                <span class="text-[10px] opacity-75 font-mono">({{ $timetable->locations_count }})</span>
            @endif
        </a>
        <a href="{{ route('timetables.logistics.index', $timetable) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ in_array($active, ['logistics', 'logistics-timeline', 'equipments'], true) ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>🚚</span>
            <span>搬出入</span>
            @if(($timetable->logistics_plans_count ?? 0) > 0)
                <span class="text-[10px] opacity-75 font-mono">({{ $timetable->logistics_plans_count }})</span>
            @endif
        </a>
        <a href="{{ route('timetables.layouts.index', $timetable) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ in_array($active, ['layouts', 'layouts-booths', 'layouts-facilities', 'vendors', 'goza-stores'], true) ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>🗺️</span>
            <span>配置計画</span>
        </a>
        <a href="{{ route('timetables.site-maps.editor', $timetable) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ $active === 'site-maps' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>🎨</span>
            <span>白地図</span>
        </a>
        <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ $active === 'matrix' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>⏱️</span>
            <span>進行表</span>
        </a>
        <a href="{{ route('timetables.print-assignments', $timetable) }}"
            class="px-2 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition text-slate-600 hover:text-slate-900 hover:bg-slate-100 flex items-center space-x-1"
            title="人員配置一覧表をPDFでダウンロード">
            <span>📄</span>
            <span>配置表PDF</span>
            <span class="text-[10px] opacity-60">↓</span>
        </a>
    </div>
</div>
@endif

