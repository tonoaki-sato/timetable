<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-3">
    <!-- ヘッダー -->
    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
        <div class="flex items-center space-x-2">
            <span class="text-base">🤝</span>
            <h3 class="text-sm font-bold text-slate-900">直近の会議・打合せ</h3>
            @if($hasMeetings())
            <span class="text-[10px] bg-indigo-50 text-indigo-700 border border-indigo-200/80 px-1.5 py-0.5 rounded font-mono font-bold">
                {{ $meetings->count() }}件
            </span>
            @endif
        </div>
        <a href="{{ route('meetings.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition flex items-center space-x-0.5">
            <span>一覧</span>
            <span class="text-[10px]">→</span>
        </a>
    </div>

    @if($hasMeetings())
    <!-- ミーティングリスト（最大3件） -->
    <div class="space-y-2.5">
        @foreach($meetings as $meeting)
        <div class="p-3 bg-slate-50/80 hover:bg-slate-100/80 rounded-xl border border-slate-200/80 transition space-y-2 group">
            <div class="flex items-start space-x-3">
                <!-- 日付ブロック -->
                <div class="shrink-0 text-center w-12 bg-white border border-slate-200 rounded-lg py-1 px-1 shadow-2xs">
                    <span class="block text-xs font-black text-slate-900 leading-none">
                        {{ $meeting->meeting_date?->format('n/j') }}
                    </span>
                    <span class="block text-[10px] font-bold {{ $isWeekend($meeting) ? 'text-red-600' : 'text-slate-500' }} leading-tight mt-0.5">
                        ({{ $weekdayText($meeting) }})
                    </span>
                </div>

                <!-- 会議情報メイン -->
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <a href="{{ route('meetings.show', $meeting) }}" class="text-xs font-bold text-slate-900 group-hover:text-indigo-600 transition truncate">
                            {{ $meeting->title }}
                        </a>
                        @if($badge = $badgeInfo($meeting))
                        <span class="px-1.5 py-0.5 text-[9px] font-bold rounded border {{ $badge['class'] }}">
                            {{ $badge['text'] }}
                        </span>
                        @endif
                    </div>

                    <!-- メタ情報（時間・場所） -->
                    <div class="flex items-center gap-x-2 text-[11px] text-slate-500 mt-1 flex-wrap">
                        <span class="inline-flex items-center space-x-0.5 font-mono">
                            <span>🕒</span>
                            <span>{{ $meeting->formatted_time_range }}</span>
                        </span>
                        @if($meeting->location)
                        <span class="inline-flex items-center space-x-0.5">
                            <span>📍</span>
                            <span class="truncate max-w-[120px]">{{ $meeting->location }}</span>
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- フッター行（主催者 & 詳細リンク） -->
            <div class="flex items-center justify-between text-[11px] pt-1.5 border-t border-slate-200/60 text-slate-500">
                <span class="inline-flex items-center space-x-1">
                    <span>👤</span>
                    <span>主催: <strong class="text-slate-700 font-medium">{{ $meeting->host?->name ?? '未定' }}</strong></span>
                </span>
                <a href="{{ route('meetings.show', $meeting) }}" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-bold transition">
                    詳細 →
                </a>
            </div>
        </div>
        @endforeach
    </div>

    @can('create', App\Models\Meeting::class)
    <div class="pt-1 text-right">
        <a href="{{ route('meetings.create') }}" class="inline-flex items-center space-x-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
            <span>➕</span>
            <span>新しい会議を登録</span>
        </a>
    </div>
    @endcan

    @else
    <!-- 空状態（Empty State） -->
    <div class="text-center py-6 px-4 bg-slate-50/60 rounded-xl border border-dashed border-slate-200">
        <span class="text-2xl block mb-1">📅</span>
        <p class="text-xs font-bold text-slate-700">予定されている会議はありません</p>
        <p class="text-[11px] text-slate-400 mt-0.5">定例幹事会や部会打ち合わせを登録できます</p>
        @can('create', App\Models\Meeting::class)
        <a href="{{ route('meetings.create') }}" class="inline-flex items-center space-x-1 mt-3 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition shadow-xs">
            <span>➕</span>
            <span>新しい会議を登録</span>
        </a>
        @endcan
    </div>
    @endif
</div>
