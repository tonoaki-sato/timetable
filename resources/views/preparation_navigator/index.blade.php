<x-layouts.app title="まつり運行準備ナビゲーター">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ページヘッダー -->
    <x-page-header
        title="まつり運行準備ナビゲーター"
        icon="🚀"
        description="タイムテーブル作成から班編成・タスク登録・余剰スタッフ配置まで、本番運行に向けた準備手順をガイドします。">
        <x-slot:breadcrumbs>
            <span class="text-slate-800 font-bold shrink-0">運行準備ナビ</span>
        </x-slot:breadcrumbs>
    </x-page-header>

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
                    @if($totalCount === 0)
                    <a href="{{ route('timetables.create') }}" class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center space-x-1">
                        <span>＋ 新規作成へ</span>
                        <span>→</span>
                    </a>
                    @else
                    <a href="{{ route('timetables.index') }}" class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center space-x-1">
                        <span>一覧確認へ</span>
                        <span>→</span>
                    </a>
                    @endif
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
                            担当班編成
                        </span>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
                            <span>🏷️</span>
                            <span>Step 2: 担当班編成</span>
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
                            <span>Step 3: タスク登録</span>
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
                            人員配置
                        </span>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
                            <span>👥</span>
                            <span>Step 4: 人員配置</span>
                        </h4>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            スタッフ稼働タイムラインで空き枠を確認し、不足タスクへ割り当てます。
                        </p>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    @if($firstActiveTimetable)
                    <a href="{{ route('timetables.staff-timeline', $firstActiveTimetable) }}" class="text-indigo-600 hover:text-indigo-800 font-bold">
                        スタッフタイムラインへ →
                    </a>
                    @else
                    <a href="{{ route('timetables.index') }}" class="text-indigo-600 hover:text-indigo-800 font-bold">
                        日程一覧から選択 →
                    </a>
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>
</x-layouts.app>
