<x-layouts.app :title="'担当班（レーン）管理 - ' . $timetable->name">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        title="担当班（レーン）管理"
        icon="🏷️"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）のレーン編成と、現場を率いる班長および固定スタッフを設定・管理します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('lanes.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1">
                <span>🏷️</span>
                <span>担当班（レーン）管理</span>
            </a>
            <span>/</span>
            <span class="text-slate-700 font-bold">📅 {{ $timetable->name }}</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <a href="{{ route('lanes.index') }}"
                    class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                    <span>←</span>
                    <span>運行日別 担当班編成一覧に戻る</span>
                </a>
                <a href="{{ route('timetable-tasks.create', ['timetable_id' => $timetable->id]) }}"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                    <span>タスク登録へ進む (Step 4へ)</span>
                    <span>→</span>
                </a>
            </div>
        </x-slot:actions>
    </x-page-header>

    <x-timetable-workflow-steps :current-step="3" :timetable="$timetable" />

    <!-- アラートメッセージ -->
    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>✅</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>⚠️</span>
        <span>{{ session('error') }}</span>
    </div>
    @endif

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

    <!-- 1. 新規レーン追加アコーディオン / フォーム -->
    <details class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden group">
        <summary class="p-4 sm:p-5 flex items-center justify-between cursor-pointer hover:bg-slate-50/70 transition font-bold text-sm text-slate-800 select-none">
            <div class="flex items-center space-x-2">
                <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-base">➕</span>
                <span>新しい担当班（レーン）を追加する</span>
            </div>
            <span class="text-xs text-indigo-600 font-medium group-open:hidden">クリックして開く ↓</span>
            <span class="text-xs text-slate-400 font-medium hidden group-open:inline">閉じる ↑</span>
        </summary>
        <div class="p-5 border-t border-slate-100 bg-slate-50/50">
            <form action="{{ route('timetables.lanes.store', $timetable) }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- レーン名 -->
                    <div>
                        <label for="new_name" class="block text-xs font-bold text-slate-700 mb-1">
                            レーン名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="new_name" name="name" value="{{ old('name') }}" required
                            placeholder="例: 広報班、音響班"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- アイコン -->
                    <div>
                        <label for="new_icon" class="block text-xs font-bold text-slate-700 mb-1">
                            絵文字アイコン
                        </label>
                        <input type="text" id="new_icon" name="icon" value="{{ old('icon', '📋') }}"
                            placeholder="例: 🎤, 🎪, 📢"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- テーマカラー -->
                    <div>
                        <label for="new_color" class="block text-xs font-bold text-slate-700 mb-1">
                            テーマカラー <span class="text-red-500">*</span>
                        </label>
                        <select id="new_color" name="color" required
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach($colorOptions as $colorKey => $colorLabel)
                            <option value="{{ $colorKey }}" {{ old('color') === $colorKey ? 'selected' : '' }}>
                                {{ $colorLabel }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 表示順 -->
                    <div>
                        <label for="new_display_order" class="block text-xs font-bold text-slate-700 mb-1">
                            表示順
                        </label>
                        <input type="number" id="new_display_order" name="display_order" value="{{ old('display_order', ($lanes->max('display_order') ?? 0) + 10) }}" min="0" max="9999"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- 班長（後から設定・変更可能） -->
                    <div>
                        <label for="new_lead_staff_id" class="block text-xs font-bold text-slate-700 mb-1">
                            👑 班長（後から設定・変更可能）
                        </label>
                        <select id="new_lead_staff_id" name="lead_staff_id"
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

                    <!-- 業務説明 -->
                    <div>
                        <label for="new_description" class="block text-xs font-bold text-slate-700 mb-1">
                            業務所管・担当エリア（メモ）
                        </label>
                        <input type="text" id="new_description" name="description" value="{{ old('description') }}"
                            placeholder="例: 会場放送設備の設営、式典アナウンスの実施"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- 固定スタッフ選択エリア (新規作成時) -->
                <div class="p-4 bg-white rounded-xl border border-indigo-200 space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <span class="text-xs font-bold text-indigo-950 flex items-center space-x-1">
                            <span>📌</span>
                            <span>固定スタッフ設定（この班のタスクに自動で基本配置されます）</span>
                        </span>
                        <span class="text-[11px] text-slate-500">決まっている場合は選択（複数選択可）</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 max-h-40 overflow-y-auto p-1 border border-slate-100 rounded-lg bg-slate-50/50">
                        @foreach($activeStaff as $st)
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
                        @endforeach
                    </div>
                </div>

                <!-- 主要班・有効化フラグ -->
                <div class="flex flex-wrap items-center gap-6 pt-2 border-t border-slate-200">
                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_core" value="1" {{ old('is_core') ? 'checked' : '' }}
                            class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                        <span class="text-xs font-bold text-slate-700">⭐ 主要班（マトリクス固定表示・強調）</span>
                    </label>

                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                            class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                        <span class="text-xs font-bold text-slate-700">👁️ 有効（マトリクス・帳票に表示）</span>
                    </label>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        このレーンを新規登録
                    </button>
                </div>
            </form>
        </div>
    </details>

    <!-- 2. 登録済みレーン一覧（ドラッグ＆ドロップ対応） -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-base text-slate-900 flex items-center space-x-2">
                    <span>📋</span>
                    <span>編成レーン一覧</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono font-normal">
                        全 {{ $lanes->count() }} レーン
                    </span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    左端のハンドル（⋮⋮）をドラッグ＆ドロップすると並び順を即時変更できます。
                </p>
            </div>
        </div>

        <div id="lanes-container"
            data-reorder-url="{{ route('timetables.lanes.reorder', $timetable) }}"
            class="divide-y divide-slate-100">
            @forelse($lanes as $lane)
            @php
                $classes = $lane->color_classes;
            @endphp
            <div data-lane-id="{{ $lane->id }}" draggable="true"
                class="p-4 sm:p-5 hover:bg-slate-50/70 transition flex flex-col lg:flex-row lg:items-center justify-between gap-4 cursor-default">
                <!-- 左側: ハンドル + レーン情報 -->
                <div class="flex items-center space-x-3 flex-1 min-w-0">
                    <!-- ドラッグハンドル -->
                    <div class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-indigo-600 px-1 py-2 select-none" title="ドラッグして並び替え">
                        <span class="text-base font-bold">⋮⋮</span>
                    </div>

                    <div class="space-y-1.5 flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="order-badge font-mono text-[10px] text-slate-400 font-bold px-1.5 py-0.5 bg-slate-100 rounded">
                                #{{ $lane->display_order }}
                            </span>

                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $classes['badge'] }}">
                                <span class="mr-1.5">{{ $lane->icon ?: '📋' }}</span>
                                <span>{{ $lane->name }}</span>
                            </span>

                            <!-- 主要班バッジ -->
                            @if($lane->is_core)
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-bold flex items-center space-x-0.5">
                                <span>⭐</span>
                                <span>主要班</span>
                            </span>
                            @endif

                            <!-- 非表示バッジ -->
                            @if(!$lane->is_active)
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 font-bold">
                                🔒 非表示中
                            </span>
                            @endif

                            <!-- タスク件数バッジ -->
                            <span class="text-[11px] text-slate-500 font-mono">
                                タスク: <strong class="text-slate-800">{{ $lane->tasks->count() }}</strong> 件
                            </span>

                            <!-- 固定スタッフ件数バッジ -->
                            <span class="text-[11px] text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded font-bold">
                                📌 固定スタッフ: {{ $lane->fixedStaff->count() }} 名
                            </span>

                            <!-- クイックタスク追加導線 -->
                            <a href="{{ route('timetable-tasks.create', ['timetable_id' => $timetable->id, 'lane_id' => $lane->id]) }}"
                                class="text-[11px] text-indigo-600 hover:text-indigo-800 font-bold ml-1 transition">
                                ＋ タスク追加
                            </a>
                        </div>

                        @if($lane->description)
                        <p class="text-xs text-slate-600 line-clamp-1">
                            {{ $lane->description }}
                        </p>
                        @endif

                        @if($lane->fixedStaff->isNotEmpty())
                        <div class="flex flex-wrap items-center gap-1 pt-0.5">
                            <span class="text-[10px] text-slate-400 font-bold">固定スタッフ:</span>
                            @foreach($lane->fixedStaff as $fixedMember)
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $fixedMember->name }}
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>

                <!-- 中央: 班長設定フォーム -->
                <div class="flex items-center gap-2 shrink-0 bg-slate-50 p-2 rounded-xl border border-slate-200/80">
                    <form action="{{ route('timetables.lanes.update-lead', [$timetable, $lane]) }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <div class="flex items-center space-x-1.5">
                            <span class="text-xs text-slate-500 font-bold" title="班長を設定・変更すると、この班のタスクにデフォルトで自動アサインされます">👑 班長:</span>
                            <select name="lead_staff_id" data-auto-submit
                                title="班長を選択すると、このレーンの全タスクに班長として自動アサインされます"
                                class="px-2.5 py-1 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                                <option value="" {{ !$lane->lead_staff_id ? 'selected' : '' }}>-- 未設定 --</option>
                                @foreach($activeStaff as $st)
                                <option value="{{ $st->id }}" {{ $lane->lead_staff_id == $st->id ? 'selected' : '' }}>
                                    {{ $st->name }} {{ $st->affiliation ? "({$st->affiliation})" : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </form>

                    @if($lane->leadStaff)
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">
                        設定済
                    </span>
                    @else
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-bold">
                        未定
                    </span>
                    @endif
                </div>

                <!-- 右側: 詳細編集・削除ボタン -->
                <div class="flex items-center space-x-2 shrink-0">
                    <!-- 編集アコーディオン開閉ボタン -->
                    <button type="button" data-toggle-target="#edit-lane-{{ $lane->id }}"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition">
                        ✏️ 編集
                    </button>

                    <!-- 削除ボタン（タスクがある場合は移行モーダルを開く） -->
                    @if($lane->tasks->count() > 0)
                    <button type="button"
                        data-action="open-delete-modal"
                        data-lane-id="{{ $lane->id }}"
                        data-lane-name="{{ $lane->name }}"
                        data-task-count="{{ $lane->tasks->count() }}"
                        data-delete-url="{{ route('timetables.lanes.destroy', [$timetable, $lane]) }}"
                        class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer">
                        🗑️ 削除...
                    </button>
                    @else
                    <form action="{{ route('timetables.lanes.destroy', [$timetable, $lane]) }}" method="POST" class="inline"
                        data-confirm="レーン「{{ $lane->name }}」を削除しますか？">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="px-3 py-1.5 bg-white hover:bg-rose-50 text-rose-600 border border-slate-200 hover:border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer">
                            🗑️ 削除
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- レーン編集展開パネル（初期非表示） -->
            <div id="edit-lane-{{ $lane->id }}" class="hidden p-5 bg-indigo-50/40 border-t border-slate-100">
                <form action="{{ route('timetables.lanes.update', [$timetable, $lane]) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="font-bold text-xs text-indigo-900 flex items-center space-x-1 mb-2">
                        <span>✏️</span>
                        <span>「{{ $lane->name }}」の情報を編集</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">レーン名</label>
                            <input type="text" name="name" value="{{ old('name', $lane->name) }}" required
                                class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">アイコン</label>
                            <input type="text" name="icon" value="{{ old('icon', $lane->icon) }}"
                                class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">テーマカラー</label>
                            <select name="color" required class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                                @foreach($colorOptions as $colorKey => $colorLabel)
                                <option value="{{ $colorKey }}" {{ old('color', $lane->color) === $colorKey ? 'selected' : '' }}>
                                    {{ $colorLabel }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">表示順</label>
                            <input type="number" name="display_order" value="{{ old('display_order', $lane->display_order) }}" required
                                class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">👑 班長</label>
                            <select name="lead_staff_id" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                                <option value="">-- 未設定 --</option>
                                @foreach($activeStaff as $st)
                                <option value="{{ $st->id }}" {{ old('lead_staff_id', $lane->lead_staff_id) == $st->id ? 'selected' : '' }}>
                                    {{ $st->name }} {{ $st->affiliation ? "({$st->affiliation})" : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">業務所管・メモ</label>
                            <input type="text" name="description" value="{{ old('description', $lane->description) }}"
                                class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                        </div>
                    </div>

                    <!-- 固定スタッフ選択エリア -->
                    <div class="p-3 bg-white rounded-xl border border-indigo-200 space-y-2">
                        <input type="hidden" name="sync_fixed_staff" value="1">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-indigo-900">📌 固定スタッフ設定（この班の全タスクに基本アサインされます）</span>
                            <span class="text-[10px] text-slate-500">余剰がある場合は他班のタスクに割り当てられます</span>
                        </div>
                        @php
                            $currentFixedIds = $lane->fixedStaff->pluck('id')->all();
                            if ($lane->lead_staff_id && ! in_array((int) $lane->lead_staff_id, array_map('intval', $currentFixedIds), true)) {
                                $currentFixedIds[] = (int) $lane->lead_staff_id;
                            }
                        @endphp
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 max-h-40 overflow-y-auto p-1">
                            @foreach($activeStaff as $st)
                            <label class="flex items-center space-x-1.5 text-xs text-slate-700 cursor-pointer hover:bg-indigo-50/60 p-1 rounded">
                                <input type="checkbox" name="fixed_staff_ids[]" value="{{ $st->id }}"
                                    {{ in_array($st->id, $currentFixedIds) ? 'checked' : '' }}
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-3.5 h-3.5">
                                <span class="truncate">{{ $st->name }}</span>
                                @if($lane->lead_staff_id == $st->id)
                                <span class="text-[10px] text-amber-700 font-bold ml-0.5 shrink-0">👑 班長</span>
                                @endif
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- 主要班・有効化フラグ -->
                    <div class="flex flex-wrap items-center gap-6 pt-2">
                        <label class="flex items-center space-x-2 cursor-pointer select-none">
                            <input type="checkbox" name="is_core" value="1" {{ old('is_core', $lane->is_core) ? 'checked' : '' }}
                                class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <span class="text-xs font-bold text-slate-700">⭐ 主要班（マトリクス固定表示・強調）</span>
                        </label>

                        <label class="flex items-center space-x-2 cursor-pointer select-none">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $lane->is_active) ? 'checked' : '' }}
                                class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <span class="text-xs font-bold text-slate-700">👁️ 有効（マトリクス・帳票に表示）</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-2">
                        <button type="button" data-close-target="#edit-lane-{{ $lane->id }}"
                            class="px-3 py-1.5 bg-white border border-slate-200 text-slate-600 rounded-lg text-xs font-bold hover:bg-slate-50 transition">
                            キャンセル
                        </button>
                        <button type="submit"
                            class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                            保存する
                        </button>
                    </div>
                </form>
            </div>
            @empty
            <div class="p-8 text-center text-slate-400 text-xs">
                レーンがまだ登録されていません。上の「新しい担当班を追加する」から登録してください。
            </div>
            @endforelse
        </div>
    </div>

    <!-- 3. Step 3 完了案内 & Step 4（タスク登録）への次ステップ大型バナー -->
    <div class="bg-gradient-to-r from-indigo-900 to-slate-900 rounded-2xl p-6 text-white shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center space-x-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                    Step 3 完了
                </span>
                <h3 class="text-base font-bold text-white">班長・固定スタッフの編成が整いましたか？</h3>
            </div>
            <p class="text-xs text-slate-300">
                次はタイムテーブル進行表を開いて、各班の作業タスクを登録していきます。班員はタスクに自動配置されます。
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('timetable-tasks.create', ['timetable_id' => $timetable->id]) }}"
                class="px-5 py-3 bg-indigo-500 hover:bg-indigo-400 text-white rounded-xl text-xs font-bold shadow-lg transition flex items-center space-x-2">
                <span>📝 タスク登録へ進む (Step 4へ)</span>
                <span>→</span>
            </a>
        </div>
    </div>

</div>


<!-- 4. タスク移行削除モーダル -->
<div id="delete-migration-modal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-rose-900 flex items-center space-x-2">
                <span>⚠️</span>
                <span>担当班の削除とタスク引き継ぎ</span>
            </h3>
            <button type="button" data-close-modal class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
        </div>

        <form id="delete-migration-form" action="" method="POST" class="p-5 space-y-4">
            @csrf
            @method('DELETE')

            <p class="text-xs text-slate-700 leading-relaxed">
                担当班「<strong id="modal-delete-lane-name" class="text-slate-900"></strong>」には、現在
                <strong id="modal-delete-task-count" class="text-rose-600 font-mono">0</strong> 件の作業タスクが登録されています。
            </p>

            <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-xl space-y-3">
                <div>
                    <label for="modal-migrate-target-select" class="block text-xs font-bold text-amber-900 mb-1">
                        タスクの引き継ぎ先レーンを選択（推奨）
                    </label>
                    <select id="modal-migrate-target-select" name="migrate_to_lane_id"
                        class="w-full px-3 py-2 bg-white border border-amber-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="">-- 引き継ぎ先レーンを選択 --</option>
                        @foreach($lanes as $otherLane)
                        <option value="{{ $otherLane->id }}">
                            {{ $otherLane->icon ?: '📋' }} {{ $otherLane->name }}
                        </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-amber-800 mt-1">※ 全タスクが選択したレーンに付け替えられた後、当該レーンが安全に削除されます。</p>
                </div>

                <div class="pt-2 border-t border-amber-200/80">
                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" name="cascade_delete_tasks" value="1"
                            class="w-4 h-4 text-rose-600 rounded border-amber-400 focus:ring-rose-500">
                        <span class="text-xs font-bold text-rose-800">引き継がず、タスクも含めてすべて削除する</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" data-close-modal
                    class="px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold transition">
                    キャンセル
                </button>
                <button type="submit"
                    class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    削除を実行する
                </button>
            </div>
        </form>
    </div>
</div>

@vite(['resources/js/lanes.js'])
</x-layouts.app>
