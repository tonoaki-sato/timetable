<!-- 右カラム (lg:col-span-4): 物流サマリー ＆ 現場要請 ＆ 削除アクション -->
<div class="lg:col-span-4 space-y-3">
    <!-- 資材搬出入・物流配備サマリー -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 space-y-2.5">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-xs text-slate-900 flex items-center space-x-1.5">
                    <span>🚚</span>
                    <span>資材搬出入・物流配備サマリー</span>
                </h3>
            </div>
            <a href="{{ route('timetables.logistics.index', $timetable) }}"
                class="px-2 py-0.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[11px] font-bold transition flex items-center space-x-0.5 shadow-2xs">
                <span>搬出入計画へ</span>
                <span>→</span>
            </a>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                <span class="text-[10px] font-bold text-slate-500 block">配備資材数</span>
                <div class="flex items-baseline space-x-1">
                    <strong class="text-base font-bold font-mono text-slate-900">{{ $overview->uniqueEquipmentCount }}</strong>
                    <span class="text-[10px] text-slate-500">種</span>
                </div>
            </div>

            <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                <span class="text-[10px] font-bold text-slate-500 block">計画ステップ総数</span>
                <div class="flex items-baseline space-x-1">
                    <strong class="text-base font-bold font-mono text-slate-900">{{ $overview->totalLogisticsPlans }}</strong>
                    <span class="text-[10px] text-slate-500">件</span>
                </div>
            </div>

            <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                <span class="text-[10px] font-bold text-slate-500 block">完了ステップ</span>
                <div class="flex items-baseline space-x-1">
                    <strong class="text-base font-bold font-mono {{ $overview->completedLogisticsPlans === $overview->totalLogisticsPlans && $overview->totalLogisticsPlans > 0 ? 'text-emerald-600' : 'text-indigo-600' }}">
                        {{ $overview->completedLogisticsPlans }} / {{ $overview->totalLogisticsPlans }}
                    </strong>
                    <span class="text-[9px] text-slate-500 font-mono">({{ $overview->logisticsProgressPercent }}%)</span>
                </div>
            </div>

            <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                <span class="text-[10px] font-bold text-slate-500 block">スタッフ立会必須</span>
                <div class="flex items-baseline space-x-1">
                    <strong class="text-base font-bold font-mono {{ $overview->witnessRequiredPlans > 0 ? 'text-red-600' : 'text-slate-700' }}">{{ $overview->witnessRequiredPlans }}</strong>
                    <span class="text-[10px] text-slate-500">件</span>
                </div>
            </div>
        </div>

        @if($overview->totalLogisticsPlans === 0)
        <div class="p-2 bg-indigo-50/70 rounded-lg border border-indigo-100 text-[11px] text-indigo-900 flex items-center justify-between">
            <span>💡 搬出入計画ステップが未作成です。</span>
            <a href="{{ route('timetables.logistics.index', $timetable) }}" class="text-indigo-700 font-bold underline shrink-0 ml-1">
                計画作成 →
            </a>
        </div>
        @endif
    </div>

    <!-- 当運行計画の現場要請 (班長からの応援要請・機材申請) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 space-y-2">
        <div class="flex items-center justify-between pb-1.5 border-b border-slate-100">
            <h3 class="font-bold text-xs text-slate-900 flex items-center space-x-1">
                <span>👑</span>
                <span>当計画の現場要請（人員・機材申請）</span>
            </h3>
            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded {{ $overview->pendingLaneRequestsCount > 0 ? 'bg-red-100 text-red-700 font-mono' : 'bg-emerald-50 text-emerald-700' }}">
                未対応 {{ $overview->pendingLaneRequestsCount }}件
            </span>
        </div>

        @if(!empty($overview->pendingLaneRequests))
        <div class="space-y-1.5 max-h-36 overflow-y-auto pr-0.5 text-xs">
            @foreach($overview->pendingLaneRequests as $req)
            <div class="p-2 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                <div class="flex items-center justify-between gap-1">
                    <span class="font-bold text-slate-900 text-[11px] truncate">
                        [{{ $req->timetableLane?->name ?? '班' }}] {{ $req->presenter()->typeLabel() }}
                    </span>
                    <span class="text-[9px] px-1 py-0.2 rounded font-bold shrink-0 {{ $req->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800' }}">
                        {{ $req->presenter()->statusLabel() }}
                    </span>
                </div>
                <div class="text-[10px] text-slate-700">
                    @if($req->request_type === 'equipment')
                        機材: <strong>{{ $req->equipment_name }}</strong> × {{ $req->requested_count }}
                    @else
                        応援要請: <strong>+{{ $req->requested_count }}名</strong>
                        @if($req->task)
                            （{{ $req->task->title }}）
                        @endif
                    @endif
                </div>
                @if($req->reason)
                <div class="text-[10px] text-slate-500">{{ $req->reason }}</div>
                @endif
                <div class="flex items-center justify-between pt-0.5">
                    <span class="text-[9px] text-slate-400">
                        申請者: {{ $req->requestedByStaff?->name ?? '-' }}
                    </span>
                    <div class="flex items-center gap-1">
                        @if($req->status === 'pending')
                        <form action="{{ route('lane-requests.update-status', $req) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="px-1.5 py-0.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[9px] font-bold cursor-pointer">
                                手配
                            </button>
                        </form>
                        @endif
                        <form action="{{ route('lane-requests.update-status', $req) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="resolved">
                            <button type="submit" class="px-1.5 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[9px] font-bold cursor-pointer">
                                完了
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-[11px] text-slate-400 py-1 text-center">現在、未対応の人員要請・機材申請はありません。</p>
        @endif
    </div>

    <!-- タイムテーブル削除（管理者操作） -->
    @can('delete', $timetable)
    <div class="pt-2 border-t border-slate-200/80 flex items-center justify-end">
        <form action="{{ route('timetables.destroy', $timetable) }}" method="POST"
            data-confirm="タイムテーブル「{{ $timetable->name }}」を削除しますか？&#10;（関連するすべての作業タスク、アサイン、レーンも削除されます）">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="px-2.5 py-1 bg-red-50 hover:bg-red-100 border border-red-200 text-red-600 rounded-lg text-xs font-bold transition cursor-pointer flex items-center space-x-1">
                <span>🗑️</span>
                <span>このタイムテーブルを削除する</span>
            </button>
        </form>
    </div>
    @endcan
</div>
