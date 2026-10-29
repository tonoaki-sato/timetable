<x-layouts.app title="新規タイムテーブル登録">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        title="新規タイムテーブル登録"
        icon="➕"
        description="まつり開催日（例: 前日設営、本祭1日目など）の基本枠を新設します。">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1">
                <span>⏱️</span>
                <span>タイムテーブル一覧</span>
            </a>
            <span>/</span>
            <span class="text-slate-700 font-bold">新規登録</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.index') }}" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>←</span>
                <span>タイムテーブル一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-timetable-workflow-steps :current-step="2" />

    <!-- バリデーションエラーサマリー -->
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
        <form action="{{ route('timetables.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- 開催日 & ステータス -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 開催日 -->
                <div>
                    <label for="target_date" class="block text-xs font-bold text-slate-700 mb-1">
                        開催日 <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="target_date" name="target_date" value="{{ old('target_date', '2026-10-17') }}" required
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
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    placeholder="例: 1日目 (Day 1) 本祭運行・パレード"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- 運行時間枠（開始時刻・終了時刻） -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_time" class="block text-xs font-bold text-slate-700 mb-1">
                        運行開始予定時刻 <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="start_time" name="start_time" value="{{ old('start_time', '08:00') }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label for="end_time" class="block text-xs font-bold text-slate-700 mb-1">
                        運行終了予定時刻 <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="end_time" name="end_time" value="{{ old('end_time', '18:00') }}" required
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
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('description') }}</textarea>
            <!-- 運行状況・時間帯指定（道路・まつり） -->
            @include('timetables._periods_form', ['timetable' => null])

            <!-- 初期班自動生成オプション (Step 3 連携) -->
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
                            チェックを入れて登録すると、登録完了後にそのまま各班の班長および固定スタッフ編成画面（Step 3）へ進みます。
                        </p>
                    </div>
                </label>
            </div>

            <!-- ボタンエリア -->
            <div class="pt-4 border-t border-slate-200 flex items-center justify-end space-x-3">
                <a href="{{ route('timetables.index') }}"
                    class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-medium transition">
                    キャンセル
                </a>
                <button type="submit"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition cursor-pointer flex items-center space-x-1.5">
                    <span>運行日を登録して担当班編成へ進む (Step 3へ)</span>
                    <span>→</span>
                </button>
            </div>

        </form>
    </div>

</div>
</x-layouts.app>
