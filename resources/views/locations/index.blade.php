<x-layouts.app :title="$timetable->name . ' 拠点一覧'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$timetable->name . ' 拠点一覧'"
        icon="📍"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）における番所・駐車場・コミュニティスペース等の拠点設定と、利用可能時間帯を管理します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>運行情報一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>📅</span>
                <span>{{ $timetable->name }}</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">📍 拠点一覧</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button variant="header-action" :href="route('timetables.locations.create', $timetable)">
                <span>➕</span>
                <span>新規拠点を追加</span>
            </x-ui.button>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="locations" />

    <x-ui.validation-errors title="入力内容を確認してください" />

    <!-- 登録拠点一覧カード -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-base text-slate-900 flex items-center space-x-2">
                    <span>📍</span>
                    <span>登録拠点一覧</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono font-normal">
                        全 {{ $locationsWithStatus->count() }} 拠点
                    </span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    左端のハンドル（⋮⋮）をドラッグ＆ドロップすると並び順を即時変更できます。拠点名をクリックすると詳細画面へ遷移します。
                </p>
            </div>
            <span class="text-[11px] text-slate-400 font-mono shrink-0">
                判定基準: {{ $now->format('Y/m/d H:i') }} 現在
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">拠点名</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">現在の状態</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">本日の利用可能枠</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">備考・メモ</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap text-right">操作</th>
                    </tr>
                </thead>
                <tbody id="locations-container"
                    data-reorder-url="{{ route('timetables.locations.reorder', $timetable) }}"
                    class="divide-y divide-slate-100 bg-white">
                    @forelse($locationsWithStatus as $item)
                        <tr data-location-id="{{ $item->location->id }}" draggable="true"
                            class="hover:bg-slate-50/80 transition cursor-default">
                            <!-- 拠点名 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <span class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-indigo-600 px-1 select-none font-bold text-sm" title="ドラッグして並び替え">⋮⋮</span>
                                    <a href="{{ route('timetables.locations.show', [$timetable, $item->location]) }}"
                                        class="font-bold text-slate-800 hover:text-indigo-600 hover:underline transition"
                                        title="クリックして詳細を表示">
                                        {{ $item->location->name }}
                                    </a>
                                </div>
                            </td>

                            <!-- 現在の状態 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $item->currentEvaluation->badgeClass() }}"
                                        title="{{ $item->currentEvaluation->detail }}">
                                        <span>{{ $item->currentEvaluation->icon() }}</span>
                                        <span>{{ $item->currentEvaluation->label() }}</span>
                                    </span>
                                </div>
                            </td>

                            <!-- 本日の利用可能枠 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $item->dailyStatus->badgeClass() }}">
                                        <span>{{ $item->dailyStatus->icon() }}</span>
                                        <span>{{ $item->dailyStatus->label() }}</span>
                                    </span>
                                    @if($item->todaySchedules->isNotEmpty())
                                        <span class="text-[11px] text-slate-500 font-mono" title="{{ $item->todaySchedules->map(fn($s) => $s->start_at->format('H:i').'-'.$s->end_at->format('H:i').'['.$s->availability_status_label.']')->join(', ') }}">
                                            ({{ $item->todaySchedules->count() }}枠)
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 備考・メモ -->
                            <td class="px-4 py-3 whitespace-nowrap max-w-xs truncate text-slate-600" title="{{ $item->location->notes }}">
                                {{ $item->location->notes ?: '—' }}
                            </td>

                            <!-- 操作 -->
                            <td class="px-4 py-2.5 whitespace-nowrap text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    <x-ui.button variant="edit" size="xs" :href="route('timetables.locations.edit', [$timetable, $item->location])">
                                        <span>✏️</span>
                                        <span>編集</span>
                                    </x-ui.button>
                                    <form action="{{ route('timetables.locations.destroy', [$timetable, $item->location]) }}" method="POST" class="inline"
                                          data-confirm="拠点「{{ $item->location->name }}」を削除しますか？&#10;（関連する時間帯データも削除されます）">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button variant="delete" size="xs" type="submit">
                                            <span>🗑️</span>
                                            <span>削除</span>
                                        </x-ui.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-ui.empty-state icon="📍" title="この運行日に登録されている拠点はありません。" />
                            </td>
                        </tr>
                                拠点がまだ登録されていません。ヘッダーの「➕ 新規拠点を追加」から登録してください。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@vite(['resources/js/locations.js'])
</x-layouts.app>
