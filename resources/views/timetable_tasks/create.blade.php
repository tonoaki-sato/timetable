<x-layouts.app :title="'新規作業タスク登録 - ' . $timetable->name">
<div class="max-w-3xl mx-auto px-4 py-6 space-y-6">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        title="新規作業タスク登録"
        icon="➕"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y/m/d') . '）に紐づく作業タスクと必要人員を登録します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1">
                <span>⏱️</span>
                <span>タイムテーブル一覧</span>
            </a>
            <span>/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition">
                <span>📅 {{ $timetable->name }}</span>
            </a>
            <span>/</span>
            <span class="text-slate-700 font-bold">タスク追加</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.show', $timetable) }}" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>←</span>
                <span>「{{ $timetable->name }}」に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- エラーサマリー -->
    @if ($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl space-y-1">
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
    <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
        <form action="{{ route('timetable-tasks.store') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="timetable_id" value="{{ $timetable->id }}">

            <!-- 作業タスク名 -->
            <div>
                <label for="title" class="block text-xs font-bold text-slate-700 mb-1">
                    作業タスク名 <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required
                    placeholder="例: 中央通り カラーコーン・規制バー設置"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- 担当班 & 場所 -->
            <div class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="lane_id" class="block text-xs font-bold text-slate-700 mb-1">
                            担当班（レーン） <span class="text-red-500">*</span>
                        </label>
                        <select id="lane_id" name="lane_id" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @if(isset($timetableLanes) && $timetableLanes->isNotEmpty())
                                @foreach($timetableLanes as $l)
                                <option value="{{ $l->id }}"
                                    data-lead-name="{{ $l->leadStaff?->name }}"
                                    data-lead-aff="{{ $l->leadStaff?->affiliation }}"
                                    {{ (old('lane_id', $selectedLane?->id) == $l->id || old('lane') == $l->name) ? 'selected' : '' }}>
                                    {{ $l->icon ?: '📋' }} {{ $l->name }}
                                    @if($l->leadStaff)
                                        (主担当: {{ $l->leadStaff->name }})
                                    @endif
                                </option>
                                @endforeach
                            @else
                                @foreach($lanes as $key => $label)
                                <option value="" {{ old('lane') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div>
                        <label for="location" class="block text-xs font-bold text-slate-700 mb-1">
                            実施場所・持ち場
                        </label>
                        <input type="text" id="location" name="location" value="{{ old('location') }}"
                            placeholder="例: 第1ゲート前交差点、西口救護テント"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- 主担当スタッフの自動アサイン案内 -->
                @php
                    $initialLeadStaff = $selectedLane?->leadStaff;
                @endphp
                @if($initialLeadStaff)
                <div class="p-3 bg-indigo-50/70 border border-indigo-200/80 rounded-xl flex items-center justify-between text-xs text-indigo-900">
                    <div class="flex items-center space-x-2">
                        <span>👤</span>
                        <span>
                            レーン主担当: <strong>{{ $initialLeadStaff->name }}</strong>
                            @if($initialLeadStaff->affiliation)
                            <span class="text-indigo-600 text-[11px]">({{ $initialLeadStaff->affiliation }})</span>
                            @endif
                        </span>
                    </div>
                    <label class="inline-flex items-center space-x-2 cursor-pointer font-bold text-indigo-700 hover:text-indigo-900">
                        <input type="checkbox" name="auto_assign_lead" value="1" {{ old('auto_assign_lead', '1') ? 'checked' : '' }}
                            class="w-4 h-4 text-indigo-600 rounded border-indigo-300 focus:ring-indigo-500">
                        <span>主担当を班リーダーとして自動アサインする</span>
                    </label>
                </div>
                @elseif($selectedLane)
                <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-500 flex items-center justify-between">
                    <span>👤 このレーンには現在主担当が設定されていません。</span>
                    <a href="{{ route('timetables.lanes.index', $timetable) }}" class="text-indigo-600 hover:underline font-bold text-[11px]">
                        主担当を設定する →
                    </a>
                </div>
                @endif
            </div>

            <!-- 時間帯（開始〜終了） -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_time" class="block text-xs font-bold text-slate-700 mb-1">
                        作業開始時刻 <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="start_time" name="start_time" value="{{ old('start_time', $suggestedStartTime ?? '08:00') }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label for="end_time" class="block text-xs font-bold text-slate-700 mb-1">
                        作業終了時刻 <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="end_time" name="end_time" value="{{ old('end_time', '09:30') }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
            </div>

            <!-- 必要人員数 ＆ 「〇〇名以上」設定 -->
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800 flex items-center space-x-1">
                        <span>👥</span>
                        <span>現場スタッフ必要人員設定</span>
                    </span>
                    <span class="text-[11px] text-slate-500">ボランティア・現場スタッフのアテンド基準</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                    <div>
                        <label for="required_people_count" class="block text-xs font-semibold text-slate-700 mb-1">
                            必要人数（名） <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="required_people_count" name="required_people_count"
                            value="{{ old('required_people_count', 2) }}" min="0" max="999" required
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    </div>

                    <div class="pt-2 sm:pt-5">
                        <label class="flex items-start space-x-2.5 cursor-pointer">
                            <input type="checkbox" name="is_at_least" value="1"
                                {{ old('is_at_least') ? 'checked' : '' }}
                                class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">「〇〇名以上」として扱う</span>
                                <span class="text-[11px] text-slate-500 block leading-tight">
                                    チェックを入れると設定人数以上のスタッフが配置されても「充足（OK）」と判定されます。
                                </span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 作業内容・手順指示 (content) -->
            <div>
                <label for="content" class="block text-xs font-bold text-slate-700 mb-1">
                    作業内容・手順指示
                </label>
                <textarea id="content" name="content" rows="4"
                    placeholder="現場スタッフへの具体的な手順指示や注意事項（例: コーンバーは3m間隔で設置、一般車両の誤進入を防ぐため声掛けを徹底）"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('content') }}</textarea>
            </div>

            <!-- ステータス ＆ フラグ設定 -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 mb-1">
                        初期進行ステータス <span class="text-red-500">*</span>
                    </label>
                    <select id="status" name="status" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ old('status', 'not_started') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-6">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_critical" value="1"
                            {{ old('is_critical') ? 'checked' : '' }}
                            class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 h-4 w-4">
                        <span class="text-xs font-bold text-rose-700">🚨 最優先タスク（重要）</span>
                    </label>
                </div>

                <div class="pt-6">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="requires_review" value="1"
                            {{ old('requires_review') ? 'checked' : '' }}
                            class="rounded border-slate-300 text-amber-600 focus:ring-amber-500 h-4 w-4">
                        <span class="text-xs font-bold text-amber-800">📋 本部完了確認を要する</span>
                    </label>
                </div>
            </div>

            <!-- 送信ボタンエリア -->
            <div class="pt-6 border-t border-slate-200 flex items-center justify-end space-x-3">
                <a href="{{ route('timetables.show', $timetable) }}"
                    class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-medium transition">
                    キャンセル
                </a>
                <button type="submit"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition cursor-pointer">
                    作業タスクを登録する
                </button>
            </div>

        </form>
    </div>

</div>
</x-layouts.app>
