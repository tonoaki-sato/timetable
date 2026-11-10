<x-layouts.app :title="'担当班の編集 - ' . $lane->name">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="'担当班の編集: ' . $lane->name"
        icon="✏️"
        :description="'「' . $timetable->name . '」における担当班「' . $lane->name . '」の基本情報・班長・固定スタッフ設定を更新します。'">
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
            <a href="{{ route('timetables.lanes.index', $timetable) }}" class="hover:text-indigo-600 transition truncate">
                🏷️ 担当班一覧
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.lanes.show', [$timetable, $lane]) }}" class="hover:text-indigo-600 transition truncate">
                {{ $lane->icon ?: '📋' }} {{ $lane->name }}
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">✏️ 編集</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.lanes.show', [$timetable, $lane]) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>👁️</span>
                <span>詳細を見る</span>
            </a>
            <a href="{{ route('timetables.lanes.index', $timetable) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>一覧に戻る</span>
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

    <!-- 編集フォームカード -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                <span>📋</span>
                <span>担当班情報の変更</span>
            </h2>
            <div class="flex items-center space-x-2 text-xs">
                <span class="text-xs text-slate-400">日付: {{ $timetable->target_date?->format('Y/m/d') }}</span>
            </div>
        </div>

        <form action="{{ route('timetables.lanes.update', [$timetable, $lane]) }}" method="POST" class="p-6 space-y-6" id="lane-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="sync_fixed_staff" value="1">

            <!-- 2段組みグリッド -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                <!-- 左カラム: 基本情報・体制設定 -->
                <div class="space-y-4">
                    <!-- 1. 担当班名 & 2. 班長 (横1列配置) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                                担当班名（レーン名） <span class="text-rose-600">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name', $lane->name) }}" required
                                placeholder="例: 会場放送班、音響PA班"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <p class="text-[10px] text-slate-400 mt-1">※ タイムテーブルや作業割当に表示</p>
                        </div>

                        <div>
                            <label for="lead_staff_id" class="block text-xs font-bold text-slate-700 mb-1">
                                👑 班長（現場リーダー / 後から設定可能）
                            </label>
                            <select id="lead_staff_id" name="lead_staff_id"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">-- 未設定（後で割り当てる） --</option>
                                @foreach($activeStaff as $st)
                                <option value="{{ $st->id }}" {{ (string) old('lead_staff_id', $lane->lead_staff_id) === (string) $st->id ? 'selected' : '' }}>
                                    {{ $st->name }} {{ $st->affiliation ? "({$st->affiliation})" : '' }}
                                </option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-slate-400 mt-1">※ 全タスクへ自動アサイン同期</p>
                        </div>
                    </div>

                    <!-- 3. 業務所管・担当エリア（メモ） -->
                    <div>
                        <label for="description" class="block text-xs font-bold text-slate-700 mb-1">
                            業務所管・担当エリア（メモ）
                        </label>
                        <input type="text" id="description" name="description" value="{{ old('description', $lane->description) }}"
                            placeholder="例: 会場放送設備の設営、式典アナウンスの実施、本陣周辺"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- 4. 固定スタッフ選択エリア -->
                    <div class="p-4 bg-indigo-50/40 rounded-xl border border-indigo-200/80 space-y-2">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="text-xs font-bold text-indigo-950 flex items-center space-x-1">
                                <span>📌</span>
                                <span>固定スタッフ設定（この班のタスクに自動で基本配置されます）</span>
                            </span>
                            <span class="text-[11px] text-slate-500">現在: {{ count($lane->fixed_staff_ids) }}名設定中</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-80 sm:max-h-96 overflow-y-auto p-2 border border-slate-200 rounded-lg bg-white">
                            @forelse($activeStaff as $st)
                            <label class="flex items-center space-x-1.5 text-xs text-slate-700 cursor-pointer hover:bg-indigo-50 p-1.5 rounded transition">
                                <input type="checkbox" name="fixed_staff_ids[]" value="{{ $st->id }}"
                                    {{ in_array($st->id, $lane->resolveSelectedFixedIds(old('fixed_staff_ids'))) ? 'checked' : '' }}
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
                </div>

                <!-- 右カラム: ビジュアル・外観設定 & プレビュー -->
                <div class="space-y-4">
                    <!-- 5. リアルタイム・バッジプレビュー -->
                    <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center space-x-3">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider shrink-0">表示プレビュー:</span>
                            <div id="lane-preview-badge"
                                class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold border transition-all duration-150 {{ \App\Models\Lane::paletteFor(old('color', $lane->color))['badge'] }}">
                                <span id="preview-icon" class="mr-1.5 text-sm">{{ old('icon', $lane->icon) ?: '📋' }}</span>
                                <span id="preview-name">{{ old('name', $lane->name) ?: '担当班名' }}</span>
                            </div>
                        </div>
                        <span class="text-[11px] text-slate-400">※ 入力内容やカラー選択が即座に反映されます</span>
                    </div>

                    <!-- 絵文字設定 & テーマカラー選択 (横1列配置) -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-start">
                        <!-- 絵文字エリア (sm:col-span-5) -->
                        <div class="sm:col-span-5 space-y-3">
                            <!-- 6. 絵文字アイコン入力 -->
                            <div>
                                <label for="icon" class="block text-xs font-bold text-slate-700 mb-1">
                                    絵文字アイコン
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="text" id="icon" name="icon" value="{{ old('icon', $lane->icon) }}" maxlength="20"
                                        placeholder="例: 🎤"
                                        class="w-16 text-center px-2 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <div class="text-[10px] text-slate-400 leading-tight">
                                        自由入力または下から選択
                                    </div>
                                </div>
                            </div>

                            <!-- 7. おすすめ絵文字パレット -->
                            <div class="p-2.5 bg-slate-50/70 border border-slate-200/80 rounded-xl space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-700 flex items-center space-x-1">
                                        <span>✨</span>
                                        <span>おすすめ絵文字</span>
                                    </span>
                                    <span class="text-[9px] text-slate-400 font-mono">クリック即時</span>
                                </div>
                                <div class="flex flex-wrap gap-1" id="emoji-palette">
                                    @foreach(\App\Models\Lane::EMOJI_PRESETS as $emoji)
                                    <button type="button"
                                        data-emoji="{{ $emoji }}"
                                        class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50 text-xs flex items-center justify-center transition cursor-pointer shadow-2xs">
                                        {{ $emoji }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- テーマカラー選択 (sm:col-span-7) -->
                        <div class="sm:col-span-7 space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-700">
                                    テーマカラー選択 <span class="text-rose-600">*</span>
                                </label>
                                <span class="text-[10px] text-slate-400">見本を確認</span>
                            </div>
                            <div class="grid grid-cols-2 gap-1.5" id="color-options-grid">
                                @foreach($colorOptions as $colorKey => $colorLabel)
                                <label class="lane-color-card relative flex items-center justify-between p-2 rounded-xl border cursor-pointer transition select-none hover:bg-slate-50 {{ old('color', $lane->color) === $colorKey ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20 shadow-2xs' : 'border-slate-200 bg-white' }}"
                                    data-color="{{ $colorKey }}"
                                    data-badge-class="{{ \App\Models\Lane::paletteFor($colorKey)['badge'] }}">
                                    <input type="radio" name="color" value="{{ $colorKey }}" {{ old('color', $lane->color) === $colorKey ? 'checked' : '' }} required class="sr-only">
                                    <div class="flex items-center space-x-1.5 truncate mr-1">
                                        <span class="w-3 h-3 rounded-full {{ \App\Models\Lane::paletteFor($colorKey)['dot'] }} shrink-0 border border-black/10"></span>
                                        <span class="text-[11px] font-bold text-slate-800 truncate">{{ $colorLabel }}</span>
                                    </div>
                                    <span class="inline-flex items-center px-1 py-0.5 rounded text-[9px] font-bold border {{ \App\Models\Lane::paletteFor($colorKey)['badge'] }} shrink-0">
                                        見本
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 共通下部バー: 送信ボタンエリア -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-4 pt-4 border-t border-slate-200">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('timetables.lanes.show', [$timetable, $lane]) }}"
                        class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition">
                        キャンセル
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5 cursor-pointer">
                        <span>💾</span>
                        <span>更新内容を保存</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>

@vite(['resources/js/lanes.js'])
</x-layouts.app>