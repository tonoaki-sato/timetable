<x-layouts.app :title="'担当班の編集 - ' . $lane->name">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="'担当班の編集: ' . $lane->name"
        icon="✏️"
        :description="'「' . $timetable->name . '」における担当班「' . $lane->name . '」の基本情報・班長・固定スタッフ設定を更新します。'">
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
                <span class="font-mono text-slate-400">#{{ $lane->display_order }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border {{ $lane->color_classes['badge'] }}">
                    {{ $lane->icon ?: '📋' }} {{ $lane->name }}
                </span>
            </div>
        </div>

        <form action="{{ route('timetables.lanes.update', [$timetable, $lane]) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="sync_fixed_staff" value="1">

            <!-- 1. 基本属性グリッド -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <!-- 担当班名 -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                        担当班名（レーン名） <span class="text-rose-600">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name', $lane->name) }}" required
                        placeholder="例: 会場放送班、音響PA班"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- 絵文字アイコン -->
                <div>
                    <label for="icon" class="block text-xs font-bold text-slate-700 mb-1">
                        絵文字アイコン
                    </label>
                    <input type="text" id="icon" name="icon" value="{{ old('icon', $lane->icon) }}"
                        placeholder="例: 🎤, 🎪, 📢, 🔺"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- テーマカラー -->
                <div>
                    <label for="color" class="block text-xs font-bold text-slate-700 mb-1">
                        テーマカラー <span class="text-rose-600">*</span>
                    </label>
                    <select id="color" name="color" required
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($colorOptions as $colorKey => $colorLabel)
                        <option value="{{ $colorKey }}" {{ old('color', $lane->color) === $colorKey ? 'selected' : '' }}>
                            {{ $colorLabel }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- 表示順 -->
                <div>
                    <label for="display_order" class="block text-xs font-bold text-slate-700 mb-1">
                        表示順
                    </label>
                    <input type="number" id="display_order" name="display_order" value="{{ old('display_order', $lane->display_order) }}" min="0" max="9999"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <!-- 2. 班長および説明メモ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 班長（主担当スタッフ） -->
                <div>
                    <label for="lead_staff_id" class="block text-xs font-bold text-slate-700 mb-1">
                        👑 班長（現場リーダー / 後から設定・変更可能）
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
                    <p class="text-[10px] text-slate-400 mt-1">※ 班長に任命されたスタッフは、この班の全タスクへ自動的にアサイン同期されます。</p>
                </div>

                <!-- 業務説明メモ -->
                <div>
                    <label for="description" class="block text-xs font-bold text-slate-700 mb-1">
                        業務所管・担当エリア（メモ）
                    </label>
                    <input type="text" id="description" name="description" value="{{ old('description', $lane->description) }}"
                        placeholder="例: 会場放送設備の設営、式典アナウンスの実施、本陣周辺"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <!-- 3. 固定スタッフ選択エリア -->
            @php
                $currentFixedStaffIds = $lane->fixedStaff->pluck('id')->all();
            @endphp
            <div class="p-4 bg-indigo-50/40 rounded-xl border border-indigo-200/80 space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <span class="text-xs font-bold text-indigo-950 flex items-center space-x-1">
                        <span>📌</span>
                        <span>固定スタッフ設定（この班のタスクに自動で基本配置されます）</span>
                    </span>
                    <span class="text-[11px] text-slate-500">現在: {{ count($currentFixedStaffIds) }}名設定中</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 max-h-48 overflow-y-auto p-2 border border-slate-200 rounded-lg bg-white">
                    @forelse($activeStaff as $st)
                    <label class="flex items-center space-x-1.5 text-xs text-slate-700 cursor-pointer hover:bg-indigo-50 p-1.5 rounded transition">
                        <input type="checkbox" name="fixed_staff_ids[]" value="{{ $st->id }}"
                            {{ in_array($st->id, old('fixed_staff_ids', $currentFixedStaffIds)) ? 'checked' : '' }}
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

            <!-- 4. 主要班・有効化フラグ -->
            <div class="flex flex-wrap items-center gap-6 pt-3 border-t border-slate-200">
                <label class="flex items-center space-x-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_core" value="1" {{ old('is_core', $lane->is_core) ? 'checked' : '' }}
                        class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                    <span class="text-xs font-bold text-slate-700">⭐ 主要班（マトリクス固定表示・強調）</span>
                </label>

                <label class="flex items-center space-x-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $lane->is_active ? '1' : '0') === '1' ? 'checked' : '' }}
                        class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                    <span class="text-xs font-bold text-slate-700">👁️ 有効（マトリクス・帳票に表示）</span>
                </label>
            </div>

            <!-- 5. 送信ボタンエリア -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                <div class="text-xs text-slate-400 font-mono">
                    紐づくタスク: {{ $lane->tasks->count() }} 件
                </div>

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

    <!-- 危険ゾーン（削除） -->
    @can('delete', $lane)
    <div class="bg-rose-50/50 rounded-2xl border border-rose-200 p-5 space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xs font-bold text-rose-900 flex items-center space-x-1">
                    <span>⚠️</span>
                    <span>危険な操作: 担当班の削除</span>
                </h3>
                <p class="text-[11px] text-rose-700 mt-0.5">
                    この担当班を削除します。タスクが登録されている場合は、別の担当班への引き継ぎまたは一括削除が必要です。
                </p>
            </div>

            @if($lane->tasks->count() > 0)
            <button type="button"
                data-action="open-delete-modal"
                data-lane-id="{{ $lane->id }}"
                data-lane-name="{{ $lane->name }}"
                data-task-count="{{ $lane->tasks->count() }}"
                data-delete-url="{{ route('timetables.lanes.destroy', [$timetable, $lane]) }}"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                🗑️ この担当班を削除する...
            </button>
            @else
            <form action="{{ route('timetables.lanes.destroy', [$timetable, $lane]) }}" method="POST"
                data-confirm="担当班「{{ $lane->name }}」を削除しますか？">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    🗑️ この担当班を削除する
                </button>
            </form>
            @endif
        </div>
    </div>
    @endcan

</div>

<!-- タスク移行削除モーダル -->
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
                担当班「<strong id="modal-delete-lane-name" class="text-slate-900">{{ $lane->name }}</strong>」には、現在
                <strong id="modal-delete-task-count" class="text-rose-600 font-mono">{{ $lane->tasks->count() }}</strong> 件の作業タスクが登録されています。
            </p>

            <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-xl space-y-3">
                <div>
                    <label for="modal-migrate-target-select" class="block text-xs font-bold text-amber-900 mb-1">
                        タスクの引き継ぎ先レーンを選択（推奨）
                    </label>
                    <select id="modal-migrate-target-select" name="migrate_to_lane_id"
                        class="w-full px-3 py-2 bg-white border border-amber-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="">-- 引き継ぎ先レーンを選択 --</option>
                        @foreach($otherLanes as $targetLane)
                        <option value="{{ $targetLane->id }}">
                            {{ $targetLane->icon ?: '📋' }} {{ $targetLane->name }}
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

