<x-layouts.app :title="'担当班（レーン）管理 - ' . $timetable->name">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ナビゲーション & ヘッダー -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition">
                    📅 {{ $timetable->name }}
                </a>
                <span>/</span>
                <span>担当班（レーン）管理</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 flex items-center space-x-2">
                <span>🏷️</span>
                <span>担当班（レーン）管理</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                「{{ $timetable->name }}」（{{ $timetable->target_date?->format('Y年m月d日') }}）のレーン編成と、現場を率いる主担当（リーダー）を設定・管理します。
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}"
                class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                <span>⏱️</span>
                <span>運行マトリクスを見る</span>
            </a>
            <a href="{{ route('timetables.show', $timetable) }}"
                class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                <span>←</span>
                <span>運行日詳細に戻る</span>
            </a>
        </div>
    </div>

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
                            表示順 <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="new_display_order" name="display_order" value="{{ old('display_order', ($lanes->max('display_order') ?? 0) + 10) }}" required min="0" max="9999"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- 主担当スタッフ（後付け可能） -->
                    <div>
                        <label for="new_lead_staff_id" class="block text-xs font-bold text-slate-700 mb-1">
                            主担当スタッフ（後から設定・変更可能）
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
                        <p class="text-[10px] text-slate-400 mt-1">※ 人員配置が確定していない場合は、未設定のまま作成できます。</p>
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

                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        このレーンを新規登録
                    </button>
                </div>
            </form>
        </div>
    </details>

    <!-- 2. 登録済みレーン一覧 -->
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
                    主担当（現場リーダー）はドロップダウンから直接クイック変更・解除できます。
                </p>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($lanes as $lane)
            @php
                $classes = $lane->color_classes;
            @endphp
            <div class="p-4 sm:p-5 hover:bg-slate-50/50 transition flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- 左側: レーン情報 -->
                <div class="space-y-1.5 flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-[10px] text-slate-400 font-bold px-1.5 py-0.5 bg-slate-100 rounded">
                            #{{ $lane->display_order }}
                        </span>

                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $classes['badge'] }}">
                            <span class="mr-1.5">{{ $lane->icon ?: '📋' }}</span>
                            <span>{{ $lane->name }}</span>
                        </span>

                        <!-- タスク件数バッジ -->
                        <span class="text-[11px] text-slate-500 font-mono">
                            タスク: <strong class="text-slate-800">{{ $lane->tasks->count() }}</strong> 件
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
                </div>

                <!-- 中央: 主担当（リーダー）設定フォーム -->
                <div class="flex items-center gap-2 shrink-0 bg-slate-50 p-2.5 rounded-xl border border-slate-200/80">
                    <form action="{{ route('timetables.lanes.update-lead', [$timetable, $lane]) }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <div class="flex items-center space-x-1.5">
                            <span class="text-xs text-slate-500 font-bold">👤 主担当:</span>
                            <select name="lead_staff_id" data-auto-submit
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

                <!-- 右側: 詳細編集・削除モーダル/ボタン -->
                <div class="flex items-center space-x-2 shrink-0">
                    <!-- 編集アコーディオン開閉ボタン -->
                    <button type="button" data-toggle-target="#edit-lane-{{ $lane->id }}"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition">
                        ✏️ 編集
                    </button>

                    <!-- 削除ボタン（タスクが存在する場合は無効化・警告） -->
                    @if($lane->tasks->count() > 0)
                    <button type="button" disabled
                        title="タスクが登録されているため削除できません"
                        class="px-3 py-1.5 bg-slate-100 text-slate-400 border border-slate-200 rounded-lg text-xs font-bold cursor-not-allowed">
                        🗑️ 削除
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
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">主担当スタッフ</label>
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

</div>
</x-layouts.app>
