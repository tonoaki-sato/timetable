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
                <x-ui.button
                    variant="header-action"
                    id="btn-open-create-location"
                    data-store-url="{{ route('locations.store') }}"
                    :disabled="$types->isEmpty()"
                    class="{{ $types->isEmpty() ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}">
                    <span>➕</span>
                    <span>新規拠点を追加</span>
                </x-ui.button>
            </x-slot:actions>
        </x-page-header>

        <x-ui.validation-errors title="入力内容を確認してください" />

        @if($types->isEmpty())
            <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 text-xs rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="font-bold flex items-center space-x-1.5">
                        <span>⚠️</span>
                        <span>拠点タイプ（種別マスタ）がまだ登録されていません</span>
                    </div>
                    <p class="text-[11px] text-amber-800">
                        拠点を登録するには、先に「拠点タイプ管理」画面から拠点タイプ（駐車場・保管倉庫・コミュニティスペース等）を追加してください。
                    </p>
                </div>
                <a href="{{ route('location-types.index') }}"
                    class="inline-flex items-center space-x-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-xs transition shrink-0">
                    <span>🏷️</span>
                    <span>拠点タイプ管理へ移動</span>
                </a>
            </div>
        @endif

        @php
            $allCount = $locationsWithStatus->count();
        @endphp

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
                        左端のハンドル（⋮⋮）をドラッグ＆ドロップすると並び順を即時変更できます。拠点名をクリックすると利用枠を含む詳細モーダルを表示します。
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
                                    <div class="flex items-center space-x-1.5">
                                        <button type="button"
                                            class="btn-view-location font-bold text-slate-800 hover:text-indigo-600 hover:underline transition cursor-pointer text-left"
                                            data-location-id="{{ $item->location->id }}"
                                            data-name="{{ $item->location->name }}"
                                            data-type-label="{{ $item->location->type?->label ?? '未分類' }}"
                                            data-is-all-day="{{ $item->location->is_all_day ? '1' : '0' }}"
                                            data-is-active="{{ $item->location->is_active ? '1' : '0' }}"
                                            data-notes="{{ $item->location->notes ?? '' }}"
                                            data-schedules-count="{{ $item->location->schedules->count() }}"
                                            data-daily-status-label="{{ $item->dailyStatus->label() }}"
                                            data-daily-status-icon="{{ $item->dailyStatus->icon() }}"
                                            data-daily-status-class="{{ $item->dailyStatus->badgeClass() }}"
                                            data-detail-schedules="{{ json_encode($item->toDetailSchedulesPayload($now), JSON_UNESCAPED_UNICODE) }}">
                                            {{ $item->location->name }}
                                        </button>
                                        @if(!$item->location->is_active)
                                            <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 border border-slate-200 text-[10px] font-bold">
                                                無効
                                            </span>
                                        @endif
                                    </div>
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
                                    <button type="button"
                                        class="btn-edit-location inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                        data-location-id="{{ $item->location->id }}"
                                        data-update-url="{{ route('locations.update', $item->location) }}"
                                        data-name="{{ $item->location->name }}"
                                        data-location-type-id="{{ $item->location->location_type_id }}"
                                        data-is-all-day="{{ $item->location->is_all_day ? '1' : '0' }}"
                                        data-is-active="{{ $item->location->is_active ? '1' : '0' }}"
                                        data-notes="{{ $item->location->notes ?? '' }}"
                                        data-schedules="{{ json_encode($item->toFormSchedulesPayload($now), JSON_UNESCAPED_UNICODE) }}">
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
                                <td colspan="7" class="px-4 py-8 text-center text-slate-400">
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
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity modal-backdrop"></div>
            <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
                <div class="relative flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-4rem)] w-full sm:max-w-2xl rounded-2xl bg-white text-left shadow-xl border border-slate-200 overflow-hidden my-auto">
                    <!-- ヘッダー（固定） -->
                    <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 shrink-0">
                        <h3 id="detail-modal-title" class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                            <span>📍</span>
                            <span>拠点詳細</span>
                        </h3>
                        <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                            <span class="sr-only">閉じる</span>
                            <span class="text-lg leading-none">✕</span>
                        </button>
                    </div>

                    <!-- 本文（スクロール可能） -->
                    <div class="p-4 sm:p-6 space-y-4 text-xs flex-1 min-h-0 overflow-y-auto">
                        <!-- 基本情報 -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 bg-slate-50/80 border border-slate-200/80 rounded-xl p-3.5">
                            <div>
                                <span class="text-slate-400 block text-[11px]">拠点名</span>
                                <div class="flex items-center space-x-1.5 mt-0.5">
                                    <span id="detail-location-name" class="font-bold text-slate-900 text-sm">—</span>
                                    <span id="detail-inactive-badge" class="hidden px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 text-[10px] font-bold">無効</span>
                                </div>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">拠点種別</span>
                                <span id="detail-type-label" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border bg-white text-slate-700 border-slate-200 mt-0.5">—</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">利用可能形態</span>
                                <span id="detail-is-all-day" class="font-bold text-slate-800 block mt-0.5">—</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">本日の状況</span>
                                <span id="detail-daily-status" class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[11px] font-bold border bg-slate-100 text-slate-600 border-slate-200 mt-0.5">—</span>
                            </div>
                        </div>

                        <!-- 特記事項・備考 -->
                        <div>
                            <span class="text-slate-500 block text-[11px] mb-1 font-bold">特記事項・備考</span>
                            <div id="detail-notes" class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl text-slate-700 leading-relaxed whitespace-pre-wrap max-h-28 overflow-y-auto">—</div>
                        </div>

                        <!-- 利用枠セクション -->
                        <div id="detail-schedules-section" class="space-y-3 pt-2 border-t border-slate-100">
                            <div id="detail-schedules-all-day" class="hidden p-3.5 bg-blue-50/70 border border-blue-200 rounded-xl text-blue-800 text-xs flex items-center space-x-2">
                                <span>🕒</span>
                                <span>終日（24時間）常時利用可能な拠点のため、個別利用枠の制限はありません。</span>
                            </div>

                            <div id="detail-schedules-split" class="space-y-4">
                                <!-- 現在・今後の利用枠 -->
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-bold text-xs text-slate-800 flex items-center space-x-1.5">
                                            <span>🟢</span>
                                            <span>現在・今後の利用枠</span>
                                        </h4>
                                        <span id="detail-upcoming-count" class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono text-[11px] font-bold">0件</span>
                                    </div>
                                    <div id="detail-upcoming-list" class="space-y-2"></div>
                                    <div id="detail-upcoming-empty" class="p-3 bg-slate-50 border border-slate-200/70 rounded-xl text-slate-400 text-center text-[11px]">
                                        現在および今後の利用枠は登録されていません。
                                    </div>
                                </div>

                                <!-- 過去の利用枠 -->
                                <div class="space-y-2 pt-2 border-t border-slate-100">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-bold text-xs text-slate-600 flex items-center space-x-1.5">
                                            <span>🕒</span>
                                            <span>過去の利用枠</span>
                                        </h4>
                                        <span id="detail-past-count" class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 font-mono text-[11px] font-bold">0件</span>
                                    </div>
                                    <div id="detail-past-list" class="space-y-2"></div>
                                    <div id="detail-past-empty" class="p-3 bg-slate-50 border border-slate-200/70 rounded-xl text-slate-400 text-center text-[11px]">
                                        過去の利用枠はありません。
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- フッター（固定） -->
                    <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex justify-end shrink-0">
                        <x-ui.button variant="secondary" size="sm" type="button" class="btn-close-modal">
                            閉じる
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. 入力フォームモーダル (新規作成・編集兼用) -->
        <div id="modal-location-form" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="form-modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity modal-backdrop"></div>
            <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
                <div class="relative flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-4rem)] w-full sm:max-w-3xl rounded-2xl bg-white text-left shadow-xl border border-slate-200 overflow-hidden my-auto">
                    <form id="form-location-mutation" method="POST" action="{{ route('locations.store') }}" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                        @csrf
                        <input type="hidden" id="form-method" name="_method" value="POST">

                        <!-- モーダルヘッダー（固定） -->
                        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 shrink-0">
                            <h3 id="form-modal-title" class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                                <span>📍</span>
                                <span>拠点の新規登録</span>
                            </h3>
                            <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                                <span class="sr-only">閉じる</span>
                                <span class="text-lg leading-none">✕</span>
                            </button>
                        </div>

                        <!-- モーダル本文（スクロール可能） -->
                        <div class="p-4 sm:p-6 space-y-4 text-xs flex-1 min-h-0 overflow-y-auto">
                            <!-- 基本項目 (拠点名・拠点タイプ) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label for="location_name" class="block font-bold text-slate-700 mb-1">
                                        拠点名 <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="location_name" name="name" required maxlength="255"
                                        placeholder="例: 番所地下倉庫、税務署前駐車場"
                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                </div>

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
                            </div>

                            <!-- 終日利用可能フラグ & 有効フラグ -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                                        <input type="checkbox" id="location_is_all_day" name="is_all_day" value="1"
                                            class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                        <span class="font-bold text-slate-800">終日利用可能（常時開放倉庫など）</span>
                                    </label>
                                    <p class="text-[11px] text-slate-500 pl-6">
                                        チェックを入れると、個別利用枠なしでも24時間利用可能と判定されます。
                                    </p>
                                </div>

                                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                                    <input type="hidden" name="is_active" value="0">
                                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                                        <input type="checkbox" id="location_is_active" name="is_active" value="1" checked
                                            class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                        <span class="font-bold text-slate-800">有効（稼働中）</span>
                                    </label>
                                    <p class="text-[11px] text-slate-500 pl-6">
                                        一時閉鎖中の場合はチェックを外して非稼働にできます。
                                    </p>
                                </div>
                            </div>

                            <!-- 特記事項・備考 -->
                            <div>
                                <label for="location_notes" class="block font-bold text-slate-700 mb-1">
                                    特記事項・備考
                                </label>
                                <textarea id="location_notes" name="notes" rows="2" maxlength="2000"
                                    placeholder="利用可能時間、鍵の保管場所、進入制限、使用上の注意など"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"></textarea>
                            </div>

                            <!-- 利用枠の登録・編集セクション -->
                            <div id="schedules-form-container" class="space-y-2.5 pt-3 border-t border-slate-100">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                                            <span>⏱️</span>
                                            <span>利用枠（利用可能時間帯）</span>
                                        </h4>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            終日利用可能でない場合、利用枠（年月日時分）と確保ステータスを追加・編集・削除できます。
                                        </p>
                                    </div>
                                    <button type="button" id="add-schedule-row-btn"
                                        data-default-date="{{ $now->format('Y-m-d') }}"
                                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition inline-flex items-center space-x-1 self-start sm:self-auto cursor-pointer shrink-0">
                                        <span>➕</span>
                                        <span>利用枠を追加</span>
                                    </button>
                                </div>

                                <div class="overflow-x-auto border border-slate-200 rounded-xl bg-slate-50/50">
                                    <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                                        <thead class="bg-slate-100/80 text-slate-700 font-bold">
                                            <tr>
                                                <th scope="col" class="py-2 px-2.5 whitespace-nowrap">開始〜終了日時 <span class="text-red-500">*</span></th>
                                                <th scope="col" class="py-2 px-2.5 whitespace-nowrap">利用ステータス <span class="text-red-500">*</span></th>
                                                <th scope="col" class="py-2 px-2.5 whitespace-nowrap">枠名 / タイトル</th>
                                                <th scope="col" class="py-2 px-2.5 whitespace-nowrap">メモ</th>
                                                <th scope="col" class="py-2 px-2.5 whitespace-nowrap text-right">削除</th>
                                            </tr>
                                        </thead>
                                        <tbody id="schedules-form-body" class="divide-y divide-slate-100 bg-white"></tbody>
                                    </table>
                                    <div id="schedules-empty-message" class="p-4 text-center text-slate-400 text-xs">
                                        利用枠が登録されていません。「➕ 利用枠を追加」から登録してください。
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- モーダルフッター（固定） -->
                        <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-2 shrink-0">
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
