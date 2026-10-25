<x-layouts.app title="会議・打ち合わせ">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- 1. ヘッダーエリア -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="text-2xl">🤝</span>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">会議・打ち合わせ</h1>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                定例幹事会・部会打ち合わせの管理と、全体LINEグループへの告知テキスト自動生成
            </p>
        </div>

        <div class="flex items-center space-x-2 shrink-0">
            @can('create', App\Models\Meeting::class)
            <a href="{{ route('meetings.create') }}"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition space-x-1.5 cursor-pointer">
                <span>➕</span>
                <span>新しい会議を登録</span>
            </a>
            @endcan
        </div>
    </div>

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

    <!-- 4. 会議一覧カード -->
    <div class="space-y-3">
        @forelse($meetings as $meeting)
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs hover:border-slate-300 transition p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

            <!-- 左側: 日時・タイトル・場所・主催者・議題 -->
            <div class="flex items-start space-x-4 min-w-0">
                <!-- 日付バッジ -->
                <div class="shrink-0 text-center w-16 sm:w-20 bg-slate-50 border border-slate-200 rounded-xl p-2">
                    <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        {{ $meeting->meeting_date?->format('Y年') }}
                    </span>
                    <span class="block text-base sm:text-lg font-black text-slate-900 leading-tight">
                        {{ $meeting->meeting_date?->format('m/d') }}
                    </span>
                    @php
                        $weekdayNames = ['日', '月', '火', '水', '木', '金', '土'];
                        $weekday = $meeting->meeting_date ? $weekdayNames[$meeting->meeting_date->dayOfWeek] : '';
                        $isWeekend = in_array($meeting->meeting_date?->dayOfWeek, [0, 6], true);
                    @endphp
                    <span class="block text-[11px] font-bold {{ $isWeekend ? 'text-red-600' : 'text-slate-600' }}">
                        ({{ $weekday }})
                    </span>
                </div>

                <!-- 会議情報メイン -->
                <div class="space-y-1.5 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('meetings.show', $meeting) }}"
                            class="text-base font-bold text-slate-900 hover:text-indigo-600 transition">
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

                    <!-- メタ情報行 -->
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600">
                        <span class="inline-flex items-center space-x-1">
                            <span>🕒</span>
                            <span class="font-mono font-bold">{{ $meeting->formatted_time_range }}</span>
                        </span>

                        <span class="inline-flex items-center space-x-1">
                            <span>📍</span>
                            <span>{{ $meeting->location }}</span>
                        </span>

                        <span class="inline-flex items-center space-x-1">
                            <span>👤 主催:</span>
                            @if($meeting->host)
                            <span class="font-bold text-slate-800">{{ $meeting->host->name }}</span>
                            @if($meeting->host->affiliation)
                            <span class="text-slate-400 text-[11px]">({{ $meeting->host->affiliation }})</span>
                            @endif
                            @else
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">主催者未定（後から設定可）</span>
                            @endif
                        </span>
                    </div>

                    <!-- 議題プレビュー -->
                    @if($meeting->description)
                    <p class="text-xs text-slate-500 line-clamp-1 pt-0.5">
                        <span class="font-bold text-slate-600">議題:</span> {{ $meeting->description }}
                    </p>
                    @endif
                </div>
            </div>

            <!-- 右側: アクションボタン群（LINEコピー、詳細、編集） -->
            <div class="flex items-center space-x-2 shrink-0 pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100 justify-end">
                <!-- LINEテキストクイックコピーボタン -->
                <button type="button"
                    data-action="copy-meeting-line"
                    data-line-text="{{ $meeting->line_share_text }}"
                    title="全体LINE用の告知テキストをクリップボードにコピー"
                    class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 cursor-pointer">
                    <span>📋</span>
                    <span>LINE文面コピー</span>
                </button>

                <a href="{{ route('meetings.show', $meeting) }}"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition">
                    詳細
                </a>

                @can('update', $meeting)
                <a href="{{ route('meetings.edit', $meeting) }}"
                    class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition">
                    編集
                </a>
                @endcan
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
            <span class="text-4xl block mb-2">🤝</span>
            <h3 class="text-base font-bold text-slate-800">
                {{ $currentTab === 'upcoming' ? '開催予定の会議はありません' : '過去の会議記録はありません' }}
            </h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                {{ $currentTab === 'upcoming' ? '新しい会議や打ち合わせ日程が決まったら登録しましょう。' : '過去の会議アーカイブはここに表示されます。' }}
            </p>
            @can('create', App\Models\Meeting::class)
            <div class="mt-4">
                <a href="{{ route('meetings.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                    新しい会議を登録する
                </a>
            </div>
            @endcan
        </div>
        @endforelse
    </div>

    <!-- 5. ページネーション -->
    <div class="pt-2">
        {{ $meetings->links() }}
    </div>

</div>

@vite(['resources/js/meetings.js'])
</x-layouts.app>
