<x-layouts.app :title="'新規担当班の追加 - ' . $timetable->name">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        title="新規担当班の追加"
        icon="➕"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）に新しい担当班（レーン）を追加します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('lanes.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>🏷️</span>
                <span>担当班管理</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.lanes.index', $timetable) }}" class="hover:text-indigo-600 transition truncate">
                📅 {{ $timetable->name }}
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">➕ 新規追加</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.lanes.index', $timetable) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>担当班一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="lanes" />

    <!-- 入力エラーメッセージ -->
    @if($errors->any())
    <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl space-y-1">
        <div class="font-bold flex items-center space-x-1">
            <span>⚠️</span>
            <span>入力内容を確認してください</span>
        </div>
        @foreach($errors->all() as $err)
        <div>・{{ $err }}</div>
        @endforeach
    </div>
    @endif

    @php
        $colorClassesMap = [
            'amber'   => ['dot' => 'bg-amber-500', 'badge' => 'bg-amber-100 text-amber-800 border-amber-300'],
            'sky'     => ['dot' => 'bg-sky-500', 'badge' => 'bg-sky-100 text-sky-800 border-sky-300'],
            'emerald' => ['dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            'purple'  => ['dot' => 'bg-purple-500', 'badge' => 'bg-purple-100 text-purple-800 border-purple-300'],
            'pink'    => ['dot' => 'bg-pink-500', 'badge' => 'bg-pink-100 text-pink-800 border-pink-300'],
            'red'     => ['dot' => 'bg-rose-500', 'badge' => 'bg-rose-100 text-rose-800 border-rose-300'],
            'orange'  => ['dot' => 'bg-orange-500', 'badge' => 'bg-orange-100 text-orange-800 border-orange-300'],
            'indigo'  => ['dot' => 'bg-indigo-500', 'badge' => 'bg-indigo-100 text-indigo-800 border-indigo-300'],
            'teal'    => ['dot' => 'bg-teal-500', 'badge' => 'bg-teal-100 text-teal-800 border-teal-300'],
            'yellow'  => ['dot' => 'bg-yellow-400', 'badge' => 'bg-yellow-100 text-yellow-800 border-yellow-300'],
            'slate'   => ['dot' => 'bg-slate-500', 'badge' => 'bg-slate-100 text-slate-800 border-slate-300'],
        ];
        $selectedColor = old('color', 'slate');
        $initialBadgeClass = $colorClassesMap[$selectedColor]['badge'] ?? $colorClassesMap['slate']['badge'];
        $emojiPresets = [
            '🔺', '🚨', '🪧', '🛑', '🚧', '🛡️',
            '🚰', '🚻', '💡', '🔧', '🧹', '🎪', '⛺',
            '📢', '🎤', '🏛️', '📋', '📻', '🎫',
            '🏮', '🎈', '🧺', '🍱', '🍧', '🎯',
            '🚚', '📦', '🛒', '🏃‍♂️', '🚲',
        ];
    @endphp

    <!-- 作成フォームカード -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                <span>📋</span>
                <span>担当班の基本設定とメンバー編成</span>
            </h2>
            <span class="text-xs text-slate-500 font-mono">
                日付: {{ $timetable->target_date?->format('Y/m/d') }}
            </span>
        </div>

        <form action="{{ route('timetables.lanes.store', $timetable) }}" method="POST" class="p-6 space-y-6" id="lane-form">
            @csrf

            <!-- リアルタイム・バッジプレビュー -->
            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider shrink-0">表示プレビュー:</span>
                    <div id="lane-preview-badge"
                        class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold border transition-all duration-150 {{ $initialBadgeClass }}">
                        <span id="preview-icon" class="mr-1.5 text-sm">{{ old('icon', '📋') ?: '📋' }}</span>
                        <span id="preview-name">{{ old('name') ?: '担当班名' }}</span>
                    </div>
                </div>
                <span class="text-[11px] text-slate-400">※ 入力内容やカラー選択が即座に反映されます</span>
            </div>

            <!-- 1. 基本属性（名前 & アイコン） -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- 担当班名 -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                        担当班名（レーン名） <span class="text-rose-600">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        placeholder="例: 会場放送班、音響PA班"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-[10px] text-slate-400 mt-1">※ タイムテーブルや作業割当カードに表示される班の名前です。</p>
                </div>

                <!-- 絵文字アイコン入力 -->
                <div>
                    <label for="icon" class="block text-xs font-bold text-slate-700 mb-1">
                        絵文字アイコン
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="text" id="icon" name="icon" value="{{ old('icon', '📋') }}" maxlength="20"
                            placeholder="例: 🎤"
                            class="w-20 text-center px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <div class="text-[10px] text-slate-400 leading-tight">
                            自由入力または下の候補から選択
                        </div>
                    </div>
                </div>
            </div>

            <!-- 絵文字クイックパレット -->
            <div class="p-3 bg-slate-50/70 border border-slate-200/80 rounded-xl space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <span class="text-xs font-bold text-slate-700 flex items-center space-x-1">
                        <span>✨</span>
                        <span>おすすめ絵文字パレット（クリックで即時セット）</span>
                    </span>
                    <span class="text-[10px] text-slate-400 font-mono">
                        PCショートカット: Win + . / Mac: Ctrl + Cmd + Space
                    </span>
                </div>
                <div class="flex flex-wrap gap-1.5" id="emoji-palette">
                    @foreach($emojiPresets as $emoji)
                    <button type="button"
                        data-emoji="{{ $emoji }}"
                        class="w-8 h-8 rounded-lg bg-white border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50 text-sm flex items-center justify-center transition cursor-pointer shadow-2xs">
                        {{ $emoji }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- テーマカラー（視覚的カラースウォッチ） -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-700">
                        テーマカラー選択 <span class="text-rose-600">*</span>
                    </label>
                    <span class="text-[10px] text-slate-400">実際の色味とバッジ見本を確認しながら選択できます</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2" id="color-options-grid">
                    @foreach($colorOptions as $colorKey => $colorLabel)
                    @php
                        $colorMeta = $colorClassesMap[$colorKey] ?? $colorClassesMap['slate'];
                        $isSelected = $selectedColor === $colorKey;
                    @endphp
                    <label class="lane-color-card relative flex items-center justify-between p-2 rounded-xl border cursor-pointer transition select-none hover:bg-slate-50 {{ $isSelected ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20 shadow-2xs' : 'border-slate-200 bg-white' }}"
                        data-color="{{ $colorKey }}"
                        data-badge-class="{{ $colorMeta['badge'] }}">
                        <input type="radio" name="color" value="{{ $colorKey }}" {{ $isSelected ? 'checked' : '' }} required class="sr-only">
                        <div class="flex items-center space-x-2 truncate mr-1">
                            <span class="w-3.5 h-3.5 rounded-full {{ $colorMeta['dot'] }} shrink-0 border border-black/10"></span>
                            <span class="text-xs font-bold text-slate-800 truncate">{{ $colorLabel }}</span>
                        </div>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold border {{ $colorMeta['badge'] }} shrink-0">
                            見本
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- 2. 班長および説明メモ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                <!-- 班長（主担当スタッフ） -->
                <div>
                    <label for="lead_staff_id" class="block text-xs font-bold text-slate-700 mb-1">
                        👑 班長（現場リーダー / 後から設定・変更可能）
                    </label>
                    <select id="lead_staff_id" name="lead_staff_id"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- 未設定（後で割り当てる） --</option>
                        @foreach($activeStaff as $st)
                        <option value="{{ $st->id }}" {{ old('lead_staff_id') == $st->id ? 'selected' : '' }}>
                            {{ $st->name }} {{ $st->affiliation ? "({$st->affiliation})" : '' }}
                        </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">※ 班長に任命されたスタッフは、この班の全タスクへ自動的にアサインされます。</p>
                </div>

                <!-- 業務説明メモ -->
                <div>
                    <label for="description" class="block text-xs font-bold text-slate-700 mb-1">
                        業務所管・担当エリア（メモ）
                    </label>
                    <input type="text" id="description" name="description" value="{{ old('description') }}"
                        placeholder="例: 会場放送設備の設営、式典アナウンスの実施、本陣周辺"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <!-- 3. 固定スタッフ選択エリア -->
            <div class="p-4 bg-indigo-50/40 rounded-xl border border-indigo-200/80 space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <span class="text-xs font-bold text-indigo-950 flex items-center space-x-1">
                        <span>📌</span>
                        <span>固定スタッフ設定（この班のタスクに自動で基本配置されます）</span>
                    </span>
                    <span class="text-[11px] text-slate-500">決まっている場合は選択（複数選択可）</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 max-h-48 overflow-y-auto p-2 border border-slate-200 rounded-lg bg-white">
                    @forelse($activeStaff as $st)
                    <label class="flex items-center space-x-1.5 text-xs text-slate-700 cursor-pointer hover:bg-indigo-50 p-1.5 rounded transition">
                        <input type="checkbox" name="fixed_staff_ids[]" value="{{ $st->id }}"
                            {{ in_array($st->id, old('fixed_staff_ids', [])) ? 'checked' : '' }}
                            class="w-3.5 h-3.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                        <span class="truncate" title="{{ $st->name }} ({{ $st->affiliation ?: '所属なし' }})">
                            {{ $st->name }}
                            @if($st->affiliation)
                            <span class="text-[10px] text-slate-400">({{ $st->affiliation }})</span>
                            @endif
                        </span>
                    </label>
                    @empty
                    <div class="col-span-full py-2 text-center text-xs text-slate-400">
                        利用可能なスタッフが登録されていません。
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- 4. 有効化フラグ -->
            <div class="pt-3 border-t border-slate-200">
                <label class="inline-flex items-center space-x-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                        class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                    <span class="text-xs font-bold text-slate-700">👁️ 有効（マトリクス・帳票に表示）</span>
                </label>
            </div>

            <!-- 5. 送信ボタンエリア -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                <a href="{{ route('timetables.lanes.index', $timetable) }}"
                    class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition">
                    キャンセル
                </a>
                <button type="submit"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5 cursor-pointer">
                    <span>➕</span>
                    <span>この担当班を新規登録</span>
                </button>
            </div>
        </form>
    </div>

</div>

@vite(['resources/js/lanes.js'])
</x-layouts.app>