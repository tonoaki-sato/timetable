<x-layouts.app :title="$equipment->name . ' の編集'">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ヘッダーエリア -->
    <x-page-header
        title="資材情報の編集"
        icon="✏️"
        :description="$equipment->name . ' の基本情報を更新します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('equipments.index') }}" class="hover:underline">資材一覧</a>
            <span>/</span>
            <a href="{{ route('equipments.show', $equipment) }}" class="hover:underline">{{ $equipment->name }}</a>
            <span>/</span>
            <span class="text-slate-800 font-bold">編集</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('equipments.show', $equipment) }}"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                <span>← 詳細に戻る</span>
            </a>
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
                <label class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">資材区分 <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="p-3 rounded-xl border flex items-center space-x-2 cursor-pointer transition has-checked:border-emerald-600 has-checked:bg-emerald-50/40 border-slate-200">
                        <input type="radio" name="type" value="committee" {{ old('type', $equipment->type) === 'committee' ? 'checked' : '' }}
                               class="text-emerald-600 focus:ring-emerald-500">
                        <span class="text-xs font-bold text-slate-800">🏛️ 実行委員会管理</span>
                    </label>
                    <label class="p-3 rounded-xl border flex items-center space-x-2 cursor-pointer transition has-checked:border-sky-600 has-checked:bg-sky-50/40 border-slate-200">
                        <input type="radio" name="type" value="rental" {{ old('type', $equipment->type) === 'rental' ? 'checked' : '' }}
                               class="text-sky-600 focus:ring-sky-500">
                        <span class="text-xs font-bold text-slate-800">🏢 イベント会社レンタル</span>
                    </label>
                </div>
            </div>

            <!-- 資材名 -->
            <div>
                <label for="name" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">資材名 <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $equipment->name) }}" required
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- 数量 & 単位 & 担当班 -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
                <div>
                    <label for="lane_id" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">担当班</label>
                    <select id="lane_id" name="lane_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">担当班なし</option>
                        @foreach($lanes as $lane)
                            <option value="{{ $lane->id }}" {{ old('lane_id', $equipment->lane_id) == $lane->id ? 'selected' : '' }}>
                                {{ $lane->icon }} {{ $lane->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 保管場所（自前） -->
            <div id="section-committee" class="{{ $equipment->isCommittee() ? '' : 'hidden' }} space-y-2 p-3.5 rounded-xl bg-emerald-50/50 border border-emerald-200">
                <label for="storage_location" class="block text-[11px] sm:text-xs font-bold text-emerald-900">定常保管場所（自前機材）</label>
                <input type="text" id="storage_location" name="storage_location" value="{{ old('storage_location', $equipment->storage_location) }}"
                       placeholder="番所地下倉庫、コミュニティルーム等"
                       class="w-full px-3 py-2 bg-white border border-emerald-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <!-- イベント会社（レンタル） -->
            <div id="section-rental" class="{{ $equipment->isRental() ? '' : 'hidden' }} space-y-3 p-3.5 rounded-xl bg-sky-50/50 border border-sky-200">
                <div class="font-bold text-xs text-sky-900">イベント会社情報</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="event_company_name" class="block text-[11px] font-bold text-slate-700 mb-1">イベント会社名</label>
                        <input type="text" id="event_company_name" name="event_company_name" value="{{ old('event_company_name', $equipment->event_company_name) }}"
                               class="w-full px-3 py-2 bg-white border border-sky-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>
                    <div>
                        <label for="event_company_contact" class="block text-[11px] font-bold text-slate-700 mb-1">連絡先・電話番号</label>
                        <input type="text" id="event_company_contact" name="event_company_contact" value="{{ old('event_company_contact', $equipment->event_company_contact) }}"
                               class="w-full px-3 py-2 bg-white border border-sky-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    </div>
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

