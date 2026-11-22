<x-layouts.app title="会場エリアマスター">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ページヘッダー -->
    <x-page-header
        title="会場エリアマスター"
        icon="📐"
        description="配置計画・配置一覧で全運行日共通に使用する会場エリア定義を一元管理します。">
        <x-slot:breadcrumbs>
            <span class="text-slate-500">🗂️ マスター管理</span>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">📐 会場エリアマスター</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('create', App\Models\LayoutArea::class)
                <x-ui.button
                    variant="header-action"
                    id="btn-open-create-layout-area"
                    data-store-url="{{ route('layout-areas.store') }}">
                    <span>➕</span>
                    <span>新規エリアを追加</span>
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-ui.validation-errors title="入力内容を確認してください" />

    <!-- 会場エリアマスター一覧テーブルカード -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                    <span>📐</span>
                    <span>登録会場エリア一覧</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono font-normal">
                        全 {{ $layoutAreas->count() }} エリア
                    </span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    @can('update', App\Models\LayoutArea::class)
                        左端のハンドル（⋮⋮）をドラッグ＆ドロップすると配置計画・一覧でのエリア表示順を変更できます。
                    @endcan
                    エリア名をクリックすると詳細と利用状況を確認できます。
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                    <tr>
                        <th scope="col" class="w-10 px-3 py-2.5 text-center">順</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">エリア名</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">関連拠点</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">利用状況</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">備考・特記事項</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap text-right">操作</th>
                    </tr>
                </thead>
                <tbody id="layout-areas-container"
                    data-reorder-url="{{ route('layout-areas.reorder') }}"
                    data-can-reorder="{{ auth()->user()?->can('update', App\Models\LayoutArea::class) ? '1' : '0' }}"
                    class="divide-y divide-slate-100 bg-white">
                    @forelse($layoutAreas as $area)
                        @php
                            $payloadJson = json_encode($area->presenter()->toModalPayload(), JSON_UNESCAPED_UNICODE);
                            $inUse = (int) ($area->items_count ?? 0) > 0;
                            $canUpdate = auth()->user()?->can('update', $area) ?? false;
                        @endphp
                        <tr data-layout-area-id="{{ $area->id }}"
                            draggable="{{ $canUpdate ? 'true' : 'false' }}"
                            class="hover:bg-slate-50/80 transition cursor-default">
                            <!-- 並び順ハンドル -->
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                @if($canUpdate)
                                    <span class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-indigo-600 select-none font-bold text-sm" title="ドラッグして並び替え">⋮⋮</span>
                                @else
                                    <span class="text-slate-300 font-mono text-[11px]">{{ $area->display_order }}</span>
                                @endif
                            </td>

                            <!-- エリア名（閲覧専用詳細モーダル起動） -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    <button type="button"
                                        class="btn-view-layout-area font-bold text-slate-800 hover:text-indigo-600 hover:underline transition cursor-pointer text-left"
                                        data-layout-area="{{ $payloadJson }}"
                                        title="クリックして詳細を表示">
                                        {{ $area->name }}
                                    </button>
                                    @if(!$area->is_active)
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 border border-slate-200 text-[10px] font-bold">
                                            無効
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 関連拠点 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($area->location)
                                    <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[11px] font-bold border bg-slate-100 text-slate-700 border-slate-200">
                                        <span>📍</span>
                                        <span>{{ $area->location->name }}</span>
                                        @if(!$area->location->is_active)
                                            <span class="text-slate-400">（無効）</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <!-- 利用状況 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border bg-slate-50 text-slate-700 border-slate-200 font-mono">
                                        📅 {{ (int) ($area->layout_maps_count ?? 0) }}日程
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border bg-slate-50 text-slate-700 border-slate-200 font-mono">
                                        📌 {{ (int) ($area->items_count ?? 0) }}配置
                                    </span>
                                </div>
                            </td>

                            <!-- 備考・特記事項 -->
                            <td class="px-4 py-3 text-slate-600 max-w-xs truncate" title="{{ $area->notes }}">
                                {{ $area->notes ?: '—' }}
                            </td>

                            <!-- 操作 -->
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    @can('update', $area)
                                        <button type="button"
                                            class="btn-edit-layout-area inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                            data-layout-area="{{ $payloadJson }}">
                                            <span>✏️</span>
                                            <span>編集</span>
                                        </button>
                                    @endcan
                                    @can('delete', $area)
                                        @if($inUse)
                                            <span class="px-2 py-1 text-[11px] text-slate-400 cursor-not-allowed" title="配置データで使用中のため削除できません（不要な場合は無効化してください）">
                                                🔒 使用中
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('layout-areas.destroy', $area) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <x-ui.button variant="delete" size="xs" type="button"
                                                    class="btn-delete-layout-area"
                                                    data-area-name="{{ $area->name }}">
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
                                <x-ui.empty-state icon="📐" title="会場エリアマスターが登録されていません。" description="右上の「新規エリアを追加」から登録してください。" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- 1. 閲覧専用詳細モーダル -->
<div id="modal-layout-area-detail" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="layout-area-detail-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity modal-backdrop"></div>
    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-4rem)] w-full sm:max-w-lg rounded-2xl bg-white text-left shadow-xl border border-slate-200 overflow-hidden my-auto">
            <!-- ヘッダー（固定） -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 shrink-0">
                <h3 id="layout-area-detail-title" class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                    <span>📐</span>
                    <span>会場エリア詳細</span>
                </h3>
                <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    <span class="sr-only">閉じる</span>
                    <span class="text-lg leading-none">✕</span>
                </button>
            </div>

            <!-- 本文（スクロール可能） -->
            <div class="p-4 sm:p-6 space-y-4 text-xs flex-1 min-h-0 overflow-y-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 bg-slate-50/80 border border-slate-200/80 rounded-xl p-3.5">
                    <div class="sm:col-span-2">
                        <span class="text-slate-400 block text-[11px]">エリア名</span>
                        <div class="flex items-center space-x-1.5 mt-0.5">
                            <span id="detail-area-name" class="font-bold text-slate-900 text-sm">—</span>
                            <span id="detail-area-active-badge" class="hidden px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">有効</span>
                            <span id="detail-area-inactive-badge" class="hidden px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 text-[10px] font-bold">無効</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">関連拠点</span>
                        <span id="detail-area-location" class="font-bold text-slate-800 block mt-0.5">—</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">利用状況</span>
                        <span id="detail-area-usage" class="font-mono font-bold text-slate-800 block mt-0.5">—</span>
                    </div>
                </div>

                <div>
                    <span class="text-slate-500 block text-[11px] mb-1 font-bold">備考・特記事項</span>
                    <div id="detail-area-notes" class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl text-slate-700 leading-relaxed whitespace-pre-wrap max-h-36 overflow-y-auto">—</div>
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

@if(auth()->user()?->can('create', App\Models\LayoutArea::class) || auth()->user()?->can('update', App\Models\LayoutArea::class))
<!-- 2. 入力フォームモーダル（新規登録・編集兼用） -->
<div id="modal-layout-area-form"
    class="hidden fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="layout-area-form-modal-title"
    role="dialog"
    aria-modal="true"
    data-reopen-on-error="{{ $errors->any() ? '1' : '0' }}"
    data-old-action="{{ old('_form_action', route('layout-areas.store')) }}"
    data-old-method="{{ old('_form_method', 'POST') }}">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity modal-backdrop"></div>
    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-4rem)] w-full sm:max-w-lg rounded-2xl bg-white text-left shadow-xl border border-slate-200 overflow-hidden my-auto">
            <form id="form-layout-area-mutation" method="POST" action="{{ old('_form_action', route('layout-areas.store')) }}" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf
                <input type="hidden" id="layout-area-form-method" name="_method" value="{{ old('_form_method', 'POST') }}">
                <input type="hidden" id="layout-area-form-action-mirror" name="_form_action" value="{{ old('_form_action', route('layout-areas.store')) }}">
                <input type="hidden" id="layout-area-form-method-mirror" name="_form_method" value="{{ old('_form_method', 'POST') }}">

                <!-- モーダルヘッダー（固定） -->
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 shrink-0">
                    <h3 id="layout-area-form-modal-title" class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                        <span>📐</span>
                        <span>新規会場エリアの登録</span>
                    </h3>
                    <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                        <span class="sr-only">閉じる</span>
                        <span class="text-lg leading-none">✕</span>
                    </button>
                </div>

                <!-- モーダル本文（スクロール可能） -->
                <div class="p-4 sm:p-6 space-y-4 text-xs flex-1 min-h-0 overflow-y-auto">
                    <!-- 使用中エリア編集時の注意バナー -->
                    <div id="edit-area-usage-warning" class="hidden p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 space-y-1">
                        <div class="font-bold flex items-center space-x-1.5">
                            <span>⚠️</span>
                            <span>使用中エリアの編集について</span>
                        </div>
                        <p class="text-[11px] leading-relaxed">
                            このエリアには <strong id="edit-area-usage-items-count">0</strong> 件の配置データ（<strong id="edit-area-usage-maps-count">0</strong> 日程分）が紐づいています。名称変更は全運行日に反映されます。不要になった場合は削除せず「有効」のチェックを外して無効化してください（過去履歴は保護されます）。
                        </p>
                    </div>

                    <!-- エリア名 -->
                    <div>
                        <label for="layout_area_name" class="block font-bold text-slate-700 mb-1">
                            エリア名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="layout_area_name" name="name" value="{{ old('name') }}" required maxlength="255"
                            placeholder="例: 北川駐車場内、メイン通りA列（東側歩道）"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- 関連拠点 -->
                    <div>
                        <label for="layout_area_location_id" class="block font-bold text-slate-700 mb-1">
                            関連拠点（任意）
                        </label>
                        <select id="layout_area_location_id" name="location_id"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">（関連拠点なし・通り等）</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ (string) old('location_id') === (string) $loc->id ? 'selected' : '' }}>
                                    {{ $loc->name }}@if(!$loc->is_active)（無効）@endif
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">
                            貸切駐車場や番所前スペースなど、拠点マスターと対応するエリアの場合に選択します。
                        </p>
                    </div>

                    <!-- 有効フラグ -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-center space-x-2 cursor-pointer select-none">
                            <input type="checkbox" id="layout_area_is_active" name="is_active" value="1"
                                {{ old('is_active', '1') ? 'checked' : '' }}
                                class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <span class="font-bold text-slate-800">有効（新規配置の選択肢に表示する）</span>
                        </label>
                        <p class="text-[11px] text-slate-500 pl-6">
                            チェックを外すと新規配置では選べなくなりますが、過去運行日の配置履歴はそのまま保持されます。
                        </p>
                    </div>

                    <!-- 備考・特記事項 -->
                    <div>
                        <label for="layout_area_notes" class="block font-bold text-slate-700 mb-1">
                            備考・特記事項
                        </label>
                        <textarea id="layout_area_notes" name="notes" rows="3" maxlength="2000"
                            placeholder="電源容量、車両進入可否、周辺店舗との調整事項など"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <!-- モーダルフッター（固定） -->
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-2 shrink-0">
                    <x-ui.button variant="secondary" size="sm" type="button" class="btn-close-modal">
                        キャンセル
                    </x-ui.button>
                    <x-ui.button variant="primary" size="sm" type="submit" id="layout-area-form-submit-btn">
                        エリアを登録する
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@vite(['resources/js/layout-areas.js'])
</x-layouts.app>
