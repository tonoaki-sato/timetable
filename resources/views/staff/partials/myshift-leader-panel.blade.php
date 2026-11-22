<!-- ========================================== -->
<!-- パネル2: 班長コンソール（班切替タブ対応）      -->
<!-- ========================================== -->
@if($ledLanes->isNotEmpty())
    <section id="leaderModePanel" data-main-panel="leader" class="hidden space-y-3.5">

        <!-- 同一範囲に複数班長担当がある場合の班切り替えサブタブ -->
        @if($ledLanes->count() > 1)
            <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5" role="tablist" aria-label="担当班切り替え">
                @foreach($ledLanes as $ledLane)
                    @php
                        $lanePendingCount = $ledLane->laneRequests->where('status', 'pending')->count();
                    @endphp
                    <button type="button"
                            role="tab"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            data-leader-lane-tab="{{ $ledLane->id }}"
                            class="leaderLaneTabBtn shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition cursor-pointer {{ $loop->first ? 'bg-amber-500 text-slate-950 border-amber-400 shadow-sm' : 'bg-slate-900 text-slate-300 border-slate-800 hover:border-amber-500/40' }}">
                        <span>{{ $ledLane->icon ?: '📋' }}</span>
                        <span>{{ $ledLane->name }}</span>
                        @if($showAllTimetables && $ledLane->timetable)
                            <span class="text-[10px] opacity-80">({{ $ledLane->timetable->target_date?->format('n/j') }})</span>
                        @endif
                        @if($lanePendingCount > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ $loop->first ? 'bg-slate-950 text-amber-300' : 'bg-red-500 text-white' }}">
                                {{ $lanePendingCount }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif

        @foreach($ledLanes as $ledLane)
            @php
                $canOperateLedLane = (bool) $ledLane->timetable?->canOperateOnMyShift();
            @endphp
            <div data-leader-lane-panel="{{ $ledLane->id }}"
                 class="{{ $loop->first ? '' : 'hidden ' }}p-4 rounded-2xl bg-gradient-to-br from-amber-950/80 via-slate-900 to-slate-900 border border-amber-500/50 shadow-xl space-y-4">
                <div class="flex items-start justify-between gap-2 border-b border-amber-500/30 pb-3">
                    <div class="space-y-1 min-w-0">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-black bg-amber-500 text-slate-950">
                            👑 班長コンソール
                        </span>
                        <h2 class="text-sm font-bold text-amber-100 break-words">
                            {{ $ledLane->icon ?: '📋' }} {{ $ledLane->name }}
                            @if($ledLane->timetable)
                                <span class="text-xs font-normal text-amber-200/80">（{{ $ledLane->timetable->name }}）</span>
                            @endif
                        </h2>
                    </div>
                    <div class="text-right shrink-0 text-[11px] text-amber-300/90 font-mono space-y-0.5">
                        <div>固定: {{ $ledLane->fixedStaff->count() }}名</div>
                        <div>タスク: {{ $ledLane->tasks->count() }}件</div>
                    </div>
                </div>

                @if(! $canOperateLedLane)
                    <div class="p-3 rounded-xl bg-slate-950/70 border border-amber-500/30 text-[11px] text-amber-200/90 flex items-start gap-2 leading-relaxed">
                        <span class="shrink-0">👀</span>
                        <span>
                            @if($ledLane->timetable?->target_date)
                                {{ $ledLane->timetable->target_date->format('n月j日') }}の運行日当日までは閲覧専用です。班タスクのステータス変更および人員応援・機材申請は当日から行えます。
                            @else
                                運行日当日までは閲覧専用です。班タスクのステータス変更および人員応援・機材申請は当日から行えます。
                            @endif
                        </span>
                    </div>
                @endif

                <!-- 1. 班のタスク管理（進捗・人員充足確認） -->
                <div class="space-y-2">
                    <h3 class="text-xs font-bold text-amber-200 flex items-center space-x-1">
                        <span>📋</span>
                        <span>班のタスク管理（全 {{ $ledLane->tasks->count() }} 件）</span>
                    </h3>
                    @if($ledLane->tasks->isEmpty())
                        <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 text-center text-xs text-slate-400">
                            この担当班に登録されたタスクはありません。
                        </div>
                    @else
                        <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                            @foreach($ledLane->tasks as $laneTask)
                                <div class="p-3 rounded-xl bg-slate-900/95 border border-slate-800 space-y-2 text-xs">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="font-mono text-amber-300 font-bold text-[11px]">
                                                {{ substr($laneTask->start_time, 0, 5) }}〜{{ substr($laneTask->end_time, 0, 5) }}
                                            </div>
                                            <div class="font-bold text-white break-words mt-0.5">{{ $laneTask->title }}</div>
                                        </div>
                                        <span class="shrink-0 text-[10px] px-2 py-0.5 rounded font-bold {{ $laneTask->is_satisfied ? 'bg-emerald-900/70 text-emerald-300 border border-emerald-700/50' : 'bg-red-900/80 text-red-200 border border-red-700/50' }}">
                                            配置 {{ $laneTask->assigned_count }} / 必要 {{ $laneTask->presenter()->requiredPeopleLabel() }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-800/80">
                                        <span class="text-[11px] text-slate-400 leading-relaxed">
                                            班員: {{ $laneTask->assignments->map(fn($a) => $a->staff?->name . '(' . ($a->role_description ?: '担当') . ')')->implode('、') ?: '未配置' }}
                                        </span>
                                        @if($canOperateLedLane)
                                            <form action="{{ route('staff.myshift.lane-task-status', ['token' => $staff->access_token, 'task' => $laneTask->id], false) }}" method="POST" class="shrink-0">
                                                @csrf
                                                <select name="status"
                                                        data-leader-task-select
                                                        data-endpoint="{{ route('staff.myshift.lane-task-status', ['token' => $staff->access_token, 'task' => $laneTask->id], false) }}"
                                                        class="px-2 py-1 rounded-lg bg-slate-800 border border-slate-700 text-[11px] font-bold text-slate-100 cursor-pointer">
                                                    @foreach(\App\Enums\TimetableTaskStatus::options() as $stKey => $stLabel)
                                                        <option value="{{ $stKey }}" {{ $laneTask->status === $stKey ? 'selected' : '' }}>{{ $stLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        @else
                                            <span class="shrink-0 px-2 py-1 rounded-lg bg-slate-800/80 border border-slate-700 text-[11px] font-bold text-slate-300">
                                                {{ $laneTask->presenter()->statusLabel() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($canOperateLedLane)
                    <!-- 2. 管理者への要請・申請（サブタブ切替） -->
                    <div class="pt-2 border-t border-amber-500/30 space-y-2.5" data-leader-req-group="{{ $ledLane->id }}">
                        <div class="grid grid-cols-2 gap-1.5">
                            <button type="button"
                                    data-leader-req-tab="staffing"
                                    data-lane-id="{{ $ledLane->id }}"
                                    class="leaderReqTabBtn py-1.5 px-2.5 rounded-xl text-xs font-bold border transition cursor-pointer bg-amber-500/20 text-amber-200 border-amber-500/50">
                                🙋 人員応援要請
                            </button>
                            <button type="button"
                                    data-leader-req-tab="equipment"
                                    data-lane-id="{{ $ledLane->id }}"
                                    class="leaderReqTabBtn py-1.5 px-2.5 rounded-xl text-xs font-bold border transition cursor-pointer bg-slate-900/90 text-slate-400 border-slate-800 hover:text-sky-200">
                                🧰 必要機材申請
                            </button>
                        </div>

                        <!-- 人員応援要請フォーム -->
                        <form action="{{ route('staff.myshift.lane-requests.store', ['token' => $staff->access_token, 'timetableLane' => $ledLane->id], false) }}"
                              method="POST"
                              data-async-lane-request
                              data-leader-req-panel="staffing"
                              data-lane-id="{{ $ledLane->id }}"
                              class="p-3.5 rounded-xl bg-slate-900/95 border border-slate-800 space-y-2.5">
                            @csrf
                            <input type="hidden" name="request_type" value="staffing">
                            <div class="text-xs font-bold text-amber-300">🙋 人員不足時の管理者への要請</div>
                            <div>
                                <select name="timetable_task_id" class="w-full px-2.5 py-2 rounded-lg bg-slate-950 border border-slate-700 text-xs text-slate-200">
                                    <option value="">-- 対象タスク（班全体の場合は未選択） --</option>
                                    @foreach($ledLane->tasks as $laneTask)
                                        <option value="{{ $laneTask->id }}">{{ substr($laneTask->start_time, 0, 5) }} {{ $laneTask->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="number" name="requested_count" value="1" min="1" max="50" required
                                       class="w-20 px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white" placeholder="人数">
                                <span class="text-xs text-slate-400">名応援要請</span>
                            </div>
                            <input type="text" name="reason" placeholder="理由（例: 欠員発生・設営遅れのため）"
                                   class="w-full px-2.5 py-2 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white">
                            <button type="submit" class="w-full py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 rounded-xl text-xs font-bold transition cursor-pointer">
                                管理者へ人員を要請する
                            </button>
                        </form>

                        <!-- 必要機材の申請フォーム -->
                        <form action="{{ route('staff.myshift.lane-requests.store', ['token' => $staff->access_token, 'timetableLane' => $ledLane->id], false) }}"
                              method="POST"
                              data-async-lane-request
                              data-leader-req-panel="equipment"
                              data-lane-id="{{ $ledLane->id }}"
                              class="hidden p-3.5 rounded-xl bg-slate-900/95 border border-slate-800 space-y-2.5">
                            @csrf
                            <input type="hidden" name="request_type" value="equipment">
                            <div class="text-xs font-bold text-sky-300">🧰 必要機材の申請</div>
                            <input type="text" name="equipment_name" required placeholder="機材名（例: カラーコーン、台車、給水タンク）"
                                   class="w-full px-2.5 py-2 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white">
                            <div class="flex items-center gap-2">
                                <input type="number" name="requested_count" value="1" min="1" max="100" required
                                       class="w-20 px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white" placeholder="数量">
                                <span class="text-xs text-slate-400">個 / 台</span>
                            </div>
                            <input type="text" name="reason" placeholder="受け渡し場所・用途メモ"
                                   class="w-full px-2.5 py-2 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white">
                            <button type="submit" class="w-full py-2 bg-sky-500 hover:bg-sky-400 text-slate-950 rounded-xl text-xs font-bold transition cursor-pointer">
                                管理者へ機材を申請する
                            </button>
                        </form>
                    </div>
                @endif

                <!-- 送信済み要請・申請ステータス -->
                @if($ledLane->laneRequests->isNotEmpty())
                    <div class="pt-2 border-t border-amber-500/30 space-y-1.5">
                        <div class="text-[11px] font-bold text-slate-300">📨 管理者への要請・申請状況</div>
                        @foreach($ledLane->laneRequests->take(8) as $req)
                            <div class="flex items-center justify-between gap-2 text-[11px] bg-slate-900/80 px-2.5 py-1.5 rounded-lg border border-slate-800">
                                <div class="min-w-0">
                                    <span class="font-bold text-amber-300">[{{ $req->presenter()->typeLabel() }}]</span>
                                    <span class="text-slate-200">
                                        {{ $req->request_type === 'equipment' ? $req->equipment_name . ' × ' . $req->requested_count : '応援 +' . $req->requested_count . '名' }}
                                    </span>
                                    @if($req->reason)
                                        <span class="text-slate-400">({{ $req->reason }})</span>
                                    @endif
                                </div>
                                <span class="shrink-0 px-2 py-0.5 rounded font-bold text-[10px] {{ $req->status === 'resolved' ? 'bg-emerald-900 text-emerald-300' : ($req->status === 'approved' ? 'bg-sky-900 text-sky-300' : 'bg-amber-900 text-amber-200') }}">
                                    {{ $req->presenter()->statusLabel() }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </section>
@endif
