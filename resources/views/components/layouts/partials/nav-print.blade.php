<!-- 4. 🖨️ 帳票・印刷 ▾ -->
<div class="relative nav-dropdown shrink-0">
    <button type="button"
        class="dropdown-trigger px-2 py-1 lg:px-2.5 lg:py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1 whitespace-nowrap cursor-pointer shrink-0 {{ $nav->isPrintActive() ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
        <span>🖨️</span>
        <span>帳票・印刷</span>
        <span class="text-[9px] opacity-75">▼</span>
    </button>
    <div class="dropdown-menu hidden absolute left-0 mt-1.5 w-80 max-h-[calc(100vh-4.5rem)] overflow-y-auto bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50">
        <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
            <span>🌐 全体・マスター帳票 (区分A)</span>
            <span class="text-[9px] text-indigo-300 font-mono font-bold">年間・共通</span>
        </div>
        <div class="py-1 space-y-0.5">
            <!-- 組織図PDF -->
            <a href="{{ route('organizations.print') }}"
                class="flex items-center space-x-2 p-2 rounded-xl transition text-slate-200 hover:bg-slate-800 hover:text-white"
                title="実行委員会組織体制のA4縦帳票PDFをダウンロード">
                <span class="text-base shrink-0">🏛️</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold flex items-center space-x-1">
                        <span class="whitespace-nowrap truncate">組織図 (PDF)</span>
                    </div>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">実行委員会組織体制のA4縦帳票PDFダウンロード</p>
                </div>
            </a>

            <!-- スタッフカード一括印刷 (実装済み) -->
            <a href="{{ route('staff.print-cards') }}" target="_blank"
                class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('staff.print*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                <span class="text-base shrink-0">🪪</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold flex items-center space-x-1">
                        <span class="whitespace-nowrap truncate">名札カード一括印刷 (PDF)</span>
                        <span class="text-[10px] text-slate-400">↗</span>
                    </div>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">全登録スタッフのA4面付け印刷</p>
                </div>
            </a>

            <!-- スタッフCSV出力 (実装済み) -->
            <a href="{{ route('staff.export-csv') }}"
                class="flex items-center space-x-2 p-2 rounded-xl transition text-slate-200 hover:bg-slate-800 hover:text-white">
                <span class="text-base shrink-0">📥</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold flex items-center space-x-1">
                        <span class="whitespace-nowrap truncate">スタッフCSV出力</span>
                        <span class="text-[10px] text-slate-400">↓</span>
                    </div>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">現場スタッフ台帳をCSVで保存・保管</p>
                </div>
            </a>

            <!-- 年間スケジュール表 (PDF) -->
            <a href="{{ route('schedules.pdf') }}"
                class="flex items-center space-x-2 p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition group cursor-pointer"
                title="年間スケジュール一覧のA4縦帳票PDFをダウンロード">
                <span class="text-base shrink-0 group-hover:scale-110 transition-transform">📅</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold flex items-center space-x-1">
                        <span class="whitespace-nowrap truncate">年間スケジュール表 (PDF)</span>
                        <span class="text-[10px] text-slate-400">↓</span>
                    </div>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">年間工程・会議日程一覧表 (A4縦)</p>
                </div>
            </a>

            <!-- スタッフ一覧 (PDF) -->
            <a href="{{ route('staff.pdf') }}"
                class="flex items-center space-x-2 p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition group cursor-pointer"
                title="全登録スタッフ一覧のA4縦・2段組帳票PDFをダウンロード">
                <span class="text-base shrink-0 group-hover:scale-110 transition-transform">👥</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold flex items-center space-x-1">
                        <span class="whitespace-nowrap truncate">スタッフ名簿一覧 (PDF)</span>
                        <span class="text-[10px] text-slate-400">↓</span>
                    </div>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">全スタッフ名簿一覧表 (A4縦・2段組)</p>
                </div>
            </a>

            <!-- レンタル機材一覧 (未実装: テキストのみ表示) -->
            <div class="flex items-center space-x-2 p-2 rounded-xl text-slate-500 opacity-70 cursor-not-allowed">
                <span class="text-base shrink-0">📦</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold flex items-center justify-between">
                        <span class="whitespace-nowrap truncate">レンタル機材一覧 (PDF)</span>
                        <span class="text-[9px] bg-slate-800 text-slate-400 px-1.5 py-0.2 rounded font-mono">準備中</span>
                    </div>
                    <p class="text-[10px] text-slate-500 truncate mt-0.5">発注レンタル機材の集計リスト</p>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800/80 my-1"></div>

        <!-- 区分Bへの案内フッター -->
        <div class="px-2.5 py-2 text-[10px] text-slate-400 bg-slate-800/40 rounded-xl leading-relaxed">
            💡 運行日別（タイムテーブル・担当班・搬出入等）の現場帳票は、各「運行情報詳細」画面から出力できます。
        </div>
    </div>
</div>
