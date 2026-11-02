<x-layouts.app title="タイムテーブル管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ヘッダー -->
    <x-page-header
        title="タイムテーブル管理"
        icon="⏱️"
        description="まつり開催日ごとの基本枠（運行日・開始終了時刻）および各日の作業タスク・人員動態を管理します。">
        @can('create', App\Models\Timetable::class)
        <x-slot:actions>
            <a href="{{ route('timetables.create') }}"
                class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                <span>➕</span>
                <span>新規タイムテーブルを登録</span>
            </a>
        </x-slot:actions>
        @endcan
    </x-page-header>

    <!-- ステータス絞り込みタブ -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-2">
        <a href="{{ route('timetables.index') }}"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ !request('status') ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            すべての運行日
        </a>
        @foreach($statuses as $key => $label)
        <a href="{{ route('timetables.index', ['status' => $key]) }}"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ request('status') === $key ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <!-- 運行日一覧テーブル -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-4 py-3 font-bold whitespace-nowrap">運行日</th>
                        <th class="px-4 py-3 font-bold">タイムテーブル名 / 概要</th>
                        <th class="px-4 py-3 font-bold whitespace-nowrap">運行時間枠</th>
                        <th class="px-4 py-3 font-bold whitespace-nowrap text-center">登録タスク</th>
                        <th class="px-4 py-3 font-bold whitespace-nowrap text-center">状態</th>
                        <th class="px-4 py-3 font-bold text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($timetables as $timetable)
                    <tr class="hover:bg-slate-50/70 transition">
                        <!-- 運行日 -->
                        <td class="px-4 py-3 whitespace-nowrap">
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

                        <!-- タイムテーブル名 / 概要 -->
                        <td class="px-4 py-3 min-w-[200px]">
                            <div class="font-bold text-slate-900 text-sm">
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

                        <!-- 運行時間枠 -->
                        <td class="px-4 py-3 font-mono text-slate-700 whitespace-nowrap">
                            {{ substr($timetable->start_time, 0, 5) }} 〜 {{ substr($timetable->end_time, 0, 5) }}
                        </td>

                        <!-- 登録タスク数 -->
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                {{ $timetable->tasks_count ?? 0 }} 件
                            </span>
                        </td>

                        <!-- 状態 -->
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @php
                                $statusBadge = match($timetable->status) {
                                    'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold animate-pulse',
                                    'completed' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    default => 'bg-blue-50 text-blue-700 border-blue-200',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] border {{ $statusBadge }}">
                                {{ $timetable->status_label }}
                            </span>
                        </td>

                        <!-- 操作 -->
                        <td class="px-4 py-3 text-right whitespace-nowrap space-x-1.5">
                            <!-- タイムテーブルリンク -->
                            <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}"
                                class="inline-flex items-center space-x-1 px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold transition">
                                <span>⏱️</span>
                                <span>タイムテーブル</span>
                            </a>

                            <!-- タスク一覧リンク -->
                            <a href="{{ route('timetables.show', $timetable) }}"
                                class="inline-flex items-center space-x-1 px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold transition">
                                <span>📋</span>
                                <span>タスク一覧</span>
                            </a>

                            <!-- 削除ボタン -->
                            @can('delete', $timetable)
                            <form action="{{ route('timetables.destroy', $timetable) }}" method="POST" class="inline"
                                data-confirm="タイムテーブル「{{ $timetable->name }}」を削除しますか？&#10;（関連するすべての作業タスクも削除されます）">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-2.5 py-1 bg-white hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-rose-600 rounded-lg text-[11px] font-medium transition cursor-pointer">
                                    削除
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                            <span class="text-3xl block mb-2">⏱️</span>
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
