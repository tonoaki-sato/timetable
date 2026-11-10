<x-layouts.app :title="'担当班詳細 - ' . $lane->name">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$lane->name"
        :icon="$lane->icon ?: '📋'"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）における担当班の基本設定、班長・固定メンバー体制、所属作業タスクおよび資材サマリーです。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>運行情報一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>📅</span>
                <span>{{ $timetable->name }}</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.lanes.index', $timetable) }}" class="hover:text-indigo-600 transition truncate">
                🏷️ 担当班一覧
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">{{ $lane->name }}</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
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

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
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
                        <span class="text-slate-400 block text-[11px]">班名バッジ</span>
                        <div class="mt-0.5">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold border {{ $lane->color_classes['badge'] }}">
                                {{ $lane->icon ?: '📋' }} {{ $lane->name }}
                            </span>
                        </div>
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

                    <a href="{{ route('timetables.tasks.index', $timetable) }}"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-bold transition flex items-center space-x-1 shrink-0">
                        <span>📋</span>
                        <span>タスク管理</span>
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
                                        <a href="{{ route('timetables.tasks.index', $timetable) }}" class="hover:text-indigo-600 transition">
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
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-bold {{ $task->is_satisfied ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                        {{ $task->assigned_count }} / {{ $task->required_people_count }}名
                                    </span>
                                </td>
                                <td class="p-3.5 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $task->status_badge_class }}">
                                        {{ $task->status_label }}
                                    </span>
                                </td>
                                <td class="p-3.5 pr-5 text-right whitespace-nowrap">
                                    <a href="{{ route('timetables.tasks.index', $timetable) }}"
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
                    <p class="text-[11px] text-slate-400">「タスク管理」から運行タスクを作成してこの班を割り当ててください。</p>
                    <div class="pt-2">
                        <a href="{{ route('timetables.tasks.index', $timetable) }}"
                            class="inline-flex items-center space-x-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition">
                            <span>＋</span>
                            <span>タスク一覧・追加を開く</span>
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

        </div>

    </div>

</div>
</x-layouts.app>
