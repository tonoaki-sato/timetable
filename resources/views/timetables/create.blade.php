<x-layouts.app title="運行情報登録">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        title="運行情報登録"
        icon="➕"
        description="まつり開催日（例: 前日設営、本祭1日目など）の基本枠を新設します。">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>運行情報一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">新規登録</span>
        </x-slot:breadcrumbs>
    </x-page-header>

    <div class="max-w-5xl mx-auto space-y-3.5">
        <!-- バリデーションエラーサマリー -->
        @if ($errors->any())
        <div class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl space-y-1">
            <div class="font-bold flex items-center space-x-1">
                <span>⚠️</span>
                <span>入力内容を確認してください</span>
            </div>
            @foreach ($errors->all() as $error)
            <div>・{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <!-- 入力フォーム -->
        <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200 shadow-2xs">
        <form action="{{ route('timetables.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- 過去の運行情報からの複製元選択セクション -->
            @if(($cloneSourceOptions ?? collect())->isNotEmpty())
            <div class="p-4 sm:p-5 bg-indigo-50/60 border border-indigo-200 rounded-xl space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-xs font-bold text-indigo-950 flex items-center space-x-1.5">
                            <span>📋</span>
                            <span>過去の運行情報をもとに作成（任意）</span>
                        </h3>
                        <p class="text-[11px] text-slate-600 mt-0.5">
                            過去の運行を選択すると、基本情報・担当班・タスク・拠点・搬出入・配置計画を引き継いで新しい運行を作成できます。
                        </p>
                    </div>
                    @if($sourceTimetable)
                    <a href="{{ route('timetables.create') }}"
                        class="inline-flex items-center space-x-1 px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-600 border border-slate-300 rounded-lg text-[11px] font-bold transition shrink-0 self-start sm:self-auto">
                        <span>✕</span>
                        <span>白紙作成に戻す</span>
                    </a>
                    @endif
                </div>

                <div>
                    <label for="source_timetable_id" class="block text-xs font-bold text-slate-700 mb-1">
                        複製元の運行情報
                    </label>
                    <select id="source_timetable_id" name="source_timetable_id"
                        data-create-url="{{ route('timetables.create') }}"
                        class="w-full px-3.5 py-2.5 bg-white border border-indigo-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- 白紙から新規作成する --</option>
                        @foreach($cloneSourceOptions as $option)
                        <option value="{{ $option->id }}" {{ (int) old('source_timetable_id', $sourceTimetable?->id ?? 0) === $option->id ? 'selected' : '' }}>
                            {{ $option->target_date?->format('Y/m/d') }} {{ $option->name }}（{{ $option->status_label }}：班{{ $option->lanes_count }}・タスク{{ $option->tasks_count }}・拠点{{ $option->locations_count }}）
                        </option>
                        @endforeach
                    </select>
                </div>

                @if($sourceTimetable)
                <div class="pt-3 border-t border-indigo-200/80 space-y-2.5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs font-bold text-indigo-950">引き継ぎ対象データを選択</span>
                        <span class="text-[10px] text-indigo-800 bg-white/80 px-2 py-0.5 rounded border border-indigo-200">
                            ※ ステータスは初期状態にリセットされ、拠点スケジュール・搬出入の日付は新開催日に合わせて自動シフトされます
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                        <!-- 1. 担当班 -->
                        <label data-clone-option-item class="flex items-start space-x-2 p-2.5 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                            <input type="hidden" name="copy_lanes" value="0">
                            <input type="checkbox" id="copy_lanes" name="copy_lanes" value="1"
                                {{ old('copy_lanes', '1') ? 'checked' : '' }}
                                class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 flex items-center justify-between">
                                    <span>🏷️ 担当班（レーン）</span>
                                    <span class="font-mono text-[10px] text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">{{ $sourceTimetable->lanes_count }}件</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">班名・色・アイコン・表示順を引き継ぎます</p>
                            </div>
                        </label>

                        <!-- 2. 班長・固定スタッフ -->
                        <label data-clone-option-item class="flex items-start space-x-2 p-2.5 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                            <input type="hidden" name="copy_lane_staff" value="0">
                            <input type="checkbox" id="copy_lane_staff" name="copy_lane_staff" value="1"
                                {{ old('copy_lane_staff', '1') ? 'checked' : '' }}
                                class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900">👑 班長・固定スタッフ</div>
                                <p class="text-[11px] text-slate-500 mt-0.5">各班の班長および専属固定メンバーを引き継ぎます</p>
                            </div>
                        </label>

                        <!-- 3. 作業タスク -->
                        <label data-clone-option-item class="flex items-start space-x-2 p-2.5 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                            <input type="hidden" name="copy_tasks" value="0">
                            <input type="checkbox" id="copy_tasks" name="copy_tasks" value="1"
                                {{ old('copy_tasks', '1') ? 'checked' : '' }}
                                class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 flex items-center justify-between">
                                    <span>📝 作業タスク</span>
                                    <span class="font-mono text-[10px] text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">{{ $sourceTimetable->tasks_count }}件</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">時間枠・必要人数を引き継ぎ（状態は未着手に初期化）</p>
                            </div>
                        </label>

                        <!-- 4. タスクのスタッフアサイン -->
                        <label data-clone-option-item class="flex items-start space-x-2 p-2.5 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                            <input type="hidden" name="copy_task_assignments" value="0">
                            <input type="checkbox" id="copy_task_assignments" name="copy_task_assignments" value="1"
                                {{ old('copy_task_assignments', '0') ? 'checked' : '' }}
                                class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900">👥 タスクのスタッフアサイン</div>
                                <p class="text-[11px] text-slate-500 mt-0.5">担当人員の割当も引き継ぐ（欠席除外・配置済で初期化）</p>
                            </div>
                        </label>

                        <!-- 5. 拠点・利用スケジュール -->
                        <label data-clone-option-item class="flex items-start space-x-2 p-2.5 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                            <input type="hidden" name="copy_locations" value="0">
                            <input type="checkbox" id="copy_locations" name="copy_locations" value="1"
                                {{ old('copy_locations', '1') ? 'checked' : '' }}
                                class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 flex items-center justify-between">
                                    <span>📍 拠点・利用時間帯スケジュール</span>
                                    <span class="font-mono text-[10px] text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">{{ $sourceTimetable->locations_count }}件</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">日付を自動シフト（確保状態は未確保に初期化）</p>
                            </div>
                        </label>

                        <!-- 6. 資材搬出入計画 -->
                        <label data-clone-option-item class="flex items-start space-x-2 p-2.5 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                            <input type="hidden" name="copy_logistics_plans" value="0">
                            <input type="checkbox" id="copy_logistics_plans" name="copy_logistics_plans" value="1"
                                {{ old('copy_logistics_plans', '1') ? 'checked' : '' }}
                                class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 flex items-center justify-between">
                                    <span>🚚 資材搬出入計画</span>
                                    <span class="font-mono text-[10px] text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">{{ $sourceTimetable->logistics_plans_count }}件</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">日付を自動シフト（状態は計画中に初期化）</p>
                            </div>
                        </label>

                        <!-- 7. 配置計画・白地図配置 -->
                        <label data-clone-option-item class="sm:col-span-2 flex items-start space-x-2 p-2.5 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                            <input type="hidden" name="copy_site_layouts" value="0">
                            <input type="checkbox" id="copy_site_layouts" name="copy_site_layouts" value="1"
                                {{ old('copy_site_layouts', '1') ? 'checked' : '' }}
                                class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 flex items-center justify-between">
                                    <span>🗺️ 現場配置計画（ブース・設備）＆ 🎨 白地図配置</span>
                                    <span class="font-mono text-[10px] text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">配置 {{ $sourceTimetable->layout_items_count }} / 白地図 {{ $sourceTimetable->placements_count }}</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">エリア・ブース・設備・CAD図面配置を引き継ぎます（配置ステータスは未定に初期化）</p>
                            </div>
                        </label>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- 開催日 & ステータス -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 開催日 -->
                <div>
                    <label for="target_date" class="block text-xs font-bold text-slate-700 mb-1">
                        開催日 <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="target_date" name="target_date" value="{{ old('target_date', $defaultDate ?? now()->toDateString()) }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>

                <!-- ステータス -->
                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 mb-1">
                        ステータス <span class="text-red-500">*</span>
                    </label>
                    <select id="status" name="status" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ old('status', 'draft') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 運行日名称 -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                    運行日名称 <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $sourceTimetable ? $sourceTimetable->name . ' (複製)' : '') }}" required
                    placeholder="例: 1日目 (Day 1) 本祭運行・パレード"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- 運行時間枠（開始時刻・終了時刻） -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_time" class="block text-xs font-bold text-slate-700 mb-1">
                        運行開始予定時刻 <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="start_time" name="start_time" value="{{ old('start_time', $sourceTimetable ? substr($sourceTimetable->start_time, 0, 5) : '08:00') }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label for="end_time" class="block text-xs font-bold text-slate-700 mb-1">
                        運行終了予定時刻 <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="end_time" name="end_time" value="{{ old('end_time', $sourceTimetable ? substr($sourceTimetable->end_time, 0, 5) : '18:00') }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
            </div>

            <!-- 概要・全体注意事項 -->
            <div>
                <label for="description" class="block text-xs font-bold text-slate-700 mb-1">
                    概要・全体注意事項
                </label>
                <textarea id="description" name="description" rows="3"
                    placeholder="交通規制の時間帯や各班の全体連絡事項など"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('description', $sourceTimetable?->description ?? '') }}</textarea>
            </div>

            <!-- 運行状況・時間帯指定（道路・まつり） -->
            @include('timetables._periods_form', ['timetable' => $sourceTimetable ?? null])

            <!-- 初期班自動生成オプション (白紙作成時のみ表示) -->
            @if(! $sourceTimetable)
            <div class="p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-1">
                <label class="flex items-start space-x-2.5 cursor-pointer select-none">
                    <input type="hidden" name="create_default_lanes" value="0">
                    <input type="checkbox" name="create_default_lanes" value="1" {{ old('create_default_lanes', '1') === '1' ? 'checked' : '' }}
                        class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                    <div>
                        <span class="text-xs font-bold text-indigo-950">
                            🏷️ まつり標準10班（カラーコーン・給水・衛生・巡回・キッズ村など）を自動生成する（推奨）
                        </span>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            チェックを入れて登録すると、登録完了後にそのまま各班の班長および固定スタッフ編成画面（Step 2）へ進みます。
                        </p>
                    </div>
                </label>
            </div>
            @endif

            <!-- ボタンエリア -->
            <div class="pt-4 border-t border-slate-200 flex items-center justify-end space-x-2">
                <a href="{{ route('timetables.index') }}"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition">
                    キャンセル
                </a>
                <button type="submit"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer flex items-center space-x-1.5 whitespace-nowrap">
                    @if($sourceTimetable)
                        <span>📋 過去運行をもとに新規登録する</span>
                    @else
                        <span>運行日を登録して担当班編成へ進む (Step 2へ)</span>
                    @endif
                    <span>→</span>
                </button>
            </div>

        </form>
    </div>
    </div>

</div>

@vite(['resources/js/timetable-create.js'])
</x-layouts.app>
