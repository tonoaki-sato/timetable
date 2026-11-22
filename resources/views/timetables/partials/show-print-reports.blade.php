<!-- 4. 🖨️ 当運行計画 専用現場帳票カード (区分B) -->
<div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3.5 space-y-3">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 pb-2 border-b border-slate-100">
        <div class="flex items-center space-x-2">
            <span class="text-base sm:text-lg shrink-0">🖨️</span>
            <div>
                <h2 class="text-xs font-bold text-slate-900">運行日別・現場帳票（PDF印刷）</h2>
                <p class="text-[10px] text-slate-500">「{{ $timetable->name }}」の現場運営・本部控え用帳票を出力します</p>
            </div>
        </div>
        <span class="inline-flex items-center self-start sm:self-auto text-[10px] font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-lg shrink-0">
            区分B: 運行情報紐づき
        </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2">
        <!-- 1. 運行タイムテーブル (実装済み) -->
        <a href="{{ route('timetables.print-matrix', $timetable) }}" target="_blank"
            class="flex items-start space-x-2 p-2.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 transition group">
            <span class="text-base shrink-0">⏱️</span>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-bold text-slate-900 group-hover:text-indigo-600 flex items-center justify-between">
                    <span class="truncate">運行タイムテーブル</span>
                    <span class="text-[10px] text-slate-400">↗</span>
                </div>
                <p class="text-[10px] text-slate-500 mt-0.5 truncate">時間軸×班軸マトリクス (A4横)</p>
            </div>
        </a>

        <!-- 2. 担当班編成表 (実装済み) -->
        <a href="{{ route('timetables.lanes.print', $timetable) }}" target="_blank"
            class="flex items-start space-x-2 p-2.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 transition group">
            <span class="text-base shrink-0">🏷️</span>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-bold text-slate-900 group-hover:text-indigo-600 flex items-center justify-between">
                    <span class="truncate">担当班編成表</span>
                    <span class="text-[10px] text-slate-400">↗</span>
                </div>
                <p class="text-[10px] text-slate-500 mt-0.5 truncate">班長・メンバー・無線機 (A4縦)</p>
            </div>
        </a>

        <!-- 3. 機材搬出入計画表 (実装済み) -->
        <a href="{{ route('timetables.logistics.print', $timetable) }}" target="_blank"
            class="flex items-start space-x-2 p-2.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 transition group">
            <span class="text-base shrink-0">🚚</span>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-bold text-slate-900 group-hover:text-indigo-600 flex items-center justify-between">
                    <span class="truncate">機材搬出入計画表</span>
                    <span class="text-[10px] text-slate-400">↗</span>
                </div>
                <p class="text-[10px] text-slate-500 mt-0.5 truncate">6フェーズ資材移動指示書 (A4縦)</p>
            </div>
        </a>

        <!-- 4. 担当班別タスク一覧 (実装済み: timetables.print-assignments) -->
        <a href="{{ route('timetables.print-assignments', $timetable) }}" target="_blank"
            class="flex items-start space-x-2 p-2.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 transition group">
            <span class="text-base shrink-0">📋</span>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-bold text-slate-900 group-hover:text-indigo-600 flex items-center justify-between">
                    <span class="truncate">担当班別タスク一覧</span>
                    <span class="text-[10px] text-slate-400">↗</span>
                </div>
                <p class="text-[10px] text-slate-500 mt-0.5 truncate">班ごとのタスク割当一覧 (A4横)</p>
            </div>
        </a>

        <!-- 5. スタッフ別タスク一覧 (未実装: テキストのみ表示) -->
        <div class="flex items-start space-x-2 p-2.5 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 opacity-75">
            <span class="text-base shrink-0">👤</span>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-bold text-slate-500 flex items-center justify-between">
                    <span class="truncate">スタッフ別タスク一覧</span>
                    <span class="text-[9px] bg-slate-200 text-slate-600 px-1 rounded font-mono">準備中</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-0.5 truncate">個人別タイムラインシフト表</p>
            </div>
        </div>
    </div>
</div>
