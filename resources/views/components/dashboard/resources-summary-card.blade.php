<div class="space-y-3">
    <!-- 1. 組織体制 ＆ 拠点・資機材サマリー -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 space-y-2.5">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <h3 class="font-bold text-xs text-slate-900 flex items-center space-x-1.5">
                <span>🏛️</span>
                <span>組織・資機材・拠点マスタ</span>
            </h3>
        </div>

        <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/70">
                <div class="text-[10px] text-slate-500 font-bold">👥 スタッフ</div>
                <div class="text-sm font-bold text-slate-900 mt-0.5">
                    {{ $overview->staffTotalCount }}名
                </div>
                <div class="text-[10px] text-slate-500 mt-0.5">
                    幹事・班長: <strong>{{ $overview->staffLeaderCount }}名</strong>
                </div>
            </div>

            <div class="p-2 bg-slate-50 rounded-lg border border-slate-200/70">
                <div class="text-[10px] text-slate-500 font-bold">📦 資機材・拠点</div>
                <div class="text-sm font-bold text-slate-900 mt-0.5">
                    {{ $overview->equipmentTotalCount }}品目
                </div>
                <div class="text-[10px] text-slate-500 mt-0.5">
                    拠点施設: <strong>{{ $overview->locationTotalCount }}所</strong>
                </div>
            </div>
        </div>

        <div class="pt-1">
            <a href="{{ route('staff.join') }}"
               class="w-full py-1.5 px-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200/80 rounded-lg text-xs font-bold transition flex items-center justify-center space-x-1 shadow-2xs">
                <span>📢</span>
                <span>公募スタッフ・ボランティア参加登録</span>
                <span>→</span>
            </a>
        </div>
    </div>

    <!-- 2. 司令塔クイックランチャー (主要機能へのショートカット) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 space-y-2">
        <h3 class="font-bold text-xs text-slate-900 flex items-center space-x-1.5 pb-1.5 border-b border-slate-100">
            <span>🚀</span>
            <span>司令塔クイックランチャー</span>
        </h3>

        <div class="grid grid-cols-2 gap-1.5 text-xs">
            <a href="{{ route('preparation-navigator.index') }}"
               class="p-2 bg-slate-50/80 hover:bg-indigo-50 hover:border-indigo-200 rounded-lg border border-slate-200/70 transition flex items-center space-x-1.5 font-bold text-slate-800 hover:text-indigo-700">
                <span>🧭</span>
                <span class="truncate">運行準備ナビ</span>
            </a>

            <a href="{{ route('schedules.index') }}"
               class="p-2 bg-slate-50/80 hover:bg-indigo-50 hover:border-indigo-200 rounded-lg border border-slate-200/70 transition flex items-center space-x-1.5 font-bold text-slate-800 hover:text-indigo-700">
                <span>📅</span>
                <span class="truncate">年間カレンダー</span>
            </a>

            <a href="{{ route('staff.index') }}"
               class="p-2 bg-slate-50/80 hover:bg-indigo-50 hover:border-indigo-200 rounded-lg border border-slate-200/70 transition flex items-center space-x-1.5 font-bold text-slate-800 hover:text-indigo-700">
                <span>👥</span>
                <span class="truncate">スタッフ名簿</span>
            </a>

            <a href="{{ route('equipments.index') }}"
               class="p-2 bg-slate-50/80 hover:bg-indigo-50 hover:border-indigo-200 rounded-lg border border-slate-200/70 transition flex items-center space-x-1.5 font-bold text-slate-800 hover:text-indigo-700">
                <span>📦</span>
                <span class="truncate">資機材管理</span>
            </a>

            <a href="{{ route('location-types.index') }}"
               class="p-2 bg-slate-50/80 hover:bg-indigo-50 hover:border-indigo-200 rounded-lg border border-slate-200/70 transition flex items-center space-x-1.5 font-bold text-slate-800 hover:text-indigo-700">
                <span>🏢</span>
                <span class="truncate">拠点種別マスタ</span>
            </a>

            <a href="{{ route('manual.index') }}"
               class="p-2 bg-slate-50/80 hover:bg-indigo-50 hover:border-indigo-200 rounded-lg border border-slate-200/70 transition flex items-center space-x-1.5 font-bold text-slate-800 hover:text-indigo-700">
                <span>📖</span>
                <span class="truncate">業務マニュアル</span>
            </a>
        </div>
    </div>
</div>
