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
            <x-ui.button variant="header-action" :href="route('timetables.create')">
                <span>➕</span>
                <span>新規運行情報登録</span>
            </x-ui.button>
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
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600 select-none">
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
                                <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 hover:underline transition">
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
                                <span class="text-[11px] font-bold {{ $timetable->is_weekend ? 'text-red-600' : 'text-slate-500' }}">
                                    ({{ $timetable->weekday_label }})
                                </span>
                            </div>
                        </td>

                        <!-- 運行時間枠 -->
                        <td class="px-3.5 py-2.5 font-mono text-slate-700 whitespace-nowrap">
                            {{ substr($timetable->start_time, 0, 5) }} 〜 {{ substr($timetable->end_time, 0, 5) }}
                        </td>

                        <!-- 状態 -->
                        <td class="px-3.5 py-2.5 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] border whitespace-nowrap {{ $timetable->status_badge_class }} {{ $timetable->is_active ? 'animate-pulse' : '' }}">
                                {{ $timetable->status_label }}
                            </span>
                        </td>

                        <!-- 操作 -->
                        <td class="px-3.5 py-2.5 text-right whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1 flex-wrap">
                                <!-- 進行表リンク -->
                                <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}"
                                    class="inline-flex items-center space-x-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition whitespace-nowrap">
                                    <span>⏱️</span>
                                    <span>進行表</span>
                                </a>

                                <!-- 管理メニュー (担当班・拠点・搬出入・タスク台帳・人員配置) -->
                                <details class="inline-block align-middle text-left">
                                    <summary class="list-none inline-flex items-center space-x-1 px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition cursor-pointer select-none whitespace-nowrap">
                                        <span>管理メニュー</span>
                                        <span class="text-[10px]">▾</span>
                                    </summary>
                                    <div class="inline-flex flex-wrap items-center gap-1 mt-1.5">
                                        <a href="{{ route('timetables.lanes.index', $timetable) }}"
                                            class="inline-flex items-center space-x-1 px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition shadow-2xs whitespace-nowrap">
                                            <span>🏷️</span>
                                            <span>担当班</span>
                                        </a>
                                        <a href="{{ route('timetables.locations.index', $timetable) }}"
                                            class="inline-flex items-center space-x-1 px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition shadow-2xs whitespace-nowrap">
                                            <span>📍</span>
                                            <span>拠点</span>
                                        </a>
                                        <a href="{{ route('timetables.logistics.index', $timetable) }}"
                                            class="inline-flex items-center space-x-1 px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition shadow-2xs whitespace-nowrap">
                                            <span>🚚</span>
                                            <span>搬出入</span>
                                        </a>
                                        <a href="{{ route('timetables.tasks.index', $timetable) }}"
                                            class="inline-flex items-center space-x-1 px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition shadow-2xs whitespace-nowrap">
                                            <span>📋</span>
                                            <span>タスク台帳</span>
                                        </a>
                                        <a href="{{ route('timetables.staff-timeline', $timetable) }}"
                                            class="inline-flex items-center space-x-1 px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition shadow-2xs whitespace-nowrap">
                                            <span>👥</span>
                                            <span>人員配置</span>
                                        </a>
                                    </div>
                                </details>

                                <!-- 編集リンク -->
                                @can('update', $timetable)
                                <x-ui.button variant="edit" size="xs" :href="route('timetables.edit', $timetable)">
                                    <span>✏️</span>
                                    <span>編集</span>
                                </x-ui.button>
                                @endcan

                                <!-- 削除ボタン -->
                                @can('delete', $timetable)
                                <form action="{{ route('timetables.destroy', $timetable) }}" method="POST" class="inline"
                                    data-confirm="運行情報「{{ $timetable->name }}」を削除しますか？&#10;（関連するすべての作業タスクも削除されます）">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button variant="delete" size="xs" type="submit">
                                        <span>🗑️</span>
                                        <span>削除</span>
                                    </x-ui.button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <x-ui.empty-state icon="⏱️" title="登録されている運行タイムテーブルはありません。" />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</x-layouts.app>
