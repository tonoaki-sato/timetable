<x-layouts.app title="年間・月間スケジュール管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- 1. ヘッダーエリア & 表示切り替えタブ -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2.5">
                <span class="text-2xl">📅</span>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">年間・月間スケジュール管理</h1>
                <span class="bg-indigo-100 text-indigo-700 text-xs px-2.5 py-0.5 rounded-full font-bold">
                    {{ $selectedYear }}年度
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                行政協議・出店募集・会場設営・定例会議のロードマップをカレンダー上で直感的に管理します。
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <!-- 表示切り替えスイッチ -->
            <div class="bg-slate-200/80 p-1 rounded-xl flex items-center space-x-1 text-xs font-bold">
                <a href="{{ route('annual-schedules.index', array_merge(request()->except('page'), ['view' => 'calendar', 'year' => $selectedYear, 'month' => $selectedMonth ?? 4])) }}"
                    class="px-3 py-1.5 rounded-lg transition flex items-center space-x-1.5 {{ ($viewMode ?? 'calendar') === 'calendar' ? 'bg-white text-indigo-600 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    <span>📅</span>
                    <span>カレンダー</span>
                </a>
                <a href="{{ route('annual-schedules.index', array_merge(request()->except('page'), ['view' => 'list', 'year' => $selectedYear])) }}"
                    class="px-3 py-1.5 rounded-lg transition flex items-center space-x-1.5 {{ ($viewMode ?? 'calendar') === 'list' ? 'bg-white text-indigo-600 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    <span>📋</span>
                    <span>リスト一覧</span>
                </a>
            </div>

            @can('create', App\Models\AnnualSchedule::class)
            <button type="button" onclick="openNewEventModal()"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                <span>➕</span>
                <span>新規登録</span>
            </button>
            @endcan
        </div>
    </div>

    <!-- 2. サマリーステータスカード -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200 shadow-xs">
            <span class="text-[11px] font-bold text-slate-500">年間総件数</span>
            <div class="text-lg sm:text-xl font-bold text-slate-900 mt-0.5 font-mono">{{ $yearSummary['total'] }} <span class="text-xs font-normal text-slate-500">件</span></div>
        </div>
        <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-blue-200 shadow-xs">
            <span class="text-[11px] font-bold text-blue-700">予定（未着手）</span>
            <div class="text-lg sm:text-xl font-bold text-blue-700 mt-0.5 font-mono">{{ $yearSummary['planned'] }} <span class="text-xs font-normal text-slate-500">件</span></div>
        </div>
        <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-amber-200 shadow-xs">
            <span class="text-[11px] font-bold text-amber-700">進行中</span>
            <div class="text-lg sm:text-xl font-bold text-amber-700 mt-0.5 font-mono">{{ $yearSummary['in_progress'] }} <span class="text-xs font-normal text-slate-500">件</span></div>
        </div>
        <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-emerald-200 shadow-xs">
            <span class="text-[11px] font-bold text-emerald-700">完了</span>
            <div class="text-lg sm:text-xl font-bold text-emerald-700 mt-0.5 font-mono">{{ $yearSummary['completed'] }} <span class="text-xs font-normal text-slate-500">件</span></div>
        </div>
    </div>

    <!-- 3. 検索・絞り込みフォーム（カレンダー・リスト共通） -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('annual-schedules.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <input type="hidden" name="view" value="{{ $viewMode ?? 'calendar' }}">
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            @if(($viewMode ?? 'calendar') === 'calendar')
            <input type="hidden" name="month" value="{{ $currentMonth ?? 4 }}">
            @endif

            <!-- キーワード -->
            <div>
                <label for="search" class="block text-[11px] font-bold text-slate-600 mb-1">フリーワード</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="予定名・場所・担当者等"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- カテゴリ -->
            <div>
                <label for="category" class="block text-[11px] font-bold text-slate-600 mb-1">区分（カテゴリ）</label>
                <select id="category" name="category" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべての区分</option>
                    @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
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
                <label for="staff_id" class="block text-[11px] font-bold text-slate-600 mb-1">担当者・主催者</label>
                <select id="staff_id" name="staff_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべての担当者</option>
                    @foreach($staffs as $staff)
                    <option value="{{ $staff->id }}" {{ (string) request('staff_id') === (string) $staff->id ? 'selected' : '' }}>
                        {{ $staff->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- 検索 & リセットボタン -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                    🔍 絞り込み
                </button>
                <a href="{{ route('annual-schedules.index', ['view' => $viewMode ?? 'calendar', 'year' => $selectedYear, 'month' => $currentMonth ?? 4]) }}"
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium transition text-center">
                    リセット
                </a>
            </div>
        </form>
    </div>

    @if(($viewMode ?? 'calendar') === 'calendar')
    <!-- ========================================== -->
    <!-- カレンダービュー (Calendar View)           -->
    <!-- ========================================== -->

    <!-- 4. ドラッグ＆ドロップ パレット（5種アイコン） -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 p-4 sm:p-5 rounded-2xl shadow-md text-white border border-slate-800">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
            <div class="flex items-center space-x-2">
                <span class="text-lg">✨</span>
                <span class="text-xs sm:text-sm font-bold tracking-wide">ドラッグ＆ドロップ パレット</span>
                <span class="bg-indigo-500/30 text-indigo-200 border border-indigo-400/30 text-[10px] px-2 py-0.5 rounded font-mono">D&D対応</span>
            </div>
            <p class="text-[11px] text-slate-300">
                💡 下のアイコンをカレンダーのマス目へドラッグ＆ドロップして、即座に詳細・日程を入力できます
            </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
            @foreach($paletteCategories as $palette)
            <div draggable="true"
                data-category="{{ $palette['key'] }}"
                data-name="{{ $palette['name'] }}"
                data-icon="{{ $palette['icon'] }}"
                class="draggable-palette-item group cursor-grab active:cursor-grabbing select-none bg-slate-800/90 hover:bg-slate-800 border border-slate-700 hover:border-indigo-400 p-2.5 rounded-xl transition transform hover:-translate-y-0.5 shadow-xs flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-lg {{ $palette['bg'] }} shrink-0 shadow-inner">
                    {{ $palette['icon'] }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold text-white group-hover:text-indigo-300 transition truncate">
                        {{ $palette['name'] }}
                    </div>
                    <div class="text-[10px] text-slate-400 truncate">
                        {{ $palette['description'] }}
                    </div>
                </div>
                <span class="text-slate-500 text-xs opacity-50 group-hover:opacity-100">⠿</span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- 5. 月ナビゲーション & カレンダーヘッダー -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60">
            <!-- 月送りナビゲーション -->
            <div class="flex items-center space-x-2">
                <a href="{{ route('annual-schedules.index', array_merge(request()->except('month', 'year'), ['year' => $prevYear, 'month' => $prevMonth, 'view' => 'calendar'])) }}"
                    class="p-2 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg text-slate-700 text-xs font-bold transition shadow-xs flex items-center space-x-1"
                    title="前月へ">
                    <span>◀</span>
                    <span class="hidden sm:inline">{{ $prevMonth }}月</span>
                </a>

                <div class="px-4 py-1.5 bg-white border border-slate-200 rounded-xl shadow-xs text-center min-w-[150px]">
                    <span class="text-sm sm:text-base font-black text-slate-900 font-mono">
                        {{ $currentYear }}年 {{ $currentMonth }}月
                    </span>
                </div>

                <a href="{{ route('annual-schedules.index', array_merge(request()->except('month', 'year'), ['year' => $nextYear, 'month' => $nextMonth, 'view' => 'calendar'])) }}"
                    class="p-2 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg text-slate-700 text-xs font-bold transition shadow-xs flex items-center space-x-1"
                    title="翌月へ">
                    <span class="hidden sm:inline">{{ $nextMonth }}月</span>
                    <span>▶</span>
                </a>

                <a href="{{ route('annual-schedules.index', array_merge(request()->except('month', 'year'), ['year' => now()->year, 'month' => now()->month, 'view' => 'calendar'])) }}"
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition">
                    今月
                </a>
            </div>

            <!-- 年度セレクター -->
            <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 sm:pb-0">
                <span class="text-[11px] font-bold text-slate-500 mr-1 whitespace-nowrap">年度:</span>
                @foreach($availableYears as $year)
                <a href="{{ route('annual-schedules.index', array_merge(request()->except('year'), ['year' => $year, 'month' => $currentMonth, 'view' => 'calendar'])) }}"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition whitespace-nowrap {{ (int)$selectedYear === (int)$year ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                    {{ $year }}
                </a>
                @endforeach
            </div>
        </div>

        <!-- 6. カレンダー曜日ヘッダー (日〜土) -->
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-100/90 text-center text-xs font-bold text-slate-700 select-none">
            <div class="py-2.5 text-red-600 bg-red-50/40">日 (Sun)</div>
            <div class="py-2.5">月 (Mon)</div>
            <div class="py-2.5">火 (Tue)</div>
            <div class="py-2.5">水 (Wed)</div>
            <div class="py-2.5">木 (Thu)</div>
            <div class="py-2.5">金 (Fri)</div>
            <div class="py-2.5 text-blue-600 bg-blue-50/40">土 (Sat)</div>
        </div>

        <!-- 7. カレンダーマス目グリッド -->
        <div class="grid grid-cols-7 divide-x divide-y divide-slate-200 bg-slate-200">
            @foreach($calendarWeeks as $week)
                @foreach($week as $day)
                <div data-date="{{ $day['date'] }}"
                    class="calendar-cell min-h-[120px] sm:min-h-[140px] p-2 bg-white transition flex flex-col justify-between group relative {{ !$day['isCurrentMonth'] ? 'bg-slate-50/60 opacity-60' : '' }} {{ $day['isToday'] ? 'ring-2 ring-indigo-500 ring-inset bg-indigo-50/20' : '' }}">

                    <!-- マス目上部: 日付番号 & ＋ボタン -->
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-bold font-mono {{ $day['isToday'] ? 'w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold shadow-xs' : ($day['dayOfWeek'] === 0 ? 'text-red-600' : ($day['dayOfWeek'] === 6 ? 'text-blue-600' : 'text-slate-800')) }}">
                            {{ $day['day'] }}
                        </span>

                        <button type="button"
                            data-date="{{ $day['date'] }}"
                            title="{{ $day['date'] }}に新しい予定を追加"
                            class="add-event-cell-btn opacity-0 group-hover:opacity-100 p-1 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded text-[11px] transition">
                            ➕
                        </button>
                    </div>

                    <!-- 予定ピル一覧 -->
                    <div class="space-y-1.5 flex-1 overflow-y-auto max-h-[110px] pr-0.5">
                        @foreach($day['events'] as $event)
                        <div draggable="true"
                            data-event-id="{{ $event['id'] }}"
                            data-event-source="{{ $event['source'] }}"
                            data-reschedule-url="{{ $event['reschedule_url'] }}"
                            data-event-json="{{ json_encode($event) }}"
                            class="event-pill cursor-grab active:cursor-grabbing p-1.5 rounded-lg border text-[11px] transition shadow-xs hover:shadow-sm select-none leading-tight {{ $event['badge_class'] }}"
                            title="クリックで詳細表示、ドラッグで別の日付へ移動">
                            
                            <div class="flex items-center space-x-1 font-bold truncate">
                                <span>{{ $event['icon'] }}</span>
                                @if($event['time'])
                                <span class="font-mono text-[10px]">{{ $event['start_time'] }}</span>
                                @endif
                                <span class="truncate">{{ $event['title'] }}</span>
                            </div>

                            <div class="flex items-center justify-between mt-0.5 text-[10px] opacity-80">
                                @if($event['is_period'])
                                <span class="text-[9px] bg-sky-100/80 text-sky-800 px-1 rounded font-mono">期間</span>
                                @elseif($event['location'])
                                <span class="truncate max-w-[80px]">📍 {{ $event['location'] }}</span>
                                @endif

                                @if($event['has_unassigned_host'])
                                <span class="px-1 rounded bg-amber-200/80 text-amber-900 font-bold text-[9px]">主催未定</span>
                                @elseif($event['staff_name'])
                                <span class="truncate max-w-[65px]">👤 {{ $event['staff_name'] }}</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- ドロッププレースホルダー表示枠 -->
                    <div class="drop-hint hidden absolute inset-1 bg-indigo-50/80 border-2 border-dashed border-indigo-400 rounded-lg pointer-events-none flex items-center justify-center text-xs font-bold text-indigo-700">
                        ここに配置
                    </div>
                </div>
                @endforeach
            @endforeach
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- スケジュール一覧テーブル (List Table)      -->
    <!-- ========================================== -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-sm">📋</span>
                <span class="text-xs font-bold text-slate-800">年間スケジュール一覧・絞り込み結果</span>
            </div>
            <span class="text-[11px] text-slate-500 font-mono">{{ $schedules->total() }}件</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-4 py-3 font-bold">実施時期</th>
                        <th class="px-4 py-3 font-bold">区分</th>
                        <th class="px-4 py-3 font-bold">スケジュール・行事名</th>
                        <th class="px-4 py-3 font-bold">場所</th>
                        <th class="px-4 py-3 font-bold">ステータス</th>
                        <th class="px-4 py-3 font-bold">担当者</th>
                        <th class="px-4 py-3 font-bold text-right">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($schedules as $schedule)
                    <tr class="hover:bg-slate-50/70 transition">
                        <!-- 日時 -->
                        <td class="px-4 py-3 font-mono text-slate-700 whitespace-nowrap">
                            <span class="font-bold text-slate-900">{{ $schedule->start_date?->format('Y/m/d') }}</span>
                            @if($schedule->end_date && $schedule->end_date->ne($schedule->start_date))
                            <span class="text-slate-400 text-[10px] block">〜 {{ $schedule->end_date->format('Y/m/d') }}</span>
                            @endif
                        </td>

                        <!-- 区分 -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php
                                $categoryBadgeClasses = match($schedule->category) {
                                    'committee' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'permit' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'briefing' => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'operation' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border {{ $categoryBadgeClasses }}">
                                {{ $schedule->category_label }}
                            </span>
                        </td>

                        <!-- タイトル -->
                        <td class="px-4 py-3">
                            <a href="{{ route('annual-schedules.show', $schedule) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition">
                                {{ $schedule->title }}
                            </a>
                            @if($schedule->description)
                            <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">{{ $schedule->description }}</p>
                            @endif
                        </td>

                        <!-- 場所 -->
                        <td class="px-4 py-3 text-slate-600">
                            {{ $schedule->location ?? '-' }}
                        </td>

                        <!-- ステータス -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php
                                $statusBadgeClasses = match($schedule->status) {
                                    'planned' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'in_progress' => 'bg-amber-50 text-amber-800 border-amber-300 font-bold',
                                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'cancelled' => 'bg-slate-100 text-slate-500 border-slate-200 line-through',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium border {{ $statusBadgeClasses }}">
                                {{ $schedule->status_label }}
                            </span>
                        </td>

                        <!-- 担当者 -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($schedule->assignee)
                            <span class="inline-flex items-center font-bold text-slate-800" title="{{ $schedule->assignee->affiliation }}">
                                👤 {{ $schedule->assignee->name }}
                            </span>
                            @else
                            <span class="text-slate-400 text-[11px]">-</span>
                            @endif
                        </td>

                        <!-- 操作 -->
                        <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                            <a href="{{ route('annual-schedules.show', $schedule) }}"
                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-medium transition">
                                詳細
                            </a>

                            @can('update', $schedule)
                            <a href="{{ route('annual-schedules.edit', $schedule) }}"
                                class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[11px] font-bold transition">
                                編集
                            </a>
                            @endcan

                            @can('delete', $schedule)
                            <form action="{{ route('annual-schedules.destroy', $schedule) }}" method="POST" class="inline"
                                onsubmit="return confirm('年間スケジュール「{{ $schedule->title }}」を削除しますか？');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded text-[11px] font-medium transition cursor-pointer">
                                    削除
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                            <span class="text-3xl block mb-2">📋</span>
                            該当する年間スケジュールは見つかりませんでした。
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($schedules->hasPages())
        <div class="p-4 border-t border-slate-200 bg-slate-50/50">
            {{ $schedules->links() }}
        </div>
        @endif
    </div>

</div>

<!-- ========================================== -->
<!-- 8. 新規予定登録モーダル (New Event Modal)   -->
<!-- ========================================== -->
<div id="newEventModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeNewEventModal()"></div>
    
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden z-10 transition transform">
            <!-- モーダルヘッダー -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span id="modalCategoryIcon" class="text-xl">🤝</span>
                    <h2 class="text-base font-bold" id="modalTitle">新しい予定・会議の登録</h2>
                </div>
                <button type="button" onclick="closeNewEventModal()" class="text-slate-400 hover:text-white transition text-lg cursor-pointer">✕</button>
            </div>

            <!-- フォーム本体 -->
            <form id="newEventForm" method="POST" action="{{ route('annual-schedules.store') }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="target_year" id="formTargetYear" value="{{ $selectedYear }}">

                <!-- 種別（カテゴリ）選択 -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">予定の種別（カテゴリ） <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                        @foreach($paletteCategories as $palette)
                        <label class="cursor-pointer">
                            <input type="radio" name="category" value="{{ $palette['key'] }}" class="peer sr-only" onchange="handleCategoryChange(this.value)">
                            <div class="peer-checked:ring-2 peer-checked:ring-indigo-600 peer-checked:bg-indigo-50 border border-slate-200 rounded-xl p-2 text-center text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                                <span class="block text-base mb-0.5">{{ $palette['icon'] }}</span>
                                <span class="text-[11px]">{{ $palette['name'] }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- スケジュール名 -->
                <div>
                    <label for="eventTitle" class="block text-xs font-bold text-slate-700 mb-1">タイトル <span class="text-red-500">*</span></label>
                    <input type="text" id="eventTitle" name="title" required
                        placeholder="例: 第1回 警備・動線事前協議"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- 日程指定モード切り替え（単日時刻指定 vs 期間指定） -->
                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700">📅 日付・期間の設定</span>
                        <div class="inline-flex bg-slate-200/80 p-0.5 rounded-lg text-xs font-bold" id="modeSwitchContainer">
                            <button type="button" onclick="setScheduleMode('single')" id="modeSingleBtn" class="px-2.5 py-1 rounded-md transition bg-white text-indigo-700 shadow-xs">
                                単日（時刻あり）
                            </button>
                            <button type="button" onclick="setScheduleMode('period')" id="modePeriodBtn" class="px-2.5 py-1 rounded-md transition text-slate-600">
                                期間指定
                            </button>
                        </div>
                    </div>

                    <!-- 単日指定フィールド -->
                    <div id="singleDateFields" class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <div>
                            <label for="eventDate" class="block text-[11px] font-bold text-slate-600 mb-1">開催日 <span class="text-red-500">*</span></label>
                            <input type="date" id="eventDate" name="start_date" required
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div id="startTimeContainer">
                            <label for="eventStartTime" class="block text-[11px] font-bold text-slate-600 mb-1">開始時刻</label>
                            <input type="time" id="eventStartTime" name="start_time" value="18:00"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div id="endTimeContainer">
                            <label for="eventEndTime" class="block text-[11px] font-bold text-slate-600 mb-1">終了時刻</label>
                            <input type="time" id="eventEndTime" name="end_time" value="20:00"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- 期間指定フィールド（デフォルト非表示） -->
                    <div id="periodDateFields" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label for="eventStartDate" class="block text-[11px] font-bold text-slate-600 mb-1">開始日 <span class="text-red-500">*</span></label>
                            <input type="date" id="eventStartDate"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="eventEndDate" class="block text-[11px] font-bold text-slate-600 mb-1">終了日</label>
                            <input type="date" id="eventEndDate" name="end_date"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- 開催場所 & 主催者/担当者 -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="eventLocation" class="block text-xs font-bold text-slate-700 mb-1">実施場所 <span class="text-slate-400 font-normal">（任意）</span></label>
                        <input type="text" id="eventLocation" name="location"
                            placeholder="例: コミュニティルーム / 保土ケ谷警察署"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="eventStaffId" class="block text-xs font-bold text-slate-700 mb-1">
                            主催者・担当者 <span class="text-slate-400 font-normal">（未定可・後から設定）</span>
                        </label>
                        <select id="eventStaffId" name="staff_id"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">未定（後から設定する）</option>
                            @foreach($staffs as $staff)
                            <option value="{{ $staff->id }}">
                                {{ $staff->name }} @if($staff->affiliation)({{ $staff->affiliation }})@endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- ステータス（年間スケジュール用） -->
                <div id="statusFieldContainer">
                    <label for="eventStatus" class="block text-xs font-bold text-slate-700 mb-1">ステータス</label>
                    <select id="eventStatus" name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ $key === 'planned' ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- 詳細説明・議題 -->
                <div>
                    <label for="eventDescription" class="block text-xs font-bold text-slate-700 mb-1">内容・議題・詳細メモ <span class="text-slate-400 font-normal">（任意）</span></label>
                    <textarea id="eventDescription" name="description" rows="2"
                        placeholder="議題や資機材、連絡事項など"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <!-- LINE特記メモ（会議選択時用） -->
                <div id="noticeNoteContainer">
                    <label for="eventNoticeNote" class="block text-xs font-bold text-slate-700 mb-1">LINE告知用特記メモ <span class="text-slate-400 font-normal">（任意・返信期日など）</span></label>
                    <input type="text" id="eventNoticeNote" name="notice_note"
                        placeholder="出欠は開催2日前までにご返信ください。"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- モーダルフッター操作 -->
                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeNewEventModal()"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                        キャンセル
                    </button>
                    <button type="submit" id="submitEventBtn"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition cursor-pointer">
                        登録する
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 9. 予定詳細 & クイック編集モーダル         -->
<!-- ========================================== -->
<div id="eventDetailModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeEventDetailModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden z-10">
            <!-- ヘッダー -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span id="detailIcon" class="text-xl">📅</span>
                    <span id="detailCategoryLabel" class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-600/80">予定</span>
                </div>
                <button type="button" onclick="closeEventDetailModal()" class="text-slate-400 hover:text-white transition text-lg cursor-pointer">✕</button>
            </div>

            <!-- コンテンツ -->
            <div class="p-6 space-y-4">
                <div>
                    <h2 id="detailTitle" class="text-xl font-bold text-slate-900">タイトル</h2>
                    <div class="flex items-center space-x-3 mt-1 text-xs text-slate-500 font-mono">
                        <span id="detailDatetime">2026/04/10</span>
                        <span id="detailLocation">📍 コミュニティルーム</span>
                    </div>
                </div>

                <!-- 主催者・担当者セクション（未設定時のクイック設定UI） -->
                <div id="detailHostSection" class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="text-[11px] font-bold text-slate-500 mb-1">👤 主催者・担当スタッフ</div>
                    <div id="detailAssignedStaff" class="text-xs font-bold text-slate-800">未設定</div>

                    <!-- 主催者が未設定の場合に表示するクイック割り当てフォーム -->
                    <div id="quickAssignHostContainer" class="hidden mt-2 pt-2 border-t border-slate-200">
                        <div class="flex items-center space-x-2">
                            <select id="quickAssignStaffSelect" class="flex-1 px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">スタッフを選択して主催者に設定</option>
                                @foreach($staffs as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->affiliation }})</option>
                                @endforeach
                            </select>
                            <button type="button" onclick="saveQuickAssignHost()" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs cursor-pointer">
                                決定
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 詳細説明 -->
                <div id="detailDescriptionContainer" class="space-y-1">
                    <span class="text-[11px] font-bold text-slate-500 block">📝 内容・議題</span>
                    <div id="detailDescription" class="p-3 bg-slate-50 rounded-xl text-xs text-slate-800 whitespace-pre-wrap leading-relaxed max-h-[120px] overflow-y-auto">
                        詳細なし
                    </div>
                </div>

                <!-- LINE告知テキスト（会議の場合のみ表示） -->
                <div id="detailLineShareContainer" class="hidden p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-900 flex items-center space-x-1">
                            <span>💬</span>
                            <span>全体LINE告知用テキスト</span>
                        </span>
                        <button type="button" id="copyLineTextBtn" onclick="copyDetailLineText()"
                            class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold transition flex items-center space-x-1 cursor-pointer">
                            <span>📋</span>
                            <span>文面をコピー</span>
                        </button>
                    </div>
                    <pre id="detailLineText" class="text-[11px] text-emerald-950 font-mono whitespace-pre-wrap bg-white/80 p-2.5 rounded-lg border border-emerald-100 max-h-[140px] overflow-y-auto"></pre>
                </div>

                <!-- アクションボタン群 -->
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <a id="detailShowLink" href="#" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                        詳細ページを開く
                    </a>
                    
                    <div class="flex items-center space-x-2">
                        <a id="detailEditLink" href="#" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition">
                            ✏️ 編集する
                        </a>
                        <button type="button" onclick="closeEventDetailModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition">
                            閉じる
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 10. フローティング トースト通知            -->
<!-- ========================================== -->
<div id="toastNotification" class="hidden fixed bottom-6 right-6 z-50 px-4 py-3 bg-slate-900 text-white text-xs font-bold rounded-xl shadow-2xl border border-slate-700 flex items-center space-x-2 transition transform translate-y-2">
    <span id="toastIcon">✔</span>
    <span id="toastMessage">完了しました</span>
</div>

<!-- ========================================== -->
<!-- 11. インタラクティブ Vanilla JS スクリプト -->
<!-- ========================================== -->
<script>
(function() {
    'use strict';

    let draggedItem = null;
    let currentDetailEvent = null;

    // CSRF トークン取得
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // 1. パレットアイコンのドラッグ設定
    const paletteItems = document.querySelectorAll('.draggable-palette-item');
    paletteItems.forEach(item => {
        item.addEventListener('dragstart', function(e) {
            draggedItem = {
                type: 'palette',
                category: this.dataset.category,
                name: this.dataset.name,
                icon: this.dataset.icon,
            };
            e.dataTransfer.effectAllowed = 'copy';
            e.dataTransfer.setData('text/plain', JSON.stringify(draggedItem));
            this.classList.add('opacity-50');
        });

        item.addEventListener('dragend', function() {
            this.classList.remove('opacity-50');
            draggedItem = null;
        });
    });

    // 2. カレンダーセル（ドロップゾーン）のイベントリスナー
    const calendarCells = document.querySelectorAll('.calendar-cell');
    calendarCells.forEach(cell => {
        cell.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            this.classList.add('ring-2', 'ring-indigo-500', 'bg-indigo-50/40');
            const hint = this.querySelector('.drop-hint');
            if (hint) hint.classList.remove('hidden');
        });

        cell.addEventListener('dragleave', function(e) {
            if (!this.contains(e.relatedTarget)) {
                this.classList.remove('ring-2', 'ring-indigo-500', 'bg-indigo-50/40');
                const hint = this.querySelector('.drop-hint');
                if (hint) hint.classList.add('hidden');
            }
        });

        cell.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('ring-2', 'ring-indigo-500', 'bg-indigo-50/40');
            const hint = this.querySelector('.drop-hint');
            if (hint) hint.classList.add('hidden');

            const targetDate = this.dataset.date;
            if (!targetDate) return;

            let data = null;
            try {
                data = JSON.parse(e.dataTransfer.getData('text/plain') || '{}');
            } catch (err) {
                data = draggedItem;
            }

            if (!data) return;

            // パレットからの新規ドロップ
            if (data.type === 'palette') {
                openNewEventModal(targetDate, data.category);
            } 
            // 既存イベントピルのリスケジュール移動
            else if (data.type === 'event_pill' && data.rescheduleUrl) {
                rescheduleEvent(data.rescheduleUrl, targetDate, data.source);
            }
        });
    });

    // 3. 既存イベントピルのドラッグ設定
    const eventPills = document.querySelectorAll('.event-pill');
    eventPills.forEach(pill => {
        pill.addEventListener('dragstart', function(e) {
            e.stopPropagation();
            draggedItem = {
                type: 'event_pill',
                eventId: this.dataset.eventId,
                source: this.dataset.eventSource,
                rescheduleUrl: this.dataset.rescheduleUrl,
            };
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', JSON.stringify(draggedItem));
            this.classList.add('opacity-40');
        });

        pill.addEventListener('dragend', function() {
            this.classList.remove('opacity-40');
            draggedItem = null;
        });
    });

    // クリックイベントリスナー（イベント詳細 & セルからの新規追加）
    document.addEventListener('click', function(e) {
        const pill = e.target.closest('.event-pill');
        if (pill && pill.dataset.eventJson) {
            try {
                const eventData = JSON.parse(pill.dataset.eventJson);
                openEventDetailModal(eventData);
            } catch (err) {
                console.error('Failed to parse event JSON', err);
            }
            return;
        }

        const addBtn = e.target.closest('.add-event-cell-btn');
        if (addBtn && addBtn.dataset.date) {
            openNewEventModal(addBtn.dataset.date);
            return;
        }
    });

    // 4. リスケジュール非同期通信処理
    function rescheduleEvent(url, newDate, source) {
        showToast('🔄 日程を更新中...', '⏳');

        const payload = source === 'meeting' 
            ? { meeting_date: newDate } 
            : { start_date: newDate };

        fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) throw new Error('通信エラーが発生しました');
            return response.json();
        })
        .then(data => {
            showToast(data.message || '日程を変更しました！', '✔');
            setTimeout(() => {
                window.location.reload();
            }, 600);
        })
        .catch(err => {
            alert('日程の変更に失敗しました。画面を再読み込みしてください。');
        });
    }

    // 5. グローバルモーダルハンドラー
    window.openNewEventModal = function(date = null, category = 'committee') {
        const modal = document.getElementById('newEventModal');
        const form = document.getElementById('newEventForm');
        form.reset();

        const defaultDate = date || '{{ $currentYear }}-{{ sprintf("%02d", $currentMonth) }}-01';
        document.getElementById('eventDate').value = defaultDate;
        document.getElementById('eventStartDate').value = defaultDate;
        document.getElementById('eventEndDate').value = defaultDate;

        // カテゴリラジオ選択
        const categoryRadio = form.querySelector(`input[name="category"][value="${category}"]`);
        if (categoryRadio) {
            categoryRadio.checked = true;
            handleCategoryChange(category);
        } else {
            handleCategoryChange('committee');
        }

        modal.classList.remove('hidden');
    };

    window.closeNewEventModal = function() {
        document.getElementById('newEventModal').classList.add('hidden');
    };

    window.setScheduleMode = function(mode) {
        const singleBtn = document.getElementById('modeSingleBtn');
        const periodBtn = document.getElementById('modePeriodBtn');
        const singleFields = document.getElementById('singleDateFields');
        const periodFields = document.getElementById('periodDateFields');

        if (mode === 'single') {
            singleBtn.classList.add('bg-white', 'text-indigo-700', 'shadow-xs');
            singleBtn.classList.remove('text-slate-600');
            periodBtn.classList.remove('bg-white', 'text-indigo-700', 'shadow-xs');
            periodBtn.classList.add('text-slate-600');

            singleFields.classList.remove('hidden');
            periodFields.classList.add('hidden');

            document.getElementById('eventDate').required = true;
            document.getElementById('eventStartDate').required = false;
        } else {
            periodBtn.classList.add('bg-white', 'text-indigo-700', 'shadow-xs');
            periodBtn.classList.remove('text-slate-600');
            singleBtn.classList.remove('bg-white', 'text-indigo-700', 'shadow-xs');
            singleBtn.classList.add('text-slate-600');

            periodFields.classList.remove('hidden');
            singleFields.classList.add('hidden');

            document.getElementById('eventStartDate').value = document.getElementById('eventDate').value;
            document.getElementById('eventStartDate').required = true;
            document.getElementById('eventDate').required = false;
        }
    };

    window.handleCategoryChange = function(category) {
        const form = document.getElementById('newEventForm');
        const noticeContainer = document.getElementById('noticeNoteContainer');
        const modalCategoryIcon = document.getElementById('modalCategoryIcon');
        const hostLabel = document.querySelector('label[for="eventStaffId"]');

        const icons = {
            'committee': '🤝',
            'permit': '📑',
            'briefing': '📢',
            'operation': '🎪',
            'other': '📌'
        };
        modalCategoryIcon.textContent = icons[category] || '📅';

        // 会議・打合せ選択時は会議エンドポイントへPOST、LINE特記メモを表示
        if (category === 'committee') {
            form.action = "{{ route('meetings.store') }}";
            noticeContainer.classList.remove('hidden');
            hostLabel.innerHTML = '主催者（スタッフ） <span class="text-slate-400 font-normal">（未定可・後から設定）</span>';
            document.getElementById('eventStaffId').name = 'staff_id';
            document.getElementById('eventDate').name = 'meeting_date';
            setScheduleMode('single');
            document.getElementById('modeSwitchContainer').classList.add('hidden');
            document.getElementById('statusFieldContainer').classList.add('hidden');
        } else {
            form.action = "{{ route('annual-schedules.store') }}";
            noticeContainer.classList.add('hidden');
            hostLabel.innerHTML = '担当スタッフ <span class="text-slate-400 font-normal">（任意・未定可）</span>';
            document.getElementById('eventStaffId').name = 'staff_id';
            document.getElementById('eventDate').name = 'start_date';
            document.getElementById('modeSwitchContainer').classList.remove('hidden');
            document.getElementById('statusFieldContainer').classList.remove('hidden');
        }
    };

    // 6. 詳細モーダルハンドラー
    window.openEventDetailModal = function(event) {
        currentDetailEvent = event;
        const modal = document.getElementById('eventDetailModal');

        document.getElementById('detailIcon').textContent = event.icon || '📅';
        document.getElementById('detailCategoryLabel').textContent = event.category_label || '予定';
        document.getElementById('detailTitle').textContent = event.title;

        let datetimeStr = event.start_date;
        if (event.is_period && event.end_date) {
            datetimeStr += ` 〜 ${event.end_date} (期間)`;
        } else if (event.time) {
            datetimeStr += ` ${event.time}`;
        }
        document.getElementById('detailDatetime').textContent = `📅 ${datetimeStr}`;
        document.getElementById('detailLocation').textContent = event.location ? `📍 ${event.location}` : '';

        // 主催者 / 担当者表示
        const staffEl = document.getElementById('detailAssignedStaff');
        const quickAssignContainer = document.getElementById('quickAssignHostContainer');
        if (event.has_unassigned_host) {
            staffEl.innerHTML = '<span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">主催者・担当者 未定（後から設定可）</span>';
            quickAssignContainer.classList.remove('hidden');
        } else {
            staffEl.innerHTML = `<span class="text-slate-900 font-bold">👤 ${event.staff_name}</span>`;
            quickAssignContainer.classList.add('hidden');
        }

        // 詳細説明
        document.getElementById('detailDescription').textContent = event.description || '詳細内容の登録はありません。';

        // LINE共有テキスト（会議のみ）
        const lineSection = document.getElementById('detailLineShareContainer');
        if (event.source === 'meeting' && event.line_share_text) {
            lineSection.classList.remove('hidden');
            document.getElementById('detailLineText').textContent = event.line_share_text;
        } else {
            lineSection.classList.add('hidden');
        }

        document.getElementById('detailShowLink').href = event.show_url || '#';
        document.getElementById('detailEditLink').href = event.edit_url || '#';

        modal.classList.remove('hidden');
    };

    window.closeEventDetailModal = function() {
        document.getElementById('eventDetailModal').classList.add('hidden');
        currentDetailEvent = null;
    };

    // 7. 主催者のクイック割り当て保存
    window.saveQuickAssignHost = function() {
        if (!currentDetailEvent) return;
        const select = document.getElementById('quickAssignStaffSelect');
        const staffId = select.value;
        if (!staffId) {
            alert('設定するスタッフを選択してください。');
            return;
        }

        const isMeeting = currentDetailEvent.source === 'meeting';
        const url = isMeeting ? currentDetailEvent.edit_url.replace('/edit', '') : currentDetailEvent.edit_url.replace('/edit', '');

        showToast('主催者を更新中...', '⏳');

        const payload = isMeeting
            ? { _method: 'PUT', staff_id: staffId, title: currentDetailEvent.title, meeting_date: currentDetailEvent.start_date, start_time: currentDetailEvent.start_time, location: currentDetailEvent.location }
            : { _method: 'PUT', staff_id: staffId, title: currentDetailEvent.title, start_date: currentDetailEvent.start_date, category: currentDetailEvent.category, status: currentDetailEvent.status };

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            showToast('主催者を設定しました！', '✔');
            setTimeout(() => {
                window.location.reload();
            }, 600);
        })
        .catch(() => {
            window.location.reload();
        });
    };

    // 8. LINE文面コピー
    window.copyDetailLineText = function() {
        if (!currentDetailEvent || !currentDetailEvent.line_share_text) return;
        navigator.clipboard.writeText(currentDetailEvent.line_share_text).then(() => {
            const btn = document.getElementById('copyLineTextBtn');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span>✔</span><span>コピー完了!</span>';
            btn.classList.replace('bg-emerald-600', 'bg-emerald-800');
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.replace('bg-emerald-800', 'bg-emerald-600');
            }, 2000);
            showToast('LINE告知文をクリップボードにコピーしました！', '📋');
        });
    };

    // 9. トースト表示
    function showToast(message, icon = '✔') {
        const toast = document.getElementById('toastNotification');
        document.getElementById('toastMessage').textContent = message;
        document.getElementById('toastIcon').textContent = icon;
        toast.classList.remove('hidden', 'translate-y-2');
        setTimeout(() => {
            toast.classList.add('hidden', 'translate-y-2');
        }, 3000);
    }

    // Escキーでモーダルを閉じる
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeNewEventModal();
            closeEventDetailModal();
        }
    });

})();
</script>
</x-layouts.app>
