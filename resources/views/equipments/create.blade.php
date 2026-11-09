<x-layouts.app :title="'新規資機材の追加 - ' . $timetable->name">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        title="新規資材の登録"
        icon="➕"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）運行に使用する自前保有備品、提携イベント会社レンタル機材、または個人持ち込み私物を台帳に登録します。'">
        <x-slot:breadcrumbs>
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
            <a href="{{ route('timetables.equipments.index', $timetable) }}" class="hover:text-indigo-600 transition truncate">
                📦 資材管理
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">➕ 新規資材の登録</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.equipments.index', $timetable) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>資材管理に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="equipments" />

    <!-- 入力エラーメッセージ -->
    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl space-y-1">
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
        <!-- フォームカード -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 space-y-6">
            <form method="POST" action="{{ route('timetables.equipments.store', $timetable) }}" class="space-y-6">
                @csrf

                <!-- 資機材基本情報 -->
                <div class="space-y-4">
                    <!-- 資機材区分（自前 / レンタル / 私物） -->
                    <div>
                        <label for="type" class="block text-xs font-bold text-slate-700 mb-1">
                            所有・調達区分 <span class="text-rose-600">*</span>
                        </label>
                        <select id="type" name="type" required
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach(\App\Enums\EquipmentType::cases() as $typeCase)
                                <option value="{{ $typeCase->value }}" {{ old('type', 'committee') === $typeCase->value ? 'selected' : '' }}>
                                    {{ $typeCase->icon() }} {{ $typeCase->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 資機材名 -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                            資機材名 <span class="text-rose-600">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            placeholder="例: カラーコーン一式（バー・錘含む）、足踏み式給水設備、パイプテント 2K×3K、折りたたみ台車"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="text-[10px] text-slate-400 mt-1">※ 搬出入指示書や配置一覧に表示される資機材名称です。</p>
                    </div>

                    <!-- 数量 & 単位 & 概数フラグ -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 bg-slate-50/70 rounded-xl border border-slate-200">
                        <div>
                            <label for="quantity" class="block text-xs font-bold text-slate-700 mb-1">基準数量 <span class="text-rose-600">*</span></label>
                            <input type="number" id="quantity" name="quantity" value="{{ old('quantity', 1) }}" min="1" required
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="unit" class="block text-xs font-bold text-slate-700 mb-1">単位 <span class="text-rose-600">*</span></label>
                            <input type="text" id="unit" name="unit" value="{{ old('unit', '個') }}" required
                                placeholder="例: 本, 台, 張, セット, 箱, 式"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="flex items-center pt-5">
                            <label class="flex items-center space-x-2 text-xs font-bold text-slate-700 cursor-pointer">
                                <input type="checkbox" name="is_approximate_quantity" value="1" {{ old('is_approximate_quantity') ? 'checked' : '' }}
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                <span>概数管理（「約 ○ 個」と表記）</span>
                            </label>
                        </div>
                    </div>

                    <!-- 持出ルール & 主担当班 -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="checkout_rule" class="block text-xs font-bold text-slate-700 mb-1">持出・利用ルール</label>
                            <select id="checkout_rule" name="checkout_rule"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach(\App\Enums\EquipmentCheckoutRule::cases() as $ruleCase)
                                    <option value="{{ $ruleCase->value }}" {{ old('checkout_rule', 'free') === $ruleCase->value ? 'selected' : '' }}>
                                        {{ $ruleCase->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="lane_id" class="block text-xs font-bold text-slate-700 mb-1">主担当班（レーン）</label>
                            <select id="lane_id" name="lane_id"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">（未定・共用資機材）</option>
                                @foreach($lanes as $lane)
                                    <option value="{{ $lane->id }}" {{ old('lane_id') == $lane->id ? 'selected' : '' }}>
                                        {{ $lane->icon ?: '🏷️' }} {{ $lane->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 保管場所（自前・私物用） -->
                    <div id="section-committee" class="space-y-4 pt-2">
                        <div>
                            <label for="storage_location" class="block text-xs font-bold text-slate-700 mb-1">定常保管場所</label>
                            <input type="text" id="storage_location" name="storage_location" value="{{ old('storage_location', '番所地下倉庫') }}"
                                placeholder="例: 番所地下倉庫、番所倉庫、コミュニティルーム、自宅"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <!-- クイック選択 -->
                            <div class="flex items-center space-x-1.5 mt-1.5 flex-wrap gap-1">
                                <span class="text-[10px] text-slate-400">候補入力:</span>
                                @foreach(\App\Models\Equipment::STORAGE_LOCATIONS as $locName)
                                    <button type="button" data-set-storage-location="{{ $locName }}"
                                        class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-600 transition cursor-pointer">
                                        {{ $locName }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label for="storage_detail" class="block text-xs font-bold text-slate-700 mb-1">棚番・置場詳細・私物所有者</label>
                            <input type="text" id="storage_detail" name="storage_detail" value="{{ old('storage_detail') }}"
                                placeholder="例: 奥側スチールラック中段、西側コンテナ、所有者: 鈴木（090-XXXX-XXXX）"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- 仕様・備考メモ -->
                    <div>
                        <label for="description" class="block text-xs font-bold text-slate-700 mb-1">仕様メモ・注意事項</label>
                        <textarea id="description" name="description" rows="3"
                            placeholder="例: 前日に清掃点検が必要。給水用と排水用でポリタンク（20L）が4個付属。"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('description') }}</textarea>
                    </div>

                    <!-- 表示順 -->
                    <div>
                        <label for="display_order" class="block text-xs font-bold text-slate-700 mb-1">表示順（小さい順）</label>
                        <input type="number" id="display_order" name="display_order" value="{{ old('display_order', 10) }}" step="5"
                            class="w-32 px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- フォーム送信ボタン (班担当管理統一スタイル) -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-200">
                    <a href="{{ route('timetables.equipments.index', $timetable) }}"
                        class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                        キャンセル
                    </a>
                    <button type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                        資機材を登録する
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@vite(['resources/js/equipments.js'])
</x-layouts.app>
