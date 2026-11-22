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
                    {{ $timetable->target_date->format('Y/m/d') }}（{{ $timetable->presenter()->weekdayLabel() }}）
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
                            $payload = $tl->presenter()->toModalPayload();
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
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold border {{ $tl->presenter()->colorClasses()['badge'] }}">
                                    <span class="mr-1 shrink-0">{{ $tl->presenter()->iconLabel() }}</span>
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

    @include('timetable_lanes.partials.detail-modal')

    @include('timetable_lanes.partials.add-modal')

    @include('timetable_lanes.partials.edit-modal')

    @include('timetable_lanes.partials.delete-modal')
</div>

@vite(['resources/js/timetable-lanes.js'])
</x-layouts.app>
