<x-layouts.app title="スケジュール管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- 1. ヘッダーエリア -->
    <x-page-header
        title="スケジュール管理"
        icon="📅"
        description="会議・打ち合わせ・行政協議・出店募集・会場設営などのスケジュールを一元管理します。">
        @can('create', App\Models\Schedule::class)
        <x-slot:actions>
            <a href="{{ route('schedules.create') }}"
                class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                <span>➕</span>
                <span>新規登録</span>
            </a>
        </x-slot:actions>
        @endcan
    </x-page-header>

    <!-- 成功・エラーメッセージ表示 -->
    @if(session('success'))
    <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 font-bold flex items-center space-x-2">
        <span>✔</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-800 font-bold flex items-center space-x-2">
        <span>⚠️</span>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <!-- 2. サマリーステータスカード -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <div class="bg-white px-3 py-2 rounded-lg border border-slate-200 shadow-2xs flex items-center justify-between">
            <span class="text-xs font-bold text-slate-600">総登録件数</span>
            <span class="text-base font-bold font-mono text-slate-900">{{ $statusSummary['total'] }}<span class="text-[11px] font-normal text-slate-400 ml-0.5">件</span></span>
        </div>
        <div class="bg-white px-3 py-2 rounded-lg border border-blue-200 shadow-2xs flex items-center justify-between">
            <span class="text-xs font-bold text-blue-700">予定</span>
            <span class="text-base font-bold font-mono text-blue-700">{{ $statusSummary['planned'] }}<span class="text-[11px] font-normal text-blue-400 ml-0.5">件</span></span>
        </div>
        <div class="bg-white px-3 py-2 rounded-lg border border-amber-200 shadow-2xs flex items-center justify-between">
            <span class="text-xs font-bold text-amber-700">進行中</span>
            <span class="text-base font-bold font-mono text-amber-700">{{ $statusSummary['in_progress'] }}<span class="text-[11px] font-normal text-amber-400 ml-0.5">件</span></span>
        </div>
        <div class="bg-white px-3 py-2 rounded-lg border border-emerald-200 shadow-2xs flex items-center justify-between">
            <span class="text-xs font-bold text-emerald-700">完了</span>
            <span class="text-base font-bold font-mono text-emerald-700">{{ $statusSummary['completed'] }}<span class="text-[11px] font-normal text-emerald-400 ml-0.5">件</span></span>
        </div>
    </div>

    <!-- 3. タブ切り替え（全期間 / 直近予定 / 過去ログ） -->
    <div class="flex items-center space-x-2 border-b border-slate-200">
        <a href="{{ route('schedules.index', array_merge(request()->except('page', 'tab'), [])) }}"
            class="px-4 py-3 text-xs sm:text-sm font-bold border-b-2 transition flex items-center space-x-2 {{ empty($currentTab) ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <span>📋 すべての期間</span>
        </a>
        <a href="{{ route('schedules.index', array_merge(request()->except('page'), ['tab' => 'upcoming'])) }}"
            class="px-4 py-3 text-xs sm:text-sm font-bold border-b-2 transition flex items-center space-x-2 {{ $currentTab === 'upcoming' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <span>📅 今後の予定</span>
        </a>
        <a href="{{ route('schedules.index', array_merge(request()->except('page'), ['tab' => 'past'])) }}"
            class="px-4 py-3 text-xs sm:text-sm font-bold border-b-2 transition flex items-center space-x-2 {{ $currentTab === 'past' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <span>📁 過去の記録</span>
        </a>
    </div>

    <!-- 4. 検索・絞り込みフォーム & カテゴリクイックフィルター -->
    <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
        <!-- カテゴリクイックボタン -->
        <div class="flex items-center space-x-2 border-b border-slate-100 pb-3 overflow-x-auto">
            <span class="text-xs font-bold text-slate-500 whitespace-nowrap">区分:</span>
            <a href="{{ route('schedules.index', array_merge(request()->except('page', 'category'), [])) }}"
                class="px-3 py-1 rounded-lg text-xs font-bold transition whitespace-nowrap {{ empty($currentCategory) ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                すべて
            </a>
            @foreach($categories as $key => $label)
            @php
                $catIcon = match($key) {
                    'committee' => '🤝',
                    'permit' => '📑',
                    'briefing' => '📢',
                    'operation' => '🎪',
                    default => '📌',
                };
            @endphp
            <a href="{{ route('schedules.index', array_merge(request()->except('page'), ['category' => $key])) }}"
                class="px-3 py-1 rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center space-x-1 {{ $currentCategory === $key ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <span>{{ $catIcon }}</span>
                <span>{{ $label }}</span>
            </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('schedules.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @if($currentTab)
            <input type="hidden" name="tab" value="{{ $currentTab }}">
            @endif

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

    <!-- 5. スケジュール一覧テーブル -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-4 py-3 font-bold whitespace-nowrap">開催日・期間</th>
                        <th class="px-4 py-3 font-bold whitespace-nowrap">区分</th>
                        <th class="px-4 py-3 font-bold">タイトル・議題</th>
                        <th class="px-4 py-3 font-bold whitespace-nowrap">場所</th>
                        <th class="px-4 py-3 font-bold whitespace-nowrap">担当・主催</th>
                        <th class="px-4 py-3 font-bold whitespace-nowrap">状態</th>
                        <th class="px-4 py-3 font-bold text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($schedules as $schedule)
                    <tr class="hover:bg-slate-50/70 transition">
                        <!-- 日時 -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="space-y-0.5">
                                <div class="font-mono font-bold text-slate-900 text-xs flex items-center space-x-1.5">
                                    <span>{{ $schedule->start_date?->format('Y/m/d') }}</span>
                                    @if($schedule->end_date && $schedule->end_date->ne($schedule->start_date))
                                    <span class="text-slate-400 font-normal">〜 {{ $schedule->end_date->format('Y/m/d') }}</span>
                                    @endif
                                </div>
                                @if($schedule->formatted_time_range)
                                <div class="text-[11px] font-mono text-indigo-600 font-medium">
                                    ⏰ {{ $schedule->formatted_time_range }}
                                </div>
                                @endif
                                @if($schedule->is_today)
                                <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">本日</span>
                                @endif
                            </div>
                        </td>

                        <!-- 区分 -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($schedule->is_linked_to_timetable)
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold border flex items-center space-x-1 w-max bg-rose-50 text-rose-700 border-rose-200">
                                <span>🏮</span>
                                <span>運行スケジュール</span>
                            </span>
                            @else
                            @php
                                $badgeMeta = match($schedule->category) {
                                    'committee' => ['icon' => '🤝', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
                                    'permit' => ['icon' => '📑', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
                                    'briefing' => ['icon' => '📢', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
                                    'operation' => ['icon' => '🎪', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                    default => ['icon' => '📌', 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold border flex items-center space-x-1 w-max {{ $badgeMeta['class'] }}">
                                <span>{{ $badgeMeta['icon'] }}</span>
                                <span>{{ $schedule->category_label }}</span>
                            </span>
                            @endif
                        </td>

                        <!-- タイトル・議題 -->
                        <td class="px-4 py-3">
                            <div class="space-y-1 max-w-md">
                                <div class="flex items-center space-x-1.5 flex-wrap">
                                    <a href="{{ route('schedules.show', $schedule) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition">
                                        {{ $schedule->title }}
                                    </a>
                                    @if($schedule->timetable_id)
                                    <a href="{{ route('timetables.show', $schedule->timetable_id) }}"
                                        class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 inline-flex items-center space-x-0.5 transition"
                                        title="タイムテーブル詳細へ">
                                        <span>⏱️</span>
                                        <span>運行枠</span>
                                    </a>
                                    <a href="{{ route('timetable.index', ['timetable_id' => $schedule->timetable_id]) }}"
                                        class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 inline-flex items-center space-x-0.5 transition"
                                        title="現場進行マトリクスへ">
                                        <span>📊</span>
                                        <span>進行表</span>
                                    </a>
                                    @endif
                                </div>
                                @if($schedule->description)
                                <p class="text-[11px] text-slate-500 line-clamp-1 leading-relaxed">
                                    {{ $schedule->description }}
                                </p>
                                @endif
                                @if($schedule->notice_note)
                                <p class="text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200/60 line-clamp-1">
                                    📌 {{ $schedule->notice_note }}
                                </p>
                                @endif
                            </div>
                        </td>

                        <!-- 場所 -->
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                            {{ $schedule->location ?: '-' }}
                        </td>

                        <!-- 担当・主催 -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($schedule->assignee)
                            <div class="font-medium text-slate-900">
                                {{ $schedule->assignee->name }}
                            </div>
                            @if($schedule->assignee->affiliation)
                            <div class="text-[10px] text-slate-400">
                                {{ $schedule->assignee->affiliation }}
                            </div>
                            @endif
                            @else
                            <span class="text-slate-400 italic">未定</span>
                            @endif
                        </td>

                        <!-- 状態 -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php
                                $statusClass = match($schedule->status) {
                                    'planned' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'in_progress' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'cancelled' => 'bg-slate-100 text-slate-500 border-slate-200',
                                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold border {{ $statusClass }}">
                                {{ $schedule->status_label }}
                            </span>
                        </td>

                        <!-- 操作 -->
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end space-x-1.5">
                                <a href="{{ route('schedules.show', $schedule) }}"
                                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition flex items-center space-x-1"
                                    title="詳細・LINE告知文">
                                    <span>👁️</span>
                                    <span>詳細</span>
                                </a>

                                @can('update', $schedule)
                                <a href="{{ route('schedules.edit', $schedule) }}"
                                    class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition"
                                    title="編集">
                                    ✏️
                                </a>
                                @endcan

                                @can('delete', $schedule)
                                <form action="{{ route('schedules.destroy', $schedule) }}" method="POST" class="inline"
                                    data-confirm="予定「{{ $schedule->title }}」を削除しますか？">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                        title="削除">
                                        🗑️
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                            <span class="text-2xl block mb-2">📋</span>
                            条件に一致するスケジュールはありません。
                        </td>
                    </tr>
                    @endforelse
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
</x-layouts.app>

