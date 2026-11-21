<x-layouts.app title="拠点マスター">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

        <!-- ページヘッダー -->
        <x-page-header
            title="拠点マスター"
            icon="📍"
            description="番所倉庫・一時集約スペース・貸切駐車場等の拠点マスター台帳を一元管理します。">
            <x-slot:breadcrumbs>
                <span class="text-slate-500">🗂️ マスター管理</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">📍 拠点マスター</span>
            </x-slot:breadcrumbs>

            <x-slot:actions>
                <x-ui.button variant="header-action" :href="route('location-types.index')" title="拠点の分類・アイコン設定">
                    <span>🏷️</span>
                    <span>拠点タイプ管理</span>
                </x-ui.button>
                <x-ui.button variant="header-action" id="btn-open-create-location" data-store-url="{{ route('locations.store') }}">
                    <span>➕</span>
                    <span>新規拠点を追加</span>
                </x-ui.button>
            </x-slot:actions>
        </x-page-header>

        <x-ui.validation-errors title="入力内容を確認してください" />

        <!-- KPI / サマリーカード -->
        @php
        $allCount = $locationsWithStatus->count();
        $allDayCount = $locationsWithStatus->filter(fn($item) => $item->location->is_all_day)->count();
        $scheduledCount = $allCount - $allDayCount;
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-lg font-bold shrink-0">
                    📍
                </div>
                <div class="min-w-0">
                    <div class="text-slate-500 text-[11px] font-medium">登録拠点総数</div>
                    <div class="text-slate-800 text-lg font-bold font-mono">{{ $allCount }} <span class="text-xs font-normal text-slate-500">箇所</span></div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 text-lg font-bold shrink-0">
                    🕒
                </div>
                <div class="min-w-0">
                    <div class="text-slate-500 text-[11px] font-medium">終日利用可能（常設）</div>
                    <div class="text-slate-800 text-lg font-bold font-mono">{{ $allDayCount }} <span class="text-xs font-normal text-slate-500">箇所</span></div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 text-lg font-bold shrink-0">
                    ⏱️
                </div>
                <div class="min-w-0">
                    <div class="text-slate-500 text-[11px] font-medium">時間枠指定（貸切等）</div>
                    <div class="text-slate-800 text-lg font-bold font-mono">{{ $scheduledCount }} <span class="text-xs font-normal text-slate-500">箇所</span></div>
                </div>
            </div>
        </div>

        <!-- 登録拠点一覧データテーブルカード -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                        <span>📍</span>
                        <span>登録拠点一覧</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono font-normal">
                            全 {{ $allCount }} 拠点
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        左端のハンドル（⋮⋮）をドラッグ＆ドロップすると並び順を即時変更できます。拠点名をクリックすると詳細モーダルを表示します。
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                        <tr>
                            <th scope="col" class="w-10 px-3 py-2.5 text-center">順</th>
                            <th scope="col" class="px-4 py-2.5 whitespace-nowrap">拠点名</th>
                            <th scope="col" class="px-4 py-2.5 whitespace-nowrap">拠点種別</th>
                            <th scope="col" class="px-4 py-2.5 whitespace-nowrap">利用形態</th>
                            <th scope="col" class="px-4 py-2.5 whitespace-nowrap">本日の状況</th>
                            <th scope="col" class="px-4 py-2.5 whitespace-nowrap">備考・特記事項</th>
                            <th scope="col" class="px-4 py-2.5 whitespace-nowrap text-right">操作</th>
                        </tr>
                    </thead>
                    <tbody id="locations-container"
                        data-reorder-url="{{ route('locations.reorder') }}"
                        class="divide-y divide-slate-100 bg-white">
                        @forelse($locationsWithStatus as $item)
                        <tr data-location-id="{{ $item->location->id }}" draggable="true"
                            class="hover:bg-slate-50/80 transition cursor-default">
                            <!-- 並び順ハンドル -->
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <span class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-indigo-600 select-none font-bold text-sm" title="ドラッグして並び替え">⋮⋮</span>
                            </td>

                            <!-- 拠点名（閲覧専用詳細モーダル起動） -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <button type="button"
                                    class="btn-view-location font-bold text-slate-800 hover:text-indigo-600 hover:underline transition cursor-pointer text-left"
                                    data-location-id="{{ $item->location->id }}"
                                    data-name="{{ $item->location->name }}"
                                    data-type-label="{{ $item->location->type?->label ?? '未分類' }}"
                                    data-is-all-day="{{ $item->location->is_all_day ? '1' : '0' }}"
                                    data-display-order="{{ $item->location->display_order }}"
                                    data-notes="{{ $item->location->notes ?? '' }}"
                                    data-schedules-count="{{ $item->location->schedules->count() }}"
                                    data-show-url="{{ route('locations.show', $item->location) }}">
                                    {{ $item->location->name }}
                                </button>
                            </td>

                            <!-- 拠点種別 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border bg-slate-100 text-slate-700 border-slate-200">
                                    {{ $item->location->type?->label ?? '未分類' }}
                                </span>
                            </td>

                            <!-- 利用形態 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($item->location->is_all_day)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border bg-blue-50 text-blue-700 border-blue-200">
                                    終日利用可
                                </span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border bg-amber-50 text-amber-700 border-amber-200">
                                    時間枠指定 ({{ $item->location->schedules->count() }}枠)
                                </span>
                                @endif
                            </td>

                            <!-- 本日の状況 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $item->dailyStatus->badgeClass() }}">
                                    <span>{{ $item->dailyStatus->icon() }}</span>
                                    <span>{{ $item->dailyStatus->label() }}</span>
                                </span>
                            </td>

                            <!-- 備考・特記事項 -->
                            <td class="px-4 py-3 text-slate-600 max-w-xs truncate" title="{{ $item->location->notes }}">
                                {{ $item->location->notes ?: '—' }}
                            </td>

                            <!-- 行操作ボタン -->
                            <td class="px-4 py-3 whitespace-nowrap text-right space-x-1">
                                <a href="{{ route('locations.show', $item->location) }}"
                                    class="inline-flex items-center space-x-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                    title="詳細・利用可能時間帯スケジュール管理">
                                    <span>📅</span>
                                    <span>枠管理</span>
                                </a>
                                <button type="button"
                                    class="btn-edit-location inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                    data-location-id="{{ $item->location->id }}"
                                    data-update-url="{{ route('locations.update', $item->location) }}"
                                    data-name="{{ $item->location->name }}"
                                    data-location-type-id="{{ $item->location->location_type_id }}"
                                    data-is-all-day="{{ $item->location->is_all_day ? '1' : '0' }}"
                                    data-display-order="{{ $item->location->display_order }}"
                                    data-is-active="{{ $item->location->is_active ? '1' : '0' }}"
                                    data-notes="{{ $item->location->notes ?? '' }}">
                                    <span>✏️</span>
                                    <span>編集</span>
                                </button>
                                <form action="{{ route('locations.destroy', $item->location) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                        class="btn-delete-location inline-flex items-center space-x-1 px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                        data-location-name="{{ $item->location->name }}">
                                        <span>🗑️</span>
                                        <span>削除</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                拠点データが登録されていません。「新規拠点を追加」から登録してください。
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 1. 閲覧専用詳細モーダル -->
        <div id="modal-location-detail" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="detail-modal-title" role="dialog" aria-modal="true">
            <div class="min-h-screen px-4 text-center flex items-center justify-center">
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity modal-backdrop"></div>
                <div class="relative bg-white rounded-2xl max-w-lg w-full p-6 text-left shadow-xl transform transition-all border border-slate-200">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 id="detail-modal-title" class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                            <span>📍</span>
                            <span>拠点詳細</span>
                        </h3>
                        <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition">
                            <span class="sr-only">閉じる</span>
                            <span class="text-lg leading-none">✕</span>
                        </button>
                    </div>

                    <div class="py-4 space-y-4 text-xs">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-slate-400 block text-[11px]">拠点名</span>
                                <span id="detail-location-name" class="font-bold text-slate-800 block mt-0.5 text-sm">—</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">拠点種別</span>
                                <span id="detail-type-label" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border bg-slate-100 text-slate-700 border-slate-200 mt-0.5">—</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-slate-400 block text-[11px]">利用可能形態</span>
                                <span id="detail-is-all-day" class="font-bold text-slate-800 block mt-0.5">—</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">表示順序</span>
                                <span id="detail-display-order" class="font-mono text-slate-700 block mt-0.5">—</span>
                            </div>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[11px] mb-1 font-bold">特記事項・備考</span>
                            <div id="detail-notes" class="p-3 bg-slate-50 border border-slate-100 rounded-xl text-slate-700 leading-relaxed whitespace-pre-wrap max-h-32 overflow-y-auto">—</div>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-slate-500 text-[11px]">スケジュール枠の追加・管理:</span>
                            <a id="detail-show-link" href="#" class="inline-flex items-center space-x-1 text-xs text-indigo-600 font-bold hover:underline">
                                <span>📅 利用可能枠の管理画面を開く →</span>
                            </a>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end">
                        <x-ui.button variant="secondary" size="sm" type="button" class="btn-close-modal">
                            閉じる
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. 入力フォームモーダル (新規作成・編集兼用) -->
        <div id="modal-location-form" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="form-modal-title" role="dialog" aria-modal="true">
            <div class="min-h-screen px-4 text-center flex items-center justify-center">
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity modal-backdrop"></div>
                <div class="relative bg-white rounded-2xl max-w-lg w-full p-6 text-left shadow-xl transform transition-all border border-slate-200">
                    <form id="form-location-mutation" method="POST" action="">
                        @csrf
                        <input type="hidden" id="form-method" name="_method" value="POST">

                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 id="form-modal-title" class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                                <span>📍</span>
                                <span>拠点の登録</span>
                            </h3>
                            <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition">
                                <span class="sr-only">閉じる</span>
                                <span class="text-lg leading-none">✕</span>
                            </button>
                        </div>

                        <div class="py-4 space-y-3.5 text-xs">
                            <!-- 拠点名 -->
                            <div>
                                <label for="location_name" class="block font-bold text-slate-700 mb-1">
                                    拠点名 <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="location_name" name="name" required maxlength="255"
                                    placeholder="例: 番所地下倉庫、税務署前駐車場"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            </div>

                            <!-- 拠点タイプ -->
                            <div>
                                <label for="location_type_id" class="block font-bold text-slate-700 mb-1">
                                    拠点タイプ <span class="text-red-500">*</span>
                                </label>
                                <select id="location_type_id" name="location_type_id" required
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                    @foreach($types as $type)
                                    <option value="{{ $type->id }}">
                                        {{ $type->label ?? $type->name }} ({{ $type->name }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 終日利用可能フラグ -->
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                                <label class="flex items-center space-x-2 cursor-pointer select-none">
                                    <input type="checkbox" id="location_is_all_day" name="is_all_day" value="1"
                                        class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                    <span class="font-bold text-slate-800">終日利用可能（常時開放倉庫など）</span>
                                </label>
                                <p class="text-[11px] text-slate-500 pl-6">
                                    チェックを入れると、特定スケジュール枠の設定がなくても24時間利用可能と判定されます。
                                </p>
                            </div>

                            <!-- 表示順 -->
                            <div>
                                <label for="location_display_order" class="block font-bold text-slate-700 mb-1">
                                    表示順
                                </label>
                                <input type="number" id="location_display_order" name="display_order" min="0" max="9999" step="10"
                                    placeholder="例: 10"
                                    class="w-32 px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            </div>

                            <!-- 特記事項・備考 -->
                            <div>
                                <label for="location_notes" class="block font-bold text-slate-700 mb-1">
                                    特記事項・備考
                                </label>
                                <textarea id="location_notes" name="notes" rows="3" maxlength="2000"
                                    placeholder="利用可能時間、鍵の保管場所、進入制限、使用上の注意など"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"></textarea>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                            <x-ui.button variant="secondary" size="sm" type="button" class="btn-close-modal">
                                キャンセル
                            </x-ui.button>
                            <x-ui.button variant="primary" size="sm" type="submit">
                                保存する
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- 画面専用JavaScript (CSPノンス対応外部モジュール) -->
    @vite(['resources/js/locations.js'])
</x-layouts.app>