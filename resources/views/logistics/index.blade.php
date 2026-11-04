<x-layouts.app title="搬出入パターン管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- 1. ヘッダーセクション -->
    <x-page-header
        title="搬出入パターン管理"
        icon="🧩"
        description="よく使う搬出入・中継・夜間保管のステップ構成をパターン（テンプレート）として登録・管理します。">
        <x-slot:breadcrumbs>
            <a href="{{ route('equipments.index') }}" class="hover:underline shrink-0">資材管理</a>
            <span class="shrink-0 text-slate-300">/</span>
            <span class="text-slate-800 font-bold shrink-0">搬出入パターン管理</span>
        </x-slot:breadcrumbs>
        <x-slot:badges>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
                全 {{ $patterns->count() }} パターン
            </span>
        </x-slot:badges>
        <x-slot:actions>
            <button type="button" data-action="open-pattern-modal" data-mode="create"
                    class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer whitespace-nowrap shrink-0">
                <span>➕</span>
                <span>新規パターン</span>
            </button>
        </x-slot:actions>
    </x-page-header>

    <!-- タブ切り替え（相互リンク） -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-2 overflow-x-auto flex-nowrap text-xs">
        <a href="{{ route('equipments.index') }}"
           class="px-3 py-1.5 rounded-lg font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 transition whitespace-nowrap shrink-0">
            📦 資材一覧
        </a>
        <span class="px-3 py-1.5 rounded-lg font-bold bg-indigo-600 text-white shadow-2xs whitespace-nowrap shrink-0">
            🧩 搬出入パターン管理 ({{ $patterns->count() }})
        </span>
        <a href="{{ route('logistics.print') }}"
           class="px-3 py-1.5 rounded-lg font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 transition whitespace-nowrap shrink-0">
            🖨️ 搬出入指示書印刷
        </a>
    </div>

    @if (session('status'))
        <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between">
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
            <div class="font-bold">入力内容に不備があります：</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 2. パターン一覧 -->
    <div class="space-y-4">
        @forelse($patterns as $pattern)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2 flex-wrap">
                            <span class="font-bold text-slate-900 text-base">{{ $pattern->name }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                {{ $pattern->target_type_label }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-bold bg-slate-100 text-slate-700">
                                {{ $pattern->steps->count() }} ステップ
                            </span>
                        </div>
                        @if($pattern->description)
                            <p class="text-xs text-slate-500">{{ $pattern->description }}</p>
                        @endif
                    </div>

                    <div class="flex items-center space-x-2 shrink-0 self-end sm:self-center">
                        <button type="button"
                                data-action="open-pattern-modal"
                                data-mode="edit"
                                data-pattern="{{ json_encode($pattern) }}"
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer inline-flex items-center space-x-1">
                            <span>✏️</span>
                            <span>編集</span>
                        </button>
                        <form method="POST" action="{{ route('logistics.patterns.destroy', $pattern) }}" class="inline"
                              data-confirm="パターン「{{ $pattern->name }}」を削除しますか？&#10;（すでに各資材に生成済みの計画ステップは消えません）">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer inline-flex items-center space-x-1">
                                <span>🗑️</span>
                                <span>削除</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold text-[11px]">
                            <tr>
                                <th class="py-2 px-3">#</th>
                                <th class="py-2 px-3">フェーズ</th>
                                <th class="py-2 px-3">ステップ名</th>
                                <th class="py-2 px-3">移動ルート (From ➔ To)</th>
                                <th class="py-2 px-3">作業主体 / 立会</th>
                                <th class="py-2 px-3">備考</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($pattern->steps as $idx => $step)
                                <tr>
                                    <td class="py-2 px-3 font-mono text-slate-400">{{ $idx + 1 }}</td>
                                    <td class="py-2 px-3 font-bold text-slate-700 whitespace-nowrap">{{ $step->phase_label }}</td>
                                    <td class="py-2 px-3 font-bold text-slate-900">{{ $step->title }}</td>
                                    <td class="py-2 px-3 text-slate-700 whitespace-nowrap">
                                        <span class="inline-flex items-center space-x-1">
                                            <span class="font-medium {{ $step->fromLocationModel ? 'text-slate-800 font-bold' : 'text-slate-400' }}">{{ $step->from_location_display }}</span>
                                            <span class="text-slate-400 font-bold mx-1">➔</span>
                                            <span class="font-medium {{ $step->toLocationModel ? 'text-slate-800 font-bold' : 'text-slate-400' }}">{{ $step->to_location_display }}</span>
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 whitespace-nowrap">
                                        <span>{{ $step->operator_type === 'event_company' ? '🏢 イベント会社' : '👥 委員会スタッフ' }}</span>
                                        @if($step->witness_required)
                                            <span class="ml-1 text-rose-700 font-bold">👁️立会</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 text-slate-500 max-w-xs truncate">
                                        {{ $step->notes ?: '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="p-12 text-center bg-white rounded-2xl border border-dashed border-slate-300 text-slate-500 space-y-3">
                <span class="text-3xl block">🧩</span>
                <p class="font-bold text-slate-700">登録されている搬出入パターンはありません</p>
                <p class="text-xs text-slate-400">「新規パターンを作成」ボタンから登録するか、資材詳細画面で組んだステップをパターンとして保存してください。</p>
                <button type="button" data-action="open-pattern-modal" data-mode="create"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 transition cursor-pointer">
                    ➕ 新規パターンを作成する
                </button>
            </div>
        @endforelse
    </div>

</div>

<!-- ========================================================================= -->
<!-- パターン新規作成・編集モーダル (CSP準拠) -->
<!-- ========================================================================= -->
<div id="modal-pattern" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-4xl w-full p-5 sm:p-6 shadow-2xl border border-slate-200 text-left space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="modal-pattern-title" class="text-sm sm:text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>🧩</span>
                <span>新規パターンの作成</span>
            </h3>
            <button type="button" data-action="close-pattern-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">✕</button>
        </div>

        <form id="form-pattern" method="POST" action="{{ route('logistics.patterns.store') }}" class="space-y-4">
            @csrf
            <div id="form-pattern-method"></div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label for="pattern_name" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">パターン名 <span class="text-rose-500">*</span></label>
                    <input type="text" id="pattern_name" name="name" required
                           placeholder="例: パターンA: 自前備品標準、パターンB: テント一式"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="pattern_target_type" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">主な対象区分 <span class="text-rose-500">*</span></label>
                    <select id="pattern_target_type" name="target_type" required
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Models\EquipmentLogisticsPattern::TARGET_TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="pattern_description" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">パターンの説明（任意）</label>
                <input type="text" id="pattern_description" name="description"
                       placeholder="例: 前日倉庫出し ➔ 本部テント設置 ➔ 2日目夕方撤収"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- ステップ行コンテナ -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-800">構成ステップ一覧 <span class="text-rose-500">*</span></label>
                    <button type="button" data-action="add-pattern-step"
                            class="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition cursor-pointer">
                        ＋ ステップ行を追加
                    </button>
                </div>

                <div id="pattern-steps-container" class="space-y-3"></div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" data-action="close-pattern-modal"
                        class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                    キャンセル
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    パターンを保存する
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ステップ行のHTMLテンプレート（JSから複製利用） -->
<template id="tpl-pattern-step-row">
    <div class="pattern-step-row p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5 text-xs">
        <div class="flex items-center justify-between gap-2 border-b border-slate-200/60 pb-2">
            <span class="font-bold text-slate-700 step-row-number">ステップ #1</span>
            <button type="button" data-action="remove-pattern-step" class="text-rose-600 hover:text-rose-800 font-bold text-xs cursor-pointer">
                ✕ この行を削除
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">フェーズ *</label>
                <select data-field="day_phase" required class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs font-bold">
                    @foreach(\App\Models\EquipmentLogisticsPlan::DAY_PHASES as $key => $lbl)
                        <option value="{{ $key }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">アクション種別 *</label>
                <select data-field="action_type" required class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs font-bold">
                    @foreach(\App\Models\EquipmentLogisticsPlan::ACTION_TYPES as $key => $lbl)
                        <option value="{{ $key }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">作業主体 *</label>
                <select data-field="operator_type" required class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs font-bold">
                    <option value="committee">👥 委員会スタッフ</option>
                    <option value="event_company">🏢 イベント会社</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">ステップ表示名 *</label>
            <input type="text" data-field="title" required placeholder="例: 前日倉庫出し・現場へ搬送、夕方夜間保管場所へ移動"
                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded text-xs">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div class="p-2.5 rounded-lg bg-white border border-slate-200 space-y-1">
                <label class="block text-[10px] font-bold text-slate-600 flex items-center space-x-1">
                    <span>📍</span>
                    <span>移動元拠点 (From)</span>
                </label>
                <select data-field="from_location_id" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-300 rounded text-xs font-medium">
                    <option value="">未指定（現場または各資材設定を参照）</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="p-2.5 rounded-lg bg-white border border-slate-200 space-y-1">
                <label class="block text-[10px] font-bold text-slate-600 flex items-center space-x-1">
                    <span>🏁</span>
                    <span>移動先拠点 (To)</span>
                </label>
                <select data-field="to_location_id" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-300 rounded text-xs font-medium">
                    <option value="">未指定（現場または各資材設定を参照）</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 pt-1">
            <div class="flex-1 w-full">
                <input type="text" data-field="notes" placeholder="申し送り・注意事項メモ（任意）"
                       class="w-full px-2 py-1 bg-white border border-slate-300 rounded text-xs">
            </div>
            <div class="shrink-0">
                <label class="inline-flex items-center space-x-1.5 cursor-pointer">
                    <input type="checkbox" data-field="witness_required" value="1" class="rounded border-slate-300 text-indigo-600">
                    <span class="font-bold text-slate-700 text-xs">👁️ 幹事・スタッフ立会必須</span>
                </label>
            </div>
        </div>
    </div>
</template>

@vite(['resources/js/equipments.js'])
</x-layouts.app>
