<x-layouts.app :title="$equipment->name . ' の編集'">
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- パンくず -->
    <div class="flex items-center space-x-2 text-xs text-slate-500">
        <a href="{{ route('equipments.index') }}" class="hover:text-indigo-600 font-bold">資材一覧</a>
        <span>/</span>
        <a href="{{ route('equipments.show', $equipment) }}" class="hover:text-indigo-600 font-bold">{{ $equipment->name }}</a>
        <span>/</span>
        <span class="text-slate-700">編集</span>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900 flex items-center space-x-2">
                    <span>✏️</span>
                    <span>資材情報の編集</span>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">{{ $equipment->name }} の基本情報を更新します。</p>
            </div>

            <!-- 削除ボタンフォーム -->
            <form method="POST" action="{{ route('equipments.destroy', $equipment) }}" onsubmit="return confirm('本当にこの資材を削除しますか？紐づくすべての搬出入計画ステップも削除されます。')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 border border-rose-200 transition">
                    🗑️ 資材を削除
                </button>
            </form>
        </div>

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

        <form method="POST" action="{{ route('equipments.update', $equipment) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- 資材区分 -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">資材区分 <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="p-3 rounded-xl border flex items-center space-x-2 cursor-pointer transition has-checked:border-emerald-600 has-checked:bg-emerald-50/40 border-slate-200">
                        <input type="radio" name="type" value="committee" {{ old('type', $equipment->type) === 'committee' ? 'checked' : '' }}
                               onchange="toggleType(this.value)"
                               class="text-emerald-600 focus:ring-emerald-500">
                        <span class="text-xs font-bold text-slate-800">🏛️ 実行委員会管理</span>
                    </label>
                    <label class="p-3 rounded-xl border flex items-center space-x-2 cursor-pointer transition has-checked:border-sky-600 has-checked:bg-sky-50/40 border-slate-200">
                        <input type="radio" name="type" value="rental" {{ old('type', $equipment->type) === 'rental' ? 'checked' : '' }}
                               onchange="toggleType(this.value)"
                               class="text-sky-600 focus:ring-sky-500">
                        <span class="text-xs font-bold text-slate-800">🏢 イベント会社レンタル</span>
                    </label>
                </div>
            </div>

            <!-- 資材名 -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 mb-1">資材名 <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $equipment->name) }}" required
                       class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 p-2.5">
            </div>

            <!-- 数量 & 単位 & 担当班 -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="quantity" class="block text-xs font-bold text-slate-700 mb-1">総数量 <span class="text-rose-500">*</span></label>
                    <input type="number" id="quantity" name="quantity" value="{{ old('quantity', $equipment->quantity) }}" min="1" required
                           class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 p-2.5">
                </div>
                <div>
                    <label for="unit" class="block text-xs font-bold text-slate-700 mb-1">単位 <span class="text-rose-500">*</span></label>
                    <input type="text" id="unit" name="unit" value="{{ old('unit', $equipment->unit) }}" required
                           class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 p-2.5">
                </div>
                <div>
                    <label for="lane_id" class="block text-xs font-bold text-slate-700 mb-1">担当班</label>
                    <select id="lane_id" name="lane_id" class="w-full text-xs sm:text-sm rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 p-2.5">
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
            <div id="section-committee" class="{{ $equipment->isCommittee() ? '' : 'hidden' }} space-y-2 p-3 rounded-xl bg-emerald-50/50 border border-emerald-200">
                <label for="storage_location" class="block text-xs font-bold text-emerald-900">定常保管場所（自前機材）</label>
                <input type="text" id="storage_location" name="storage_location" value="{{ old('storage_location', $equipment->storage_location) }}"
                       placeholder="番所地下倉庫、コミュニティルーム等"
                       class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 p-2 bg-white">
            </div>

            <!-- イベント会社（レンタル） -->
            <div id="section-rental" class="{{ $equipment->isRental() ? '' : 'hidden' }} space-y-3 p-3 rounded-xl bg-sky-50/50 border border-sky-200">
                <div class="font-bold text-xs text-sky-900">イベント会社情報</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="event_company_name" class="block text-[11px] font-bold text-slate-700 mb-1">イベント会社名</label>
                        <input type="text" id="event_company_name" name="event_company_name" value="{{ old('event_company_name', $equipment->event_company_name) }}"
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500 p-2 bg-white">
                    </div>
                    <div>
                        <label for="event_company_contact" class="block text-[11px] font-bold text-slate-700 mb-1">連絡先・電話番号</label>
                        <input type="text" id="event_company_contact" name="event_company_contact" value="{{ old('event_company_contact', $equipment->event_company_contact) }}"
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500 p-2 bg-white">
                    </div>
                </div>
            </div>

            <!-- 備考 -->
            <div>
                <label for="description" class="block text-xs font-bold text-slate-700 mb-1">備考・仕様メモ</label>
                <textarea id="description" name="description" rows="3"
                          class="w-full text-xs rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 p-2.5">{{ old('description', $equipment->description) }}</textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                <a href="{{ route('equipments.show', $equipment) }}" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                    キャンセル
                </a>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-500 shadow-sm transition">
                    更新を保存する
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleType(type) {
        if (type === 'committee') {
            document.getElementById('section-committee').classList.remove('hidden');
            document.getElementById('section-rental').classList.add('hidden');
        } else {
            document.getElementById('section-committee').classList.add('hidden');
            document.getElementById('section-rental').classList.remove('hidden');
        }
    }
</script>
</x-layouts.app>

