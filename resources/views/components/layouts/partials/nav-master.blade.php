<!-- 3. 🗂️ マスター管理 ▾ -->
<div class="relative nav-dropdown shrink-0">
    <button type="button"
        class="dropdown-trigger px-2 py-1 lg:px-2.5 lg:py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1 whitespace-nowrap cursor-pointer shrink-0 {{ $nav->isMasterActive() ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
        <span>🗂️</span>
        <span>マスター管理</span>
        <span class="text-[9px] opacity-75">▼</span>
    </button>
    <div class="dropdown-menu hidden absolute left-0 mt-1.5 w-[620px] lg:w-[650px] max-h-[calc(100vh-4.5rem)] overflow-y-auto bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-2 z-50">
        <div class="grid grid-cols-2 gap-2">
            <!-- 左カラム: 👥 組織・人員 & 🎪 出展・図面マスター -->
            <div class="space-y-2">
                <div class="space-y-1">
                    <div class="px-2.5 py-1 text-[10px] font-bold text-slate-300 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                        <span>👥 組織・人員マスター</span>
                        <span class="text-[9px] text-indigo-300 font-mono font-bold">体制</span>
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('organizations.index') }}"
                            class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('organizations.*') && !request()->routeIs('organizations.print*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base shrink-0">🏛️</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold whitespace-nowrap truncate">組織管理</div>
                                <p class="text-[10px] text-slate-400 truncate mt-0.5">実行委員会の部門・係・役職・担当者体制</p>
                            </div>
                        </a>
                        <a href="{{ route('staff.index') }}"
                            class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('staff.*') && !request()->routeIs('staff.print*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base shrink-0">👷</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold whitespace-nowrap truncate">スタッフ名簿</div>
                                <p class="text-[10px] text-slate-400 truncate mt-0.5">ボランティア・幹事・個別QRコード</p>
                            </div>
                        </a>
                        <a href="{{ route('lanes.index') }}"
                            class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('lanes.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base shrink-0">🏷️</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold whitespace-nowrap truncate">担当班マスター</div>
                                <p class="text-[10px] text-slate-400 truncate mt-0.5">班名・テーマカラー・標準役割の一元管理</p>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="px-2.5 py-1 text-[10px] font-bold text-slate-300 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                        <span>🎪 出展・図面マスター</span>
                        <span class="text-[9px] text-emerald-300 font-mono font-bold">会場</span>
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('vendors.index') }}"
                            class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('vendors.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base shrink-0">🏪</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold whitespace-nowrap truncate">出展・出店者マスター</div>
                                <p class="text-[10px] text-slate-400 truncate mt-0.5">ござ市・企業ブース・特設企画の全出展者台帳</p>
                            </div>
                        </a>
                        <a href="{{ route('site-base-maps.index') }}"
                            class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('site-base-maps.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base shrink-0">🗺️</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold whitespace-nowrap truncate">白地図マスター</div>
                                <p class="text-[10px] text-slate-400 truncate mt-0.5">CAD白地図・縮尺・図面SVGの一元台帳</p>
                            </div>
                        </a>
                        <a href="{{ route('layout-areas.index') }}"
                            class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('layout-areas.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base shrink-0">📐</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold whitespace-nowrap truncate">会場エリアマスター</div>
                                <p class="text-[10px] text-slate-400 truncate mt-0.5">配置計画・一覧で共有する会場エリア定義</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 右カラム: 📦 資材・拠点マスター -->
            <div class="space-y-1">
                <div class="px-2.5 py-1 text-[10px] font-bold text-slate-300 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                    <span>📦 資材・拠点マスター</span>
                    <span class="text-[9px] text-amber-300 font-mono font-bold">リソース</span>
                </div>
                <div class="space-y-0.5">
                    <a href="{{ route('equipments.index') }}"
                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('equipments.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                        <span class="text-base shrink-0">📦</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold whitespace-nowrap truncate">機材・備品一覧</div>
                            <p class="text-[10px] text-slate-400 truncate mt-0.5">自前備品・レンタル機材・私物の一元台帳</p>
                        </div>
                    </a>
                    <a href="{{ route('locations.index') }}"
                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('locations.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                        <span class="text-base shrink-0">📍</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold whitespace-nowrap truncate">拠点マスター</div>
                            <p class="text-[10px] text-slate-400 truncate mt-0.5">倉庫・駐車場・オープンスペース台帳</p>
                        </div>
                    </a>
                    <a href="{{ route('location-types.index') }}"
                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('location-types.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                        <span class="text-base shrink-0">🏷️</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold whitespace-nowrap truncate">拠点タイプ管理</div>
                            <p class="text-[10px] text-slate-400 truncate mt-0.5">拠点種別・アイコン設定</p>
                        </div>
                    </a>
                    <a href="{{ route('equipment-patterns.index') }}"
                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('equipment-patterns.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                        <span class="text-base shrink-0">🧩</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold whitespace-nowrap truncate">搬出入パターン</div>
                            <p class="text-[10px] text-slate-400 truncate mt-0.5">資材搬出入ステップ・テンプレート</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
