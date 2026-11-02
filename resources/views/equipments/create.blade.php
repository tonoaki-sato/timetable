<x-layouts.app title="新規資材の登録">
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ヘッダーエリア -->
    <x-page-header
        title="新規資材の登録"
        icon="📦"
        description="自前保有備品、提携イベント会社のレンタル機材、または個人持ち込み私物を台帳に登録します。">
        <x-slot:breadcrumbs>
            <a href="{{ route('equipments.index') }}" class="hover:underline">資材管理</a>
            <span>/</span>
            <span class="text-slate-800 font-bold">新規登録</span>
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <a href="{{ route('equipments.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition">
                ← 資材管理に戻る
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
            <div class="font-bold">入力内容に不備があります：</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- フォームカード -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-6 sm:p-8 space-y-6">
        <form method="POST" action="{{ route('equipments.store') }}" class="space-y-6">
            @csrf

            <!-- 資材基本情報 -->
            <div class="space-y-4">
                <!-- 資材区分（自前 / レンタル / 私物） -->
                <div>
                    <label for="type" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">資材区分 <span class="text-rose-500">*</span></label>
                    <select id="type" name="type" required
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Models\Equipment::TYPES as $key => $label)
                            <option value="{{ $key }}" {{ old('type', 'committee') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 資材名 -->
                <div>
                    <label for="name" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">資材・機材名 <span class="text-rose-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           placeholder="例: カラーコーン一式（バー・錘含む）、足踏み式給水設備、パイプテント 2K×3K、折りたたみ台車"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- 持出ルール & 担当班 -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div>
                        <label for="checkout_rule" class="block text-[11px] font-bold text-slate-700 mb-1">持出・利用ルール <span class="text-rose-500">*</span></label>
                        <select id="checkout_rule" name="checkout_rule" required
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach(\App\Models\Equipment::CHECKOUT_RULES as $key => $label)
                                <option value="{{ $key }}" {{ old('checkout_rule', 'free') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="lane_id" class="block text-[11px] font-bold text-slate-700 mb-1">主担当班（レーン）</label>
                        <select id="lane_id" name="lane_id"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">担当班なし（本部共通）</option>
                            @foreach($lanes as $lane)
                                <option value="{{ $lane->id }}" {{ old('lane_id') == $lane->id ? 'selected' : '' }}>
                                    {{ $lane->icon }} {{ $lane->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 数量 & 単位 & 概数フラグ -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                    <div>
                        <label for="quantity" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">総数量 <span class="text-rose-500">*</span></label>
                        <input type="number" id="quantity" name="quantity" value="{{ old('quantity', 1) }}" min="1" required
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    </div>

                    <div>
                        <label for="unit" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">単位 <span class="text-rose-500">*</span></label>
                        <input type="text" id="unit" name="unit" value="{{ old('unit', '個') }}" required
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="pb-2">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="is_approximate_quantity" value="1" {{ old('is_approximate_quantity') ? 'checked' : '' }}
                                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-xs font-bold text-slate-700">概数管理（「約○個」として表示）</span>
                        </label>
                    </div>
                </div>

                <!-- 自前・私物用: 保管場所 -->
                <div id="section-committee" class="space-y-3 p-3.5 rounded-xl bg-emerald-50/50 border border-emerald-200">
                    <div>
                        <label for="storage_location" class="block text-[11px] sm:text-xs font-bold text-emerald-900 mb-1">
                            定常保管場所（自前機材・私物）
                        </label>
                        <div class="flex items-center space-x-2 flex-wrap gap-y-1.5">
                            <input type="text" id="storage_location" name="storage_location" value="{{ old('storage_location', '番所地下倉庫') }}"
                                   placeholder="番所地下倉庫、番所倉庫、コミュニティルーム、自宅等"
                                   class="flex-1 min-w-[200px] px-3 py-2 bg-white border border-emerald-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <button type="button" data-set-storage-location="番所地下倉庫"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 hover:bg-emerald-200 transition cursor-pointer">
                                番所地下倉庫
                            </button>
                            <button type="button" data-set-storage-location="番所倉庫"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 hover:bg-emerald-200 transition cursor-pointer">
                                番所倉庫
                            </button>
                            <button type="button" data-set-storage-location="コミュニティルーム"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 hover:bg-emerald-200 transition cursor-pointer">
                                コミュニティルーム
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="storage_detail" class="block text-[11px] font-bold text-emerald-900 mb-1">
                            定位置詳細・棚番メモ
                        </label>
                        <input type="text" id="storage_detail" name="storage_detail" value="{{ old('storage_detail') }}"
                               placeholder="例: 地下倉庫入口右手・台車ラック、本陣受付裏 備品棚2段目"
                               class="w-full px-3 py-2 bg-white border border-emerald-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <!-- 備考 -->
                <div>
                    <label for="description" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">備考・仕様メモ</label>
                    <textarea id="description" name="description" rows="2" placeholder="仕様や付属品、取り扱い上の注意点など"
                              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('description') }}</textarea>
                </div>
            </div>

            <!-- 送信ボタン -->
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <span class="text-[11px] text-slate-400">※登録完了後、資材詳細画面から搬出入計画（パターン一括適用・個別ステップ）を設定できます。</span>
                <div class="flex items-center space-x-3 shrink-0 self-end sm:self-auto">
                    <a href="{{ route('equipments.index') }}"
                       class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition whitespace-nowrap shrink-0">
                        キャンセル
                    </a>
                    <button type="submit"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5 cursor-pointer whitespace-nowrap shrink-0">
                        <span>💾</span>
                        <span>資材を登録する</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>
@vite(['resources/js/equipments.js'])
</x-layouts.app>
