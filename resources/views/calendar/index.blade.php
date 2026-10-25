<x-layouts.app title="総合カレンダー">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- 1. ヘッダーエリア -->
    <x-page-header
        title="総合カレンダー"
        icon="📅"
        description="行政締切、準備タスク、定例会議、まつり本番運行日のすべてをカレンダー上で俯瞰・調整できます。">
        @can('create', App\Models\AnnualSchedule::class)
        <x-slot:actions>
            <button type="button" data-action="open-new-event-modal"
                class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                <span>➕</span>
                <span>新規予定を登録</span>
            </button>
        </x-slot:actions>
        @endcan
    </x-page-header>

    <!-- 2. 検索・絞り込みフォーム -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('calendar.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <input type="hidden" name="month" value="{{ $currentMonth ?? 4 }}">

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
                <a href="{{ route('calendar.index', ['year' => $selectedYear, 'month' => $currentMonth ?? 4]) }}"
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium transition text-center">
                    リセット
                </a>
            </div>
        </form>
    </div>

    <!-- 3. 月ナビゲーション & カレンダーヘッダー -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60">
            <!-- 月送りナビゲーション -->
            <div class="flex items-center space-x-2">
                <a href="{{ route('calendar.index', array_merge(request()->except('month', 'year'), ['year' => $prevYear, 'month' => $prevMonth])) }}"
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

                <a href="{{ route('calendar.index', array_merge(request()->except('month', 'year'), ['year' => $nextYear, 'month' => $nextMonth])) }}"
                    class="p-2 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg text-slate-700 text-xs font-bold transition shadow-xs flex items-center space-x-1"
                    title="翌月へ">
                    <span class="hidden sm:inline">{{ $nextMonth }}月</span>
                    <span>▶</span>
                </a>

                <a href="{{ route('calendar.index', array_merge(request()->except('month', 'year'), ['year' => now()->year, 'month' => now()->month])) }}"
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition">
                    今月
                </a>
            </div>

            <!-- 年度セレクター -->
            <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 sm:pb-0">
                <span class="text-[11px] font-bold text-slate-500 mr-1 whitespace-nowrap">年度:</span>
                @foreach($availableYears as $year)
                <a href="{{ route('calendar.index', array_merge(request()->except('year'), ['year' => $year, 'month' => $currentMonth])) }}"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition whitespace-nowrap {{ (int)$selectedYear === (int)$year ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                    {{ $year }}
                </a>
                @endforeach
            </div>
        </div>

        <!-- 4. カレンダー曜日ヘッダー (日〜土) -->
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-100/90 text-center text-xs font-bold text-slate-700 select-none">
            <div class="py-2.5 text-red-600 bg-red-50/40">日 (Sun)</div>
            <div class="py-2.5">月 (Mon)</div>
            <div class="py-2.5">火 (Tue)</div>
            <div class="py-2.5">水 (Wed)</div>
            <div class="py-2.5">木 (Thu)</div>
            <div class="py-2.5">金 (Fri)</div>
            <div class="py-2.5 text-blue-600 bg-blue-50/40">土 (Sat)</div>
        </div>

        <!-- 5. カレンダーマス目グリッド -->
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
                            class="add-event-cell-btn opacity-0 group-hover:opacity-100 p-1 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded text-[11px] transition cursor-pointer">
                            ➕
                        </button>
                    </div>

                    <!-- 予定ピル一覧 -->
                    <div class="space-y-1.5 flex-1 overflow-y-auto max-h-[110px] pr-0.5">
                        @foreach($day['events'] as $event)
                        <div data-event-id="{{ $event['id'] }}"
                            data-event-source="{{ $event['source'] }}"
                            data-event-json="{{ json_encode($event) }}"
                            class="event-pill cursor-pointer p-1.5 rounded-lg border text-[11px] transition shadow-xs hover:shadow-sm select-none leading-tight {{ $event['badge_class'] }}"
                            title="クリックで詳細表示">
                            
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

                </div>
                @endforeach
            @endforeach
        </div>
    </div>

</div>

<!-- ========================================== -->
<!-- 6. 新規予定登録モーダル (New Event Modal)   -->
<!-- ========================================== -->
<div id="newEventModal"
    data-meetings-store-url="{{ route('meetings.store') }}"
    data-annual-schedules-store-url="{{ route('annual-schedules.store') }}"
    data-default-date="{{ $currentYear }}-{{ sprintf('%02d', $currentMonth) }}-01"
    class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" data-action="close-new-event-modal"></div>
    
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden z-10 transition transform">
            <!-- モーダルヘッダー -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span id="modalCategoryIcon" class="text-xl">🤝</span>
                    <h2 class="text-base font-bold" id="modalTitle">新しい予定・会議の登録</h2>
                </div>
                <button type="button" data-action="close-new-event-modal" class="text-slate-400 hover:text-white transition text-lg cursor-pointer">✕</button>
            </div>

            <!-- フォーム本体 -->
            <form id="newEventForm" method="POST" action="{{ route('annual-schedules.store') }}" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="target_year" id="formTargetYear" value="{{ $selectedYear }}">

                <!-- 種別（カテゴリ）選択 -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">予定の種別（カテゴリ） <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                        @foreach($categoryOptions as $palette)
                        <label class="cursor-pointer">
                            <input type="radio" name="category" value="{{ $palette['key'] }}" class="peer sr-only">
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
                            <button type="button" id="btnModeSingle" data-date-mode="single"
                                class="px-2.5 py-1 rounded-md text-[11px] bg-white text-indigo-700 shadow-xs cursor-pointer">
                                単日
                            </button>
                            <button type="button" id="btnModePeriod" data-date-mode="period"
                                class="px-2.5 py-1 rounded-md text-[11px] text-slate-600 hover:text-slate-900 cursor-pointer">
                                期間指定
                            </button>
                        </div>
                    </div>

                    <!-- 単日指定時 -->
                    <div id="singleDateFields" class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <div class="sm:col-span-1">
                            <label for="eventDate" class="block text-[11px] font-bold text-slate-600 mb-1">開催日 <span class="text-red-500">*</span></label>
                            <input type="date" id="eventDate" name="start_date" required
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>
                        <div id="timeFields" class="sm:col-span-2 grid grid-cols-2 gap-2">
                            <div>
                                <label for="eventStartTime" class="block text-[11px] font-bold text-slate-600 mb-1">開始時刻 <span class="text-red-500">*</span></label>
                                <input type="time" id="eventStartTime" name="start_time" value="18:00"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                            </div>
                            <div>
                                <label for="eventEndTime" class="block text-[11px] font-bold text-slate-600 mb-1">終了時刻</label>
                                <input type="time" id="eventEndTime" name="end_time" value="20:00"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                            </div>
                        </div>
                    </div>

                    <!-- 期間指定時（年間スケジュールのみ） -->
                    <div id="periodDateFields" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label for="eventStartDate" class="block text-[11px] font-bold text-slate-600 mb-1">開始日 <span class="text-red-500">*</span></label>
                            <input type="date" id="eventStartDate" name="start_date_period"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>
                        <div>
                            <label for="eventEndDate" class="block text-[11px] font-bold text-slate-600 mb-1">終了日（締切日） <span class="text-red-500">*</span></label>
                            <input type="date" id="eventEndDate" name="end_date"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>
                    </div>
                </div>

                <!-- 開催場所 & 主催・担当者 -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="eventLocation" class="block text-xs font-bold text-slate-700 mb-1">場所・会場 <span class="text-slate-400 font-normal">（任意）</span></label>
                        <input type="text" id="eventLocation" name="location" placeholder="例: コミュニティルーム / オンライン"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="eventStaffId" class="block text-xs font-bold text-slate-700 mb-1" id="hostFieldLabel">主催・担当スタッフ <span class="text-slate-400 font-normal">（任意・未定可）</span></label>
                        <select id="eventStaffId" name="staff_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">未設定（後から指定）</option>
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
                    <button type="button" data-action="close-new-event-modal"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer">
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
<!-- 7. 予定詳細 & クイック編集モーダル         -->
<!-- ========================================== -->
<div id="eventDetailModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" data-action="close-event-detail-modal"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden z-10">
            <!-- ヘッダー -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span id="detailIcon" class="text-xl">📅</span>
                    <span id="detailCategoryLabel" class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-600/80">予定</span>
                </div>
                <button type="button" data-action="close-event-detail-modal" class="text-slate-400 hover:text-white transition text-lg cursor-pointer">✕</button>
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
                            <button type="button" data-action="save-quick-assign-host" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs cursor-pointer">
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
                        <button type="button" id="copyLineTextBtn" data-action="copy-detail-line-text"
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
                        <button type="button" data-action="close-event-detail-modal" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition cursor-pointer">
                            閉じる
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 8. フローティング トースト通知            -->
<!-- ========================================== -->
<div id="toastNotification" class="hidden fixed bottom-6 right-6 z-50 px-4 py-3 bg-slate-900 text-white text-xs font-bold rounded-xl shadow-2xl border border-slate-700 flex items-center space-x-2 transition transform translate-y-2">
    <span id="toastIcon">✔</span>
    <span id="toastMessage">完了しました</span>
</div>

@vite(['resources/js/calendar.js'])
</x-layouts.app>
