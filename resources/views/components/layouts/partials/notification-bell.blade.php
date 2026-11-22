<!-- ヘッダー通知ベル (未対応現場要請) -->
<div class="nav-dropdown relative shrink-0">
    <button type="button"
        id="header-notification-bell"
        aria-label="現場要請通知"
        title="未対応の班長現場要請"
        class="dropdown-trigger relative inline-flex items-center justify-center p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg transition cursor-pointer">
        <span class="text-sm leading-none">🔔</span>
        @if(($headerPendingRequestsCount ?? 0) > 0)
            <span id="header-notification-badge"
                class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white font-mono text-[10px] font-bold flex items-center justify-center shadow-xs">
                {{ $headerPendingRequestsCount > 99 ? '99+' : $headerPendingRequestsCount }}
            </span>
        @endif
    </button>

    <div class="dropdown-menu hidden absolute right-0 mt-1.5 w-80 sm:w-96 max-h-[calc(100vh-4.5rem)] overflow-y-auto bg-slate-900 border border-slate-700/80 rounded-2xl shadow-xl p-2 z-50">
        <div class="px-2.5 py-1.5 border-b border-slate-800 flex items-center justify-between">
            <span class="text-xs font-bold text-white flex items-center space-x-1.5">
                <span>🔔</span>
                <span>未対応の現場要請 ({{ $headerPendingRequestsCount ?? 0 }}件)</span>
            </span>
            <span class="text-[10px] text-slate-400 font-mono">全運行日</span>
        </div>

        @if(($headerPendingRequests ?? collect())->isNotEmpty())
            <div class="divide-y divide-slate-800/80 mt-1">
                @foreach($headerPendingRequests as $req)
                    <div class="p-2.5 rounded-xl hover:bg-slate-800/70 transition space-y-1.5 text-xs">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="font-bold text-slate-100 truncate">
                                    [{{ $req->timetableLane?->name ?? '班' }}] {{ $req->type_label }}
                                </div>
                                <div class="text-[11px] text-slate-300 mt-0.5">
                                    @if($req->request_type === 'equipment')
                                        機材: <strong class="text-white">{{ $req->equipment_name }}</strong> × {{ $req->requested_count }}
                                    @else
                                        応援要請: <strong class="text-white">+{{ $req->requested_count }}名</strong>
                                        @if($req->task)
                                            （{{ $req->task->title }}）
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <span class="shrink-0 px-1.5 py-0.5 rounded text-[10px] font-bold {{ $req->status === 'pending' ? 'bg-amber-500/20 text-amber-300 border border-amber-400/30' : 'bg-sky-500/20 text-sky-300 border border-sky-400/30' }}">
                                {{ $req->status_label }}
                            </span>
                        </div>

                        @if($req->reason)
                            <p class="text-[11px] text-slate-400 line-clamp-2">{{ $req->reason }}</p>
                        @endif

                        <div class="flex items-center justify-between gap-2 pt-1">
                            <div class="text-[10px] text-slate-400 truncate">
                                申請: {{ $req->requestedByStaff?->name ?? '班長' }}
                                @if($req->timetable_id)
                                    · <a href="{{ route('timetables.show', $req->timetable_id) }}" class="text-indigo-300 hover:underline">運行計画を開く</a>
                                @endif
                            </div>

                            @can('update', $req->timetableLane ?? App\Models\TimetableLane::class)
                            <div class="flex items-center space-x-1 shrink-0">
                                @if($req->status === 'pending')
                                <form action="{{ route('lane-requests.update-status', $req) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="px-2 py-0.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-[10px] font-bold transition cursor-pointer">
                                        手配
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('lane-requests.update-status', $req) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="resolved">
                                    <button type="submit" class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[10px] font-bold transition cursor-pointer">
                                        完了
                                    </button>
                                </form>
                            </div>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-6 px-3 text-center text-xs text-slate-400">
                現在、未対応の現場要請はありません。
            </div>
        @endif
    </div>
</div>
