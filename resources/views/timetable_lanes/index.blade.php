<x-layouts.app :title="'担当班編成 - ' . $timetable->name">
<div id="timetable-lanes-app"
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5"
    data-reorder-url="{{ route('timetables.lanes.reorder', $timetable) }}">

    <!-- ページヘッダー -->
    <x-page-header
        title="担当班編成"
        icon="🏷️"
        :description="'「' . $timetable->name . '」における当日の担当班体制・班長・固定スタッフを管理します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition shrink-0">📅 運行日程管理</a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition truncate">{{ $timetable->name }}</a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">🏷️ 担当班編成</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            @if($timetable->target_date)
                <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    {{ $timetable->target_date->format('Y/m/d') }}（{{ $timetable->weekday_label }}）
                </span>
            @endif
        </x-slot:badges>

        <x-slot:actions>
            <x-ui.button variant="header-action" :href="route('lanes.index')">
                <span>⚙️</span>
                <span>担当班マスター管理</span>
            </x-ui.button>
            <x-ui.button variant="header-action" :href="route('timetables.lanes.print', $timetable)" target="_blank">
                <span>🖨️</span>
                <span>担当班一覧PDF</span>
            </x-ui.button>
            @can('create', App\Models\TimetableLane::class)
                <x-ui.button variant="header-action" id="btn-open-tlane-add">
                    <span>➕</span>
                    <span>担当班を追加</span>
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="lanes" />

    <!-- Step 2 -> Step 3 誘導バナー -->
    <div class="p-3 bg-indigo-50/70 border border-indigo-200 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
        <div class="flex items-center space-x-2 text-indigo-900">
            <span class="px-2 py-0.5 rounded bg-indigo-600 text-white font-bold text-[10px] shrink-0">Step 2 → Step 3</span>
            <span>この日の担当班・班長・固定スタッフの編成が完了したら、各班の作業タスクを登録しましょう。</span>
        </div>
        <a href="{{ route('timetables.tasks.index', $timetable) }}"
            class="inline-flex items-center space-x-1 px-3 py-1 bg-white hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-300 rounded-lg font-bold transition shrink-0 whitespace-nowrap">
            <span>📝</span>
            <span>作業タスク管理へ進む →</span>
        </a>
    </div>

    <x-ui.validation-errors title="入力内容を確認してください" />

    <div id="tlane-reorder-feedback" class="hidden px-3 py-2 rounded-xl text-xs font-bold border"></div>

    <!-- 日別担当班編成テーブルカード -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                    <span>🏷️</span>
                    <span>{{ $timetable->name }} の編成済み担当班</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono font-normal">
                        全 {{ $timetableLanes->count() }} 班
                    </span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    班名をクリックすると当日のメンバー・タスク一覧・現場要請の詳細を確認できます。ドラッグ操作でこの日の進行表列順を変更できます。
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap w-20">順</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">班名</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">バッジ</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">班長名</th>
                        <th scope="col" class="px-4 py-2.5">固定人数・メモ</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap text-right">操作</th>
                    </tr>
                </thead>
                <tbody id="timetable-lanes-sortable-body" class="divide-y divide-slate-100 bg-white">
                    @forelse($timetableLanes as $tl)
                        @php
                            $payload = $tl->toModalPayload();
                            $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
                            $pendingCount = $tl->laneRequests->where('status', 'pending')->count();
                        @endphp
                        <tr data-timetable-lane-id="{{ $tl->id }}"
                            @can('update', App\Models\TimetableLane::class) draggable="true" @endcan
                            class="tlane-row hover:bg-slate-50/70 transition">
                            <!-- 1. 順 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    @can('update', App\Models\TimetableLane::class)
                                        <span class="drag-handle cursor-grab active:cursor-grabbing text-slate-400 hover:text-indigo-600 select-none text-sm" title="ドラッグして並び替え">⋮⋮</span>
                                    @endcan
                                    <span class="order-number font-mono font-bold text-slate-600">{{ $loop->iteration }}</span>
                                </div>
                            </td>

                            <!-- 2. 班名（閲覧専用詳細モーダル起動） -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <button type="button"
                                        class="btn-view-tlane font-bold text-slate-800 hover:text-indigo-600 hover:underline transition cursor-pointer text-left"
                                        data-tlane="{{ $payloadJson }}"
                                        title="クリックして詳細・タスク・要請を表示">
                                        {{ $tl->name }}
                                    </button>
                                    <span class="text-[11px] font-mono text-slate-400">({{ $tl->tasks_count }}件)</span>
                                    @if($pendingCount > 0)
                                        <span class="px-1.5 py-0.5 rounded bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold">
                                            要請 {{ $pendingCount }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 3. バッジ -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold border {{ $tl->color_classes['badge'] }}">
                                    <span class="mr-1 shrink-0">{{ $tl->icon_label }}</span>
                                    <span>{{ $tl->name }}</span>
                                </span>
                            </td>

                            <!-- 4. 班長名 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($tl->leadStaff)
                                    <span class="inline-flex items-center space-x-1 font-bold text-slate-800">
                                        <span>👑</span>
                                        <span>{{ $tl->leadStaff->name }}</span>
                                    </span>
                                    @if($tl->leadStaff->affiliation)
                                        <span class="text-[11px] text-slate-400 ml-1">({{ $tl->leadStaff->affiliation }})</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">未設定</span>
                                @endif
                            </td>

                            <!-- 5. 固定人数・メモ -->
                            <td class="px-4 py-3 text-slate-600 max-w-md">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold text-[11px] shrink-0">
                                        📌 固定 {{ $tl->fixedStaff->count() }}名
                                    </span>
                                    @if($tl->notes)
                                        <span class="text-[11px] text-slate-600 truncate max-w-xs" title="{{ $tl->notes }}">
                                            📝 {{ $tl->notes }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 6. 操作 -->
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex items-center justify-end space-x-1.5">
                                    @can('update', $tl)
                                        <button type="button"
                                            class="btn-edit-tlane inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                            data-tlane="{{ $payloadJson }}">
                                            <span>✏️</span>
                                            <span>編集</span>
                                        </button>
                                    @endcan

                                    @can('delete', $tl)
                                        <button type="button"
                                            class="btn-delete-tlane inline-flex items-center space-x-1 px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                            data-tlane="{{ $payloadJson }}">
                                            <span>🗑️</span>
                                            <span>解除</span>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-ui.empty-state
                                    icon="🏷️"
                                    title="この運行日に編成された担当班がまだありません"
                                    description="「担当班を追加」から担当班マスターを選択して当日の体制を編成してください。" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 1. 閲覧専用 詳細モーダル -->
    <x-ui.modal id="modal-tlane-detail" title="担当班編成の詳細" icon="🏷️" max-width="xl">
        <div class="space-y-4 text-xs">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
                <span id="detail-tlane-badge" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border bg-slate-100 text-slate-800 border-slate-300">
                    <span id="detail-tlane-badge-icon" class="mr-1.5">📋</span>
                    <span id="detail-tlane-badge-name">班名</span>
                </span>
                <a id="detail-tlane-tasks-link" href="{{ route('timetables.tasks.index', $timetable) }}"
                    class="text-indigo-600 hover:underline font-bold">
                    この班のタスク管理を見る →
                </a>
            </div>

            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                <div class="text-[11px] font-bold text-slate-500">標準役割（マスター定義）</div>
                <div id="detail-tlane-description" class="text-slate-700 whitespace-pre-wrap leading-relaxed">-</div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                    <div class="text-[11px] font-bold text-slate-500">👑 当日の班長（リーダー）</div>
                    <div id="detail-tlane-lead" class="font-bold text-slate-800">未設定</div>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                    <div class="text-[11px] font-bold text-slate-500">📌 固定スタッフ</div>
                    <div id="detail-tlane-fixed-staff" class="flex flex-wrap gap-1"></div>
                </div>
            </div>

            <div class="space-y-1">
                <div class="text-[11px] font-bold text-slate-500">📝 当日運用メモ</div>
                <div id="detail-tlane-notes" class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-slate-700 whitespace-pre-wrap">-</div>
            </div>

            <div class="space-y-1.5">
                <div class="text-[11px] font-bold text-slate-500">
                    📋 登録タスク（<span id="detail-tlane-tasks-count" class="font-mono">0</span> 件）
                </div>
                <div id="detail-tlane-tasks-list" class="max-h-40 overflow-y-auto space-y-1"></div>
            </div>

            <div class="space-y-1.5">
                <div class="text-[11px] font-bold text-slate-500">📣 班長からの応援・機材要請履歴</div>
                <div id="detail-tlane-requests-list" class="max-h-36 overflow-y-auto space-y-1"></div>
            </div>
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" data-close-target="#modal-tlane-detail">閉じる</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <!-- 2. 担当班を追加モーダル（単一指定 / マスターから一括追加タブ） -->
    <x-ui.modal id="modal-tlane-add" title="この運行日に担当班を追加" icon="➕" max-width="xl">
        @if($availableMasterLanes->isEmpty())
            <div class="space-y-4 text-xs">
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 space-y-1.5">
                    <div class="font-bold">追加可能な有効担当班マスターがありません。</div>
                    <p class="leading-relaxed">
                        すべての有効な担当班マスターがこの運行日にすでに追加されているか、マスターが未登録です。新しい班が必要な場合は「担当班マスター管理」から登録してください。
                    </p>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <a href="{{ route('lanes.index') }}" class="text-indigo-600 hover:underline font-bold">
                        ⚙️ 担当班マスター管理へ →
                    </a>
                    <x-ui.button variant="secondary" data-close-target="#modal-tlane-add">閉じる</x-ui.button>
                </div>
            </div>
        @else
            <div class="space-y-4 text-xs">
                <div class="flex border-b border-slate-200 gap-2">
                    <button type="button" data-add-tab="single"
                        class="tlane-add-tab-btn px-3 py-2 font-bold border-b-2 border-indigo-600 text-indigo-700 cursor-pointer">
                        1班ずつ追加（班長・固定スタッフ指定）
                    </button>
                    <button type="button" data-add-tab="bulk"
                        class="tlane-add-tab-btn px-3 py-2 font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-700 cursor-pointer">
                        マスターから一括追加（{{ $availableMasterLanes->count() }}班選択可）
                    </button>
                </div>

                <!-- Tab 1: 単一追加フォーム -->
                <form id="tlane-add-single-panel" action="{{ route('timetables.lanes.store', $timetable) }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="add-lane-id" class="block font-bold text-slate-700 mb-1">
                            担当班マスターを選択 <span class="text-red-600">*</span>
                        </label>
                        <select id="add-lane-id" name="lane_id" required
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- 担当班を選択してください --</option>
                            @foreach($availableMasterLanes as $master)
                                <option value="{{ $master->id }}">{{ $master->icon_label }} {{ $master->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="add-lead-staff-id" class="block font-bold text-slate-700 mb-1">
                            👑 当日の班長（後から設定・変更可能）
                        </label>
                        <select id="add-lead-staff-id" name="lead_staff_id"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- 未設定 --</option>
                            @foreach($activeStaff as $st)
                                <option value="{{ $st->id }}">{{ $st->name }} {{ $st->affiliation ? "({$st->affiliation})" : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <div class="font-bold text-slate-700 mb-1">📌 固定スタッフ（この日の班タスクに基本自動配置）</div>
                        <div class="max-h-40 overflow-y-auto border border-slate-200 rounded-xl p-2 grid grid-cols-1 sm:grid-cols-2 gap-1.5 bg-slate-50/50">
                            @foreach($activeStaff as $st)
                                <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-white cursor-pointer">
                                    <input type="checkbox" name="fixed_staff_ids[]" value="{{ $st->id }}"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
                                    <span class="truncate text-slate-700 font-medium">
                                        {{ $st->name }}
                                        @if($st->affiliation)
                                            <span class="text-[10px] text-slate-400">({{ $st->affiliation }})</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="add-notes" class="block font-bold text-slate-700 mb-1">当日運用メモ</label>
                        <textarea id="add-notes" name="notes" rows="2" maxlength="2000"
                            placeholder="当日の連絡事項・集合場所・無線チャンネルなど"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                        <x-ui.button variant="secondary" data-close-target="#modal-tlane-add">キャンセル</x-ui.button>
                        <x-ui.button type="submit" variant="primary">この担当班を追加</x-ui.button>
                    </div>
                </form>

                <!-- Tab 2: マスターから一括追加フォーム -->
                <form id="tlane-add-bulk-panel" action="{{ route('timetables.lanes.bulk-store', $timetable) }}" method="POST" class="hidden space-y-4">
                    @csrf

                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-700">追加する担当班マスターを選択</span>
                        <div class="space-x-2">
                            <button type="button" id="btn-bulk-select-all" class="text-indigo-600 hover:underline font-bold cursor-pointer">全選択</button>
                            <button type="button" id="btn-bulk-clear-all" class="text-slate-500 hover:underline font-bold cursor-pointer">全解除</button>
                        </div>
                    </div>

                    <div class="max-h-64 overflow-y-auto border border-slate-200 rounded-xl p-2 space-y-1.5 bg-slate-50/50">
                        @foreach($availableMasterLanes as $master)
                            <label class="flex items-center justify-between p-2 rounded-lg bg-white border border-slate-200 hover:border-indigo-300 cursor-pointer">
                                <div class="flex items-center space-x-2 min-w-0">
                                    <input type="checkbox" name="lane_ids[]" value="{{ $master->id }}"
                                        class="tlane-bulk-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border shrink-0 {{ $master->color_classes['badge'] }}">
                                        <span class="mr-1">{{ $master->icon_label }}</span>
                                        <span>{{ $master->name }}</span>
                                    </span>
                                </div>
                                @if($master->description)
                                    <span class="text-[11px] text-slate-400 truncate max-w-xs ml-2">{{ $master->description }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                        <x-ui.button variant="secondary" data-close-target="#modal-tlane-add">キャンセル</x-ui.button>
                        <x-ui.button type="submit" variant="primary">選択した担当班を一括追加</x-ui.button>
                    </div>
                </form>
            </div>
        @endif
    </x-ui.modal>

    <!-- 3. 担当班編成の編集モーダル -->
    <x-ui.modal id="modal-tlane-edit" title="担当班編成の編集" icon="✏️" max-width="xl">
        <form id="tlane-edit-form" action="" method="POST" class="space-y-4 text-xs">
            @csrf
            @method('PUT')
            <input type="hidden" name="sync_fixed_staff" value="1">

            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span id="edit-tlane-badge" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border bg-slate-100 text-slate-800 border-slate-300">
                        <span id="edit-tlane-badge-icon" class="mr-1.5">📋</span>
                        <span id="edit-tlane-badge-name">班名</span>
                    </span>
                    <a href="{{ route('lanes.index') }}" class="text-[11px] text-indigo-600 hover:underline font-bold">
                        班名・表示色の変更はマスター管理へ →
                    </a>
                </div>
                <div id="edit-tlane-description" class="text-[11px] text-slate-500"></div>
            </div>

            <div>
                <label for="edit-lead-staff-id" class="block font-bold text-slate-700 mb-1">
                    👑 当日の班長（リーダー）
                </label>
                <select id="edit-lead-staff-id" name="lead_staff_id"
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- 未設定 --</option>
                    @foreach($activeStaff as $st)
                        <option value="{{ $st->id }}">{{ $st->name }} {{ $st->affiliation ? "({$st->affiliation})" : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <div class="font-bold text-slate-700 mb-1">📌 固定スタッフ</div>
                <div id="edit-fixed-staff-list" class="max-h-44 overflow-y-auto border border-slate-200 rounded-xl p-2 grid grid-cols-1 sm:grid-cols-2 gap-1.5 bg-slate-50/50">
                    @foreach($activeStaff as $st)
                        <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-white cursor-pointer">
                            <input type="checkbox" name="fixed_staff_ids[]" value="{{ $st->id }}"
                                class="edit-fixed-staff-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
                            <span class="truncate text-slate-700 font-medium">
                                {{ $st->name }}
                                @if($st->affiliation)
                                    <span class="text-[10px] text-slate-400">({{ $st->affiliation }})</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="edit-notes" class="block font-bold text-slate-700 mb-1">当日運用メモ</label>
                <textarea id="edit-notes" name="notes" rows="3" maxlength="2000"
                    placeholder="当日の連絡事項・集合場所・注意点など"
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <x-ui.button variant="secondary" data-close-target="#modal-tlane-edit">キャンセル</x-ui.button>
                <x-ui.button type="submit" variant="primary">編成内容を保存</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- 4. 解除・削除モーダル（タスク移行 / 一括削除対応） -->
    <x-ui.modal id="delete-migration-modal" title="担当班の編成解除・削除" icon="🗑️" max-width="lg">
        <form id="tlane-delete-form" action="" method="POST" class="space-y-4 text-xs">
            @csrf
            @method('DELETE')

            <p class="text-slate-700 leading-relaxed">
                「{{ $timetable->name }}」の編成から「<strong id="delete-tlane-name" class="text-slate-900"></strong>」を解除します。
            </p>

            <div id="delete-no-tasks-msg" class="hidden p-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-600">
                この班に登録されている作業タスクはありません。そのまま解除できます。
            </div>

            <div id="delete-with-tasks-options" class="hidden space-y-3">
                <div class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl font-bold">
                    ⚠️ この班には <span id="delete-tlane-tasks-count" class="font-mono">0</span> 件の作業タスクが登録されています。タスクの扱いを選択してください。
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <label for="migrate-to-timetable-lane-id" class="block font-bold text-slate-700">
                        方法A: 登録タスクを他の担当班へ移行して解除する
                    </label>
                    <select id="migrate-to-timetable-lane-id" name="migrate_to_timetable_lane_id"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- 移行先の担当班を選択 --</option>
                        @foreach($timetableLanes as $otherTl)
                            <option value="{{ $otherTl->id }}">{{ $otherTl->icon_label }} {{ $otherTl->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="p-3 bg-red-50/70 border border-red-200 rounded-xl">
                    <label class="flex items-start space-x-2 cursor-pointer">
                        <input type="checkbox" id="cascade-delete-tasks" name="cascade_delete_tasks" value="1"
                            class="mt-0.5 rounded border-red-300 text-red-600 focus:ring-red-500 h-4 w-4">
                        <span class="text-red-800">
                            <strong>方法B: タスクもまとめて完全に削除する</strong>
                            <span class="block text-[11px] text-red-600 mt-0.5">この班の作業タスクとスタッフ配置データもすべて削除されます。</span>
                        </span>
                    </label>
                </div>

                <div id="delete-modal-error" class="hidden p-2.5 bg-red-50 border border-red-200 text-red-700 rounded-xl font-bold"></div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <x-ui.button variant="secondary" data-close-target="#delete-migration-modal">キャンセル</x-ui.button>
                <button type="submit"
                    class="inline-flex items-center space-x-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold shadow-xs transition cursor-pointer">
                    <span>🗑️</span>
                    <span>編成から解除する</span>
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>

@vite(['resources/js/timetable-lanes.js'])
</x-layouts.app>
