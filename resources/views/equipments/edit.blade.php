@php
    $isScoped = $isTimetableScoped ?? false;
    $showUrl = $isScoped ? route('timetables.equipments.show', [$timetable, $equipment]) : route('equipments.show', $equipment);
    $updateUrl = $isScoped ? route('timetables.equipments.update', [$timetable, $equipment]) : route('equipments.update', $equipment);
    $destroyUrl = $isScoped ? route('timetables.equipments.destroy', [$timetable, $equipment]) : route('equipments.destroy', $equipment);
    $indexUrl = $isScoped ? route('timetables.equipments.index', $timetable) : route('equipments.index');
    $pageTitle = $isScoped ? ($equipment->name . ' の編集 - ' . $timetable->name) : ($equipment->name . ' の編集 - 資機材管理');
    $pageDesc = $isScoped
        ? '「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）における資機材台帳の基本情報を更新します。'
        : '資機材台帳の基本情報、数量、保管場所、主担当班および持出ルールを更新します。';
@endphp

<x-layouts.app :title="$pageTitle">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$equipment->name . ' の編集'"
        icon="✏️"
        :description="$pageDesc">
        <x-slot:breadcrumbs>
            @if($isScoped)
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
                    📦 備品一覧
                </a>
                <span class="text-slate-300">/</span>
                <a href="{{ $showUrl }}" class="hover:text-indigo-600 transition truncate max-w-[160px]">
                    {{ $equipment->name }}
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">✏️ 編集</span>
            @else
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>🏮</span>
                    <span>本部</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-500">🗂️ マスター管理</span>
                <span class="text-slate-300">/</span>
                <a href="{{ route('equipments.index') }}" class="hover:text-indigo-600 transition truncate">
                    📦 備品一覧
                </a>
                <span class="text-slate-300">/</span>
                <a href="{{ $showUrl }}" class="hover:text-indigo-600 transition truncate max-w-[160px]">
                    {{ $equipment->name }}
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">✏️ 編集</span>
            @endif
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ $showUrl }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>詳細に戻る</span>
            </a>
            @can('delete', $equipment)
                <form method="POST" action="{{ $destroyUrl }}"
                      data-confirm="本当にこの資機材を削除しますか？紐づくすべての搬出入計画ステップも削除されます。" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer whitespace-nowrap shrink-0">
                        <span>🗑️</span>
                        <span>削除</span>
                    </button>
                </form>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if($isScoped)
        <x-timetable-context-bar :timetable="$timetable" active="logistics" />
    @endif

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
            <form method="POST" action="{{ $updateUrl }}" class="space-y-6">
                @csrf
                @method('PUT')

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
                                <option value="{{ $typeCase->value }}" {{ old('type', $equipment->type) === $typeCase->value ? 'selected' : '' }}>
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
                        <input type="text" id="name" name="name" value="{{ old('name', $equipment->name) }}" required
                            placeholder="例: カラーコーン一式（バー・錘含む）、足踏み式給水設備、パイプテント 2K×3K、折りたたみ台車"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="text-[10px] text-slate-400 mt-1">※ 搬出入指示書や配置一覧に表示される資機材名称です。</p>
                    </div>

                    <!-- 数量 & 単位 & 概数フラグ -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 bg-slate-50/70 rounded-xl border border-slate-200">
                        <div>
                            <label for="quantity" class="block text-xs font-bold text-slate-700 mb-1">基準数量 <span class="text-rose-600">*</span></label>
                            <input type="number" id="quantity" name="quantity" value="{{ old('quantity', $equipment->quantity) }}" min="1" required
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="unit" class="block text-xs font-bold text-slate-700 mb-1">単位 <span class="text-rose-600">*</span></label>
                            <input type="text" id="unit" name="unit" value="{{ old('unit', $equipment->unit) }}" required
                                placeholder="例: 本, 台, 張, セット, 箱, 式"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="flex items-center sm:pt-5">
                            <label class="relative flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" id="is_approximate_quantity" name="is_approximate_quantity" value="1"
                                    {{ old('is_approximate_quantity', $equipment->is_approximate_quantity) ? 'checked' : '' }}
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-xs font-bold text-slate-700">概数（約○個）として管理</span>
                            </label>
                        </div>
                    </div>

                    <!-- 主担当班の指名 -->
                    <div>
                        <label for="lane_id" class="block text-xs font-bold text-slate-700 mb-1">
                            主担当班（維持・設営・保管の責任班）
                        </label>
                        <select id="lane_id" name="lane_id"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">未指定（全社共通管理）</option>
                            @foreach($lanes as $lane)
                                <option value="{{ $lane->id }}" {{ old('lane_id', $equipment->lane_id) == $lane->id ? 'selected' : '' }}>
                                    {{ $lane->icon ?: '🏷️' }} {{ $lane->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">※ 班を指定すると、班別資機材一覧や担当班詳細に表示されます。</p>
                    </div>

                    <!-- 保管場所・調達先（自前/レンタル/私物によりJSで切替表示） -->
                    <div id="storage-container" class="space-y-3 p-4 bg-slate-50/70 rounded-xl border border-slate-200">
                        <div id="storage-location-field">
                            <label for="storage_location" class="block text-xs font-bold text-slate-700 mb-1">定常保管場所</label>
                            <input type="text" id="storage_location" name="storage_location" value="{{ old('storage_location', $equipment->storage_location) }}"
                                placeholder="例: 番所地下倉庫、本部テント内、各詰所など"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div id="storage-detail-field">
                            <label for="storage_detail" class="block text-xs font-bold text-slate-700 mb-1">棚番・置場詳細・所有者連絡先</label>
                            <input type="text" id="storage_detail" name="storage_detail" value="{{ old('storage_detail', $equipment->storage_detail) }}"
                                placeholder="例: 奥棚A-3、段ボール箱#2、個人所有者（連絡先: 090-xxxx-xxxx）など"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div id="rental-notice" class="hidden text-xs text-sky-800 bg-sky-50 border border-sky-200 p-3 rounded-lg">
                            <span class="font-bold">🏢 イベント会社手配</span>: 保管場所は手配会社による現場直接搬入となるため、登録不要です。
                        </div>
                    </div>

                    <!-- 持出・利用ルール -->
                    <div>
                        <label for="checkout_rule" class="block text-xs font-bold text-slate-700 mb-1">
                            持出・利用ルール <span class="text-rose-600">*</span>
                        </label>
                        <select id="checkout_rule" name="checkout_rule" required
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach(\App\Enums\EquipmentCheckoutRule::cases() as $ruleCase)
                                <option value="{{ $ruleCase->value }}" {{ old('checkout_rule', $equipment->checkout_rule) === $ruleCase->value ? 'selected' : '' }}>
                                    {{ $ruleCase->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 仕様・注意事項・申し送り -->
                    <div>
                        <label for="description" class="block text-xs font-bold text-slate-700 mb-1">仕様・注意事項・申し送りメモ</label>
                        <textarea id="description" name="description" rows="3"
                            placeholder="例: パイプテント用の杭とロープは別箱に保管。マイク用単三電池は予備4本同封。取扱注意。"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('description', $equipment->description) }}</textarea>
                    </div>

                    <!-- 表示順 -->
                    <div>
                        <label for="display_order" class="block text-xs font-bold text-slate-700 mb-1">表示順（小さい順）</label>
                        <input type="number" id="display_order" name="display_order" value="{{ old('display_order', $equipment->display_order) }}" step="5"
                            class="w-32 px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- フォーム送信ボタン -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-200">
                    <a href="{{ $showUrl }}"
                        class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                        キャンセル
                    </a>
                    <button type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                        変更内容を保存する
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@vite(['resources/js/equipments.js'])
</x-layouts.app>
