<x-layouts.app title="会議・打ち合わせ">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- 1. ヘッダーエリア -->
    <x-page-header
        title="会議・打ち合わせ"
        icon="🤝"
        description="定例幹事会・部会打ち合わせの管理と、全体LINEグループへの告知テキスト自動生成">
        @can('create', App\Models\Meeting::class)
        <x-slot:actions>
            <a href="{{ route('meetings.create') }}"
                class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition space-x-1.5 cursor-pointer">
                <span>➕</span>
                <span>新しい会議を登録</span>
            </a>
        </x-slot:actions>
        @endcan
    </x-page-header>

    <!-- 2. タブ切り替え（開催予定 / 過去の記録） -->
    <div class="flex items-center space-x-2 border-b border-slate-200">
        <a href="{{ route('meetings.index', array_merge(request()->except('page'), ['tab' => 'upcoming'])) }}"
            class="px-4 py-3 text-xs sm:text-sm font-bold border-b-2 transition flex items-center space-x-2 {{ $currentTab === 'upcoming' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <span>📅 開催予定</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] {{ $currentTab === 'upcoming' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">
                {{ $upcomingCount }}
            </span>
        </a>

        <a href="{{ route('meetings.index', array_merge(request()->except('page'), ['tab' => 'past'])) }}"
            class="px-4 py-3 text-xs sm:text-sm font-bold border-b-2 transition flex items-center space-x-2 {{ $currentTab === 'past' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <span>📁 過去の記録</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] {{ $currentTab === 'past' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">
                {{ $pastCount }}
            </span>
        </a>
    </div>

    <!-- 3. 検索・絞り込みフォーム -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('meetings.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input type="hidden" name="tab" value="{{ $currentTab }}">

            <!-- キーワード -->
            <div>
                <label for="search" class="block text-[11px] font-bold text-slate-600 mb-1">フリーワード</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="会議名・場所・議題など"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- 主催者 -->
            <div>
                <label for="staff_id" class="block text-[11px] font-bold text-slate-600 mb-1">主催者（スタッフ）</label>
                <select id="staff_id" name="staff_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべての主催者</option>
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
                <a href="{{ route('meetings.index', ['tab' => $currentTab]) }}"
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium transition text-center">
                    リセット
                </a>
            </div>
        </form>
    </div>

    <!-- 4. 会議一覧テーブル -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-4 py-3 font-bold whitespace-nowrap">開催日時</th>
                        <th class="px-4 py-3 font-bold">会議名・議題</th>
                        <th class="px-4 py-3 font-bold whitespace-nowrap">開催場所</th>
                        <th class="px-4 py-3 font-bold whitespace-nowrap">主催者</th>
                        <th class="px-4 py-3 font-bold text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($meetings as $meeting)
                    <tr class="hover:bg-slate-50/70 transition">
                        <!-- 開催日時 -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <span class="font-mono font-bold text-slate-900 text-xs">
                                    {{ $meeting->meeting_date?->format('Y/m/d') }}
                                </span>
                                @php
                                    $weekdayNames = ['日', '月', '火', '水', '木', '金', '土'];
                                    $weekday = $meeting->meeting_date ? $weekdayNames[$meeting->meeting_date->dayOfWeek] : '';
                                    $isWeekend = in_array($meeting->meeting_date?->dayOfWeek, [0, 6], true);
                                @endphp
                                <span class="text-[11px] font-bold {{ $isWeekend ? 'text-rose-600' : 'text-slate-500' }}">
                                    ({{ $weekday }})
                                </span>
                            </div>
                            <div class="font-mono text-slate-600 text-[11px] mt-0.5">
                                🕒 {{ $meeting->formatted_time_range }}
                            </div>
                        </td>

                        <!-- 会議名・議題 -->
                        <td class="px-4 py-3 min-w-[220px]">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a href="{{ route('meetings.show', $meeting) }}" class="font-bold text-slate-900 text-sm hover:text-indigo-600 transition">
                                    {{ $meeting->title }}
                                </a>
                                @if($meeting->is_upcoming)
                                    @if($meeting->meeting_date?->isToday())
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        今日開催
                                    </span>
                                    @elseif($meeting->meeting_date?->diffInDays(now()) <= 3)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        まもなく開催
                                    </span>
                                    @endif
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-500">
                                        終了
                                    </span>
                                @endif
                            </div>
                            @if($meeting->description)
                            <div class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                <span class="font-bold text-slate-600">議題:</span> {{ $meeting->description }}
                            </div>
                            @endif
                        </td>

                        <!-- 開催場所 -->
                        <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                            <span class="inline-flex items-center space-x-1">
                                <span>📍</span>
                                <span>{{ $meeting->location }}</span>
                            </span>
                        </td>

                        <!-- 主催者 -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($meeting->host)
                            <span class="font-bold text-slate-800">{{ $meeting->host->name }}</span>
                            @if($meeting->host->affiliation)
                            <span class="text-slate-400 text-[11px]">({{ $meeting->host->affiliation }})</span>
                            @endif
                            @else
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">主催者未定</span>
                            @endif
                        </td>

                        <!-- 操作 -->
                        <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                            <button type="button"
                                data-action="copy-meeting-line"
                                data-line-text="{{ $meeting->share_text }}"
                                title="共有用の告知テキストをクリップボードにコピー"
                                class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold transition inline-flex items-center space-x-1 cursor-pointer">
                                <span>📋</span>
                                <span>案内文コピー</span>
                            </button>

                            <a href="{{ route('meetings.show', $meeting) }}"
                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold transition">
                                詳細
                            </a>

                            @can('update', $meeting)
                            <a href="{{ route('meetings.edit', $meeting) }}"
                                class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-[11px] font-bold transition">
                                編集
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-slate-400">
                            <span class="text-3xl block mb-2">🤝</span>
                            <div class="font-bold text-slate-700 mb-1">
                                {{ $currentTab === 'upcoming' ? '開催予定の会議はありません' : '過去の会議記録はありません' }}
                            </div>
                            <div class="text-[11px] text-slate-400 max-w-sm mx-auto">
                                {{ $currentTab === 'upcoming' ? '新しい会議や打ち合わせ日程が決まったら登録しましょう。' : '過去の会議アーカイブはここに表示されます。' }}
                            </div>
                            @can('create', App\Models\Meeting::class)
                            <div class="mt-3">
                                <a href="{{ route('meetings.create') }}"
                                    class="inline-flex items-center space-x-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition">
                                    <span>➕</span>
                                    <span>新しい会議を登録する</span>
                                </a>
                            </div>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. ページネーション -->
    <div class="pt-2">
        {{ $meetings->links() }}
    </div>

</div>

@vite(['resources/js/meetings.js'])
</x-layouts.app>
