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
            <a href="{{ route('timetables.locations.create', $timetable) }}"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>➕</span>
                <span>新規拠点を追加</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="locations" />

    <!-- アラートメッセージ -->
    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>✅</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('status'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>✅</span>
        <span>{{ session('status') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>⚠️</span>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl space-y-1">
        <div class="font-bold flex items-center space-x-1">
            <span>⚠️</span>
            <span>入力内容を確認してください</span>
        </div>
        @foreach($errors->all() as $err)
        <div>・{{ $err }}</div>
        @endforeach
    </div>
    @endif

    <!-- 登録拠点一覧カード -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
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
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">拠点名</th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">現在の状態</th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">本日の利用可能枠</th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">備考・メモ</th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap text-right">操作</th>
                    </tr>
                </thead>
                <tbody id="locations-container"
                    data-reorder-url="{{ route('timetables.locations.reorder', $timetable) }}"
                    class="divide-y divide-slate-100 bg-white">
                    @forelse($locationsWithStatus as $item)
                        @php
                            $loc = $item->location;
                            $eval = $item->currentEvaluation;
                            $dailyStatus = $item->dailyStatus;
                            $todaySlots = $item->todaySchedules;
                        @endphp
                        <tr data-location-id="{{ $loc->id }}" draggable="true"
                            class="hover:bg-slate-50/80 transition cursor-default">
                            <!-- 拠点名 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <span class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-indigo-600 px-1 select-none font-bold text-sm" title="ドラッグして並び替え">⋮⋮</span>
                                    <a href="{{ route('timetables.locations.show', [$timetable, $loc]) }}"
                                        class="font-bold text-slate-800 hover:text-indigo-600 hover:underline transition"
                                        title="クリックして詳細を表示">
                                        {{ $loc->name }}
                                    </a>
                                </div>
                            </td>

                            <!-- 現在の状態 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $eval->badgeClass() }}"
                                        title="{{ $eval->detail }}">
                                        <span>{{ $eval->icon() }}</span>
                                        <span>{{ $eval->label() }}</span>
                                    </span>
                                </div>
                            </td>

                            <!-- 本日の利用可能枠 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $dailyStatus->badgeClass() }}">
                                        <span>{{ $dailyStatus->icon() }}</span>
                                        <span>{{ $dailyStatus->label() }}</span>
                                    </span>
                                    @if($todaySlots->isNotEmpty())
                                        <span class="text-[11px] text-slate-500 font-mono" title="{{ $todaySlots->map(fn($s) => $s->start_at->format('H:i').'-'.$s->end_at->format('H:i').'['.$s->availability_status_label.']')->join(', ') }}">
                                            ({{ $todaySlots->count() }}枠)
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 備考・メモ -->
                            <td class="px-4 py-3 whitespace-nowrap max-w-xs truncate text-slate-600" title="{{ $loc->notes }}">
                                {{ $loc->notes ?: '—' }}
                            </td>

                            <!-- 操作 -->
                            <td class="px-4 py-3 whitespace-nowrap text-right space-x-1">
                                <a href="{{ route('timetables.locations.edit', [$timetable, $loc]) }}"
                                    class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition inline-flex items-center space-x-1 shadow-2xs">
                                    <span>✏️</span>
                                    <span>編集</span>
                                </a>
                                <form action="{{ route('timetables.locations.destroy', [$timetable, $loc]) }}" method="POST" class="inline"
                                      data-confirm="拠点「{{ $loc->name }}」を削除しますか？&#10;（関連する時間帯データも削除されます）">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-2.5 py-1 bg-white hover:bg-rose-50 text-rose-600 border border-slate-200 hover:border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer inline-flex items-center space-x-1 shadow-2xs">
                                        <span>🗑️</span>
                                        <span>削除</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400 text-xs">
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
