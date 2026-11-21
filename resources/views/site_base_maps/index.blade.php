<x-layouts.app title="白地図マスター">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ページヘッダー -->
    <x-page-header
        title="白地図マスター"
        icon="🗺️"
        description="商店街平面図などのCAD白地図データ・縮尺・図面SVGファイルを全運行日で共有するマスターとして一元管理します。">
        <x-slot:breadcrumbs>
            <span class="text-slate-500">🗂️ マスター管理</span>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">🗺️ 白地図マスター</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('create', App\Models\SiteBaseMap::class)
                <x-ui.button
                    variant="header-action"
                    id="btn-open-create-site-base-map"
                    data-store-url="{{ route('site-base-maps.store') }}">
                    <span>➕</span>
                    <span>新規白地図を追加</span>
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-ui.validation-errors title="入力内容を確認してください" />

    <!-- 白地図マスター一覧テーブルカード -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                    <span>🗺️</span>
                    <span>登録白地図一覧</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono font-normal">
                        全 {{ $baseMaps->count() }} 件
                    </span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    白地図名をクリックすると図面プレビューと詳細情報を確認できます。図面を改訂する際は既存データの座標ズレを防ぐため新規追加を推奨します。
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">白地図名称</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">CAD版数</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">用紙・縮尺</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">実寸目安 / viewBox</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">利用状況</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap text-right">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($baseMaps as $map)
                        @php
                            $payloadJson = json_encode($map->toModalPayload(), JSON_UNESCAPED_UNICODE);
                            $inUse = $map->timetables_count > 0 || $map->placements_count > 0;
                        @endphp
                        <tr data-base-map-id="{{ $map->id }}" class="hover:bg-slate-50/80 transition cursor-default">
                            <!-- 白地図名称（閲覧専用詳細モーダル起動） -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="space-y-0.5">
                                    <div class="flex items-center space-x-1.5">
                                        <button type="button"
                                            class="btn-view-site-base-map font-bold text-slate-800 hover:text-indigo-600 hover:underline transition cursor-pointer text-left"
                                            data-base-map="{{ $payloadJson }}"
                                            title="クリックして図面プレビュー・詳細を表示">
                                            {{ $map->name }}
                                        </button>
                                        @if($map->is_default)
                                            <span class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold">
                                                デフォルト
                                            </span>
                                        @endif
                                        @if(!$map->is_active)
                                            <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 border border-slate-200 text-[10px] font-bold">
                                                無効
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] font-mono text-slate-400">
                                        {{ $map->svg_path }}
                                    </div>
                                </div>
                            </td>

                            <!-- CAD版数 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200 font-mono text-[11px] font-bold">
                                    {{ $map->cad_version }}
                                </span>
                            </td>

                            <!-- 用紙・縮尺 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ $map->paper_size }}</div>
                                <div class="text-[11px] font-mono text-slate-500">{{ number_format((float) $map->scale_ratio, 4) }} px/m</div>
                            </td>

                            <!-- 実寸目安 / viewBox -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-mono font-bold text-slate-800">
                                    {{ number_format((float) $map->width_meters, 2) }}m × {{ number_format((float) $map->height_meters, 2) }}m
                                </div>
                                <div class="text-[11px] font-mono text-slate-400">
                                    {{ $map->viewbox }}
                                </div>
                            </td>

                            <!-- 利用状況 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border bg-slate-50 text-slate-700 border-slate-200 font-mono">
                                        📅 {{ $map->timetables_count }}日程
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border bg-slate-50 text-slate-700 border-slate-200 font-mono">
                                        📌 {{ $map->placements_count }}配置
                                    </span>
                                </div>
                            </td>

                            <!-- 操作 -->
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    @can('update', $map)
                                        <button type="button"
                                            class="btn-edit-site-base-map inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                            data-base-map="{{ $payloadJson }}">
                                            <span>✏️</span>
                                            <span>編集</span>
                                        </button>
                                    @endcan
                                    @can('delete', $map)
                                        @if($map->is_default)
                                            <span class="px-2 py-1 text-[11px] text-slate-400 cursor-not-allowed" title="デフォルト白地図は削除できません">
                                                🔒 デフォルト
                                            </span>
                                        @elseif($inUse)
                                            <span class="px-2 py-1 text-[11px] text-slate-400 cursor-not-allowed" title="運行日程または配置データで使用中のため削除できません">
                                                🔒 使用中
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('site-base-maps.destroy', $map) }}" class="inline"
                                                data-confirm="白地図「{{ $map->name }}」を削除しますか？">
                                                @csrf
                                                @method('DELETE')
                                                <x-ui.button variant="delete" size="xs" type="submit">
                                                    <span>🗑️</span>
                                                    <span>削除</span>
                                                </x-ui.button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-ui.empty-state icon="🗺️" title="白地図マスターが登録されていません。" description="右上の「新規白地図を追加」から登録してください。" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- 1. 閲覧専用詳細モーダル -->
<div id="modal-site-base-map-detail" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="site-base-map-detail-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity modal-backdrop"></div>
    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-4rem)] w-full sm:max-w-2xl rounded-2xl bg-white text-left shadow-xl border border-slate-200 overflow-hidden my-auto">
            <!-- ヘッダー（固定） -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 shrink-0">
                <h3 id="site-base-map-detail-title" class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                    <span>🗺️</span>
                    <span>白地図詳細</span>
                </h3>
                <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    <span class="sr-only">閉じる</span>
                    <span class="text-lg leading-none">✕</span>
                </button>
            </div>

            <!-- 本文（スクロール可能） -->
            <div class="p-4 sm:p-6 space-y-4 text-xs flex-1 min-h-0 overflow-y-auto">
                <!-- SVGプレビュー -->
                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center justify-center">
                    <img id="detail-base-map-svg-preview" src="" alt="白地図SVGプレビュー" class="max-w-full max-h-64 object-contain bg-white border border-slate-200 rounded-lg p-1">
                    <span id="detail-base-map-svg-empty" class="hidden text-slate-400 py-8">図面プレビューを読み込めませんでした</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 bg-slate-50/80 border border-slate-200/80 rounded-xl p-3.5">
                    <div class="sm:col-span-2">
                        <span class="text-slate-400 block text-[11px]">白地図名称</span>
                        <div class="flex items-center space-x-1.5 mt-0.5">
                            <span id="detail-base-map-name" class="font-bold text-slate-900 text-sm">—</span>
                            <span id="detail-base-map-default-badge" class="hidden px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold">デフォルト</span>
                            <span id="detail-base-map-inactive-badge" class="hidden px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 text-[10px] font-bold">無効</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">CAD版数</span>
                        <span id="detail-base-map-cad-version" class="font-mono font-bold text-slate-800 block mt-0.5">—</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">用紙サイズ / 縮尺</span>
                        <span id="detail-base-map-paper-scale" class="font-mono font-bold text-slate-800 block mt-0.5">—</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">実空間サイズ目安</span>
                        <span id="detail-base-map-dimensions" class="font-mono font-bold text-slate-800 block mt-0.5">—</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">SVG viewBox</span>
                        <span id="detail-base-map-viewbox" class="font-mono font-bold text-slate-800 block mt-0.5">—</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-slate-400 block text-[11px]">SVGファイルパス</span>
                        <span id="detail-base-map-svg-path" class="font-mono text-slate-700 block mt-0.5 break-all">—</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">PDF原本</span>
                        <div class="mt-0.5">
                            <a id="detail-base-map-pdf-link" href="" target="_blank" rel="noopener"
                                class="hidden inline-flex items-center space-x-1 text-indigo-600 hover:underline font-bold">
                                <span>📄 PDF原本を開く</span>
                                <span>↗</span>
                            </a>
                            <span id="detail-base-map-pdf-none" class="text-slate-400">未登録</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">利用状況</span>
                        <span id="detail-base-map-usage" class="font-mono font-bold text-slate-800 block mt-0.5">—</span>
                    </div>
                </div>

                <div>
                    <span class="text-slate-500 block text-[11px] mb-1 font-bold">特記事項・CADメモ</span>
                    <div id="detail-base-map-notes" class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl text-slate-700 leading-relaxed whitespace-pre-wrap max-h-32 overflow-y-auto">—</div>
                </div>
            </div>

            <!-- フッター（固定・閲覧専用のため閉じるボタンのみ） -->
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex justify-end shrink-0">
                <x-ui.button variant="secondary" size="sm" type="button" class="btn-close-modal">
                    閉じる
                </x-ui.button>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()?->can('create', App\Models\SiteBaseMap::class) || auth()->user()?->can('update', App\Models\SiteBaseMap::class))
<!-- 2. 入力フォームモーダル（新規登録・編集兼用） -->
<div id="modal-site-base-map-form"
    class="hidden fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="site-base-map-form-modal-title"
    role="dialog"
    aria-modal="true"
    data-reopen-on-error="{{ $errors->any() ? '1' : '0' }}"
    data-old-action="{{ old('_form_action', route('site-base-maps.store')) }}"
    data-old-method="{{ old('_form_method', 'POST') }}">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity modal-backdrop"></div>
    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-4rem)] w-full sm:max-w-2xl rounded-2xl bg-white text-left shadow-xl border border-slate-200 overflow-hidden my-auto">
            <form id="form-site-base-map-mutation" method="POST" action="{{ old('_form_action', route('site-base-maps.store')) }}" enctype="multipart/form-data" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf
                <input type="hidden" id="base-map-form-method" name="_method" value="{{ old('_form_method', 'POST') }}">
                <input type="hidden" id="base-map-form-action-mirror" name="_form_action" value="{{ old('_form_action', route('site-base-maps.store')) }}">
                <input type="hidden" id="base-map-form-method-mirror" name="_form_method" value="{{ old('_form_method', 'POST') }}">

                <!-- モーダルヘッダー（固定） -->
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 shrink-0">
                    <h3 id="site-base-map-form-modal-title" class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                        <span>🗺️</span>
                        <span>新規白地図の登録</span>
                    </h3>
                    <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                        <span class="sr-only">閉じる</span>
                        <span class="text-lg leading-none">✕</span>
                    </button>
                </div>

                <!-- モーダル本文（スクロール可能） -->
                <div class="p-4 sm:p-6 space-y-4 text-xs flex-1 min-h-0 overflow-y-auto">
                    <!-- 配置済み編集時の座標ズレ注意バナー -->
                    <div id="edit-placement-warning" class="hidden p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 space-y-1">
                        <div class="font-bold flex items-center space-x-1.5">
                            <span>⚠️</span>
                            <span>既存配置データの座標ズレにご注意ください</span>
                        </div>
                        <p class="text-[11px] leading-relaxed">
                            この白地図には既に <strong id="edit-placement-warning-count">0</strong> 件の配置オブジェクトが紐づいています。縮尺比率・viewBox・SVG図面を変更すると既存配置の位置がズレる場合があります。図面を改訂する場合は「新規白地図を追加」から別レコードとして登録することを推奨します。
                        </p>
                    </div>

                    <!-- 行1: 白地図名称 & CAD版数 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                        <div class="md:col-span-2">
                            <label for="base_map_name" class="block font-bold text-slate-700 mb-1">
                                白地図名称 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="base_map_name" name="name" value="{{ old('name') }}" required maxlength="255"
                                placeholder="例: 商店街平面図 270801版 A3 1/400"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="base_map_cad_version" class="block font-bold text-slate-700 mb-1">
                                CAD版数 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="base_map_cad_version" name="cad_version" value="{{ old('cad_version', '260813') }}" required maxlength="50"
                                placeholder="例: 260813"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- 行2: 用紙サイズ / 縮尺比率 / 実寸横幅 / 実寸縦幅 -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <label for="base_map_paper_size" class="block font-bold text-slate-700 mb-1">
                                用紙サイズ <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="base_map_paper_size" name="paper_size" value="{{ old('paper_size', 'A3') }}" required maxlength="20"
                                placeholder="A3"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="base_map_scale_ratio" class="block font-bold text-slate-700 mb-1">
                                縮尺 (px/m) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="base_map_scale_ratio" name="scale_ratio" value="{{ old('scale_ratio', '7.0866') }}" step="0.0001" min="0.1" max="500" required
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="base_map_width_meters" class="block font-bold text-slate-700 mb-1">
                                横幅目安 (m) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="base_map_width_meters" name="width_meters" value="{{ old('width_meters', '118.80') }}" step="0.01" min="1" max="10000" required
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="base_map_height_meters" class="block font-bold text-slate-700 mb-1">
                                縦幅目安 (m) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="base_map_height_meters" name="height_meters" value="{{ old('height_meters', '168.00') }}" step="0.01" min="1" max="10000" required
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- 行3: SVG白地図ファイルアップロード & 既存SVGパス -->
                    <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800">SVG図面データ設定</span>
                            <span class="text-[11px] text-slate-500">新規登録時はファイル選択または既存パス指定が必須</span>
                        </div>

                        <div>
                            <label for="base_map_svg_file" class="block font-bold text-slate-700 mb-1">
                                SVGファイルをアップロード（最大 10MB・サーバー自動サニタイズ）
                            </label>
                            <input type="file" id="base_map_svg_file" name="svg_file" accept=".svg,image/svg+xml"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 file:mr-3 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        </div>

                        <div id="base-map-svg-preview-wrap" class="hidden p-2.5 bg-white border border-slate-200 rounded-lg flex items-center space-x-3">
                            <img id="base-map-svg-preview-img" src="" alt="選択SVGプレビュー" class="w-16 h-16 object-contain border border-slate-100 rounded">
                            <div class="min-w-0 flex-1 text-[11px]">
                                <div id="base-map-svg-preview-name" class="font-bold text-slate-800 truncate"></div>
                                <div class="text-emerald-700 font-medium">SVGファイルを読み込みました</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="base_map_svg_path" class="block font-bold text-slate-700 mb-1">
                                    または既存SVGファイルパス指定
                                </label>
                                <input type="text" id="base_map_svg_path" name="svg_path" value="{{ old('svg_path') }}"
                                    list="existing-svg-paths-list"
                                    placeholder="例: maps/shopping_street_base_map_260813.svg"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <datalist id="existing-svg-paths-list">
                                    @foreach($existingSvgPaths as $svgOption)
                                        <option value="{{ $svgOption }}"></option>
                                    @endforeach
                                </datalist>
                            </div>

                            <div>
                                <label for="base_map_viewbox" class="block font-bold text-slate-700 mb-1">
                                    SVG viewBox
                                </label>
                                <input type="text" id="base_map_viewbox" name="viewbox" value="{{ old('viewbox') }}" maxlength="50"
                                    placeholder="例: 0 0 842 1191（SVG選択時は自動抽出）"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- 行4: PDF原本設定（任意） -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="base_map_pdf_file" class="block font-bold text-slate-700 mb-1">
                                PDF原本アップロード（任意・最大 20MB）
                            </label>
                            <input type="file" id="base_map_pdf_file" name="pdf_file" accept=".pdf,application/pdf"
                                class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 file:mr-2.5 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                        </div>

                        <div>
                            <label for="base_map_pdf_path" class="block font-bold text-slate-700 mb-1">
                                または既存PDF原本パス（任意）
                            </label>
                            <input type="text" id="base_map_pdf_path" name="pdf_path" value="{{ old('pdf_path') }}"
                                list="existing-pdf-paths-list"
                                placeholder="例: maps/shopping_street_base_map_260813.pdf"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <datalist id="existing-pdf-paths-list">
                                @foreach($existingPdfPaths as $pdfOption)
                                    <option value="{{ $pdfOption }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <!-- 行5: デフォルト設定 & 有効フラグ -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                            <input type="hidden" name="is_default" value="0">
                            <label class="flex items-center space-x-2 cursor-pointer select-none">
                                <input type="checkbox" id="base_map_is_default" name="is_default" value="1"
                                    {{ old('is_default') ? 'checked' : '' }}
                                    class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                <span class="font-bold text-slate-800">デフォルト白地図に設定</span>
                            </label>
                            <p class="text-[11px] text-slate-500 pl-6">
                                未指定の運行日程で優先して開かれる標準白地図にします。
                            </p>
                        </div>

                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                            <input type="hidden" name="is_active" value="0">
                            <label class="flex items-center space-x-2 cursor-pointer select-none">
                                <input type="checkbox" id="base_map_is_active" name="is_active" value="1"
                                    {{ old('is_active', '1') ? 'checked' : '' }}
                                    class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                <span class="font-bold text-slate-800">有効（エディタで新規選択可）</span>
                            </label>
                            <p class="text-[11px] text-slate-500 pl-6">
                                旧版などを新規選択対象から外す場合はチェックを外します。
                            </p>
                        </div>
                    </div>

                    <!-- 行6: 特記事項・CADメモ -->
                    <div>
                        <label for="base_map_notes" class="block font-bold text-slate-700 mb-1">
                            特記事項・CADメモ
                        </label>
                        <textarea id="base_map_notes" name="notes" rows="2" maxlength="2000"
                            placeholder="例: 商店街メイン通り東側・西側歩道、駐車場特設エリアを含む。更新履歴や縮尺メモなど。"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <!-- モーダルフッター（固定） -->
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-2 shrink-0">
                    <x-ui.button variant="secondary" size="sm" type="button" class="btn-close-modal">
                        キャンセル
                    </x-ui.button>
                    <x-ui.button variant="primary" size="sm" type="submit" id="site-base-map-form-submit-btn">
                        白地図を登録する
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@vite(['resources/js/site-base-maps.js'])
</x-layouts.app>
