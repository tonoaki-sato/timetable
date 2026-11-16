<x-layouts.app :title="$timetable->name . ' 白地図配置エディタ'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ページヘッダー -->
    <x-page-header
        :title="$timetable->name . ' 白地図配置エディタ'"
        icon="🗺️"
        :description="'商店街白地図（A3 1/400）上にブース・設備を正確に配置・編成します'">
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
        class="flex flex-col lg:flex-row gap-3.5 items-start"
        data-base-map-id="{{ $siteBaseMap->id }}"
        data-svg-url="{{ asset($siteBaseMap->svg_path) }}"
        data-scale-ratio="{{ $siteBaseMap->scale_ratio }}"
        data-paper-size="{{ $siteBaseMap->paper_size }}"
        data-cad-version="{{ $siteBaseMap->cad_version }}"
        data-viewbox="{{ $siteBaseMap->viewbox }}"
        data-store-url="{{ route('timetables.site-maps.placements.store', $timetable) }}"
        data-placements-url="{{ route('timetables.site-maps.placements.index', $timetable) }}"
        data-csrf-token="{{ csrf_token() }}">

        <!-- 左側: アイコンパレットサイドバー -->
        <aside class="w-full lg:w-64 bg-white border border-slate-200 rounded-2xl p-3 shadow-2xs shrink-0 flex flex-col space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                    <span>🎨</span>
                    <span>配置パレット</span>
                </span>
                <span class="text-[10px] bg-indigo-50 text-indigo-700 font-bold px-1.5 py-0.5 rounded">
                    DnD配置
                </span>
            </div>

            <p class="text-[11px] text-slate-500 leading-relaxed">
                アイコンを白地図へドラッグ＆ドロップして配置します。
            </p>

            <!-- 1. 出店ブース・エリア -->
            <div class="space-y-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                    🎪 出店ブース・エリア
                </span>
                <div class="grid grid-cols-2 gap-1.5">
                    @foreach($iconDefinitions->whereIn('category', ['tent', 'stall', 'free_area']) as $def)
                        <div class="palette-item group p-2 bg-slate-50 hover:bg-indigo-50/70 border border-slate-200 hover:border-indigo-300 rounded-xl cursor-grab active:cursor-grabbing transition flex flex-col items-center text-center select-none"
                            draggable="true"
                            data-shape-type="{{ $def->icon_key }}"
                            data-default-width="{{ $def->default_width }}"
                            data-default-height="{{ $def->default_height }}"
                            data-name="{{ $def->name }}"
                            title="{{ $def->name }} ({{ round($def->default_width / $siteBaseMap->scale_ratio, 1) }}m × {{ round($def->default_height / $siteBaseMap->scale_ratio, 1) }}m)">
                            <div class="w-10 h-10 flex items-center justify-center pointer-events-none mb-1">
                                <img src="{{ asset($def->icon_asset_path) }}" alt="{{ $def->name }}" class="max-w-full max-h-full object-contain drop-shadow-xs">
                            </div>
                            <span class="text-[11px] font-bold text-slate-700 group-hover:text-indigo-700 truncate w-full pointer-events-none">
                                {{ $def->name }}
                            </span>
                            <span class="text-[9px] text-slate-400 font-mono pointer-events-none">
                                {{ round($def->default_width / $siteBaseMap->scale_ratio, 1) }}m×{{ round($def->default_height / $siteBaseMap->scale_ratio, 1) }}m
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 2. インフラ設備 -->
            <div class="space-y-1.5 pt-1 border-t border-slate-100">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                    🚻 インフラ設備
                </span>
                <div class="grid grid-cols-2 gap-1.5">
                    @foreach($iconDefinitions->where('category', 'facility') as $def)
                        <div class="palette-item group p-2 bg-slate-50 hover:bg-indigo-50/70 border border-slate-200 hover:border-indigo-300 rounded-xl cursor-grab active:cursor-grabbing transition flex flex-col items-center text-center select-none"
                            draggable="true"
                            data-shape-type="{{ $def->icon_key }}"
                            data-default-width="{{ $def->default_width }}"
                            data-default-height="{{ $def->default_height }}"
                            data-name="{{ $def->name }}"
                            title="{{ $def->name }} ({{ round($def->default_width / $siteBaseMap->scale_ratio, 1) }}m × {{ round($def->default_height / $siteBaseMap->scale_ratio, 1) }}m)">
                            <div class="w-10 h-10 flex items-center justify-center pointer-events-none mb-1">
                                <img src="{{ asset($def->icon_asset_path) }}" alt="{{ $def->name }}" class="max-w-full max-h-full object-contain drop-shadow-xs">
                            </div>
                            <span class="text-[11px] font-bold text-slate-700 group-hover:text-indigo-700 truncate w-full pointer-events-none">
                                {{ $def->name }}
                            </span>
                            <span class="text-[9px] text-slate-400 font-mono pointer-events-none">
                                {{ round($def->default_width / $siteBaseMap->scale_ratio, 1) }}m×{{ round($def->default_height / $siteBaseMap->scale_ratio, 1) }}m
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>

        <!-- 右側: メイン白地図キャンバス & ツールバー -->
        <main class="flex-1 min-w-0 w-full flex flex-col space-y-2.5">
            <!-- ツールバー & ステータスバー -->
            <div class="bg-white border border-slate-200 rounded-xl p-2.5 shadow-2xs flex flex-wrap items-center justify-between gap-3">
                <!-- 左側: 図面メタ情報バッジ -->
                <div class="flex items-center space-x-2 text-xs">
                    <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg font-bold border border-slate-200 flex items-center space-x-1">
                        <span>📐</span>
                        <span>{{ $siteBaseMap->name }}</span>
                    </span>
                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md text-[11px] font-bold border border-indigo-100">
                        縮尺 1/400 (1m = {{ $siteBaseMap->scale_ratio }}px)
                    </span>
                    <span id="placement-count-badge" class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-md text-[11px] font-bold border border-emerald-100">
                        配置数: {{ $placements->count() }}件
                    </span>
                </div>

                <!-- 右側: ズーム & ビュー操作 & 編集操作ツールバー -->
                <div class="flex items-center space-x-1.5 text-xs">
                    <!-- 選択中アイテム削除ボタン -->
                    <button type="button" id="btn-delete-selected"
                        disabled
                        class="px-2.5 py-1 bg-red-50 hover:bg-red-600 hover:text-white text-red-700 border border-red-200 hover:border-red-600 rounded-lg font-bold shadow-2xs transition flex items-center space-x-1 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-red-50 disabled:hover:text-red-700 disabled:hover:border-red-200 cursor-pointer"
                        title="選択した配置アイテムを削除 (Deleteキー)">
                        <span>🗑️</span>
                        <span class="hidden sm:inline">削除</span>
                    </button>

                    <div class="h-4 w-px bg-slate-300 mx-1"></div>

                    <!-- ズームアウト -->
                    <button type="button" id="btn-zoom-out"
                        class="p-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg shadow-2xs transition flex items-center justify-center cursor-pointer"
                        title="縮小 (ズームアウト)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                    </button>

                    <!-- ズーム比率表示 -->
                    <span id="zoom-level-text" class="px-2.5 py-1 font-mono text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg select-none min-w-[56px] text-center">
                        100%
                    </span>

                    <!-- ズームイン -->
                    <button type="button" id="btn-zoom-in"
                        class="p-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg shadow-2xs transition flex items-center justify-center cursor-pointer"
                        title="拡大 (ズームイン)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>

                    <div class="h-4 w-px bg-slate-300 mx-1"></div>

                    <!-- 100%等倍 -->
                    <button type="button" id="btn-zoom-100"
                        class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold shadow-2xs transition cursor-pointer"
                        title="等倍 (100%)">
                        1:1
                    </button>

                    <!-- 全体表示 (Fit) -->
                    <button type="button" id="btn-zoom-fit"
                        class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 text-slate-700 rounded-lg font-bold shadow-2xs transition flex items-center space-x-1 cursor-pointer"
                        title="図面全体を表示">
                        <span>🔲</span>
                        <span>全体表示</span>
                    </button>
                </div>
            </div>

            <!-- 白地図キャンバスビューポート -->
            <div id="site-map-canvas-container"
                class="relative w-full h-[76vh] min-h-[580px] bg-slate-100/80 border border-slate-300 rounded-2xl shadow-inner overflow-hidden select-none cursor-grab active:cursor-grabbing">

                <!-- ローディングインジケーター -->
                <div id="canvas-loading" class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/90 backdrop-blur-xs transition-opacity duration-300">
                    <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                    <p class="mt-3 text-xs font-bold text-slate-600">CAD白地図データを読み込み中...</p>
                    <p class="text-[11px] text-slate-400 mt-1">ベクター図面（1,302パス）を展開しています</p>
                </div>

                <!-- 移動・拡大するトランスフォームレイヤー -->
                <div id="site-map-viewport" class="absolute top-0 left-0 origin-top-left will-change-transform">
                    <!-- SVG図面保持コンテナ -->
                    <div id="site-map-svg-container" class="relative inline-block shadow-lg bg-white rounded-sm">
                        <!-- SVGがここに非同期インジェクションされます -->
                    </div>
                </div>

                <!-- 操作ヘルプチップ（画面右下） -->
                <div class="absolute bottom-3 right-3 z-10 px-3 py-1.5 bg-slate-900/75 backdrop-blur-xs text-white rounded-lg text-[11px] font-medium shadow-md flex items-center space-x-2 pointer-events-none">
                    <span>🎯 パレットからDnD配置</span>
                    <span class="text-slate-400">|</span>
                    <span>👆 クリックで選択・移動</span>
                    <span class="text-slate-400">|</span>
                    <span>⚙️ ダブルクリックで設定</span>
                    <span class="text-slate-400">|</span>
                    <span>🗑️ Deleteで削除</span>
                    <span class="text-slate-400">|</span>
                    <span>ホイールでズーム</span>
                </div>
            </div>
        </main>
    </div>
</div>

{{-- オブジェクト設定モーダル (Phase 4) --}}
@include('site_layouts.partials.placement_modal', ['layoutItems' => $layoutItems])

{{-- 初期配置データの安全な埋め込み (JSON) --}}
<div id="initial-placements-data" class="hidden" data-json="{{ json_encode($placements) }}"></div>
<div id="icon-definitions-data" class="hidden" data-json="{{ json_encode($iconDefinitions) }}"></div>
<div id="layout-items-data" class="hidden" data-json="{{ json_encode($layoutItems) }}"></div>

{{-- 厳格な CSP 対応: 専用スクリプトをスロット末尾で @vite 経由読み込み --}}
@vite(['resources/js/site-layout-editor.js'])
</x-layouts.app>
