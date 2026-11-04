<x-layouts.app title="タイムテーブル編集">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        title="タイムテーブル編集"
        icon="✏️"
        :description="'「' . $timetable->name . '」の運行基本設定を更新します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>タイムテーブル一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition shrink-0">
                <span>📅 {{ $timetable->name }}</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">編集</span>
        </x-slot:breadcrumbs>
    </x-page-header>

    <div class="max-w-4xl mx-auto space-y-3.5">
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
        <form action="{{ route('timetables.update', $timetable) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- 開催日 & ステータス -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 開催日 -->
                <div>
                    <label for="target_date" class="block text-xs font-bold text-slate-700 mb-1">
                        開催日 <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="target_date" name="target_date"
                        value="{{ old('target_date', $timetable->target_date?->format('Y-m-d')) }}" required
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
                        <option value="{{ $key }}" {{ old('status', $timetable->status) === $key ? 'selected' : '' }}>
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
                <input type="text" id="name" name="name" value="{{ old('name', $timetable->name) }}" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- 運行時間枠（開始時刻・終了時刻） -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_time" class="block text-xs font-bold text-slate-700 mb-1">
                        運行開始予定時刻 <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="start_time" name="start_time"
                        value="{{ old('start_time', substr($timetable->start_time, 0, 5)) }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label for="end_time" class="block text-xs font-bold text-slate-700 mb-1">
                        運行終了予定時刻 <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="end_time" name="end_time"
                        value="{{ old('end_time', substr($timetable->end_time, 0, 5)) }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
            </div>

            <!-- 概要・全体注意事項 -->
            <div>
                <label for="description" class="block text-xs font-bold text-slate-700 mb-1">
                    概要・全体注意事項
                </label>
                <textarea id="description" name="description" rows="3"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('description', $timetable->description) }}</textarea>
            </div>

            <!-- 運行状況・時間帯指定（道路・まつり） -->
            @include('timetables._periods_form', ['timetable' => $timetable])

            <!-- ボタンエリア -->
            <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
                <a href="{{ route('timetables.show', $timetable) }}"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition">
                    キャンセル
                </a>
                <button type="submit"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer">
                    変更内容を保存する
                </button>
            </div>

        </form>
    </div>
    </div>

</div>
</x-layouts.app>
