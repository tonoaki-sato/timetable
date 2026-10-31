<x-layouts.app title="新規資材の登録">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ヘッダーエリア -->
    <x-page-header
        title="新規資材・搬出入計画の登録"
        icon="📦"
        description="資材情報と、前日準備〜当日〜夜間保管〜撤収に至る搬出入計画を一括で登録します。">
        <x-slot:breadcrumbs>
            <a href="{{ route('equipments.index') }}" class="hover:underline">資材一覧</a>
            <span>/</span>
            <span class="text-slate-800 font-bold">新規登録</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('equipments.index') }}"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                <span>← 一覧に戻る</span>
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
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-6 sm:p-8 space-y-6">
        <form method="POST" action="{{ route('equipments.store') }}" class="space-y-6">
            @csrf

            <!-- 1. 資材基本情報セクション -->
            <div class="space-y-4">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-100">
                    1. 基本情報
                </h2>

                <!-- 資材区分（自前 / レンタル） -->
                <div>
                    <label class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-2">資材区分 <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="p-3.5 rounded-xl border-2 flex items-center space-x-3 cursor-pointer transition has-checked:border-emerald-600 has-checked:bg-emerald-50/40 border-slate-200 hover:border-slate-300">
                            <input type="radio" name="type" value="committee" {{ old('type', 'committee') === 'committee' ? 'checked' : '' }}
                                   class="text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <div class="font-bold text-slate-800 text-xs sm:text-sm flex items-center space-x-1">
                                    <span>🏛️</span>
                                    <span>実行委員会管理機材</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">カラーコーン、ござ市用テントなど自前保有</p>
                            </div>
                        </label>

                        <label class="p-3.5 rounded-xl border-2 flex items-center space-x-3 cursor-pointer transition has-checked:border-sky-600 has-checked:bg-sky-50/40 border-slate-200 hover:border-slate-300">
                            <input type="radio" name="type" value="rental" {{ old('type') === 'rental' ? 'checked' : '' }}
                                   class="text-sky-600 focus:ring-sky-500">
                            <div>
                                <div class="font-bold text-slate-800 text-xs sm:text-sm flex items-center space-x-1">
                                    <span>🏢</span>
                                    <span>イベント会社レンタル</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">パイプテント、給水設備、机・椅子など</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 資材名 -->
                <div>
                    <label for="name" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">資材・機材名 <span class="text-rose-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           placeholder="例: カラーコーン（バーセット含む）、足踏み式給水設備、パイプテント 2K×3K"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- 数量 & 単位 & 担当班 -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="quantity" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">総数量 <span class="text-rose-500">*</span></label>
                        <input type="number" id="quantity" name="quantity" value="{{ old('quantity', 1) }}" min="1" required
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    </div>

                    <div>
                        <label for="unit" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">単位 <span class="text-rose-500">*</span></label>
                        <div class="flex items-center space-x-1">
                            <input type="text" id="unit" name="unit" value="{{ old('unit', '個') }}" required
                                   class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label for="lane_id" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">主担当班（レーン）</label>
                        <select id="lane_id" name="lane_id"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">担当班なし（本部共通）</option>
                            @foreach($lanes as $lane)
                                <option value="{{ $lane->id }}" {{ old('lane_id') == $lane->id ? 'selected' : '' }}>
                                    {{ $lane->icon }} {{ $lane->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 自前機材用: 保管場所 -->
                <div id="section-committee" class="space-y-2 p-3.5 rounded-xl bg-emerald-50/50 border border-emerald-200">
                    <label for="storage_location" class="block text-[11px] sm:text-xs font-bold text-emerald-900">
                        定常保管場所（自前機材）
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="text" id="storage_location" name="storage_location" value="{{ old('storage_location', '番所地下倉庫') }}"
                               placeholder="番所地下倉庫、コミュニティルーム等"
                               class="flex-1 px-3 py-2 bg-white border border-emerald-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <button type="button" data-set-storage-location="番所地下倉庫"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 hover:bg-emerald-200 transition cursor-pointer">
                            番所地下倉庫
                        </button>
                        <button type="button" data-set-storage-location="コミュニティルーム"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 hover:bg-emerald-200 transition cursor-pointer">
                            コミュニティルーム
                        </button>
                    </div>
                </div>

                <!-- レンタル用: イベント会社情報 -->
                <div id="section-rental" class="hidden space-y-3 p-3.5 rounded-xl bg-sky-50/50 border border-sky-200">
                    <div class="font-bold text-xs text-sky-900">イベント会社情報</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="event_company_name" class="block text-[11px] font-bold text-slate-700 mb-1">イベント会社名</label>
                            <input type="text" id="event_company_name" name="event_company_name" value="{{ old('event_company_name', '東京イベントサプライ株式会社') }}"
                                   placeholder="例: ○○イベント企画株式会社"
                                   class="w-full px-3 py-2 bg-white border border-sky-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                        <div>
                            <label for="event_company_contact" class="block text-[11px] font-bold text-slate-700 mb-1">連絡先・電話番号・担当者</label>
                            <input type="text" id="event_company_contact" name="event_company_contact" value="{{ old('event_company_contact') }}"
                                   placeholder="例: 03-1234-5678 (担当: 佐々木 090-0000-0000)"
                                   class="w-full px-3 py-2 bg-white border border-sky-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                        </div>
                    </div>
                </div>

                <!-- 備考 -->
                <div>
                    <label for="description" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">備考・仕様メモ</label>
                    <textarea id="description" name="description" rows="2" placeholder="仕様や付属品、取り扱い上の注意点など"
                              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('description') }}</textarea>
                </div>
            </div>

            <!-- 2. 搬出入計画テンプレート自動展開セクション -->
            <div class="space-y-4 pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                        2. 搬出入計画ステップの一括自動展開
                    </h2>
                    <span class="text-[11px] text-indigo-600 font-bold">※登録後に各ステップを自由編集可能</span>
                </div>

                <!-- テンプレート選択ラジオ -->
                <div class="space-y-2.5">
                    <label class="p-3.5 rounded-xl border-2 flex items-start space-x-3 cursor-pointer transition has-checked:border-indigo-600 has-checked:bg-indigo-50/30 border-slate-200 hover:border-slate-300">
                        <input type="radio" name="template_type" value="pattern_a" {{ old('template_type', 'pattern_a') === 'pattern_a' ? 'checked' : '' }}
                               class="mt-1 text-indigo-600 focus:ring-indigo-500">
                        <div class="flex-1">
                            <div class="font-bold text-slate-900 text-xs sm:text-sm flex items-center space-x-2">
                                <span class="bg-indigo-100 text-indigo-800 text-[10px] px-2 py-0.5 rounded font-black">パターンA</span>
                                <span>自前機材（前日倉庫出し ➔ 1日目設置 ➔ 1日目夜分散夜間保管 ➔ 2日目設置 ➔ 2日目夜倉庫格納）</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">
                                例: カラーコーンなど。立会不要、すべて委員会スタッフが作業。夜間は2日目の設置位置に近い複数拠点へ分散保管。
                            </p>
                        </div>
                    </label>

                    <label class="p-3.5 rounded-xl border-2 flex items-start space-x-3 cursor-pointer transition has-checked:border-indigo-600 has-checked:bg-indigo-50/30 border-slate-200 hover:border-slate-300">
                        <input type="radio" name="template_type" value="pattern_b" {{ old('template_type') === 'pattern_b' ? 'checked' : '' }}
                               class="mt-1 text-indigo-600 focus:ring-indigo-500">
                        <div class="flex-1">
                            <div class="font-bold text-slate-900 text-xs sm:text-sm flex items-center space-x-2">
                                <span class="bg-indigo-100 text-indigo-800 text-[10px] px-2 py-0.5 rounded font-black">パターンB</span>
                                <span>イベント会社受取・自前設置（前日業者受取立会 ➔ 貸切後移動 ➔ 夜間分散保管 ➔ 2日目夜集約 ➔ 翌日回収立会）</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">
                                例: 足踏み式給水設備など。前日受取と翌日回収のみ立会必須。夜間は近い拠点へ分散保管、2日目夜は番所空きスペース等へ集約。
                            </p>
                        </div>
                    </label>

                    <label class="p-3.5 rounded-xl border-2 flex items-start space-x-3 cursor-pointer transition has-checked:border-indigo-600 has-checked:bg-indigo-50/30 border-slate-200 hover:border-slate-300">
                        <input type="radio" name="template_type" value="pattern_c" {{ old('template_type') === 'pattern_c' ? 'checked' : '' }}
                               class="mt-1 text-indigo-600 focus:ring-indigo-500">
                        <div class="flex-1">
                            <div class="font-bold text-slate-900 text-xs sm:text-sm flex items-center space-x-2">
                                <span class="bg-indigo-100 text-indigo-800 text-[10px] px-2 py-0.5 rounded font-black">パターンC</span>
                                <span>イベント会社設営・解体型（搬入設営 ➔ 解体搬出・立会不要・幹事時刻把握用）</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">
                                例: パイプテント、天幕、ウェイトなど。設営・解体はイベント会社に一任（立会不要）。幹事は開始・終了スケジュールを把握。
                            </p>
                        </div>
                    </label>

                    <label class="p-3.5 rounded-xl border-2 flex items-start space-x-3 cursor-pointer transition has-checked:border-slate-600 has-checked:bg-slate-50 border-slate-200 hover:border-slate-300">
                        <input type="radio" name="template_type" value="none" {{ old('template_type') === 'none' ? 'checked' : '' }}
                               class="mt-1 text-slate-600 focus:ring-slate-500">
                        <div>
                            <div class="font-bold text-slate-800 text-xs sm:text-sm">テンプレートを適用しない（空で作成し個別登録）</div>
                        </div>
                    </label>
                </div>

                <!-- テンプレート用 拠点・立会人パラメータ設定枠 -->
                <div id="template-options-box" class="p-4 rounded-xl bg-indigo-50/40 border border-indigo-200 space-y-4">
                    <div class="font-bold text-xs text-indigo-950 flex items-center justify-between">
                        <span>🎯 テンプレート自動展開の拠点・担当者設定</span>
                        <span class="text-[10px] font-normal text-indigo-700">貸切期間付きの拠点から選択</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- 前日中継拠点 -->
                        <div>
                            <label for="staging_location_id" class="block text-[11px] font-bold text-slate-700 mb-1">
                                前日の中継・集約拠点
                            </label>
                            <select id="staging_location_id" name="staging_location_id"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ str_contains($loc->name, '税務署') ? 'selected' : '' }}>
                                        {{ $loc->name }} @if(!$loc->is_all_day)(貸切)@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 1日目夜間保管拠点① -->
                        <div>
                            <label for="night_location_1_id" class="block text-[11px] font-bold text-slate-700 mb-1">
                                1日目夜の保管拠点① (近隣)
                            </label>
                            <select id="night_location_1_id" name="night_location_1_id"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ str_contains($loc->name, '税務署') ? 'selected' : '' }}>
                                        {{ $loc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 1日目夜間保管拠点② (分散) -->
                        <div>
                            <label for="night_location_2_id" class="block text-[11px] font-bold text-slate-700 mb-1">
                                1日目夜の保管拠点② (分散・任意)
                            </label>
                            <select id="night_location_2_id" name="night_location_2_id"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">分散なし（上記①のみ）</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ str_contains($loc->name, '北川') ? 'selected' : '' }}>
                                        {{ $loc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- パターンB用 立会スタッフ選択 -->
                    <div id="witness-staff-select-box">
                        <label for="witness_staff_id" class="block text-[11px] font-bold text-slate-700 mb-1">
                            業者受取・回収の立会担当者（パターンB用）
                        </label>
                        <select id="witness_staff_id" name="witness_staff_id"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">立会スタッフを選択（未定可）</option>
                            @foreach($staffs as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->affiliation ?: '本部' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- アクションボタン -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('equipments.index') }}"
                   class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                    キャンセル
                </a>
                <button type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    資材と計画ステップを登録する
                </button>
            </div>
        </form>
    </div>
</div>

@vite(['resources/js/equipments.js'])
</x-layouts.app>

