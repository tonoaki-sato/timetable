<x-layouts.app :title="$timetable->name . ' 白地図配置エディタ (A3 1/400)'">
<div class="max-w-[1600px] mx-auto px-3 sm:px-5 py-3 space-y-2.5">

    <!-- ページヘッダー -->
    <x-page-header
        :title="$timetable->name . ' 白地図配置エディタ'"
        icon="🗺️"
        :description="'A3 1/400 CADベクター図面上でブース・設備を精密に配置し、PDF出力します'">
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
            <a href="{{ route('timetables.layouts.index', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>🗺️</span>
                <span>配置計画</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">🎨 白地図エディタ</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.site-maps.pdf', $timetable) }}"
                id="btn-export-pdf"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>📥</span>
                <span>PDFダウンロード (A3 1/400)</span>
            </a>
            <a href="{{ route('timetables.layouts.booths', $timetable) }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>📋</span>
                <span>配置一覧</span>
            </a>
            <a href="{{ route('timetables.layouts.index', $timetable) }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>📊</span>
                <span>配置計画サマリー</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="site-maps" />

    <!-- エディタ全体コンテナ -->
    <div id="site-layout-editor-app"
        class="flex flex-col space-y-2"
        data-base-map-id="{{ $siteBaseMap->id }}"
        data-svg-url="{{ asset($siteBaseMap->svg_path) }}"
        data-scale-ratio="{{ $siteBaseMap->scale_ratio }}"
        data-viewbox="{{ $siteBaseMap->viewbox }}"
        data-store-url="{{ route('timetables.site-maps.placements.store', $timetable) }}"
        data-bulk-url="{{ route('timetables.site-maps.placements.bulk-update', $timetable) }}"
        data-placements-url="{{ route('timetables.site-maps.placements.index', $timetable) }}"
        data-reset-url="{{ route('timetables.site-maps.reset-preset', $timetable) }}"
        data-csrf-token="{{ csrf_token() }}">

        <!-- 上部ドローツール・アクションバー -->
        <div class="bg-white border border-slate-200 rounded-xl px-2.5 py-2 shadow-2xs flex flex-wrap items-center justify-between gap-2 text-xs">
            <!-- 左側: ツールモード / 履歴 / オブジェクト操作 / 整列 -->
            <div class="flex flex-wrap items-center gap-1.5">
                <!-- モード切替 -->
                <div class="inline-flex rounded-lg border border-slate-300 bg-slate-50 p-0.5">
                    <button type="button" id="btn-mode-select"
                        class="px-2 py-1 rounded-md font-bold bg-indigo-600 text-white transition cursor-pointer"
                        title="選択・移動モード (Vキー)">
                        ↖ 選択
                    </button>
                    <button type="button" id="btn-mode-pan"
                        class="px-2 py-1 rounded-md font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                        title="手のひら移動モード (Hキー / Space押下)">
                        ✋ 手のひら
                    </button>
                </div>

                <div class="h-4 w-px bg-slate-300"></div>

                <!-- Undo / Redo -->
                <button type="button" id="btn-undo" disabled
                    class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                    title="元に戻す (Ctrl+Z)">
                    ↩ 戻す
                </button>
                <button type="button" id="btn-redo" disabled
                    class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                    title="やり直し (Ctrl+Y)">
                    ↪ やり直し
                </button>

                <div class="h-4 w-px bg-slate-300"></div>

                <!-- 複製 / 回転 / 削除 -->
                <button type="button" id="btn-duplicate" disabled
                    class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                    title="連番複製 (Ctrl+D)">
                    📄 複製
                </button>
                <button type="button" id="btn-rotate-selected" disabled
                    class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                    title="45°回転 (Rキー)">
                    ↻ 45°
                </button>
                <button type="button" id="btn-delete-selected" disabled
                    class="px-2 py-1 bg-red-50 border border-red-200 hover:bg-red-600 hover:text-white text-red-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                    title="削除 (Deleteキー)">
                    🗑️ 削除
                </button>

                <div class="h-4 w-px bg-slate-300"></div>

                <!-- 一括整列 (2個以上選択時有効) -->
                <div class="inline-flex items-center space-x-1" title="2個以上選択時に一括整列">
                    <button type="button" id="btn-align-left" disabled
                        class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                        title="左揃え">
                        ⫷ 左揃
                    </button>
                    <button type="button" id="btn-align-top" disabled
                        class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                        title="上揃え">
                        ⊤ 上揃
                    </button>
                    <button type="button" id="btn-pack-vert" disabled
                        class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                        title="縦に隙間なく密着連結">
                        ⇕ 縦密着
                    </button>
                    <button type="button" id="btn-pack-horiz" disabled
                        class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                        title="横に隙間なく密着連結">
                        ⇔ 横密着
                    </button>
                </div>
            </div>

            <!-- 右側: 衛生20m圏 / スナップ / ズーム / 保存状態 -->
            <div class="flex flex-wrap items-center gap-1.5">
                <button type="button" id="btn-toggle-hygiene"
                    class="px-2.5 py-1 bg-sky-600 text-white border border-sky-600 rounded-lg font-bold shadow-2xs transition cursor-pointer"
                    title="給水設備の半径20m衛生チェック円を表示/非表示">
                    💧 20m衛生圏 ON
                </button>
                <button type="button" id="btn-toggle-snap"
                    class="px-2.5 py-1 bg-emerald-600 text-white border border-emerald-600 rounded-lg font-bold shadow-2xs transition cursor-pointer"
                    title="歩道レール・隣接ブースへの自動吸着ON/OFF">
                    🧲 吸着 ON
                </button>

                <div class="h-4 w-px bg-slate-300"></div>

                <button type="button" id="btn-zoom-out" class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg font-bold cursor-pointer" title="縮小">－</button>
                <span id="zoom-level-text" class="px-2 py-1 font-mono font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg min-w-[50px] text-center">100%</span>
                <button type="button" id="btn-zoom-in" class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg font-bold cursor-pointer" title="拡大">＋</button>
                <button type="button" id="btn-zoom-100" class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg font-bold cursor-pointer" title="等倍">1:1</button>
                <button type="button" id="btn-zoom-fit" class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-600 hover:text-white rounded-lg font-bold cursor-pointer" title="全体表示">🔲 全体</button>

                <span id="save-status-badge" class="px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg font-bold text-[11px]">
                    ✓ 保存済
                </span>
            </div>
        </div>

        <!-- 3ペイン・メインワークスペース -->
        <div class="flex flex-col lg:flex-row gap-2.5 items-stretch">

            <!-- 1. 左ペイン: 図形スタンプ & 台帳リスト -->
            <aside class="w-full lg:w-60 bg-white border border-slate-200 rounded-2xl p-2.5 shadow-2xs shrink-0 flex flex-col justify-between space-y-2.5 max-h-[78vh]">
                <div class="flex flex-col space-y-2 overflow-hidden flex-1">
                    <!-- タブ切替 -->
                    <div class="grid grid-cols-2 gap-1 bg-slate-100 p-1 rounded-xl shrink-0">
                        <button type="button" id="tab-btn-shapes"
                            class="py-1 rounded-lg text-xs font-bold bg-white text-indigo-700 shadow-2xs transition cursor-pointer">
                            🎨 図形パレット
                        </button>
                        <button type="button" id="tab-btn-items"
                            class="py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer">
                            📋 台帳 ({{ $layoutItems->count() }})
                        </button>
                    </div>

                    <!-- タブ1: CAD図形パレット -->
                    <div id="tab-panel-shapes" class="overflow-y-auto pr-0.5 space-y-2 flex-1">
                        <p class="text-[10px] text-slate-500 leading-snug">
                            図形を白地図へドラッグ＆ドロップで配置（A3 1/400 実寸ベクター）
                        </p>
                        <div class="grid grid-cols-2 gap-1.5">
                            @foreach($iconDefinitions as $def)
                                <div class="palette-item group p-2 bg-slate-50 hover:bg-indigo-50/70 border border-slate-200 hover:border-indigo-300 rounded-xl cursor-grab active:cursor-grabbing transition flex flex-col items-center text-center select-none"
                                    draggable="true"
                                    data-shape-type="{{ $def->icon_key }}"
                                    data-default-width="{{ $def->default_width }}"
                                    data-default-height="{{ $def->default_height }}"
                                    data-default-color="{{ $def->default_color }}"
                                    data-name="{{ $def->name }}">
                                    <div class="w-9 h-7 flex items-center justify-center pointer-events-none mb-1">
                                        @if($def->icon_key === 'water_supply')
                                            <svg viewBox="0 0 24 24" class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18"/>
                                                <rect x="6" y="6" width="12" height="12"/>
                                                <line x1="12" y1="6" x2="12" y2="18"/>
                                                <line x1="6" y1="12" x2="18" y2="12"/>
                                            </svg>
                                        @elseif($def->icon_key === 'kitchen_car')
                                            <svg viewBox="0 0 32 20" class="w-8 h-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="2" y="3" width="28" height="14"/>
                                                <line x1="8" y1="3" x2="8" y2="17"/>
                                            </svg>
                                        @elseif($def->icon_key === 'free_area')
                                            <svg viewBox="0 0 32 22" class="w-8 h-5 text-slate-700" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <rect x="2" y="2" width="28" height="18" stroke-dasharray="3 2"/>
                                                <line x1="2" y1="16" x2="16" y2="2" stroke-width="0.8"/>
                                                <line x1="8" y1="20" x2="26" y2="2" stroke-width="0.8"/>
                                                <line x1="18" y1="20" x2="30" y2="8" stroke-width="0.8"/>
                                            </svg>
                                        @elseif($def->icon_key === 'area_note')
                                            <svg viewBox="0 0 32 20" class="w-7 h-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <line x1="4" y1="6" x2="28" y2="6"/>
                                                <line x1="4" y1="12" x2="22" y2="12"/>
                                            </svg>
                                        @else
                                            <svg viewBox="0 0 28 24" class="w-7 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <rect x="2" y="2" width="24" height="20"/>
                                                <line x1="2" y1="2" x2="8" y2="8" stroke-width="1"/>
                                                <line x1="26" y1="2" x2="20" y2="8" stroke-width="1"/>
                                                <line x1="2" y1="22" x2="8" y2="16" stroke-width="1"/>
                                                <line x1="26" y1="22" x2="20" y2="16" stroke-width="1"/>
                                                <line x1="8" y1="12" x2="20" y2="12" stroke-width="1"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-700 group-hover:text-indigo-700 leading-tight pointer-events-none">
                                        {{ $def->name }}
                                    </span>
                                    <span class="text-[9px] text-slate-400 font-mono mt-0.5 pointer-events-none">
                                        {{ round($def->default_width / $siteBaseMap->scale_ratio, 1) }}m×{{ round($def->default_height / $siteBaseMap->scale_ratio, 1) }}m
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- タブ2: ブース・設備台帳リスト -->
                    <div id="tab-panel-items" class="hidden flex-col space-y-2 overflow-hidden flex-1">
                        <input type="text" id="sidebar-item-search"
                            class="w-full px-2.5 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:ring-1 focus:ring-indigo-500"
                            placeholder="コマ番号・店舗名で検索...">
                        <label class="flex items-center space-x-1.5 text-[11px] text-slate-600 font-bold cursor-pointer">
                            <input type="checkbox" id="sidebar-item-unplaced-only" class="rounded border-slate-300 text-indigo-600">
                            <span>未配置のみ表示</span>
                        </label>
                        <div id="sidebar-layout-items-list" class="overflow-y-auto space-y-1 pr-0.5 flex-1">
                            @foreach($layoutItems as $item)
                                <div class="sidebar-layout-item p-1.5 bg-slate-50 hover:bg-indigo-50 border border-slate-200 rounded-lg cursor-grab transition text-[11px]"
                                    draggable="true"
                                    data-layout-item-id="{{ $item->id }}"
                                    data-code="{{ $item->code }}"
                                    data-name="{{ $item->displayName }}"
                                    data-item-type="{{ $item->item_type?->value ?? 'booth' }}"
                                    data-vendor="{{ $item->vendor?->name ?? '' }}">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold font-mono text-slate-800">{{ $item->code }}</span>
                                        <span class="item-status-badge text-[9px] px-1.5 py-0.2 rounded font-bold bg-slate-200 text-slate-600">未配置</span>
                                    </div>
                                    <div class="text-[10px] text-slate-600 truncate">{{ $item->displayName }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- 見本配置へ復元ボタン -->
                <div class="pt-2 border-t border-slate-100 shrink-0">
                    <button type="button" id="btn-reset-preset"
                        class="w-full py-1.5 px-2 bg-slate-100 hover:bg-amber-50 hover:text-amber-800 hover:border-amber-300 text-slate-600 border border-slate-200 rounded-lg text-[11px] font-bold transition cursor-pointer">
                        🔄 標準見本配置 (260927) に復元
                    </button>
                </div>
            </aside>

            <!-- 2. 中央ペイン: 白地図SVGベクターキャンバス -->
            <main class="flex-1 min-w-0 relative bg-slate-200/80 border border-slate-300 rounded-2xl shadow-inner overflow-hidden h-[78vh] min-h-[600px]">
                <div id="site-map-canvas-container" class="relative w-full h-full overflow-hidden select-none cursor-default">
                    <!-- ローディング -->
                    <div id="canvas-loading" class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/90 backdrop-blur-xs transition-opacity duration-300">
                        <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                        <p class="mt-3 text-xs font-bold text-slate-600">A3 1/400 ベクター白地図を読み込み中...</p>
                    </div>

                    <!-- ベクター直接スケーリング・ビューポート (will-change-transform によるGPUビットマップボケを防止) -->
                    <div id="site-map-viewport" class="absolute top-0 left-0 origin-top-left">
                        <div id="site-map-svg-container" class="relative inline-block shadow-xl bg-white antialiased"></div>
                    </div>

                    <!-- 範囲選択ラバーバンド矩形 -->
                    <div id="marquee-selection-box" class="hidden absolute z-30 border-2 border-indigo-600 bg-indigo-500/15 pointer-events-none"></div>

                    <!-- インライン文字入力オーバーレイ (ダブルクリック時) -->
                    <textarea id="inline-text-editor" rows="2"
                        class="hidden absolute z-40 px-1.5 py-0.5 text-xs font-bold text-center bg-white border-2 border-indigo-600 rounded shadow-lg focus:outline-none resize-none"></textarea>
                </div>

                <!-- ホバーツールチップ -->
                <div id="node-hover-tooltip" class="hidden absolute z-30 px-2.5 py-1.5 bg-slate-900/90 text-white rounded-lg text-[11px] shadow-lg pointer-events-none max-w-xs"></div>

                <!-- トースト通知 -->
                <div id="editor-toast" class="hidden absolute bottom-10 left-1/2 -translate-x-1/2 z-40 px-4 py-2 bg-slate-900/90 text-white rounded-xl text-xs font-bold shadow-xl flex items-center space-x-3">
                    <span id="editor-toast-message"></span>
                    <button type="button" id="editor-toast-undo" class="hidden text-indigo-300 hover:text-indigo-200 underline cursor-pointer">元に戻す</button>
                </div>

                <!-- ヘルプチップ -->
                <div class="absolute bottom-2.5 right-3 z-10 px-2.5 py-1 bg-slate-900/75 text-white rounded-lg text-[10px] font-medium pointer-events-none flex items-center space-x-2">
                    <span>ダブルクリック: 文字入力</span>
                    <span class="text-slate-400">|</span>
                    <span>Shift+選択/範囲: 複数選択</span>
                    <span class="text-slate-400">|</span>
                    <span>Ctrl+D: 連番複製</span>
                    <span class="text-slate-400">|</span>
                    <span>Space+ドラッグ: 画面移動</span>
                    <span class="text-slate-400">|</span>
                    <span>矢印キー: 微調整</span>
                </div>
            </main>

            <!-- 3. 右ペイン: 常時表示プロパティ・インスペクタ -->
            <aside class="w-full lg:w-64 bg-white border border-slate-200 rounded-2xl p-3 shadow-2xs shrink-0 flex flex-col space-y-3 overflow-y-auto max-h-[78vh]">
                <div class="pb-2 border-b border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800">⚙️ プロパティ・インスペクタ</span>
                    <span id="inspector-selection-badge" class="text-[10px] px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded font-bold">未選択</span>
                </div>

                <!-- A. 未選択時: 全体サマリー & 衛生20mチェック -->
                <div id="inspector-empty-state" class="space-y-3 text-xs">
                    <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                        <div class="font-bold text-slate-700">📊 図面配置サマリー</div>
                        <div class="flex justify-between text-slate-600"><span>総配置オブジェクト:</span><span id="summary-total-count" class="font-bold font-mono text-slate-900">0</span></div>
                        <div class="flex justify-between text-red-600"><span>🔴 赤枠 (調理・販売):</span><span id="summary-red-count" class="font-bold font-mono">0</span></div>
                        <div class="flex justify-between text-emerald-600"><span>🟢 緑枠 (販売のみ):</span><span id="summary-green-count" class="font-bold font-mono">0</span></div>
                        <div class="flex justify-between text-blue-600"><span>💧 手洗い・洗浄設備:</span><span id="summary-water-count" class="font-bold font-mono">0</span></div>
                    </div>

                    <div id="hygiene-check-box" class="p-2.5 bg-sky-50 border border-sky-200 rounded-xl space-y-1">
                        <div class="font-bold text-sky-900">🩺 食品衛生 20m圏チェック</div>
                        <p id="hygiene-check-status" class="text-[11px] text-sky-800 leading-relaxed">検証中...</p>
                        <ul id="hygiene-warning-list" class="text-[10px] text-red-700 font-mono space-y-0.5 max-h-32 overflow-y-auto"></ul>
                    </div>

                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        白地図上のアイコンをクリックすると、文字・色・角度・サイズ・台帳連動をここから即時編集できます。
                    </p>
                </div>

                <!-- B. 単一選択時: インスペクタフォーム -->
                <div id="inspector-single-form" class="hidden space-y-3 text-xs">
                    <div>
                        <div class="text-[10px] font-bold text-slate-400">シンボル種別</div>
                        <div id="insp-shape-name" class="font-bold text-slate-800">--</div>
                    </div>

                    <!-- 表示テキスト -->
                    <div>
                        <label for="insp-display-text" class="block text-[11px] font-bold text-slate-700 mb-1">表示テキスト（改行可）</label>
                        <textarea id="insp-display-text" rows="2"
                            class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:bg-white"></textarea>
                    </div>

                    <!-- カラーテーマ -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] font-bold text-slate-700">カラーテーマ</span>
                            <span id="insp-color-note" class="text-[10px] text-slate-400"></span>
                        </div>
                        <div id="insp-color-buttons" class="grid grid-cols-6 gap-1.5">
                            <button type="button" data-color="red" class="insp-color-btn h-6 rounded-md bg-red-600 border-2 border-transparent cursor-pointer" title="赤"></button>
                            <button type="button" data-color="green" class="insp-color-btn h-6 rounded-md bg-emerald-600 border-2 border-transparent cursor-pointer" title="緑"></button>
                            <button type="button" data-color="black" class="insp-color-btn h-6 rounded-md bg-slate-900 border-2 border-transparent cursor-pointer" title="黒"></button>
                            <button type="button" data-color="cyan" class="insp-color-btn h-6 rounded-md bg-cyan-500 border-2 border-transparent cursor-pointer" title="水色"></button>
                            <button type="button" data-color="orange" class="insp-color-btn h-6 rounded-md bg-orange-500 border-2 border-transparent cursor-pointer" title="橙"></button>
                            <button type="button" data-color="blue" class="insp-color-btn h-6 rounded-md bg-blue-600 border-2 border-transparent cursor-pointer" title="青"></button>
                        </div>
                    </div>

                    <!-- 角度 & 通りロック -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="insp-rotation" class="block text-[11px] font-bold text-slate-700 mb-1">回転角度</label>
                            <select id="insp-rotation" class="w-full px-2 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg">
                                <option value="0">0°</option>
                                <option value="45">45°</option>
                                <option value="90">90°</option>
                                <option value="135">135°</option>
                                <option value="180">180°</option>
                                <option value="225">225°</option>
                                <option value="270">270°</option>
                                <option value="315">315°</option>
                            </select>
                        </div>
                        <div>
                            <label for="insp-font-size" class="block text-[11px] font-bold text-slate-700 mb-1">文字pt(空=自動)</label>
                            <input type="number" id="insp-font-size" step="0.5" min="3" max="24"
                                class="w-full px-2 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg" placeholder="自動">
                        </div>
                    </div>

                    <label class="flex items-center space-x-1.5 text-[11px] font-bold text-slate-600 cursor-pointer">
                        <input type="checkbox" id="insp-rotation-locked" class="rounded border-slate-300 text-indigo-600">
                        <span>通り沿い平行ロック (回転不可)</span>
                    </label>

                    <!-- 寸法 (pt / m) -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="insp-width-m" class="block text-[11px] font-bold text-slate-700 mb-1">幅 (m)</label>
                            <input type="number" id="insp-width-m" step="0.1" min="0.5"
                                class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg">
                        </div>
                        <div>
                            <label for="insp-height-m" class="block text-[11px] font-bold text-slate-700 mb-1">奥行 (m)</label>
                            <input type="number" id="insp-height-m" step="0.1" min="0.5"
                                class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg">
                        </div>
                    </div>

                    <!-- 給水20m円設定 (water_supply時のみ表示) -->
                    <div id="insp-water-options" class="hidden p-2 bg-sky-50 border border-sky-200 rounded-lg space-y-1.5">
                        <label class="flex items-center space-x-1.5 text-[11px] font-bold text-sky-900 cursor-pointer">
                            <input type="checkbox" id="insp-show-hygiene-circle" class="rounded border-sky-300 text-sky-600">
                            <span>20m衛生サークルを描画</span>
                        </label>
                        <div>
                            <label for="insp-arc-mode" class="block text-[10px] font-bold text-sky-800 mb-0.5">円弧向き</label>
                            <select id="insp-arc-mode" class="w-full px-2 py-1 text-[11px] bg-white border border-sky-300 rounded">
                                <option value="0,360">全円 (360°)</option>
                                <option value="90,270">左半円 (通り西側向け)</option>
                                <option value="-90,90">右半円 (通り東側向け)</option>
                            </select>
                        </div>
                    </div>

                    <!-- 台帳紐づけ -->
                    <div>
                        <label for="insp-layout-item-id" class="block text-[11px] font-bold text-slate-700 mb-1">台帳データ連動</label>
                        <select id="insp-layout-item-id" class="w-full px-2 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg">
                            <option value="">（未紐づけ）</option>
                            @foreach($layoutItems as $item)
                                <option value="{{ $item->id }}"
                                    data-code="{{ $item->code }}"
                                    data-name="{{ $item->displayName }}"
                                    data-vendor="{{ $item->vendor?->name ?? '' }}">
                                    {{ $item->code }}: {{ $item->displayName }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 備考 -->
                    <div>
                        <label for="insp-notes" class="block text-[11px] font-bold text-slate-700 mb-1">メモ・特記事項</label>
                        <textarea id="insp-notes" rows="2" class="w-full px-2 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg" placeholder="申し送り事項等"></textarea>
                    </div>
                </div>

                <!-- C. 複数選択時: 一括操作パネル -->
                <div id="inspector-multi-panel" class="hidden space-y-3 text-xs">
                    <p id="insp-multi-count" class="font-bold text-indigo-700 bg-indigo-50 p-2 rounded-lg border border-indigo-100">
                        0件を選択中
                    </p>
                    <div>
                        <span class="block text-[11px] font-bold text-slate-700 mb-1">一括カラー変更</span>
                        <div class="grid grid-cols-5 gap-1.5">
                            <button type="button" data-bulk-color="red" class="bulk-color-btn h-6 rounded-md bg-red-600 cursor-pointer" title="すべて赤に"></button>
                            <button type="button" data-bulk-color="green" class="bulk-color-btn h-6 rounded-md bg-emerald-600 cursor-pointer" title="すべて緑に"></button>
                            <button type="button" data-bulk-color="black" class="bulk-color-btn h-6 rounded-md bg-slate-900 cursor-pointer" title="すべて黒に"></button>
                            <button type="button" data-bulk-color="cyan" class="bulk-color-btn h-6 rounded-md bg-cyan-500 cursor-pointer" title="すべて水色に"></button>
                            <button type="button" data-bulk-color="orange" class="bulk-color-btn h-6 rounded-md bg-orange-500 cursor-pointer" title="すべて橙に"></button>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500">
                        上部ツールバーの「左揃」「上揃」「縦密着」「横密着」ボタンで一括整列できます。
                    </p>
                </div>
            </aside>
        </div>
    </div>
</div>

{{-- 初期配置データの安全な埋め込み (JSON) --}}
<div id="initial-placements-data" class="hidden" data-json="{{ json_encode($placements) }}"></div>
<div id="icon-definitions-data" class="hidden" data-json="{{ json_encode($iconDefinitions) }}"></div>
<div id="layout-items-data" class="hidden" data-json="{{ json_encode($layoutItems) }}"></div>

{{-- 厳格な CSP 対応: 専用スクリプトをスロット末尾で @vite 経由読み込み --}}
@vite(['resources/js/site-layout-editor.js'])
</x-layouts.app>
