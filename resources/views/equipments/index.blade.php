<x-layouts.app title="機材・備品一覧">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- 1. ナビゲーション & ヘッダー -->
    <x-page-header
        title="機材・備品一覧"
        icon="📦"
        description="運行で使用する保有備品・レンタル・私物の台帳を管理します。">
        <x-slot:breadcrumbs>
            <span class="text-slate-500">🗂️ マスター管理</span>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">📦 機材・備品一覧</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button variant="header-action" :href="route('equipments.export-csv')" title="資機材台帳をCSVダウンロード">
                <span>📥</span>
                <span>CSV出力</span>
            </x-ui.button>
            @can('create', App\Models\Equipment::class)
                <x-ui.button variant="header-action" id="btn-open-equipment-csv-import" data-toggle-target="#equipmentCsvImportModal" title="CSVファイルから一括取り込み">
                    <span>📤</span>
                    <span>CSV取込</span>
                </x-ui.button>
                <x-ui.button variant="header-action" id="btn-open-create-equipment" data-store-url="{{ route('equipments.store') }}">
                    <span>➕</span>
                    <span>新規備品を追加</span>
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-ui.validation-errors title="CSV取り込みまたは入力内容にエラーがあります。" />

    <!-- 2. 検索・絞り込みフィルターバー -->
    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-2xs space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2.5">
            <!-- 区分フィルター -->
            <div class="flex items-center space-x-1.5 overflow-x-auto scrollbar-none flex-nowrap py-0.5">
                <span class="text-xs font-bold text-slate-500 mr-1 whitespace-nowrap shrink-0">区分:</span>
                <a href="{{ route('equipments.index', array_filter(['keyword' => $keyword, 'sort' => request('sort'), 'direction' => request('direction'), 'per_page' => request('per_page')])) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap shrink-0 transition {{ !$typeFilter ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    すべて ({{ $totalCount }})
                </a>
                <a href="{{ route('equipments.index', array_filter(['type' => 'committee', 'keyword' => $keyword, 'sort' => request('sort'), 'direction' => request('direction'), 'per_page' => request('per_page')])) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap shrink-0 transition {{ $typeFilter === 'committee' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200' }}">
                    🏛️ 実行委員会 ({{ $committeeCount }})
                </a>
                <a href="{{ route('equipments.index', array_filter(['type' => 'rental', 'keyword' => $keyword, 'sort' => request('sort'), 'direction' => request('direction'), 'per_page' => request('per_page')])) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap shrink-0 transition {{ $typeFilter === 'rental' ? 'bg-sky-600 text-white shadow-2xs' : 'bg-sky-50 text-sky-800 hover:bg-sky-100 border border-sky-200' }}">
                    🏢 イベント会社 ({{ $rentalCount }})
                </a>
                <a href="{{ route('equipments.index', array_filter(['type' => 'personal', 'keyword' => $keyword, 'sort' => request('sort'), 'direction' => request('direction'), 'per_page' => request('per_page')])) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap shrink-0 transition {{ $typeFilter === 'personal' ? 'bg-amber-600 text-white shadow-2xs' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200' }}">
                    🎒 私物 ({{ $personalCount }})
                </a>
            </div>

            <!-- キーワード検索フォーム -->
            <form method="GET" action="{{ route('equipments.index') }}" class="flex items-center space-x-2 flex-wrap gap-2">
                @if($typeFilter)
                    <input type="hidden" name="type" value="{{ $typeFilter }}">
                @endif
                @if(request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif
                @if(request('direction'))
                    <input type="hidden" name="direction" value="{{ request('direction') }}">
                @endif

                <!-- キーワード入力 -->
                <div class="relative">
                    <input type="text" name="keyword" value="{{ $keyword }}" placeholder="品名・保管場所・備考検索..."
                           class="pl-8 pr-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-48 sm:w-60">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
                </div>

                <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                    検索
                </button>

                @if($typeFilter || $keyword)
                    <a href="{{ route('equipments.index') }}" class="px-2.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-600 rounded-lg text-xs font-medium transition">
                        クリア
                    </a>
                @endif

                <x-ui.per-page-select :options="[20, 50, 100]" :default="20" />
            </form>
        </div>
    </div>

    <!-- 3. 備品一覧テーブル（ドラッグ＆ドロップ対応） -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                    <tr>
                        <x-ui.sortable-th column="name" label="備品名" defaultSort="display_order" class="px-4 py-2.5" />
                        <x-ui.sortable-th column="type" label="区分" defaultSort="display_order" class="px-4 py-2.5" />
                        <x-ui.sortable-th column="checkout_rule" label="持ち出しルール" defaultSort="display_order" class="px-4 py-2.5" />
                        <x-ui.sortable-th column="quantity" label="総数量" defaultSort="display_order" class="px-4 py-2.5" />
                        <x-ui.sortable-th column="storage_location_id" label="定常保管場所 / 調達" defaultSort="display_order" class="px-4 py-2.5" />
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap text-right">操作</th>
                    </tr>
                </thead>
                <tbody id="equipments-container"
                    data-reorder-url="{{ route('equipments.reorder') }}"
                    class="divide-y divide-slate-100 bg-white">
                    @forelse($equipments as $item)
                        @php
                            $modalPayloadJson = json_encode($item->presenter()->toModalPayload(), JSON_UNESCAPED_UNICODE);
                        @endphp
                        <tr data-equipment-id="{{ $item->id }}" draggable="true"
                            class="hover:bg-slate-50/80 transition cursor-default">
                            <!-- 備品名（詳細モーダル起動） -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <span class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-indigo-600 px-1 select-none font-bold text-sm" title="ドラッグして並び替え">⋮⋮</span>
                                    <button type="button"
                                        class="btn-view-equipment font-bold text-slate-800 hover:text-indigo-600 hover:underline transition cursor-pointer text-left"
                                        data-equipment="{{ $modalPayloadJson }}"
                                        title="クリックして詳細を表示">
                                        {{ $item->name }}
                                    </button>
                                </div>
                            </td>

                            <!-- 区分 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $item->type_enum->badgeClasses() }}">
                                    {{ $item->type_enum->icon() }} {{ $item->type_enum->shortLabel() }}
                                </span>
                            </td>

                            <!-- 持ち出しルール -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $item->checkout_rule_enum->badgeClasses() }}">
                                    @if($item->checkout_rule_enum->icon())
                                        <span class="mr-1">{{ $item->checkout_rule_enum->icon() }}</span>
                                    @endif
                                    <span>{{ $item->checkout_rule_enum->shortLabel() }}</span>
                                </span>
                            </td>

                            <!-- 数量・単位 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-bold font-mono text-slate-900 text-sm">{{ $item->presenter()->formattedQuantity() }}</span>
                            </td>

                            <!-- 保管場所 / 調達区分 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($item->isRental())
                                    <span class="text-slate-700 font-medium">
                                        🏢 イベント会社手配
                                    </span>
                                @elseif($item->storageLocation)
                                    <div class="font-medium text-slate-800 flex items-center space-x-1">
                                        <span class="text-slate-400">📍</span>
                                        <span>{{ $item->storageLocation->name }}</span>
                                        @if(!$item->storageLocation->is_active)
                                            <span class="px-1 py-0.5 rounded bg-slate-100 text-slate-500 border border-slate-200 text-[10px] font-bold">
                                                無効
                                            </span>
                                        @endif
                                    </div>
                                @elseif($item->isPersonal())
                                    <div class="font-medium text-slate-700 flex items-center space-x-1">
                                        <span>🏠</span>
                                        <span>個人保管（持ち込み）</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">未指定</span>
                                @endif
                            </td>

                            <!-- 操作 -->
                            <td class="px-4 py-2.5 whitespace-nowrap text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    @can('update', $item)
                                        <button type="button"
                                            class="btn-edit-equipment inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                            data-equipment="{{ $modalPayloadJson }}">
                                            <span>✏️</span>
                                            <span>編集</span>
                                        </button>
                                    @endcan
                                    @can('delete', $item)
                                        <form method="POST" action="{{ route('equipments.destroy', $item) }}" class="inline"
                                            data-confirm="備品「{{ $item->name }}」を削除しますか？&#10;紐づくすべての搬出入計画ステップも削除されます。">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button variant="delete" size="xs" type="submit">
                                                <span>🗑️</span>
                                                <span>削除</span>
                                            </x-ui.button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-ui.empty-state icon="📦" title="備品がまだ登録されていません。" description="右上の「新規備品を追加」から登録してください。" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($equipments->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $equipments->links() }}
            </div>
        @endif
    </div>

</div>

<!-- 1. 閲覧専用詳細モーダル -->
<div id="modal-equipment-detail" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="equipment-detail-modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity modal-backdrop"></div>
    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-4rem)] w-full sm:max-w-xl rounded-2xl bg-white text-left shadow-xl border border-slate-200 overflow-hidden my-auto">
            <!-- ヘッダー（固定） -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 shrink-0">
                <h3 id="equipment-detail-modal-title" class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                    <span>📦</span>
                    <span>機材・備品詳細</span>
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
                        <span class="text-slate-400 block text-[11px]">資機材名</span>
                        <span id="detail-equipment-name" class="font-bold text-slate-900 text-sm block mt-0.5">—</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">所有・調達区分</span>
                        <span id="detail-equipment-type" class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border bg-slate-100 text-slate-700 border-slate-200 mt-0.5">—</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">基準総数量</span>
                        <span id="detail-equipment-quantity" class="font-bold font-mono text-slate-900 text-sm block mt-0.5">—</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">持出・利用ルール</span>
                        <span id="detail-equipment-checkout-rule" class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border bg-slate-100 text-slate-700 border-slate-200 mt-0.5">—</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">定常保管場所</span>
                        <span id="detail-equipment-storage" class="font-bold text-slate-800 block mt-0.5">—</span>
                    </div>
                </div>

                <div>
                    <span class="text-slate-500 block text-[11px] mb-1 font-bold">仕様・注意事項・申し送りメモ</span>
                    <div id="detail-equipment-description" class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl text-slate-700 leading-relaxed whitespace-pre-wrap max-h-40 overflow-y-auto">—</div>
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

@if(auth()->user()?->can('create', App\Models\Equipment::class) || auth()->user()?->can('update', App\Models\Equipment::class))
@php
    $hasEquipmentFormErrors = $errors->any() && ! $errors->has('csv_file');
@endphp
<!-- 2. 入力フォームモーダル（新規登録・編集兼用） -->
<div id="modal-equipment-form"
    class="hidden fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="equipment-form-modal-title"
    role="dialog"
    aria-modal="true"
    data-reopen-on-error="{{ $hasEquipmentFormErrors ? '1' : '0' }}"
    data-old-action="{{ old('_form_action', route('equipments.store')) }}"
    data-old-method="{{ old('_form_method', 'POST') }}">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity modal-backdrop"></div>
    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-4rem)] w-full sm:max-w-2xl rounded-2xl bg-white text-left shadow-xl border border-slate-200 overflow-hidden my-auto">
            <form id="form-equipment-mutation" method="POST" action="{{ old('_form_action', route('equipments.store')) }}" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf
                <input type="hidden" id="equipment-form-method" name="_method" value="{{ old('_form_method', 'POST') }}">
                <input type="hidden" id="equipment-form-action-mirror" name="_form_action" value="{{ old('_form_action', route('equipments.store')) }}">
                <input type="hidden" id="equipment-form-method-mirror" name="_form_method" value="{{ old('_form_method', 'POST') }}">

                <!-- モーダルヘッダー（固定） -->
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 shrink-0">
                    <h3 id="equipment-form-modal-title" class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                        <span>📦</span>
                        <span>新規備品の登録</span>
                    </h3>
                    <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                        <span class="sr-only">閉じる</span>
                        <span class="text-lg leading-none">✕</span>
                    </button>
                </div>

                <!-- モーダル本文（スクロール可能） -->
                <div class="p-4 sm:p-6 space-y-4 text-xs flex-1 min-h-0 overflow-y-auto">
                    <!-- 行1: 資機材名 & 所有・調達区分 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                        <div class="md:col-span-2">
                            <label for="equipment_name" class="block font-bold text-slate-700 mb-1">
                                資機材名 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="equipment_name" name="name" value="{{ old('name') }}" required maxlength="255"
                                placeholder="例: カラーコーン一式、足踏み式給水設備、パイプテント 2K×3K"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="equipment_type" class="block font-bold text-slate-700 mb-1">
                                所有・調達区分 <span class="text-red-500">*</span>
                            </label>
                            <select id="equipment_type" name="type" required
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach(\App\Enums\EquipmentType::cases() as $typeCase)
                                    <option value="{{ $typeCase->value }}" {{ old('type', 'committee') === $typeCase->value ? 'selected' : '' }}>
                                        {{ $typeCase->icon() }} {{ $typeCase->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 行2: 数量/単位/概数 & 持出ルール -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <div>
                            <label for="equipment_quantity" class="block font-bold text-slate-700 mb-1">
                                基準数量 &amp; 単位 <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-1.5">
                                <div class="flex items-center space-x-1.5">
                                    <input type="number" id="equipment_quantity" name="quantity" value="{{ old('quantity', 1) }}" min="1" required
                                        class="w-24 px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <input type="text" id="equipment_unit" name="unit" value="{{ old('unit', '個') }}" required maxlength="20"
                                        placeholder="個/台/張"
                                        class="flex-1 min-w-0 px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <label class="inline-flex items-center space-x-1.5 cursor-pointer select-none">
                                    <input type="checkbox" id="equipment_is_approximate_quantity" name="is_approximate_quantity" value="1"
                                        {{ old('is_approximate_quantity') ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-[11px] font-bold text-slate-600">概数（約○個）で管理</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="equipment_checkout_rule" class="block font-bold text-slate-700 mb-1">
                                持出・利用ルール <span class="text-red-500">*</span>
                            </label>
                            <select id="equipment_checkout_rule" name="checkout_rule" required
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach(\App\Enums\EquipmentCheckoutRule::cases() as $ruleCase)
                                    <option value="{{ $ruleCase->value }}" {{ old('checkout_rule', 'free') === $ruleCase->value ? 'selected' : '' }}>
                                        {{ $ruleCase->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 行3: 定常保管場所（拠点マスター参照） -->
                    <div id="storage-container">
                        <label for="storage_location_id" class="block font-bold text-slate-700 mb-1">
                            定常保管場所（拠点マスター）
                        </label>
                        <div id="storage-location-field" class="space-y-1.5">
                            <select id="storage_location_id" name="storage_location_id"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">（未指定 / 個人保管など）</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ (string) old('storage_location_id') === (string) $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }}@if($loc->type)（{{ $loc->type->label }}）@endif
                                    </option>
                                @endforeach
                            </select>
                            @if($locations->isEmpty())
                                <p class="text-[11px] text-amber-700">
                                    拠点がまだ登録されていません。先に<a href="{{ route('locations.index') }}" class="text-indigo-600 font-bold hover:underline">拠点マスター</a>から保管拠点を登録してください。
                                </p>
                            @else
                                <p class="text-[11px] text-slate-500">
                                    倉庫等の保管拠点を<a href="{{ route('locations.index') }}" class="text-indigo-600 hover:underline font-medium">拠点マスター</a>から選択してください（私物の自宅保管などは「未指定」のまま登録できます）。
                                </p>
                            @endif
                        </div>
                        <div id="rental-notice" class="hidden text-[11px] text-sky-800 bg-sky-50 border border-sky-200 p-2.5 rounded-lg mt-1">
                            <span class="font-bold">🏢 イベント会社手配</span>: 保管場所は手配会社による現場直接搬入となるため登録不要です。
                        </div>
                    </div>

                    <!-- 行4: 仕様・注意事項・申し送りメモ -->
                    <div>
                        <label for="equipment_description" class="block font-bold text-slate-700 mb-1">
                            仕様・注意事項・申し送りメモ
                        </label>
                        <textarea id="equipment_description" name="description" rows="2" maxlength="2000"
                            placeholder="例: パイプテント用の杭とロープは別箱保管。マイク用単三電池は予備4本同封。"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('description') }}</textarea>
                    </div>
                </div>

                <!-- モーダルフッター（固定） -->
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-2 shrink-0">
                    <x-ui.button variant="secondary" size="sm" type="button" class="btn-close-modal">
                        キャンセル
                    </x-ui.button>
                    <x-ui.button variant="primary" size="sm" type="submit" id="equipment-form-submit-btn">
                        備品を登録する
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@can('create', App\Models\Equipment::class)
<!-- 3. 機材CSVインポートモーダル -->
<div id="equipmentCsvImportModal"
    class="hidden fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="equipment-csv-modal-title">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" data-close-target="#equipmentCsvImportModal"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl max-w-lg w-full shadow-xl border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 id="equipment-csv-modal-title" class="text-sm font-bold text-slate-900 flex items-center space-x-1.5">
                    <span>📤</span>
                    <span>資機材・備品 CSV一括取込</span>
                </h3>
                <button type="button" data-close-target="#equipmentCsvImportModal" class="text-slate-400 hover:text-slate-600 font-bold text-sm cursor-pointer">✕</button>
            </div>

            <form method="POST" action="{{ route('equipments.import-csv') }}" enctype="multipart/form-data" class="p-4 space-y-3 text-xs">
                @csrf
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-[11px] text-slate-600 space-y-1">
                    <p class="font-bold text-slate-700">対応列フォーマット (UTF-8 / Shift-JIS・9列形式):</p>
                    <p class="font-mono text-[10px]">ID, 備品名(必須), 区分, 持ち出しルール, 基準数量, 単位, 概数フラグ, 定常保管場所, 備考</p>
                    <p>※区分・ルールは内部キー（committee/rental/personal）と日本語名称の双方に対応しています。</p>
                    <p>※定常保管場所には拠点マスターに登録済みの拠点名を指定してください（未指定・レンタル時は空欄）。</p>
                </div>

                <div>
                    <label for="equipment_csv_file" class="block font-bold text-slate-700 mb-1">
                        CSVファイル選択 <span class="text-red-500">*</span>
                    </label>
                    <input type="file"
                        id="equipment_csv_file"
                        name="csv_file"
                        accept=".csv,text/csv"
                        required
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 file:mr-3 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" data-close-target="#equipmentCsvImportModal" class="px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer">
                        キャンセル
                    </button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition cursor-pointer shadow-xs">
                        📤 取り込み実行
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@vite(['resources/js/equipments.js'])
</x-layouts.app>
