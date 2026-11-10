<x-layouts.app title="搬出入パターン一覧 - まつり総合管理システム">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

        <!-- ナビゲーション & ヘッダー -->
        <x-page-header
            title="搬出入パターン一覧"
            icon="🧩"
            description="全運行日で共通利用可能な、機材・備品の搬出入・中継・夜間保管・撤収ステップの標準テンプレート台帳です。">
            <x-slot:breadcrumbs>
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>🏮</span>
                    <span>ダッシュボード</span>
                </a>
                <span class="text-slate-300">/</span>
                <a href="{{ route('equipments.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>📦</span>
                    <span>機材・備品一覧</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">🧩 搬出入パターン一覧</span>
            </x-slot:breadcrumbs>

            <x-slot:actions>
                <button type="button"
                    data-action="open-pattern-create"
                    class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-2xs whitespace-nowrap shrink-0 cursor-pointer">
                    <span>➕</span>
                    <span>新規パターン作成</span>
                </button>
            </x-slot:actions>
        </x-page-header>

        <!-- アラートメッセージ -->
        @if (session('status'))
        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between">
            <span>✅ {{ session('status') }}</span>
        </div>
        @endif

        @if ($errors->any())
        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
            <div class="font-bold">⚠️ 入力内容に不備があります：</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- セクションヘッダーバー (見出し + 折りたたみトグル) -->
        <div class="flex items-center justify-between gap-2.5 px-1">
            <h2 class="font-bold text-sm sm:text-base text-slate-900 flex items-center space-x-2">
                <span>🧩</span>
                <span>登録済み搬出入パターン一覧 (ステップ構成)</span>
            </h2>

            @if($patterns->isNotEmpty())
            <button type="button"
                data-action="toggle-accordions"
                data-target="#pattern-accordions"
                data-state="open"
                class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer">
                <span data-icon>🔼</span>
                <span data-label>すべて折りたたむ</span>
            </button>
            @endif
        </div>

        <!-- パターン別アコーディオン一覧 -->
        <div id="pattern-accordions" class="space-y-3">
            @forelse($patterns as $pattern)
            <details id="pattern-{{ $pattern->id }}" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden group" {{ $pattern->has_steps ? 'open' : '' }}>
                <summary class="px-3.5 sm:px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-2 sm:gap-3 cursor-pointer hover:bg-slate-100/70 transition select-none">
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                        <!-- 1. 開閉トグルアイコン (固定幅) -->
                        <span class="w-4 shrink-0 text-center text-slate-400 group-open:rotate-90 transition-transform duration-150 inline-block text-xs">▶</span>

                        <!-- 2. パターン名テキスト & 詳細リンク -->
                        <div class="flex items-center min-w-0 flex-1">
                            <span class="mr-1.5 shrink-0 text-slate-500">🧩</span>
                            <button type="button"
                                data-action="open-pattern-detail"
                                data-pattern='@json($pattern->toModalPayload())'
                                class="text-indigo-600 hover:text-indigo-800 font-bold text-xs sm:text-sm truncate hover:underline text-left cursor-pointer"
                                title="パターンの詳細・全ステップを表示">
                                {{ $pattern->name }}
                            </button>
                        </div>
                    </div>

                    <!-- 5. アクションボタン (右寄せ) -->
                    <div class="flex items-center space-x-1.5 shrink-0 ml-auto" data-pattern-row data-pattern='@json($pattern->toModalPayload())'>
                        @can('update', $pattern)
                        <button type="button"
                            data-action="open-step-create"
                            data-pattern-id="{{ $pattern->id }}"
                            data-pattern-name="{{ $pattern->name }}"
                            data-store-url="{{ route('equipment-patterns.steps.store', $pattern) }}"
                            class="inline-flex items-center space-x-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition cursor-pointer"
                            title="ステップを追加">
                            <span>➕</span>
                            <span>ステップ追加</span>
                        </button>
                        @endcan

                        @can('delete', $pattern)
                        <form action="{{ route('equipment-patterns.destroy', $pattern) }}" method="POST" class="inline"
                            data-confirm="搬出入パターン「{{ $pattern->name }}」を削除しますか？&#10;（※資材に生成済みの計画ステップは削除されません）">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center space-x-1 px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                title="削除">
                                <span>🗑️</span>
                                <span>削除</span>
                            </button>
                        </form>
                        @endcan
                    </div>
                </summary>

                @if($pattern->steps->isEmpty())
                <div class="px-4 py-8 text-center text-slate-400 text-xs">
                    <span class="text-2xl block mb-1">📋</span>
                    <p>このパターンにはステップが登録されていません。「ステップ追加」からステップを追加してください。</p>
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold select-none text-xs">
                            <tr>
                                <th class="px-3.5 py-2.5 text-center whitespace-nowrap w-14">順序</th>
                                <th class="px-3.5 py-2.5 whitespace-nowrap w-28">フェーズ</th>
                                <th class="px-3.5 py-2.5 whitespace-nowrap w-28">種別</th>
                                <th class="px-3.5 py-2.5 min-w-[160px]">ステップ表示名</th>
                                <th class="px-3.5 py-2.5 min-w-[180px]">移動元 ➔ 移動先</th>
                                <th class="px-3.5 py-2.5 whitespace-nowrap w-28">作業主体</th>
                                <th class="px-3.5 py-2.5 text-center whitespace-nowrap w-16">立会</th>
                                <th class="px-3.5 py-2.5 text-center whitespace-nowrap w-28">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($pattern->steps as $step)
                            <tr class="hover:bg-slate-50/70 transition">
                                <!-- 順序 -->
                                <td class="px-3.5 py-2.5 text-center font-mono text-slate-400 font-bold whitespace-nowrap">
                                    #{{ $loop->iteration }}
                                </td>

                                <!-- フェーズ -->
                                <td class="px-3.5 py-2.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border
                                    @if($step->day_phase === 'pre_day') bg-purple-50 text-purple-700 border-purple-200
                                    @elseif($step->day_phase === 'day1_morning') bg-sky-50 text-sky-700 border-sky-200
                                    @elseif($step->day_phase === 'day1_evening') bg-indigo-50 text-indigo-700 border-indigo-200
                                    @elseif($step->day_phase === 'day2_morning') bg-teal-50 text-teal-700 border-teal-200
                                    @elseif($step->day_phase === 'day2_evening') bg-amber-50 text-amber-700 border-amber-200
                                    @else bg-slate-50 text-slate-700 border-slate-200 @endif">
                                        {{ $step->phase_label }}
                                    </span>
                                </td>

                                <!-- 種別 -->
                                <td class="px-3.5 py-2.5 font-medium text-slate-800 whitespace-nowrap">
                                    {{ $step->action_label }}
                                </td>

                                <!-- ステップ表示名 -->
                                <td class="px-3.5 py-2.5 font-bold text-slate-900">
                                    {{ $step->title }}
                                </td>

                                <!-- 移動元 ➔ 移動先 -->
                                <td class="px-3.5 py-2.5 text-slate-700 font-mono text-[11px]">
                                    <span class="text-slate-500">{{ $step->fromLocationModel?->name ?: '（資材定常保管場所）' }}</span>
                                    <span class="text-indigo-600 font-bold px-1">➔</span>
                                    <span class="font-bold text-slate-800">{{ $step->toLocationModel?->name ?: '（現地指定）' }}</span>
                                </td>

                                <!-- 作業主体 -->
                                <td class="px-3.5 py-2.5 text-slate-600 whitespace-nowrap">
                                    {{ $step->operator_type_label }}
                                </td>

                                <!-- 立会 -->
                                <td class="px-3.5 py-2.5 text-center whitespace-nowrap">
                                    @if($step->witness_required)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 font-bold text-[10px] border border-rose-200">必須</span>
                                    @else
                                    <span class="text-slate-300 text-xs">-</span>
                                    @endif
                                </td>

                                <!-- 操作 -->
                                <td class="px-3.5 py-2.5 text-center whitespace-nowrap space-x-1">
                                    @can('update', $pattern)
                                    <button type="button"
                                        data-action="open-step-edit"
                                        data-step='@json($step->toModalPayload($pattern))'
                                        class="inline-flex items-center space-x-1 px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded text-xs font-bold transition cursor-pointer"
                                        title="ステップを編集">
                                        <span>✏️</span>
                                        <span>編集</span>
                                    </button>

                                    <form action="{{ route('equipment-patterns.steps.destroy', [$pattern, $step]) }}" method="POST" class="inline"
                                        data-confirm="ステップ「{{ $step->title }}」を削除しますか？">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center space-x-1 px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded text-xs font-bold transition cursor-pointer"
                                            title="ステップを削除">
                                            <span>🗑️</span>
                                            <span>削除</span>
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-slate-300 text-xs">-</span>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </details>
            @empty
            <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-400 space-y-2">
                <span class="text-3xl block">🧩</span>
                <p class="font-bold text-slate-700">登録されている搬出入パターンはありません</p>
                <p class="text-xs text-slate-400">「新規パターン作成」ボタンから標準パターンを登録してください。</p>
            </div>
            @endforelse
        </div>

        <!-- 各種パターン操作モーダル (独立コンポーネント) -->
        <x-equipment-patterns.create-modal :locations="$locations" />
        <x-equipment-patterns.detail-modal />
        <x-equipment-patterns.step-create-modal :locations="$locations" />
        <x-equipment-patterns.step-edit-modal :locations="$locations" />

    </div>

    <!-- ステップ行のHTMLテンプレート（JSから複製利用・CSP準拠） -->
    <template id="tpl-pattern-step-row">
        <div class="pattern-step-row p-3 rounded-xl bg-slate-50/70 border border-slate-200 space-y-2.5 text-xs">
            <div class="flex items-center justify-between gap-2 border-b border-slate-200/60 pb-1.5">
                <span class="font-bold text-slate-700 step-row-number">ステップ #1</span>
                <button type="button" data-action="remove-pattern-step" class="text-rose-600 hover:text-rose-800 font-bold text-xs cursor-pointer">
                    ✕ この行を削除
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">フェーズ <span class="text-rose-500">*</span></label>
                    <select data-field="day_phase" required class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs font-bold text-slate-800">
                        @foreach(\App\Models\EquipmentLogisticsPlan::DAY_PHASES as $key => $lbl)
                        <option value="{{ $key }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">アクション種別 <span class="text-rose-500">*</span></label>
                    <select data-field="action_type" required class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs font-bold text-slate-800">
                        @foreach(\App\Models\EquipmentLogisticsPlan::ACTION_TYPES as $key => $lbl)
                        <option value="{{ $key }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">作業主体 <span class="text-rose-500">*</span></label>
                    <select data-field="operator_type" required class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs font-bold text-slate-800">
                        <option value="committee">👥 委員会スタッフ</option>
                        <option value="event_company">🏢 イベント会社</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">ステップ表示名 <span class="text-rose-500">*</span></label>
                <input type="text" data-field="title" required placeholder="例: 前日倉庫出し・現場へ搬送、夕方夜間保管場所へ移動"
                    class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div class="p-2 rounded-lg bg-white border border-slate-200 space-y-1">
                    <label class="block text-[10px] font-bold text-slate-600 flex items-center space-x-1">
                        <span>📍</span>
                        <span>移動元拠点 (From)</span>
                    </label>
                    <select data-field="from_location_id" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-300 rounded text-xs font-medium text-slate-800">
                        <option value="">未指定（現場または各資材設定を参照）</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="p-2 rounded-lg bg-white border border-slate-200 space-y-1">
                    <label class="block text-[10px] font-bold text-slate-600 flex items-center space-x-1">
                        <span>🏁</span>
                        <span>移動先拠点 (To)</span>
                    </label>
                    <select data-field="to_location_id" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-300 rounded text-xs font-medium text-slate-800">
                        <option value="">未指定（現場または各資材設定を参照）</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 pt-0.5">
                <div class="flex-1 w-full">
                    <input type="text" data-field="notes" placeholder="申し送り・注意事項メモ（任意）"
                        class="w-full px-2 py-1 bg-white border border-slate-300 rounded text-xs text-slate-800">
                </div>
                <div class="shrink-0">
                    <label class="inline-flex items-center space-x-1.5 cursor-pointer select-none">
                        <input type="checkbox" data-field="witness_required" value="1" class="rounded border-slate-300 text-indigo-600">
                        <span class="font-bold text-slate-700 text-xs">👁️ 幹事・スタッフ立会必須</span>
                    </label>
                </div>
            </div>
        </div>
    </template>

    @vite(['resources/js/equipment-patterns.js'])
</x-layouts.app>