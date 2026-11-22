<x-layouts.app title="出展・出店者マスター">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

        <!-- ページヘッダー -->
        <x-page-header
            title="出展・出店者マスター"
            icon="🎪"
            description="ござ市出店者、特設企画、企業協賛ブース等の出展・出店者マスター台帳を管理します。">
            <x-slot:breadcrumbs>
                <span class="text-slate-500">🗂️ マスター管理</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">🎪 出展・出店者マスター</span>
            </x-slot:breadcrumbs>

            <x-slot:actions>
                <x-ui.button variant="header-action" :href="route('vendors.export-csv')" title="出展・出店者一覧をCSVダウンロード">
                    <span>📥</span>
                    <span>CSV出力</span>
                </x-ui.button>
                <x-ui.button variant="header-action" id="btn-open-vendor-csv-import" data-toggle-target="#vendorCsvImportModal" title="CSVファイルから一括取り込み">
                    <span>📤</span>
                    <span>CSV取込</span>
                </x-ui.button>
                <x-ui.button variant="header-action" id="btn-open-create-vendor" data-store-url="{{ route('vendors.store') }}">
                    <span>➕</span>
                    <span>新規登録</span>
                </x-ui.button>
            </x-slot:actions>
        </x-page-header>

        <x-ui.validation-errors title="CSV取り込みまたは入力内容にエラーがあります。" />

        <!-- 検索・フィルターバー -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 shadow-2xs">
            <form method="GET" action="{{ route('vendors.index') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                @if(request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif
                @if(request('direction'))
                    <input type="hidden" name="direction" value="{{ request('direction') }}">
                @endif
                <div class="flex items-center space-x-2 flex-1 max-w-lg">
                    <div class="relative w-full">
                        <input type="text"
                            name="search"
                            value="{{ $filter->search ?? '' }}"
                            placeholder="出展・出店者名、担当者、連絡先で検索..."
                            class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <span class="absolute left-2.5 top-2 text-slate-400 text-xs">🔍</span>
                    </div>
                    <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition shrink-0 cursor-pointer">
                        検索
                    </button>
                    @if(!empty($filter->search))
                        <a href="{{ route('vendors.index') }}" class="px-2.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-600 rounded-lg text-xs font-medium transition shrink-0">
                            クリア
                        </a>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 shrink-0">
                    <x-ui.per-page-select :default="15" />
                    <div class="text-xs text-slate-500 font-mono text-right shrink-0">
                        全 <span class="font-bold text-slate-700">{{ $vendors->total() }}</span> 件中
                        {{ $vendors->firstItem() ?? 0 }}〜{{ $vendors->lastItem() ?? 0 }} 件を表示
                    </div>
                </div>
            </form>
        </div>

        <!-- データテーブル -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                            <x-ui.sortable-th column="name" label="出展・出店者名" defaultSort="id" class="py-2.5 px-3" />
                            <x-ui.sortable-th column="contact_person" label="窓口担当者" defaultSort="id" class="py-2.5 px-3" />
                            <x-ui.sortable-th column="contact" label="連絡先" defaultSort="id" class="py-2.5 px-3" />
                            <th class="py-2.5 px-3">特記事項・備考</th>
                            <th class="py-2.5 px-3 text-right">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($vendors as $vendor)
                            <tr class="hover:bg-slate-50/50 transition">
                                <!-- 名前（詳細モーダル起動） -->
                                <td class="py-2.5 px-3 font-bold">
                                    <button type="button"
                                        class="btn-view-vendor font-bold text-slate-800 hover:text-indigo-600 hover:underline transition cursor-pointer text-left"
                                        data-vendor-id="{{ $vendor->id }}"
                                        data-vendor-name="{{ $vendor->name }}"
                                        data-contact-person="{{ $vendor->contact_person ?? '' }}"
                                        data-contact="{{ $vendor->contact ?? '' }}"
                                        data-notes="{{ $vendor->notes ?? '' }}">
                                        {{ $vendor->name }}
                                    </button>
                                </td>

                                <!-- 担当者 -->
                                <td class="py-2.5 px-3 text-slate-700">
                                    {{ $vendor->contact_person ?: '—' }}
                                </td>

                                <!-- 連絡先 -->
                                <td class="py-2.5 px-3 text-slate-700 font-mono">
                                    {{ $vendor->contact ?: '—' }}
                                </td>

                                <!-- 備考 -->
                                <td class="py-2.5 px-3 text-slate-600 max-w-xs truncate" title="{{ $vendor->notes }}">
                                    {{ $vendor->notes ?: '—' }}
                                </td>

                                <!-- 操作 -->
                                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center space-x-1.5">
                                        <button type="button"
                                            class="btn-edit-vendor inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                            title="出展者を編集"
                                            data-vendor-id="{{ $vendor->id }}"
                                            data-update-url="{{ route('vendors.update', $vendor) }}"
                                            data-name="{{ $vendor->name }}"
                                            data-contact-person="{{ $vendor->contact_person ?? '' }}"
                                            data-contact="{{ $vendor->contact ?? '' }}"
                                            data-notes="{{ $vendor->notes ?? '' }}">
                                            <span>✏️</span>
                                            <span>編集</span>
                                        </button>

                                        <form method="POST" action="{{ route('vendors.destroy', $vendor) }}" class="inline-block form-delete-vendor">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                class="btn-delete-vendor inline-flex items-center space-x-1 px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                                title="出展者を削除"
                                                data-vendor-name="{{ $vendor->name }}">
                                                <span>🗑️</span>
                                                <span>削除</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    出展・出店者が登録されていません。「新規登録」ボタンから登録してください。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- ページネーション -->
            @if($vendors->hasPages())
                <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
                    {{ $vendors->links() }}
                </div>
            @endif
        </div>

    </div>

    <!-- 1. 閲覧専用詳細モーダル (SC-VENDOR-DETAIL) -->
    <div id="modal-vendor-detail" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity modal-backdrop"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center space-x-2">
                        <span class="text-xl">🎪</span>
                        <h3 class="text-sm font-bold text-slate-800" id="detail-modal-title">出展・出店者詳細</h3>
                    </div>
                    <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 transition cursor-pointer p-1">
                        ✕
                    </button>
                </div>

                <div class="p-4 sm:p-5 space-y-3.5 text-xs">
                    <!-- 基本情報テーブル -->
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-200/80 space-y-2">
                        <div class="flex items-start">
                            <span class="w-24 text-slate-500 shrink-0 font-medium">出展者名:</span>
                            <span class="text-slate-900 font-bold" id="detail-vendor-name"></span>
                        </div>
                        <div class="flex items-start">
                            <span class="w-24 text-slate-500 shrink-0 font-medium">窓口担当者:</span>
                            <span class="text-slate-800" id="detail-contact-person"></span>
                        </div>
                        <div class="flex items-start">
                            <span class="w-24 text-slate-500 shrink-0 font-medium">連絡先:</span>
                            <span class="text-slate-800 font-mono" id="detail-contact"></span>
                        </div>
                        <div class="flex items-start">
                            <span class="w-24 text-slate-500 shrink-0 font-medium">特記事項:</span>
                            <span class="text-slate-700 whitespace-pre-wrap" id="detail-notes"></span>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button type="button" class="btn-close-modal px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer shadow-2xs">
                        閉じる
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. 出展・出店者 登録・編集モーダル (SC-VENDOR-FORM) -->
    <div id="modal-vendor-form" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity modal-backdrop"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-200">
                <form id="form-vendor-mutation" method="POST" action="{{ route('vendors.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="form-method" value="POST">

                    <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                        <div class="flex items-center space-x-2">
                            <span class="text-xl">✏️</span>
                            <h3 class="text-sm font-bold text-slate-800" id="form-modal-title">出展・出店者の新規登録</h3>
                        </div>
                        <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 transition cursor-pointer p-1">
                            ✕
                        </button>
                    </div>

                    <div class="p-4 sm:p-5 space-y-3.5 text-xs">
                        <!-- 出展・出店者名 -->
                        <div>
                            <label for="vendor_name" class="block font-bold text-slate-700 mb-1">
                                出展・出店者名・屋号 <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                name="name"
                                id="vendor_name"
                                required
                                placeholder="例: 水浅青果、ハングリータイガー、帷子茶屋"
                                class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>

                        <!-- 窓口担当者名 -->
                        <div>
                            <label for="vendor_contact_person" class="block font-bold text-slate-700 mb-1">
                                窓口担当者名・代表者名
                            </label>
                            <input type="text"
                                name="contact_person"
                                id="vendor_contact_person"
                                placeholder="例: 水浅 太郎"
                                class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>

                        <!-- 連絡先 -->
                        <div>
                            <label for="vendor_contact" class="block font-bold text-slate-700 mb-1">
                                連絡先（電話番号等）
                            </label>
                            <input type="text"
                                name="contact"
                                id="vendor_contact"
                                placeholder="例: 090-1234-5678"
                                class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>

                        <!-- 特記事項・備考 -->
                        <div>
                            <label for="vendor_notes" class="block font-bold text-slate-700 mb-1">
                                特記事項・備考
                            </label>
                            <textarea
                                name="notes"
                                id="vendor_notes"
                                rows="3"
                                placeholder="搬入車両情報、電源要請、複数コマ展開などのメモ"
                                class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"></textarea>
                        </div>
                    </div>

                    <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-2">
                        <button type="button" class="btn-close-modal px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer shadow-2xs">
                            キャンセル
                        </button>
                        <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition cursor-pointer shadow-2xs">
                            💾 保存する
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CSVインポートモーダル -->
    <div id="vendorCsvImportModal"
        class="hidden fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="vendor-csv-modal-title">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" data-close-target="#vendorCsvImportModal"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl max-w-lg w-full shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <h3 id="vendor-csv-modal-title" class="text-sm font-bold text-slate-900 flex items-center space-x-1.5">
                        <span>📤</span>
                        <span>出展・出店者 CSV一括取込</span>
                    </h3>
                    <button type="button" data-close-target="#vendorCsvImportModal" class="text-slate-400 hover:text-slate-600 font-bold text-sm cursor-pointer">✕</button>
                </div>

                <form method="POST" action="{{ route('vendors.import-csv') }}" enctype="multipart/form-data" class="p-4 space-y-3 text-xs">
                    @csrf
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-[11px] text-slate-600 space-y-1">
                        <p class="font-bold text-slate-700">対応列フォーマット (UTF-8 / Shift-JIS):</p>
                        <p class="font-mono text-[10px]">ID, 出展・出店者名(必須), 窓口担当者, 連絡先, 特記事項・備考</p>
                        <p>※既存のIDまたは同名の出展者がある場合は上書き更新、それ以外は新規登録されます。</p>
                    </div>

                    <div>
                        <label for="vendor_csv_file" class="block font-bold text-slate-700 mb-1">
                            CSVファイル選択 <span class="text-red-500">*</span>
                        </label>
                        <input type="file"
                            id="vendor_csv_file"
                            name="csv_file"
                            accept=".csv,text/csv"
                            required
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 file:mr-3 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-end space-x-2">
                        <button type="button" data-close-target="#vendorCsvImportModal" class="px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer">
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

    @vite(['resources/js/vendors.js'])
</x-layouts.app>
