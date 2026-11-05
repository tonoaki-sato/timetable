<x-layouts.app :title="'担当班詳細 - ' . $lane->name">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$lane->name"
        :icon="$lane->icon ?: '📋'"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）における担当班の基本設定、班長・固定メンバー体制、所属作業タスクおよび資材サマリーです。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('lanes.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>🏷️</span>
                <span>担当班管理</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.lanes.index', $timetable) }}" class="hover:text-indigo-600 transition truncate">
                📅 {{ $timetable->name }}
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">{{ $lane->name }}</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold border {{ $lane->color_classes['badge'] }}">
                {{ $lane->icon ?: '📋' }} {{ $lane->name }}
            </span>
            <span class="font-mono text-xs text-slate-500 font-bold px-2 py-0.5 bg-slate-100 rounded-lg">
                #{{ $lane->display_order }}
            </span>
            @if($lane->is_core)
            <span class="text-xs px-2 py-0.5 rounded-lg bg-amber-100 text-amber-800 font-bold flex items-center space-x-1">
                <span>⭐</span>
                <span>主要班</span>
            </span>
            @endif
            @if($lane->is_active)
            <span class="text-xs px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 font-bold">
                👁️ 有効
            </span>
            @else
            <span class="text-xs px-2 py-0.5 rounded-lg bg-slate-200 text-slate-600 font-bold">
                🔒 非表示中
            </span>
            @endif
            <span class="text-xs px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold">
                📋 タスク: {{ $lane->tasks->count() }} 件
            </span>
            <span class="text-xs px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold">
                📌 固定スタッフ: {{ $lane->fixedStaff->count() }} 名
            </span>
        </x-slot:badges>

        <x-slot:actions>
            <a href="{{ route('timetable-tasks.create', ['timetable_id' => $timetable->id, 'lane_id' => $lane->id]) }}"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>＋</span>
                <span>タスク追加</span>
            </a>
            <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>🗺️</span>
                <span>運行マトリクス</span>
            </a>
            <a href="{{ route('timetables.lanes.edit', [$timetable, $lane]) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>✏️</span>
                <span>基本情報編集</span>
            </a>
            <a href="{{ route('timetables.lanes.index', $timetable) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="lanes" />

    <!-- アラートメッセージ -->
    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>✅</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- 左カラム (2/3): 基本情報 & 所属作業タスク一覧 -->
        <div class="lg:col-span-2 space-y-4">

            <!-- 1. 基本スペックカード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                        <span>🏷️</span>
                        <span>担当班の基本仕様</span>
                    </h2>
                    <a href="{{ route('timetables.lanes.edit', [$timetable, $lane]) }}"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-bold transition">
                        ✏️ 編集する
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">担当班名</span>
                        <span class="font-bold text-slate-800 flex items-center space-x-1 mt-0.5">
                            <span>{{ $lane->icon ?: '📋' }}</span>
                            <span>{{ $lane->name }}</span>
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">テーマカラー</span>
                        <span class="font-bold text-slate-800 flex items-center space-x-1 mt-0.5">
                            <span class="w-3 h-3 rounded-full {{ $lane->color_classes['bg'] }} border {{ $lane->color_classes['border'] }}"></span>
                            <span>{{ \App\Models\Lane::COLOR_OPTIONS[$lane->color] ?? $lane->color }}</span>
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">表示順 / 優先度</span>
                        <span class="font-bold text-slate-800 mt-0.5 block">
                            第 #{{ $lane->display_order }} レーン
                            @if($lane->is_core)
                            <span class="text-amber-600 font-bold ml-1">⭐ 主要</span>
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">運行マトリクス表示</span>
                        <span class="font-bold {{ $lane->is_active ? 'text-emerald-700' : 'text-slate-500' }} mt-0.5 block">
                            {{ $lane->is_active ? '👁️ 表示中（有効）' : '🔒 非表示' }}
                        </span>
                    </div>
                </div>

                <!-- 業務所管・担当エリア -->
                <div class="pt-3 border-t border-slate-100">
                    <span class="text-slate-400 block text-[11px] mb-1 font-bold">業務所管・担当エリア（メモ）</span>
                    <div class="p-3 bg-slate-50 rounded-xl text-xs text-slate-700 leading-relaxed">
                        {{ $lane->description ?: '業務説明は登録されていません。「基本情報編集」から担当エリアや指示メモを登録できます。' }}
                    </div>
                </div>
            </div>

            <!-- 2. 所属作業タスク一覧カード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                            <span>📋</span>
                            <span>担当作業タスク一覧</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono font-normal">
                                全 {{ $lane->tasks->count() }} 件
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            この担当班に割り当てられている作業タスクの時系列スケジュールです。
                        </p>
                    </div>

                    <a href="{{ route('timetable-tasks.create', ['timetable_id' => $timetable->id, 'lane_id' => $lane->id]) }}"
                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition flex items-center space-x-1 shrink-0">
                        <span>＋</span>
                        <span>タスク追加</span>
                    </a>
                </div>

                @if($lane->tasks->isNotEmpty())
                <div class="divide-y divide-slate-100 overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/70 text-slate-500 text-[11px] font-bold">
                                <th class="p-3.5 pl-5">時間帯</th>
                                <th class="p-3.5">タスク名</th>
                                <th class="p-3.5">担当場所</th>
                                <th class="p-3.5 text-center">必要 / 配置人数</th>
                                <th class="p-3.5 text-center">進捗</th>
                                <th class="p-3.5 pr-5 text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($lane->tasks as $task)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="p-3.5 pl-5 font-mono text-slate-700 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($task->start_time)->format('H:i') }} 〜 {{ \Carbon\Carbon::parse($task->end_time)->format('H:i') }}
                                </td>
                                <td class="p-3.5 min-w-[180px]">
                                    <div class="font-bold text-slate-900">
                                        <a href="{{ route('timetable-tasks.show', $task) }}" class="hover:text-indigo-600 transition">
                                            {{ $task->title }}
                                        </a>
                                    </div>
                                    @if($task->task_code)
                                    <span class="text-[10px] font-mono text-slate-400">{{ $task->task_code }}</span>
                                    @endif
                                </td>
                                <td class="p-3.5 text-slate-600 whitespace-nowrap">
                                    📍 {{ $task->location ?: '未定' }}
                                </td>
                                <td class="p-3.5 text-center whitespace-nowrap">
                                    @php
                                        $assignedCount = $task->assigned_count;
                                        $reqCount = $task->required_people_count;
                                        $isFulfilled = $task->is_satisfied;
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-bold {{ $isFulfilled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                        {{ $assignedCount }} / {{ $reqCount }}名
                                    </span>
                                </td>
                                <td class="p-3.5 text-center whitespace-nowrap">
                                    @php
                                        $statusClasses = match($task->status) {
                                            'completed' => 'bg-slate-100 text-slate-600',
                                            'in_progress' => 'bg-indigo-100 text-indigo-800 font-bold',
                                            default => 'bg-amber-100 text-amber-800',
                                        };
                                        $statusLabels = [
                                            'pending' => '未着手',
                                            'in_progress' => '進行中',
                                            'completed' => '完了',
                                        ];
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $statusClasses }}">
                                        {{ $statusLabels[$task->status] ?? $task->status }}
                                    </span>
                                </td>
                                <td class="p-3.5 pr-5 text-right whitespace-nowrap">
                                    <a href="{{ route('timetable-tasks.show', $task) }}"
                                        class="px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 rounded text-[11px] font-bold text-slate-700 transition">
                                        詳細 →
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="p-8 text-center space-y-2">
                    <span class="text-3xl block">📭</span>
                    <p class="text-xs font-bold text-slate-600">この担当班に割り当てられている作業タスクはありません。</p>
                    <p class="text-[11px] text-slate-400">「タスク追加」から運行タスクを作成してこの班を割り当ててください。</p>
                    <div class="pt-2">
                        <a href="{{ route('timetable-tasks.create', ['timetable_id' => $timetable->id, 'lane_id' => $lane->id]) }}"
                            class="inline-flex items-center space-x-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition">
                            <span>＋</span>
                            <span>最初のタスクを追加する</span>
                        </a>
                    </div>
                </div>
                @endif
            </div>

        </div>

        <!-- 右カラム (1/3): 班体制（班長・固定スタッフ） & 担当資材 & 削除 -->
        <div class="space-y-4">

            <!-- 3. 班体制サマリー（班長 & 固定スタッフ）カード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-1.5">
                        <span>👥</span>
                        <span>班体制・担当メンバー</span>
                    </h2>
                    <a href="{{ route('timetables.lanes.edit', [$timetable, $lane]) }}"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-bold transition">
                        設定変更
                    </a>
                </div>

                <!-- 👑 班長 -->
                <div class="p-3.5 bg-amber-50/60 rounded-xl border border-amber-200/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-900 flex items-center space-x-1">
                            <span>👑</span>
                            <span>現場班長（リーダー）</span>
                        </span>
                        @if($lane->leadStaff)
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">
                            任命済
                        </span>
                        @else
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-bold">
                            未定
                        </span>
                        @endif
                    </div>

                    @if($lane->leadStaff)
                    <div class="space-y-1">
                        <div class="font-bold text-sm text-slate-900">
                            {{ $lane->leadStaff->name }}
                        </div>
                        <div class="text-[11px] text-slate-500 flex items-center space-x-2">
                            <span>🏢 {{ $lane->leadStaff->affiliation ?: '所属なし' }}</span>
                            @if($lane->leadStaff->phone)
                            <span>📞 {{ $lane->leadStaff->phone }}</span>
                            @endif
                        </div>
                        <p class="text-[10px] text-amber-800 pt-1">
                            ※ 当該班の全タスクへリーダーとして自動配置されます。
                        </p>
                    </div>
                    @else
                    <div class="text-xs text-slate-500 py-1">
                        班長がまだ設定されていません。
                        <a href="{{ route('timetables.lanes.edit', [$timetable, $lane]) }}" class="text-indigo-600 hover:underline font-bold ml-1">
                            設定する →
                        </a>
                    </div>
                    @endif
                </div>

                <!-- 📌 固定配置スタッフ一覧 -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center space-x-1">
                            <span>📌</span>
                            <span>専属固定スタッフ</span>
                        </span>
                        <span class="text-xs font-mono text-slate-500">
                            {{ $lane->fixedStaff->count() }} 名
                        </span>
                    </div>

                    @if($lane->fixedStaff->isNotEmpty())
                    <div class="space-y-1.5 max-h-56 overflow-y-auto">
                        @foreach($lane->fixedStaff as $fixed)
                        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-800">{{ $fixed->name }}</span>
                                @if($fixed->affiliation)
                                <span class="text-[10px] text-slate-400 ml-1">({{ $fixed->affiliation }})</span>
                                @endif
                            </div>
                            @if($lane->lead_staff_id === $fixed->id)
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-bold">
                                👑 班長兼任
                            </span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-xs text-slate-400 py-2 text-center bg-slate-50 rounded-xl">
                        専属の固定スタッフは設定されていません。
                    </p>
                    @endif
                </div>
            </div>

            <!-- 4. 担当資材一覧カード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-1.5">
                        <span>📦</span>
                        <span>担当資材・機材</span>
                    </h2>
                    <span class="text-xs font-mono text-slate-500">
                        {{ $equipments->count() }} 品目
                    </span>
                </div>

                @if($equipments->isNotEmpty())
                <div class="space-y-1.5 max-h-48 overflow-y-auto text-xs">
                    @foreach($equipments as $equipment)
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between">
                        <div>
                            <a href="{{ route('equipments.show', $equipment) }}" class="font-bold text-slate-800 hover:text-indigo-600 transition">
                                {{ $equipment->name }}
                            </a>
                            <div class="text-[10px] text-slate-400 mt-0.5">
                                保管: {{ $equipment->storage_location ?: '定常保管場所未定' }}
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-white border border-slate-200 text-slate-700">
                            {{ $equipment->quantity }}{{ $equipment->unit ?: '個' }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-xs text-slate-400 py-2 text-center bg-slate-50 rounded-xl">
                    この担当班に紐づく資材はありません。
                </p>
                @endif
            </div>

            <!-- 5. 班長からの要請履歴カード -->
            @if($lane->laneRequests->isNotEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-1.5">
                        <span>📢</span>
                        <span>現場要請・申請履歴</span>
                    </h2>
                    <span class="text-xs font-mono text-slate-500">
                        {{ $lane->laneRequests->count() }} 件
                    </span>
                </div>

                <div class="space-y-2 max-h-48 overflow-y-auto text-xs">
                    @foreach($lane->laneRequests as $req)
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800">{{ $req->type_label }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded font-bold
                                @if($req->status === 'resolved') bg-emerald-100 text-emerald-800
                                @elseif($req->status === 'in_progress') bg-indigo-100 text-indigo-800
                                @else bg-amber-100 text-amber-800 @endif">
                                {{ $req->status_label }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 line-clamp-2">{{ $req->description }}</p>
                        <div class="text-[10px] text-slate-400 flex items-center justify-between pt-0.5">
                            <span>申請: {{ $req->requestedByStaff?->name ?: '班長' }}</span>
                            <span>{{ $req->created_at?->format('m/d H:i') }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- 6. 危険ゾーン（削除） -->
            @can('delete', $lane)
            <div class="bg-rose-50/50 rounded-2xl border border-rose-200 p-4 space-y-2">
                <div class="text-xs font-bold text-rose-900 flex items-center space-x-1">
                    <span>⚠️</span>
                    <span>担当班の削除</span>
                </div>
                <p class="text-[11px] text-rose-700">
                    この担当班を削除します。
                </p>
                <div class="pt-1">
                    @if($lane->tasks->count() > 0)
                    <button type="button"
                        data-action="open-delete-modal"
                        data-lane-id="{{ $lane->id }}"
                        data-lane-name="{{ $lane->name }}"
                        data-task-count="{{ $lane->tasks->count() }}"
                        data-delete-url="{{ route('timetables.lanes.destroy', [$timetable, $lane]) }}"
                        class="w-full px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer text-center">
                        🗑️ この担当班を削除する...
                    </button>
                    @else
                    <form action="{{ route('timetables.lanes.destroy', [$timetable, $lane]) }}" method="POST"
                        data-confirm="担当班「{{ $lane->name }}」を削除しますか？">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer text-center">
                            🗑️ この担当班を削除する
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endcan

        </div>

    </div>

</div>

<!-- タスク移行削除モーダル -->
<div id="delete-migration-modal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-rose-900 flex items-center space-x-2">
                <span>⚠️</span>
                <span>担当班の削除とタスク引き継ぎ</span>
            </h3>
            <button type="button" data-close-modal class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
        </div>

        <form id="delete-migration-form" action="" method="POST" class="p-5 space-y-4">
            @csrf
            @method('DELETE')

            <p class="text-xs text-slate-700 leading-relaxed">
                担当班「<strong id="modal-delete-lane-name" class="text-slate-900">{{ $lane->name }}</strong>」には、現在
                <strong id="modal-delete-task-count" class="text-rose-600 font-mono">{{ $lane->tasks->count() }}</strong> 件の作業タスクが登録されています。
            </p>

            <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-xl space-y-3">
                <div>
                    <label for="modal-migrate-target-select" class="block text-xs font-bold text-amber-900 mb-1">
                        タスクの引き継ぎ先レーンを選択（推奨）
                    </label>
                    <select id="modal-migrate-target-select" name="migrate_to_lane_id"
                        class="w-full px-3 py-2 bg-white border border-amber-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="">-- 引き継ぎ先レーンを選択 --</option>
                        @foreach($otherLanes as $targetLane)
                        <option value="{{ $targetLane->id }}">
                            {{ $targetLane->icon ?: '📋' }} {{ $targetLane->name }}
                        </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-amber-800 mt-1">※ 全タスクが選択したレーンに付け替えられた後、当該レーンが安全に削除されます。</p>
                </div>

                <div class="pt-2 border-t border-amber-200/80">
                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" name="cascade_delete_tasks" value="1"
                            class="w-4 h-4 text-rose-600 rounded border-amber-400 focus:ring-rose-500">
                        <span class="text-xs font-bold text-rose-800">引き継がず、タスクも含めてすべて削除する</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" data-close-modal
                    class="px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold transition">
                    キャンセル
                </button>
                <button type="submit"
                    class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    削除を実行する
                </button>
            </div>
        </form>
    </div>
</div>

@vite(['resources/js/lanes.js'])
</x-layouts.app>
