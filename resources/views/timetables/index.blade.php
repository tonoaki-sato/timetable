<x-layouts.app title="運行情報一覧">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ヘッダー -->
    <x-page-header
        title="運行情報一覧"
        icon="⏱️"
        description="まつり開催日ごとの基本枠（運行日・開始終了時刻）および各日の作業タスク・人員動態を管理します。">
        <x-slot:breadcrumbs>
            <span class="text-slate-700 font-bold truncate">⏱️ 運行情報一覧</span>
        </x-slot:breadcrumbs>

        @can('create', App\Models\Timetable::class)
        <x-slot:actions>
            <a href="{{ route('timetables.create') }}"
                class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0">
                <span>➕</span>
                <span>新規運行情報登録</span>
            </a>
        </x-slot:actions>
        @endcan
    </x-page-header>

    <!-- ステータス絞り込みタブ -->
    <div class="flex items-center space-x-1.5 border-b border-slate-200 pb-1.5 overflow-x-auto scrollbar-none flex-nowrap">
        <a href="{{ route('timetables.index') }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition {{ !request('status') ? 'bg-slate-900 text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            すべての運行日
        </a>
        @foreach($statuses as $key => $label)
        <a href="{{ route('timetables.index', ['status' => $key]) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition {{ request('status') === $key ? 'bg-slate-900 text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <!-- 運行日一覧テーブル -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 font-bold text-[11px] select-none">
                    <tr>
                        <th class="px-3.5 py-2 min-w-[200px]">タイムテーブル名 / 概要</th>
                        <th class="px-3.5 py-2 whitespace-nowrap">運行日</th>
                        <th class="px-3.5 py-2 whitespace-nowrap">運行時間枠</th>
                        <th class="px-3.5 py-2 whitespace-nowrap text-center">状態</th>
                        <th class="px-3.5 py-2 font-bold text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($timetables as $timetable)
                    <tr class="hover:bg-slate-50/70 transition">
                        <!-- タイムテーブル名 / 概要 -->
                        <td class="px-3.5 py-2.5 min-w-[200px]">
                            <div class="font-bold text-slate-900 text-xs sm:text-sm">
                                <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition">
                                    {{ $timetable->name }}
                                </a>
                            </div>
                            @if($timetable->description)
                            <div class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                {{ $timetable->description }}
                            </div>
                            @endif
                        </td>

                        <!-- 運行日 -->
                        <td class="px-3.5 py-2.5 whitespace-nowrap">
                            <div class="flex items-center space-x-1.5">
                                <span class="font-mono font-bold text-slate-800 text-xs">
                                    {{ $timetable->target_date?->format('Y/m/d') }}
                                </span>
                                @php
                                    $weekdayNames = ['日', '月', '火', '水', '木', '金', '土'];
                                    $weekday = $timetable->target_date ? $weekdayNames[$timetable->target_date->dayOfWeek] : '';
                                    $isWeekend = in_array($timetable->target_date?->dayOfWeek, [0, 6], true);
                                @endphp
                                <span class="text-[11px] font-bold {{ $isWeekend ? 'text-rose-600' : 'text-slate-500' }}">
                                    ({{ $weekday }})
                                </span>
                            </div>
                        </td>

                        <!-- 運行時間枠 -->
                        <td class="px-3.5 py-2.5 font-mono text-slate-700 whitespace-nowrap">
                            {{ substr($timetable->start_time, 0, 5) }} 〜 {{ substr($timetable->end_time, 0, 5) }}
                        </td>

                        <!-- 状態 -->
                        <td class="px-3.5 py-2.5 text-center whitespace-nowrap">
                            @php
                                $statusBadge = match($timetable->status) {
                                    'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold animate-pulse',
                                    'completed' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    default => 'bg-blue-50 text-blue-700 border-blue-200',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] border whitespace-nowrap {{ $statusBadge }}">
                                {{ $timetable->status_label }}
                            </span>
                        </td>

                        <!-- 操作 -->
                        <td class="px-3.5 py-2.5 text-right whitespace-nowrap space-x-1">
                            <!-- 編集リンク -->
                            @can('update', $timetable)
                            <a href="{{ route('timetables.edit', $timetable) }}"
                                class="inline-flex items-center space-x-1 px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition shadow-2xs whitespace-nowrap">
                                <span>✏️</span>
                                <span>編集</span>
                            </a>
                            @endcan

                            <!-- 担当班リンク -->
                            <a href="{{ route('timetables.lanes.index', $timetable) }}"
                                class="inline-flex items-center space-x-1 px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition shadow-2xs whitespace-nowrap">
                                <span>🏷️</span>
                                <span>担当班</span>
                            </a>

                            <!-- タスク台帳リンク -->
                            <a href="{{ route('timetables.tasks.index', $timetable) }}"
                                class="inline-flex items-center space-x-1 px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition shadow-2xs whitespace-nowrap">
                                <span>📋</span>
                                <span>タスク台帳</span>
                            </a>

                            <!-- 人員配置リンク -->
                            <a href="{{ route('timetables.staff-timeline', $timetable) }}"
                                class="inline-flex items-center space-x-1 px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition shadow-2xs whitespace-nowrap">
                                <span>👥</span>
                                <span>人員配置</span>
                            </a>

                            <!-- 進行表リンク -->
                            <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}"
                                class="inline-flex items-center space-x-1 px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition shadow-2xs whitespace-nowrap">
                                <span>⏱️</span>
                                <span>進行表</span>
                            </a>

                            <!-- 削除ボタン -->
                            @can('delete', $timetable)
                            <form action="{{ route('timetables.destroy', $timetable) }}" method="POST" class="inline"
                                data-confirm="運行情報「{{ $timetable->name }}」を削除しますか？&#10;（関連するすべての作業タスクも削除されます）">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center space-x-1 px-2.5 py-1 bg-white hover:bg-rose-50 text-rose-600 border border-slate-200 hover:border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer shadow-2xs whitespace-nowrap">
                                    <span>🗑️</span>
                                    <span>削除</span>
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                            <span class="text-2xl block mb-1">⏱️</span>
                            登録されている運行タイムテーブルはありません。
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</x-layouts.app>
