<x-layouts.app :title="$equipment->name . ' の編集'">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ヘッダーエリア -->
    <x-page-header
        title="資材情報の編集"
        icon="✏️"
        :description="$equipment->name . ' の基本情報を更新します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('equipments.index') }}" class="hover:underline">資材管理</a>
            <span>/</span>
            <a href="{{ route('equipments.show', $equipment) }}" class="hover:underline">{{ $equipment->name }}</a>
            <span>/</span>
            <span class="text-slate-800 font-bold">編集</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <!-- 削除ボタンフォーム -->
            <form method="POST" action="{{ route('equipments.destroy', $equipment) }}" data-confirm="本当にこの資材を削除しますか？紐づくすべての搬出入計画ステップも削除されます。" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition cursor-pointer">
                    <span>🗑️</span>
                    <span>資材を削除</span>
                </button>
            </form>
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

    <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-6 sm:p-8 space-y-6">

        <form method="POST" action="{{ route('equipments.update', $equipment) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- 資材区分 -->
            <div>
                <label for="type" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">資材区分 <span class="text-rose-500">*</span></label>
                <select id="type" name="type" required
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(\App\Models\Equipment::TYPES as $key => $label)
                        <option value="{{ $key }}" {{ old('type', $equipment->type) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 資材名 -->
            <div>
                <label for="name" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">資材名 <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $equipment->name) }}" required
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- 持出ルール & 担当班 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <div>
                    <label for="checkout_rule" class="block text-[11px] font-bold text-slate-700 mb-1">持出・利用ルール <span class="text-rose-500">*</span></label>
                    <select id="checkout_rule" name="checkout_rule" required
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Models\Equipment::CHECKOUT_RULES as $key => $label)
                            <option value="{{ $key }}" {{ old('checkout_rule', $equipment->checkout_rule) === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="lane_id" class="block text-[11px] font-bold text-slate-700 mb-1">担当班</label>
                    <select id="lane_id" name="lane_id" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">担当班なし</option>
                        @foreach($lanes as $lane)
                            <option value="{{ $lane->id }}" {{ old('lane_id', $equipment->lane_id) == $lane->id ? 'selected' : '' }}>
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
                    <input type="number" id="quantity" name="quantity" value="{{ old('quantity', $equipment->quantity) }}" min="1" required
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
                <div>
                    <label for="unit" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">単位 <span class="text-rose-500">*</span></label>
                    <input type="text" id="unit" name="unit" value="{{ old('unit', $equipment->unit) }}" required
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="pb-2">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_approximate_quantity" value="1" {{ old('is_approximate_quantity', $equipment->is_approximate_quantity) ? 'checked' : '' }}
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs font-bold text-slate-700">概数管理（「約○個」として表示）</span>
                    </label>
                </div>
            </div>

            <!-- 保管場所（自前・私物） -->
            <div id="section-committee" class="{{ $equipment->isRental() ? 'hidden' : '' }} space-y-3 p-3.5 rounded-xl bg-emerald-50/50 border border-emerald-200">
                <div>
                    <label for="storage_location" class="block text-[11px] sm:text-xs font-bold text-emerald-900 mb-1">定常保管場所（自前機材・私物）</label>
                    <div class="flex items-center space-x-2 flex-wrap gap-y-1.5">
                        <input type="text" id="storage_location" name="storage_location" value="{{ old('storage_location', $equipment->storage_location) }}"
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
                    <label for="storage_detail" class="block text-[11px] font-bold text-emerald-900 mb-1">定位置詳細・棚番メモ</label>
                    <input type="text" id="storage_detail" name="storage_detail" value="{{ old('storage_detail', $equipment->storage_detail) }}"
                           placeholder="例: 地下倉庫入口右手・台車ラック、本陣受付裏 備品棚2段目"
                           class="w-full px-3 py-2 bg-white border border-emerald-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <!-- 備考 -->
            <div>
                <label for="description" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">備考・仕様メモ</label>
                <textarea id="description" name="description" rows="3"
                          class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('description', $equipment->description) }}</textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                <a href="{{ route('equipments.show', $equipment) }}"
                   class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                    キャンセル
                </a>
                <button type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    更新を保存する
                </button>
            </div>
        </form>
    </div>
</div>

@vite(['resources/js/equipments.js'])
</x-layouts.app>
