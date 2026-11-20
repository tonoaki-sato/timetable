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
            <x-ui.button variant="header-action" id="btn-open-create-timetable" type="button" data-store-url="{{ route('timetables.store') }}">
                <span>➕</span>
                <span>新規運行情報登録</span>
            </x-ui.button>
        </x-slot:actions>
        @endcan
    </x-page-header>

    <!-- ステータス絞り込みトグルフィルターバー -->
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-700 flex items-center mr-1">
                <span>🔍 絞り込み:</span>
            </span>

            <!-- トグルボタン群 -->
            <div class="flex flex-wrap items-center gap-1.5" id="timetable-filter-chips">
                <!-- すべての運行日 ボタン -->
                <button type="button"
                    id="chip-status-all"
                    data-status="all"
                    class="timetable-status-chip inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border transition whitespace-nowrap shrink-0 cursor-pointer opacity-40 bg-slate-100 text-slate-600 border-slate-300">
                    <span class="chip-icon mr-1 text-[10px] text-slate-400 font-bold">✕</span>
                    <span>すべての運行日</span>
                </button>

                <!-- 準備中 ボタン (デフォルトON) -->
                <button type="button"
                    id="chip-status-draft"
                    data-status="draft"
                    class="timetable-status-chip inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border transition whitespace-nowrap shrink-0 cursor-pointer bg-white text-indigo-700 border-indigo-400 shadow-2xs">
                    <span class="chip-icon mr-1 text-[10px] text-emerald-600 font-bold">✔</span>
                    <span>準備中</span>
                </button>

                <!-- 運行中 ボタン (デフォルトON) -->
                <button type="button"
                    id="chip-status-active"
                    data-status="active"
                    class="timetable-status-chip inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border transition whitespace-nowrap shrink-0 cursor-pointer bg-white text-indigo-700 border-indigo-400 shadow-2xs">
                    <span class="chip-icon mr-1 text-[10px] text-emerald-600 font-bold">✔</span>
                    <span>運行中</span>
                </button>

                <!-- 運行完了 ボタン (デフォルトOFF) -->
                <button type="button"
                    id="chip-status-completed"
                    data-status="completed"
                    class="timetable-status-chip inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border transition whitespace-nowrap shrink-0 cursor-pointer opacity-40 bg-slate-100 text-slate-600 border-slate-300">
                    <span class="chip-icon mr-1 text-[10px] text-slate-400 font-bold">✕</span>
                    <span>運行完了</span>
                </button>
            </div>
        </div>

        <!-- 件数インジケータ -->
        <div class="flex items-center space-x-2 shrink-0 text-xs text-slate-500 font-mono">
            <span id="timetable-count-indicator">
                全 {{ $timetables->count() }} 件中 <span class="font-bold text-slate-700" id="visible-timetable-count">{{ $timetables->filter(fn($t) => in_array($t->status, ['draft', 'active'], true))->count() }}</span> 件表示中
            </span>
        </div>
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
                <tbody class="divide-y divide-slate-100" id="timetables-table-body">
                    @forelse($timetables as $timetable)
                    @php
                        $isInitiallyVisible = in_array($timetable->status, ['draft', 'active'], true);
                    @endphp
                    <tr class="timetable-row hover:bg-slate-50/70 transition {{ $isInitiallyVisible ? '' : 'hidden' }}"
                        data-status="{{ $timetable->status }}"
                        id="timetable-row-{{ $timetable->id }}">
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

                                <!-- 管理メニュー (プルダウンメニュー: 担当班・拠点・搬出入・タスク台帳・人員配置) -->
                                <div class="relative inline-block text-left timetable-menu-container">
                                    <button type="button"
                                        class="timetable-menu-trigger inline-flex items-center space-x-1 px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition cursor-pointer select-none whitespace-nowrap shadow-2xs"
                                        aria-haspopup="true"
                                        aria-expanded="false">
                                        <span>管理メニュー</span>
                                        <span class="text-[10px] text-slate-500">▾</span>
                                    </button>
                                    <div class="timetable-menu-dropdown hidden fixed w-44 bg-white border border-slate-200 rounded-xl shadow-xl z-50 py-1 divide-y divide-slate-100">
                                        <div class="py-1">
                                            <a href="{{ route('timetables.lanes.index', $timetable) }}"
                                                class="flex items-center space-x-2 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition">
                                                <span>🏷️</span>
                                                <span class="font-medium">担当班</span>
                                            </a>
                                            <a href="{{ route('timetables.locations.index', $timetable) }}"
                                                class="flex items-center space-x-2 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition">
                                                <span>📍</span>
                                                <span class="font-medium">拠点</span>
                                            </a>
                                            <a href="{{ route('timetables.logistics.index', $timetable) }}"
                                                class="flex items-center space-x-2 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition">
                                                <span>🚚</span>
                                                <span class="font-medium">搬出入</span>
                                            </a>
                                            <a href="{{ route('timetables.tasks.index', $timetable) }}"
                                                class="flex items-center space-x-2 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition">
                                                <span>📋</span>
                                                <span class="font-medium">タスク台帳</span>
                                            </a>
                                            <a href="{{ route('timetables.staff-timeline', $timetable) }}"
                                                class="flex items-center space-x-2 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition">
                                                <span>👥</span>
                                                <span class="font-medium">人員配置</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- 複製ボタン (モーダル起動) -->
                                @can('create', App\Models\Timetable::class)
                                <button type="button"
                                    class="btn-clone-timetable inline-flex items-center space-x-1 px-2.5 py-1 bg-slate-50 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-300 hover:border-indigo-300 rounded-lg text-xs font-bold transition whitespace-nowrap cursor-pointer shadow-2xs"
                                    title="この運行情報をもとに新しく運行情報を作成する"
                                    data-source-id="{{ $timetable->id }}"
                                    data-store-url="{{ route('timetables.store') }}">
                                    <span>📋</span>
                                    <span>複製</span>
                                </button>
                                @endcan

                                <!-- 編集ボタン (モーダル起動) -->
                                @can('update', $timetable)
                                <button type="button"
                                    class="btn-edit-timetable inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer shadow-2xs"
                                    title="運行情報を編集"
                                    data-timetable-id="{{ $timetable->id }}"
                                    data-update-url="{{ route('timetables.update', $timetable) }}"
                                    data-name="{{ $timetable->name }}"
                                    data-target-date="{{ $timetable->target_date?->format('Y-m-d') }}"
                                    data-status="{{ $timetable->status }}"
                                    data-start-time="{{ substr($timetable->start_time, 0, 5) }}"
                                    data-end-time="{{ substr($timetable->end_time, 0, 5) }}"
                                    data-description="{{ $timetable->description }}"
                                    data-road-periods='@json($timetable->road_periods ?? [])'
                                    data-festival-periods='@json($timetable->festival_periods ?? [])'>
                                    <span>✏️</span>
                                    <span>編集</span>
                                </button>
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
                    <!-- 絞り込み結果が0件の場合のエンプティステート行 -->
                    <tr id="timetable-filter-empty-state" class="hidden">
                        <td colspan="5" class="py-8">
                            <x-ui.empty-state icon="🔍" title="選択したステータスに該当する運行情報はありません。" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 運行情報 登録・編集・複製モーダル -->
    @include('timetables._form_modal', [
        'statuses' => $statuses,
        'cloneSourceOptions' => $cloneSourceOptions ?? collect(),
        'redirectTo' => 'index',
    ])

</div>

@vite(['resources/js/timetables.js'])
</x-layouts.app>
