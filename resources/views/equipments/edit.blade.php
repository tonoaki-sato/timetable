<x-layouts.app :title="$editTitle">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$equipment->name . ' の編集'"
        icon="✏️"
        :description="$editDesc">
        <x-slot:breadcrumbs>
            @if($isTimetableScoped)
                <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>⏱️</span>
                    <span>運行情報一覧</span>
                </a>
                <span class="text-slate-300">/</span>
                <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>📅</span>
                    <span>{{ $timetable->name }}</span>
                </a>
                <span class="text-slate-300">/</span>
                <a href="{{ $indexUrl }}" class="hover:text-indigo-600 transition truncate">
                    📦 機材・備品一覧
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">✏️ {{ $equipment->name }} の編集</span>
            @else
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>🏮</span>
                    <span>本部</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-500">🗂️ マスター管理</span>
                <span class="text-slate-300">/</span>
                <a href="{{ route('equipments.index') }}" class="hover:text-indigo-600 transition truncate">
                    📦 機材・備品一覧
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">✏️ {{ $equipment->name }} の編集</span>
            @endif
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ $indexUrl }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>機材・備品一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    @if($isTimetableScoped)
        <x-timetable-context-bar :timetable="$timetable" active="logistics" />
    @endif

    <!-- 入力エラーメッセージ -->
    @if ($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl space-y-1">
            <div class="font-bold flex items-center space-x-1">
                <span>⚠️</span>
                <span>入力内容を確認してください</span>
            </div>
            @foreach ($errors->all() as $error)
                <div>・{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="max-w-4xl mx-auto space-y-3.5">
        <!-- フォームカード（高密度コンパクトグリッド） -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6">
            <form method="POST" action="{{ $updateUrl }}" class="space-y-4">
                @csrf
                @method('PUT')

                <!-- 行1: 資機材名 & 所有・調達区分 -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div class="md:col-span-2">
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                            資機材名 <span class="text-rose-600">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name', $equipment->name) }}" required
                            placeholder="例: カラーコーン一式、足踏み式給水設備、パイプテント 2K×3K"
                            class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="type" class="block text-xs font-bold text-slate-700 mb-1">
                            所有・調達区分 <span class="text-rose-600">*</span>
                        </label>
                        <select id="type" name="type" required
                            class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach(\App\Enums\EquipmentType::cases() as $typeCase)
                                <option value="{{ $typeCase->value }}" {{ old('type', $equipment->type) === $typeCase->value ? 'selected' : '' }}>
                                    {{ $typeCase->icon() }} {{ $typeCase->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 行2: 数量/単位/概数 & 持出ルール -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            基準数量 & 単位 <span class="text-rose-600">*</span>
                        </label>
                        <div class="space-y-1.5">
                            <div class="flex items-center space-x-1.5">
                                <input type="number" id="quantity" name="quantity" value="{{ old('quantity', $equipment->quantity) }}" min="1" required
                                    class="w-24 px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <input type="text" id="unit" name="unit" value="{{ old('unit', $equipment->unit) }}" required
                                    placeholder="個/台/張"
                                    class="flex-1 min-w-0 px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <label class="inline-flex items-center space-x-1.5 cursor-pointer">
                                <input type="checkbox" id="is_approximate_quantity" name="is_approximate_quantity" value="1"
                                    {{ old('is_approximate_quantity', $equipment->is_approximate_quantity) ? 'checked' : '' }}
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-[11px] font-bold text-slate-600">概数（約○個）で管理</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label for="checkout_rule" class="block text-xs font-bold text-slate-700 mb-1">
                            持出・利用ルール <span class="text-rose-600">*</span>
                        </label>
                        <select id="checkout_rule" name="checkout_rule" required
                            class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach(\App\Enums\EquipmentCheckoutRule::cases() as $ruleCase)
                                <option value="{{ $ruleCase->value }}" {{ old('checkout_rule', $equipment->checkout_rule) === $ruleCase->value ? 'selected' : '' }}>
                                    {{ $ruleCase->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 行3: 定常保管場所 & 仕様・注意事項メモ -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <div id="storage-container">
                        <label for="storage_location" class="block text-xs font-bold text-slate-700 mb-1">定常保管場所</label>
                        <div id="storage-location-field">
                            <input type="text" id="storage_location" name="storage_location" value="{{ old('storage_location', $equipment->storage_location) }}"
                                placeholder="例: 番所地下倉庫、本部テント内、各詰所など"
                                class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div id="rental-notice" class="hidden text-[11px] text-sky-800 bg-sky-50 border border-sky-200 p-2 rounded-lg mt-1">
                            <span class="font-bold">🏢 イベント会社手配</span>: 保管場所は手配会社による現場直接搬入となるため登録不要です。
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-bold text-slate-700 mb-1">仕様・注意事項・申し送りメモ</label>
                        <textarea id="description" name="description" rows="2"
                            placeholder="例: パイプテント用の杭とロープは別箱保管。マイク用単三電池は予備4本同封。"
                            class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('description', $equipment->description) }}</textarea>
                    </div>
                </div>

                <!-- フォーム送信ボタン -->
                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                    <a href="{{ $indexUrl }}"
                        class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition">
                        キャンセル
                    </a>
                    <button type="submit"
                        class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-sm transition cursor-pointer">
                        変更内容を保存する
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@vite(['resources/js/equipments.js'])
</x-layouts.app>
