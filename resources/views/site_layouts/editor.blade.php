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
            <x-ui.button variant="header-action" id="btn-export-pdf" :href="route('timetables.site-maps.pdf', $timetable)">
                <span>📥</span>
                <span>PDFダウンロード (A3 1/400)</span>
            </x-ui.button>
            <x-ui.button variant="header-action" :href="route('timetables.cone-maps.editor', $timetable)">
                <span>🔺</span>
                <span>カラーコーン配置</span>
            </x-ui.button>
            <x-ui.button variant="header-action" :href="route('timetables.layouts.booths', $timetable)">
                <span>📋</span>
                <span>配置一覧</span>
            </x-ui.button>
            <x-ui.button variant="header-action" :href="route('timetables.layouts.index', $timetable)">
                <span>📊</span>
                <span>配置計画サマリー</span>
            </x-ui.button>
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
        data-clear-url="{{ route('timetables.site-maps.clear-placements', $timetable) }}"
        data-base-map-update-url="{{ route('timetables.site-maps.base-map.update', $timetable) }}"
        data-icon-store-url="{{ route('timetables.site-maps.icons.store', $timetable) }}"
        data-icon-base-url="{{ url('timetables/' . $timetable->id . '/site-maps/icons') }}"
        data-csrf-token="{{ csrf_token() }}">

        @include('site_layouts.partials.editor-toolbar', [
            'siteBaseMap' => $siteBaseMap,
            'availableBaseMaps' => $availableBaseMaps,
        ])

        <!-- 3ペイン・メインワークスペース -->
        <div class="flex flex-col lg:flex-row gap-2.5 items-stretch">

            @include('site_layouts.partials.editor-left-pane', [
                'layoutItems' => $layoutItems,
                'iconDefinitions' => $iconDefinitions,
                'siteBaseMap' => $siteBaseMap,
            ])

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
                    <span>右クリック: 選択/手のひら切替</span>
                    <span class="text-slate-400">|</span>
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

            @include('site_layouts.partials.editor-inspector-pane', [
                'layoutItems' => $layoutItems,
            ])
        </div>
    </div>
</div>

@include('site_layouts.partials.editor-icon-modals')

{{-- 初期配置データの安全な埋め込み (JSON) --}}
<div id="initial-placements-data" class="hidden" data-json="{{ json_encode($placements) }}"></div>
<div id="icon-definitions-data" class="hidden" data-json="{{ json_encode($iconDefinitions) }}"></div>
<div id="layout-items-data" class="hidden" data-json="{{ json_encode($layoutItems) }}"></div>

{{-- 厳格な CSP 対応: 専用スクリプトをスロット末尾で @vite 経由読み込み --}}
@vite(['resources/js/site-layout-editor.js'])
</x-layouts.app>
