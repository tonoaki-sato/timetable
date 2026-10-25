<x-layouts.app title="年間スケジュール編集">
<div class="max-w-3xl mx-auto px-4 py-6 space-y-6">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        title="年間スケジュール編集"
        icon="✏️"
        :description="'「' . $schedule->title . '」の予定内容を更新します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('annual-schedules.index', ['year' => $schedule->target_year]) }}" class="hover:text-indigo-600 transition flex items-center space-x-1">
                <span>📋</span>
                <span>{{ $schedule->target_year }}年度 年間スケジュール一覧</span>
            </a>
            <span>/</span>
            <a href="{{ route('annual-schedules.show', $schedule) }}" class="hover:text-indigo-600 transition">
                <span>{{ $schedule->title }}</span>
            </a>
            <span>/</span>
            <span class="text-slate-700 font-bold">編集</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('annual-schedules.show', $schedule) }}" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>←</span>
                <span>詳細に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

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
        <form action="{{ route('annual-schedules.update', $schedule) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- 対象年度 & 区分 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 対象年度 -->
                <div>
                    <label for="target_year" class="block text-xs font-bold text-slate-700 mb-1">
                        対象年度 <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="target_year" name="target_year" min="2020" max="2050"
                        value="{{ old('target_year', $schedule->target_year) }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>

                <!-- 区分（カテゴリ） -->
                <div>
                    <label for="category" class="block text-xs font-bold text-slate-700 mb-1">
                        区分（カテゴリ） <span class="text-red-500">*</span>
                    </label>
                    <select id="category" name="category" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category', $schedule->category) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- スケジュール名 -->
            <div>
                <label for="title" class="block text-xs font-bold text-slate-700 mb-1">
                    スケジュール・行事名 <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" value="{{ old('title', $schedule->title) }}" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- 実施日程（開始日・終了日） -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-xs font-bold text-slate-700 mb-1">
                        開始日 <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="start_date" name="start_date"
                        value="{{ old('start_date', $schedule->start_date?->format('Y-m-d')) }}" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label for="end_date" class="block text-xs font-bold text-slate-700 mb-1">
                        終了日 <span class="text-slate-400 font-normal">（単日の場合は空欄可）</span>
                    </label>
                    <input type="date" id="end_date" name="end_date"
                        value="{{ old('end_date', $schedule->end_date?->format('Y-m-d')) }}"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
            </div>

            <!-- 実施場所 & ステータス -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 実施場所 -->
                <div>
                    <label for="location" class="block text-xs font-bold text-slate-700 mb-1">
                        実施場所・会場名
                    </label>
                    <input type="text" id="location" name="location" value="{{ old('location', $schedule->location) }}"
                        placeholder="例: 市民会館 大ホール / まつり運行本部"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- ステータス -->
                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 mb-1">
                        ステータス <span class="text-red-500">*</span>
                    </label>
                    <select id="status" name="status" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ old('status', $schedule->status) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 担当者（スタッフ） -->
            <div>
                <label for="staff_id" class="block text-xs font-bold text-slate-700 mb-1">
                    担当者（スタッフ） <span class="text-slate-400 font-normal">（任意）</span>
                </label>
                <select id="staff_id" name="staff_id"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">未指定（担当者なし）</option>
                    @foreach($staffs as $staff)
                    <option value="{{ $staff->id }}" {{ (string) old('staff_id', $schedule->staff_id) === (string) $staff->id ? 'selected' : '' }}>
                        {{ $staff->name }} @if($staff->affiliation)({{ $staff->affiliation }})@endif
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- 詳細内容・特記事項 -->
            <div>
                <label for="description" class="block text-xs font-bold text-slate-700 mb-1">
                    詳細内容・特記事項・持ち物など
                </label>
                <textarea id="description" name="description" rows="4"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('description', $schedule->description) }}</textarea>
            </div>

            <!-- ボタンエリア -->
            <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
                <a href="{{ route('annual-schedules.show', $schedule) }}"
                    class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-medium transition">
                    キャンセル
                </a>
                <button type="submit"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition cursor-pointer">
                    変更内容を更新する
                </button>
            </div>

        </form>
    </div>

</div>
</x-layouts.app>
