<x-layouts.app title="年間スケジュール管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- 1. ヘッダーエリア -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">📅 年間スケジュール管理</h2>
                <span class="bg-indigo-100 text-indigo-700 text-xs px-2.5 py-0.5 rounded-full font-bold">
                    {{ $selectedYear }}年度
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                まつり準備会・行政協議・設営・本祭運行・撤収にわたる年間マイルストーンを管理します。
            </p>
        </div>

        @can('create', App\Models\AnnualSchedule::class)
        <a href="{{ route('annual-schedules.create', ['year' => $selectedYear]) }}"
            class="inline-flex items-center space-x-1.5 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
            <span>➕</span>
            <span>新規予定を登録</span>
        </a>
        @endcan
    </div>

    <!-- 2. サマリーステータスカード -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <span class="text-[11px] font-bold text-slate-500">年間総件数</span>
            <div class="text-xl font-bold text-slate-900 mt-0.5 font-mono">{{ $yearSummary['total'] }} <span class="text-xs font-normal text-slate-500">件</span></div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-blue-200 shadow-xs">
            <span class="text-[11px] font-bold text-blue-700">予定（未着手）</span>
            <div class="text-xl font-bold text-blue-700 mt-0.5 font-mono">{{ $yearSummary['planned'] }} <span class="text-xs font-normal text-slate-500">件</span></div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-amber-200 shadow-xs">
            <span class="text-[11px] font-bold text-amber-700">進行中</span>
            <div class="text-xl font-bold text-amber-700 mt-0.5 font-mono">{{ $yearSummary['in_progress'] }} <span class="text-xs font-normal text-slate-500">件</span></div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-emerald-200 shadow-xs">
            <span class="text-[11px] font-bold text-emerald-700">完了</span>
            <div class="text-xl font-bold text-emerald-700 mt-0.5 font-mono">{{ $yearSummary['completed'] }} <span class="text-xs font-normal text-slate-500">件</span></div>
        </div>
    </div>

    <!-- 3. 年度切り替えタブ -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-2">
        <span class="text-xs font-bold text-slate-500 mr-2">年度選択:</span>
        @foreach($availableYears as $year)
        <a href="{{ route('annual-schedules.index', array_merge(request()->except('page'), ['year' => $year])) }}"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ (int)$selectedYear === (int)$year ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            {{ $year }}年度
        </a>
        @endforeach
    </div>

    <!-- 4. 検索・絞り込みフォーム -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('annual-schedules.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <input type="hidden" name="year" value="{{ $selectedYear }}">

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
                <label for="staff_id" class="block text-[11px] font-bold text-slate-600 mb-1">担当者</label>
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
                <a href="{{ route('annual-schedules.index', ['year' => $selectedYear]) }}"
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
                                    'committee' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'permit' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'briefing' => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'operation' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'ceremony' => 'bg-rose-50 text-rose-700 border-rose-200',
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
</x-layouts.app>
