<x-layouts.app title="まつり運行準備ナビゲーター">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ページヘッダー (スリム版) -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-4 sm:p-5 text-white shadow-md border border-slate-800 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <span class="w-9 h-9 rounded-xl bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 flex items-center justify-center text-lg shrink-0">
                🚀
            </span>
            <div>
                <h1 class="text-base sm:text-lg font-black tracking-tight flex items-center space-x-2">
                    <span>まつり運行準備ナビゲーター</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                        Step 1〜4
                    </span>
                </h1>
                <p class="text-[11px] text-slate-300 mt-0.5">
                    タイムテーブル作成から班編成・タスク登録・余剰スタッフ配置まで、本番運行に向けた準備手順をガイドします。
                </p>
            </div>
        </div>
    </div>

    <!-- 1. 運行準備 4つのステップ詳細 (最上部に配置) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                <span>📋</span>
                <span>運行準備 4つのステップ詳細</span>
            </h3>
            <span class="text-xs text-slate-500">
                各ステップカードから直接画面へ移動できます
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- Step 1 カード -->
            <div class="bg-white rounded-2xl border {{ $totalCount === 0 ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-slate-200' }} p-4 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold font-mono {{ $totalCount > 0 ? 'bg-emerald-500 text-white' : 'bg-indigo-600 text-white' }}">
                            {{ $totalCount > 0 ? '✔' : '1' }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $totalCount > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200' }}">
                            {{ $totalCount > 0 ? '登録済 (' . $totalCount . '日)' : '未着手' }}
                        </span>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
                            <span>📅</span>
                            <span>Step 1: タイムテーブル作成</span>
                        </h4>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            本祭りや前夜祭などの運行日枠を登録し、まつり運行の土台を設定します。
                        </p>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    <a href="{{ route('timetables.create') }}" class="text-indigo-600 hover:text-indigo-800 font-bold">
                        ＋ 新規作成
                    </a>
                    <a href="{{ route('timetables.index') }}" class="text-slate-500 hover:text-slate-800 text-[11px]">
                        一覧確認 →
                    </a>
                </div>
            </div>

            <!-- Step 2 カード -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold font-mono bg-slate-100 text-slate-600">
                            2
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                            班長・スタッフ編成
                        </span>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
                            <span>🏷️</span>
                            <span>Step 2: 担当班・スタッフ編成</span>
                        </h4>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            各班（カラーコーン、給水など）に現場班長や専任スタッフを設定します。
                        </p>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    <a href="{{ route('lanes.index') }}" class="text-indigo-600 hover:text-indigo-800 font-bold">
                        班編成一覧へ →
                    </a>
                </div>
            </div>

            <!-- Step 3 カード -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold font-mono bg-slate-100 text-slate-600">
                            3
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                            タスク登録
                        </span>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
                            <span>📝</span>
                            <span>Step 3: 作業タスク登録</span>
                        </h4>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            時間帯別の作業タスクを登録します。設定済みの班員は自動配置されます。
                        </p>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    <a href="{{ route('timetables.index') }}" class="text-indigo-600 hover:text-indigo-800 font-bold">
                        日程一覧から登録 →
                    </a>
                </div>
            </div>

            <!-- Step 4 カード -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold font-mono bg-slate-100 text-slate-600">
                            4
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                            過不足調整
                        </span>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
                            <span>⚡</span>
                            <span>Step 4: 人員不足・過不足調整</span>
                        </h4>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            マトリクス進行表で過不足を確認し、他班の余剰スタッフを割り当てます。
                        </p>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    <a href="{{ route('timetable.index') }}" class="text-indigo-600 hover:text-indigo-800 font-bold">
                        進行表（マトリクス）へ →
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- 2. まつり運行準備ステップガイド（日程別一覧） -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">🏮</span>
                <h3 class="text-sm font-bold text-slate-900">
                    まつり運行準備ステップガイド（日程別一覧）
                </h3>
                @if($totalCount > 0)
                <span class="text-xs text-slate-500 font-normal">
                    進行・準備中 {{ $activeTimetableStates->count() }}件 / 完了 {{ $completedTimetableStates->count() }}件
                </span>
                @endif
            </div>

            @if($totalCount > 0)
            <a href="{{ route('timetables.create') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center space-x-1">
                <span>＋ 運行日を追加</span>
            </a>
            @endif
        </div>

        <!-- 一覧リストコンテナ -->
        <div class="space-y-3">

            <!-- A. 進行中・準備中の運行スケジュール一覧 -->
            @foreach($activeTimetableStates as $state)
                @php
                    $tt = $state['timetable'];
                    $isSatisfied = $state['is_satisfied'];
                @endphp
                <div class="bg-white border rounded-2xl p-4 shadow-2xs hover:shadow-xs transition space-y-3 {{ $state['shortage_tasks_count'] > 0 ? 'border-amber-200/80 bg-amber-50/20' : ($isSatisfied ? 'border-emerald-200/80 bg-emerald-50/20' : 'border-slate-200') }}">
                    <!-- 上段: 運行日ヘッダー + 4ステップ進捗ピル + 実行ボタン -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-2.5 border-b border-slate-100">
                        <!-- 運行日情報 -->
                        <div class="flex items-center space-x-2.5 min-w-0">
                            <span class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-bold shrink-0 {{ $tt->status === 'active' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700' }}">
                                📅
                            </span>
                            <div class="min-w-0">
                                <div class="flex items-center space-x-2">
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 truncate">
                                        {{ $tt->target_date ? $tt->target_date->translatedFormat('n月j日 (D) ') : '' }}{{ $tt->name }}
                                    </h4>
                                    @if($tt->status === 'active')
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 shrink-0">
                                            運行中
                                        </span>
                                    @else
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700 shrink-0">
                                            準備中
                                        </span>
                                    @endif
                                </div>
                                <p class="text-[10px] text-slate-400 font-mono mt-0.5">
                                    {{ substr($tt->start_time, 0, 5) }}〜{{ substr($tt->end_time, 0, 5) }}
                                </p>
                            </div>
                        </div>

                        <!-- 4ステップ進捗ミニバー (スリム化) -->
                        <div class="grid grid-cols-4 gap-1.5 flex-1 max-w-xl mx-0 lg:mx-4 text-[11px] select-none">
                            <!-- Step 1: 作成 (常に作成済) -->
                            <div class="px-2 py-1 rounded-lg border flex items-center justify-between bg-emerald-50 text-emerald-800 border-emerald-200">
                                <span class="font-bold flex items-center space-x-1">
                                    <span class="text-[10px] text-emerald-600 font-mono">1.</span>
                                    <span>作成</span>
                                </span>
                                <span class="text-xs font-bold text-emerald-600">✔</span>
                            </div>

                            <!-- Step 2: 班編成 -->
                            @php
                                $s2Done = ($state['lanes_count'] > 0 && $state['lead_configured_count'] === $state['lanes_count']);
                                $s2Active = ($state['current_step'] === 2);
                            @endphp
                            <a href="{{ route('timetables.lanes.index', $tt) }}"
                               class="px-2 py-1 rounded-lg border flex items-center justify-between transition {{ $s2Active ? 'bg-indigo-600 text-white border-indigo-600 shadow-2xs ring-1 ring-indigo-300' : ($s2Done ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border-slate-200') }}">
                                <span class="font-bold flex items-center space-x-1 truncate">
                                    <span class="text-[10px] font-mono {{ $s2Active ? 'text-indigo-200' : 'text-slate-500' }}">2.</span>
                                    <span>班編成</span>
                                </span>
                                <span class="text-[10px] font-mono shrink-0">
                                    {{ $s2Done ? '✔' : ($state['lanes_count'] > 0 ? $state['lead_configured_count'].'/'.$state['lanes_count'] : '未') }}
                                </span>
                            </a>

                            <!-- Step 3: タスク -->
                            @php
                                $s3Done = ($state['total_tasks_count'] > 0);
                                $s3Active = ($state['current_step'] === 3);
                            @endphp
                            <a href="{{ route('timetable-tasks.create', ['timetable_id' => $tt->id]) }}"
                               class="px-2 py-1 rounded-lg border flex items-center justify-between transition {{ $s3Active ? 'bg-indigo-600 text-white border-indigo-600 shadow-2xs ring-1 ring-indigo-300' : ($s3Done ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border-slate-200') }}">
                                <span class="font-bold flex items-center space-x-1 truncate">
                                    <span class="text-[10px] font-mono {{ $s3Active ? 'text-indigo-200' : 'text-slate-500' }}">3.</span>
                                    <span>タスク</span>
                                </span>
                                <span class="text-[10px] font-mono shrink-0">
                                    {{ $s3Done ? $state['total_tasks_count'].'件' : '未' }}
                                </span>
                            </a>

                            <!-- Step 4: 配置 -->
                            @php
                                $s4Done = ($state['total_tasks_count'] > 0 && $state['shortage_tasks_count'] === 0);
                                $s4Active = ($state['current_step'] === 4);
                            @endphp
                            <a href="{{ route('timetable.index', ['timetable_id' => $tt->id]) }}"
                               class="px-2 py-1 rounded-lg border flex items-center justify-between transition {{ $s4Active && !$s4Done ? 'bg-amber-100 text-amber-900 border-amber-300 shadow-2xs ring-1 ring-amber-300' : ($s4Done ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border-slate-200') }}">
                                <span class="font-bold flex items-center space-x-1 truncate">
                                    <span class="text-[10px] font-mono {{ $s4Active && !$s4Done ? 'text-amber-800' : 'text-slate-500' }}">4.</span>
                                    <span>配置</span>
                                </span>
                                <span class="text-[10px] font-mono shrink-0">
                                    {{ $s4Done ? '✔' : ($state['shortage_tasks_count'] > 0 ? '不足'.$state['shortage_tasks_count'] : '待機') }}
                                </span>
                            </a>
                        </div>

                        <!-- アクションボタン -->
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ $state['action_url'] }}"
                               class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-2xs transition flex items-center space-x-1 shrink-0">
                                <span>{{ $state['action_btn_text'] }}</span>
                                <span>→</span>
                            </a>
                            <a href="{{ route('timetable.index', ['timetable_id' => $tt->id]) }}"
                               class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs transition shrink-0"
                               title="タイムテーブル進行表を開く">
                                ⏱️
                            </a>
                        </div>
                    </div>

                    <!-- 下段スリム情報: 現在の推奨アクション案内 & KPI -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px] text-slate-600">
                        <div class="flex items-center space-x-1.5 min-w-0">
                            <span class="font-bold text-slate-800 flex items-center space-x-1 shrink-0">
                                <span>💡 推奨:</span>
                                <span>{{ $state['action_title'] }}</span>
                            </span>
                            <span class="text-slate-400 hidden sm:inline">—</span>
                            <span class="text-slate-500 truncate hidden md:inline">{{ $state['action_desc'] }}</span>
                        </div>

                        <div class="flex items-center space-x-3 shrink-0 font-mono text-[10px] text-slate-500">
                            <span>班: <strong class="text-slate-700">{{ $state['lanes_count'] }}</strong></span>
                            <span>タスク: <strong class="text-slate-700">{{ $state['total_tasks_count'] }}</strong></span>
                            @if($state['shortage_tasks_count'] > 0)
                                <span class="text-rose-600 font-bold">⚠️ 不足: {{ $state['shortage_tasks_count'] }}件</span>
                            @else
                                <span class="text-emerald-600 font-bold">✔ 充足</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- B. 完了した運行スケジュール一覧 (下部配置・グレーアウト・最大2件) -->
            @if($completedTimetableStates->isNotEmpty())
                <div class="pt-3 space-y-2">
                    <div class="flex items-center space-x-1.5 text-xs text-slate-500 font-bold">
                        <span>🏁</span>
                        <span>運行完了したスケジュール（直近{{ $completedTimetableStates->count() }}件）</span>
                    </div>

                    @foreach($completedTimetableStates as $state)
                        @php
                            $tt = $state['timetable'];
                        @endphp
                        <div class="bg-slate-100/70 border border-slate-200/90 rounded-2xl p-4 shadow-2xs opacity-75 grayscale-[30%] space-y-3">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-2.5 border-b border-slate-200">
                                <!-- 運行日情報 -->
                                <div class="flex items-center space-x-2.5 min-w-0">
                                    <span class="w-8 h-8 rounded-xl bg-slate-200 text-slate-600 flex items-center justify-center text-sm font-bold shrink-0">
                                        🏁
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <h4 class="text-xs sm:text-sm font-bold text-slate-700 truncate">
                                                {{ $tt->target_date ? $tt->target_date->translatedFormat('n月j日 (D) ') : '' }}{{ $tt->name }}
                                            </h4>
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-200 text-slate-700 shrink-0">
                                                運行完了
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 font-mono mt-0.5">
                                            {{ substr($tt->start_time, 0, 5) }}〜{{ substr($tt->end_time, 0, 5) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- 4ステップ進捗ミニバー (完了表示) -->
                                <div class="grid grid-cols-4 gap-1.5 flex-1 max-w-xl mx-0 lg:mx-4 text-[11px] select-none">
                                    <div class="px-2 py-1 rounded-lg border bg-slate-200/60 text-slate-600 border-slate-300 flex items-center justify-between">
                                        <span class="font-bold flex items-center space-x-1">
                                            <span class="text-[10px] font-mono">1.</span>
                                            <span>作成</span>
                                        </span>
                                        <span class="text-xs">✔</span>
                                    </div>
                                    <div class="px-2 py-1 rounded-lg border bg-slate-200/60 text-slate-600 border-slate-300 flex items-center justify-between">
                                        <span class="font-bold flex items-center space-x-1 truncate">
                                            <span class="text-[10px] font-mono">2.</span>
                                            <span>班編成</span>
                                        </span>
                                        <span class="text-[10px] font-mono">✔</span>
                                    </div>
                                    <div class="px-2 py-1 rounded-lg border bg-slate-200/60 text-slate-600 border-slate-300 flex items-center justify-between">
                                        <span class="font-bold flex items-center space-x-1 truncate">
                                            <span class="text-[10px] font-mono">3.</span>
                                            <span>タスク</span>
                                        </span>
                                        <span class="text-[10px] font-mono">{{ $state['total_tasks_count'] }}件</span>
                                    </div>
                                    <div class="px-2 py-1 rounded-lg border bg-slate-200/60 text-slate-600 border-slate-300 flex items-center justify-between">
                                        <span class="font-bold flex items-center space-x-1 truncate">
                                            <span class="text-[10px] font-mono">4.</span>
                                            <span>配置</span>
                                        </span>
                                        <span class="text-xs">✔</span>
                                    </div>
                                </div>

                                <!-- アクションボタン -->
                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="{{ route('timetable.index', ['timetable_id' => $tt->id]) }}"
                                       class="px-3.5 py-1.5 bg-slate-600 hover:bg-slate-700 text-white rounded-xl text-xs font-bold shadow-2xs transition flex items-center space-x-1 shrink-0">
                                        <span>運行実績を確認</span>
                                        <span>→</span>
                                    </a>
                                </div>
                            </div>

                            <!-- 下段スリム情報: 完了案内 & 実績数値 -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px] text-slate-500">
                                <span class="truncate">
                                    「{{ $tt->name }}」の運行は無事完了しました。進行表で実績やアサイン結果を確認できます。
                                </span>

                                <div class="flex items-center space-x-3 shrink-0 font-mono text-[10px] text-slate-500">
                                    <span>班: <strong class="text-slate-600">{{ $state['lanes_count'] }}</strong></span>
                                    <span>タスク: <strong class="text-slate-600">{{ $state['total_tasks_count'] }}</strong></span>
                                    <span class="text-slate-600 font-bold">✔ 完了済</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- C. 運行日未登録時のスリム案内カード (Step 1) -->
            @if($totalCount === 0)
                <div class="bg-white border border-slate-200 rounded-2xl p-6 text-center space-y-3">
                    <span class="text-3xl">📅</span>
                    <div class="space-y-1">
                        <h4 class="text-sm font-bold text-slate-900">運行日（タイムテーブル）が未登録です</h4>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">
                            本祭りや前夜祭などの運行日枠を登録して、まつり運行準備のStep 1を開始しましょう。
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('timetables.create') }}" class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            <span>➕</span>
                            <span>新規タイムテーブルを登録 (Step 1)</span>
                        </a>
                    </div>
                </div>
            @endif

            <!-- D. リスト最下部: 「全ての運行スケジュールを見る」リンク -->
            <div class="pt-3 pb-1 text-center">
                <a href="{{ route('timetables.index') }}"
                   class="inline-flex items-center space-x-2 px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 rounded-xl text-xs font-bold border border-slate-200 transition shadow-2xs">
                    <span>📋</span>
                    <span>全ての運行スケジュールを見る</span>
                    <span>→</span>
                </a>
            </div>

        </div>
    </div>

</div>
</x-layouts.app>
