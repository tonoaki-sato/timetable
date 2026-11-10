<x-layouts.app :title="$timetable->name . ' 搬出入計画一覧'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- 1. ヘッダーセクション -->
    <x-page-header
        :title="$timetable->name . ' 搬出入計画一覧'"
        icon="🚚"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）における資材の搬出入・中継・夜間保管計画の進捗台帳です。'">
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
            <span class="text-slate-700 font-bold truncate">🚚 搬出入計画</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ $printUrl }}"
               class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0"
               title="搬出入指示書兼現場チェックリストをPDFでダウンロード">
                <span>📄</span>
                <span>搬出入指示書PDF</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="logistics" />

    <!-- アラートメッセージ -->
    @if (session('status'))
        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between">
            <span>✅ {{ session('status') }}</span>
        </div>
    @endif

    @if (session('success'))
        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between">
            <span>✅ {{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
            <div class="font-bold">⚠️ 入力内容に不備があります：</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 絞り込みツールバー -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-3">
        <form method="GET" action="{{ route('timetables.logistics.index', $timetable) }}" class="flex items-center justify-between flex-wrap gap-2 text-xs">
            <div class="flex items-center space-x-2 flex-wrap gap-y-1.5">
                <span class="font-bold text-slate-600 flex items-center space-x-1">
                    <span>🔍</span>
                    <span>絞り込み:</span>
                </span>

                <!-- 担当班フィルタ -->
                <select name="lane_id" data-auto-submit class="px-2.5 py-1 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800">
                    <option value="">すべての担当班</option>
                    @foreach($lanes as $lane)
                        <option value="{{ $lane->id }}" {{ $laneFilter === $lane->id ? 'selected' : '' }}>
                            {{ $lane->icon ?: '🏷️' }} {{ $lane->name }}
                        </option>
                    @endforeach
                </select>

                <!-- 資材区分フィルタ -->
                <select name="type" data-auto-submit class="px-2.5 py-1 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800">
                    <option value="">すべての区分</option>
                    <option value="committee" {{ $typeFilter === 'committee' ? 'selected' : '' }}>自前備品 (委員会)</option>
                    <option value="rental" {{ $typeFilter === 'rental' ? 'selected' : '' }}>レンタル機材 (イベント会社)</option>
                    <option value="personal" {{ $typeFilter === 'personal' ? 'selected' : '' }}>私物 (個人持ち込み)</option>
                </select>
            </div>

            @if($laneFilter || $typeFilter)
                <a href="{{ route('timetables.logistics.index', $timetable) }}"
                   class="text-xs text-rose-600 hover:text-rose-800 font-bold transition flex items-center space-x-1">
                    <span>✕</span>
                    <span>フィルタ解除</span>
                </a>
            @endif
        </form>
    </div>

    <!-- 資材別 計画ステップ一覧 -->
    <div class="space-y-3">
            @forelse($equipments as $equipment)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 space-y-3 hover:border-slate-300 transition">
                    <!-- 資材ヘッダー行 -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2.5 flex-wrap">
                            <span class="text-xl">
                                {{ $equipment->isCommittee() ? '🏛️' : ($equipment->isRental() ? '🏢' : '🎒') }}
                            </span>
                            <a href="{{ route('timetables.logistics.equipments.show', [$timetable, $equipment]) }}"
                               class="text-sm sm:text-base font-bold text-slate-900 hover:text-indigo-600 transition">
                                {{ $equipment->name }}
                            </a>
                            <span class="px-2 py-0.5 rounded-full text-xs font-mono font-bold {{ $equipment->isCommittee() ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($equipment->isRental() ? 'bg-sky-50 text-sky-800 border border-sky-200' : 'bg-amber-50 text-amber-800 border border-amber-200') }}">
                                {{ $equipment->formatted_quantity }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $equipment->isCommittee() ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($equipment->isRental() ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                {{ $equipment->type_label }}
                            </span>
                            @if($equipment->storage_location)
                                <span class="text-[11px] text-slate-500 font-medium">
                                    保管: {{ $equipment->storage_location }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center space-x-2 shrink-0 self-end sm:self-center">
                            @if($equipment->plans->isNotEmpty())
                                <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-lg {{ $equipment->are_all_plans_completed ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200' }}">
                                    {{ $equipment->completed_plans_count }}/{{ $equipment->plans->count() }} ステップ完了
                                </span>
                            @else
                                <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 border border-amber-200">
                                    ⚠️ ステップ未設定
                                </span>
                            @endif

                            <a href="{{ route('timetables.logistics.equipments.show', [$timetable, $equipment]) }}"
                               class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition inline-flex items-center space-x-1 shadow-2xs whitespace-nowrap">
                                <span>✏️</span>
                                <span>計画・ステップ編集</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>

                    <!-- ステップ一覧（時系列ステップフロー） -->
                    @if($equipment->plans->isNotEmpty())
                        <div class="pt-1">
                            <ol class="flex flex-col md:flex-row md:flex-wrap items-stretch gap-3">
                                @foreach($equipment->plans as $plan)
                                    <li class="flex-1 min-w-[260px] max-w-full md:max-w-xs relative flex flex-col justify-between p-3 rounded-xl border {{ $plan->isCompleted() ? 'border-emerald-200 bg-emerald-50/25' : ($plan->status === 'in_progress' ? 'border-amber-300 bg-amber-50/20 shadow-2xs' : 'border-slate-200 bg-slate-50/60') }} space-y-2">
                                        <!-- ステップヘッダー: 順序番号・フェーズ・ステータス -->
                                        <div class="flex items-center justify-between gap-1 flex-wrap">
                                            <div class="flex items-center space-x-1.5 font-bold">
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-mono font-bold {{ $plan->isCompleted() ? 'bg-emerald-600 text-white' : ($plan->status === 'in_progress' ? 'bg-amber-500 text-white' : 'bg-slate-700 text-white') }}">
                                                    {{ $loop->iteration }}
                                                </span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $plan->phase_badge_class }}">
                                                    {{ $plan->phase_label }}
                                                </span>
                                            </div>

                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $plan->isCompleted() ? 'text-emerald-700 bg-emerald-100/70' : ($plan->status === 'in_progress' ? 'text-amber-700 bg-amber-100/70' : 'text-slate-600 bg-slate-200/60') }}">
                                                @if($plan->isCompleted())
                                                    ✅ 完了
                                                @elseif($plan->status === 'in_progress')
                                                    🏃 進行中
                                                @else
                                                    ⚪ 計画中
                                                @endif
                                            </span>
                                        </div>

                                        <!-- 日時・時刻 (時系列情報) -->
                                        @if($plan->target_date || $plan->formatted_time_range)
                                            <div class="flex items-center space-x-2 text-[11px] font-mono text-slate-600">
                                                @if($plan->target_date)
                                                    <span class="font-bold">📅 {{ $plan->target_date->format('m/d') }}</span>
                                                @endif
                                                @if($plan->formatted_time_range)
                                                    <span class="text-indigo-700 font-bold bg-indigo-50 px-1.5 py-0.2 rounded border border-indigo-100">
                                                        ⏱️ {{ $plan->formatted_time_range }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif

                                        <!-- ステップタイトル -->
                                        <div class="font-bold text-xs text-slate-900 truncate" title="{{ $plan->title }}">
                                            {{ $plan->title }}
                                        </div>

                                        <!-- 移動ルート（移動元 ➔ 移動先） -->
                                        <div class="text-[11px] text-slate-600 bg-white p-2 rounded-lg border border-slate-200/80 space-y-1">
                                            <div class="flex items-center justify-between text-slate-400 text-[10px]">
                                                <span>移動ルート</span>
                                                @if($plan->formatted_quantity !== '')
                                                    <span class="font-mono text-indigo-700 font-bold">{{ $plan->formatted_quantity }}</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center space-x-1.5 font-medium truncate">
                                                <span class="truncate text-slate-700" title="{{ $plan->resolved_from_location }}">{{ $plan->resolved_from_location ?: '定常保管場所' }}</span>
                                                <span class="text-indigo-500 font-bold shrink-0">➔</span>
                                                <span class="truncate font-bold text-slate-900" title="{{ $plan->resolved_to_location }}">{{ $plan->resolved_to_location ?: '現地会場' }}</span>
                                            </div>
                                        </div>

                                        <!-- フッター情報（作業主体・立会・次へ案内） -->
                                        <div class="flex items-center justify-between gap-1 text-[10px] text-slate-500 pt-1 border-t border-slate-200/60">
                                            <div class="flex items-center space-x-1 flex-wrap">
                                                @if($plan->operator_type === 'event_company')
                                                    <span class="bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold px-1.5 py-0.2 rounded">🏢 業者</span>
                                                @else
                                                    <span class="bg-slate-100 text-slate-600 font-bold px-1.5 py-0.2 rounded">👥 委員会</span>
                                                @endif

                                                @if($plan->witness_required)
                                                    <span class="text-rose-600 font-bold flex items-center space-x-0.5">
                                                        <span>⚠️</span>
                                                        <span>立会必須</span>
                                                    </span>
                                                @endif
                                            </div>

                                            @if(! $loop->last)
                                                <span class="text-slate-400 font-bold text-xs" title="次のステップへ">次へ ➔</span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @else
                        <div class="p-4 bg-slate-50 rounded-xl border border-dashed border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                            <span class="text-xs text-slate-500">
                                💡 この運行日の搬出入・中継・夜間保管ステップがまだ作成されていません。
                            </span>
                            <a href="{{ route('timetables.logistics.equipments.show', [$timetable, $equipment]) }}"
                               class="text-xs font-bold text-indigo-700 hover:text-indigo-900 underline flex items-center space-x-1">
                                <span>⚡ パターン適用・ステップ追加画面を開く</span>
                                <span>→</span>
                            </a>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-12 text-center bg-white rounded-2xl border border-dashed border-slate-300 text-slate-500 space-y-2">
                    <span class="text-3xl block">📦</span>
                    <p class="font-bold text-slate-700">条件に該当する資材はありません</p>
                    <p class="text-xs text-slate-400">絞り込み条件を変更するか、資材管理から新しい資材を登録してください。</p>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
