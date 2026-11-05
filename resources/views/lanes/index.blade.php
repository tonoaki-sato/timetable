<x-layouts.app :title="$timetable->name . ' 担当班一覧'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$timetable->name . ' 担当班一覧'"
        icon="🏷️"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）のレーン編成と、現場を率いる班長および固定スタッフを設定・管理します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>タイムテーブル一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>📅</span>
                <span>{{ $timetable->name }}</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">🏷️ 担当班一覧</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.lanes.create', $timetable) }}"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>➕</span>
                <span>新規担当班を追加</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー (日程切替・サブナビ) -->
    <x-timetable-context-bar :timetable="$timetable" active="lanes" />

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

    <!-- 登録済みレーン一覧（ドラッグ＆ドロップ対応） -->
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

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">班名</th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">バッジ</th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">班長名</th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">役割概要</th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap text-right">操作</th>
                    </tr>
                </thead>
                <tbody id="lanes-container"
                    data-reorder-url="{{ route('timetables.lanes.reorder', $timetable) }}"
                    class="divide-y divide-slate-100 bg-white">
                    @forelse($lanes as $lane)
                    @php
                        $classes = $lane->color_classes;
                    @endphp
                    <tr data-lane-id="{{ $lane->id }}" draggable="true"
                        class="hover:bg-slate-50/80 transition cursor-default">
                        <!-- 班名 -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <span class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-indigo-600 px-1 select-none font-bold text-sm" title="ドラッグして並び替え">⋮⋮</span>
                                <a href="{{ route('timetables.lanes.show', [$timetable, $lane]) }}"
                                    class="font-bold text-slate-800 hover:text-indigo-600 hover:underline transition"
                                    title="クリックして詳細を表示">
                                    {{ $lane->name }}
                                </a>
                            </div>
                        </td>

                        <!-- バッジ -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border {{ $classes['badge'] }}">
                                <span>{{ $lane->icon ?: '📋' }}</span>
                                <span class="ml-1">{{ $lane->name }}</span>
                            </span>
                        </td>

                        <!-- 班長名 -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($lane->leadStaff)
                            <span class="inline-flex items-center space-x-1 font-bold text-slate-800">
                                <span>👑</span>
                                <span>{{ $lane->leadStaff->name }}</span>
                            </span>
                            @else
                            <span class="text-slate-400">未設定</span>
                            @endif
                        </td>

                        <!-- 役割概要 -->
                        <td class="px-4 py-3 whitespace-nowrap max-w-xs md:max-w-md truncate text-slate-600" title="{{ $lane->description }}">
                            {{ $lane->description ?: '—' }}
                        </td>

                        <!-- 操作 -->
                        <td class="px-4 py-3 whitespace-nowrap text-right space-x-1">
                            <a href="{{ route('timetables.lanes.edit', [$timetable, $lane]) }}"
                                class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition inline-flex items-center space-x-1">
                                <span>✏️</span>
                                <span>編集</span>
                            </a>
                            @if($lane->tasks->count() > 0)
                            <button type="button"
                                data-action="open-delete-modal"
                                data-lane-id="{{ $lane->id }}"
                                data-lane-name="{{ $lane->name }}"
                                data-task-count="{{ $lane->tasks->count() }}"
                                data-delete-url="{{ route('timetables.lanes.destroy', [$timetable, $lane]) }}"
                                class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer inline-flex items-center space-x-1">
                                <span>🗑️</span>
                                <span>削除...</span>
                            </button>
                            @else
                            <form action="{{ route('timetables.lanes.destroy', [$timetable, $lane]) }}" method="POST" class="inline"
                                data-confirm="レーン「{{ $lane->name }}」を削除しますか？">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-2.5 py-1 bg-white hover:bg-rose-50 text-rose-600 border border-slate-200 hover:border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer inline-flex items-center space-x-1">
                                    <span>🗑️</span>
                                    <span>削除</span>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-400 text-xs">
                            レーンがまだ登録されていません。ヘッダーの「➕ 新規担当班を追加」から登録してください。
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Step 2 完了案内 & Step 3（タスク登録）への次ステップ大型バナー -->
    <div class="bg-gradient-to-r from-indigo-900 to-slate-900 rounded-2xl p-6 text-white shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center space-x-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                    Step 2 完了
                </span>
                <h3 class="text-base font-bold text-white">班長・固定スタッフの編成が整いましたか？</h3>
            </div>
            <p class="text-xs text-slate-300">
                次はタイムテーブル進行表を開いて、各班の作業タスクを登録していきます。班員はタスクに自動配置されます。
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('timetables.tasks.index', $timetable) }}"
                class="px-5 py-3 bg-indigo-500 hover:bg-indigo-400 text-white rounded-xl text-xs font-bold shadow-lg transition flex items-center space-x-2">
                <span>📝 タスク管理へ進む (Step 3へ)</span>
                <span>→</span>
            </a>
        </div>
    </div>

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
