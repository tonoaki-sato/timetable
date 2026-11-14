<x-layouts.app title="スケジュール管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- 1. ヘッダーエリア -->
    <x-page-header
        title="スケジュール管理"
        icon="📅"
        description="会議・打ち合わせ・行政協議・出店募集・会場設営などのスケジュールを一元管理します。">
        <x-slot:breadcrumbs>
            <span class="text-slate-700 font-bold truncate">📋 スケジュール管理</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('calendar.index') }}"
                class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition whitespace-nowrap shrink-0">
                <span>📅</span>
                <span>カレンダー表示に切替</span>
            </a>
            @can('create', App\Models\Schedule::class)
            <a href="{{ route('schedules.create') }}"
                class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer whitespace-nowrap shrink-0">
                <span>➕</span>
                <span>新規スケジュールを登録</span>
            </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <!-- 2. タブ切り替え（今後の予定 / 過去ログ / すべての期間） -->
    <div class="flex items-center space-x-1.5 border-b border-slate-200 pb-1.5 overflow-x-auto scrollbar-none flex-nowrap">
        <a href="{{ route('schedules.index', array_merge(request()->except('page', 'tab'), [])) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ $currentTab === 'upcoming' ? 'bg-slate-900 text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            <span>📅 今後の予定</span>
        </a>
        <a href="{{ route('schedules.index', array_merge(request()->except('page'), ['tab' => 'past'])) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ $currentTab === 'past' ? 'bg-slate-900 text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            <span>📁 過去の記録</span>
        </a>
        <a href="{{ route('schedules.index', array_merge(request()->except('page'), ['tab' => 'all'])) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ $currentTab === 'all' ? 'bg-slate-900 text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            <span>📋 すべての期間</span>
        </a>
    </div>

    <!-- 3. 検索・絞り込みフォーム & 表示区分トグルフィルター -->
    <div class="bg-white p-3 sm:p-3.5 rounded-xl border border-slate-200 shadow-2xs space-y-3">
        <!-- 表示区分切り替えフィルターバー（トグル方式） -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 pb-3">
            <span class="text-xs font-bold text-slate-700 flex items-center mr-1">
                <span>🔍 表示区分:</span>
            </span>

            <!-- 全選択 / 全解除 ボタン -->
            <div class="inline-flex rounded border border-slate-300 bg-white p-0.5 mr-1 shrink-0">
                <button type="button" data-action="select-all-categories"
                    title="全区分を表示"
                    class="px-1.5 py-0.5 text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded text-[10px] font-bold transition cursor-pointer">
                    全選択
                </button>
                <button type="button" data-action="deselect-all-categories"
                    title="全区分を非表示"
                    class="px-1.5 py-0.5 text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded text-[10px] font-bold transition cursor-pointer">
                    全解除
                </button>
            </div>

            <!-- 各区分のチップ（ON/OFF トグル） -->
            <div class="flex flex-wrap items-center gap-1.5" id="category-filter-chips">
                @foreach($displayCategories as $key => $cat)
                    @php
                        $isChipVisible = $selectedCategories === null || in_array($key, $selectedCategories, true);
                    @endphp
                    <button type="button"
                        id="chip-category-{{ $key }}"
                        data-category-id="{{ $key }}"
                        data-category-name="{{ $cat['label'] }}"
                        class="category-chip inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border transition {{ $isChipVisible ? 'bg-white text-slate-800 border-slate-300 hover:border-indigo-400' : 'opacity-40 bg-slate-100 text-slate-800 border-slate-300' }} whitespace-nowrap shrink-0 cursor-pointer">
                        <span class="mr-1 text-[10px] text-emerald-600 font-bold">{{ $isChipVisible ? '✔' : '✕' }}</span>
                        <span>{{ $cat['icon'] }} {{ $cat['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <form method="GET" action="{{ route('schedules.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @if($currentTab && $currentTab !== 'upcoming')
            <input type="hidden" name="tab" value="{{ $currentTab }}">
            @endif
            <input type="hidden" name="category" id="search-form-category" value="{{ is_array($selectedCategories) ? implode(',', $selectedCategories) : '' }}">

            <!-- キーワード -->
            <div>
                <label for="search" class="block text-[11px] font-bold text-slate-600 mb-1">フリーワード</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="予定名・場所・議題・特記等"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- ステータス -->
            <div>
                <label for="status" class="block text-[11px] font-bold text-slate-600 mb-1">ステータス</label>
                <select id="status" name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべてのステータス</option>
                    @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- 担当者 -->
            <div>
                <label for="staff_id" class="block text-[11px] font-bold text-slate-600 mb-1">担当・主催スタッフ</label>
                <select id="staff_id" name="staff_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべてのスタッフ</option>
                    @foreach($staffs as $staff)
                    <option value="{{ $staff->id }}" {{ (string) request('staff_id') === (string) $staff->id ? 'selected' : '' }}>
                        {{ $staff->name }} @if($staff->affiliation)({{ $staff->affiliation }})@endif
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- 検索 & リセットボタン -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                    🔍 絞り込み
                </button>
                <a href="{{ route('schedules.index') }}"
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium transition text-center">
                    リセット
                </a>
            </div>
        </form>
    </div>

    <!-- 4. スケジュール一覧テーブル -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-4 py-2.5 font-bold whitespace-nowrap">開催日・期間</th>
                        <th class="px-4 py-2.5 font-bold">タイトル・議題</th>
                        <th class="px-4 py-2.5 font-bold whitespace-nowrap">場所</th>
                        <th class="px-4 py-2.5 font-bold whitespace-nowrap">担当・主催</th>
                        <th class="px-4 py-2.5 font-bold whitespace-nowrap text-center">状態</th>
                        <th class="px-4 py-2.5 font-bold text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($schedules as $schedule)
                    @php
                        $scheduleCategoryKey = $schedule->is_linked_to_timetable ? 'timetable' : (string) $schedule->category;
                        $isRowVisible = $selectedCategories === null || in_array($scheduleCategoryKey, $selectedCategories, true);
                    @endphp
                    <tr class="schedule-row schedule-category-{{ $scheduleCategoryKey }} hover:bg-slate-50/70 transition {{ $isRowVisible ? '' : 'hidden' }}"
                        data-category="{{ $scheduleCategoryKey }}">
                        <!-- 日時 -->
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <div class="flex items-center space-x-1.5 font-mono text-xs">
                                <span class="font-bold text-slate-900">{{ $schedule->start_date?->format('Y/m/d') }}</span>
                                @if($schedule->end_date && $schedule->end_date->ne($schedule->start_date))
                                <span class="text-slate-400 font-normal">〜 {{ $schedule->end_date->format('Y/m/d') }}</span>
                                @endif
                                @if($schedule->formatted_time_range)
                                <span class="text-indigo-600 font-medium text-[11px] ml-1">⏰ {{ $schedule->formatted_time_range }}</span>
                                @endif
                                @if($schedule->is_today)
                                <span class="ml-1 px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">本日</span>
                                @endif
                            </div>
                        </td>

                        <!-- タイトル・議題 (タイトルの前に個々のアイコンを表示) -->
                        <td class="px-4 py-2.5">
                            <div class="flex items-center space-x-1.5 min-w-[200px]">
                                <span class="text-sm shrink-0" title="{{ $schedule->is_linked_to_timetable ? '運行スケジュール' : $schedule->category_label }}">{{ $schedule->category_icon }}</span>
                                <a href="{{ route('schedules.show', $schedule) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition truncate">
                                    {{ $schedule->title }}
                                </a>
                            </div>
                        </td>

                        <!-- 場所 -->
                        <td class="px-4 py-2.5 whitespace-nowrap text-slate-600">
                            {{ $schedule->location ?: '-' }}
                        </td>

                        <!-- 担当・主催 -->
                        <td class="px-4 py-2.5 whitespace-nowrap text-xs">
                            @if($schedule->assignee)
                            <span class="font-medium text-slate-900">{{ $schedule->assignee->name }}</span>
                            @if($schedule->assignee->affiliation)
                            <span class="text-[11px] text-slate-400 ml-1">({{ $schedule->assignee->affiliation }})</span>
                            @endif
                            @else
                            <span class="text-slate-400 italic">未定</span>
                            @endif
                        </td>

                        <!-- 状態 -->
                        <td class="px-4 py-2.5 whitespace-nowrap text-center">
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold border {{ $schedule->status_badge_class }}">
                                {{ $schedule->status_label }}
                            </span>
                        </td>

                        <!-- 操作 -->
                        <td class="px-4 py-2.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end space-x-1.5">
                                @can('update', $schedule)
                                <a href="{{ route('schedules.edit', $schedule) }}"
                                    class="inline-flex items-center space-x-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition"
                                    title="編集">
                                    <span>✏️</span>
                                    <span>編集</span>
                                </a>
                                @endcan

                                @if($schedule->is_linked_to_timetable)
                                <span class="inline-flex items-center space-x-1 px-2.5 py-1 bg-slate-100 text-slate-400 border border-slate-200 rounded-lg text-xs font-bold cursor-not-allowed select-none"
                                    title="運行スケジュールは運行情報管理画面から削除してください">
                                    <span>🔒</span>
                                    <span>連動中</span>
                                </span>
                                @else
                                @can('delete', $schedule)
                                <form action="{{ route('schedules.destroy', $schedule) }}" method="POST" class="inline"
                                    data-confirm="予定「{{ $schedule->title }}」を削除しますか？">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center space-x-1 px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                        title="削除">
                                        <span>🗑️</span>
                                        <span>削除</span>
                                    </button>
                                </form>
                                @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                            <span class="text-2xl block mb-2">📋</span>
                            条件に一致するスケジュールはありません。
                        </td>
                    </tr>
                    @endforelse

                    @if($schedules->isNotEmpty())
                    <tr id="schedule-filter-empty-state" class="{{ ($selectedCategories !== null && count($selectedCategories) === 0) ? '' : 'hidden' }}">
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                            <span class="text-2xl block mb-2">🔍</span>
                            表示対象の区分が選択されていません。上部の「全選択」または区分チップをクリックして表示を切り替えてください。
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if($schedules->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $schedules->links() }}
        </div>
        @endif
    </div>

</div>

@vite(['resources/js/schedules.js'])
</x-layouts.app>
