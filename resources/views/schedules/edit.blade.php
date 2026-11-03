<x-layouts.app :title="'スケジュール編集 - ' . $schedule->title">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ヘッダー -->
    <x-page-header
        title="スケジュール編集"
        icon="✏️"
        :description="'「' . $schedule->title . '」の内容を更新します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('schedules.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1">
                <span>📅</span>
                <span>スケジュール管理一覧</span>
            </a>
            <span>/</span>
            <a href="{{ route('schedules.show', $schedule) }}" class="hover:text-indigo-600 transition truncate max-w-xs">
                {{ $schedule->title }}
            </a>
            <span>/</span>
            <span class="text-slate-700 font-bold">編集</span>
        </x-slot:breadcrumbs>
    </x-page-header>

    @if($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-xs text-red-800 font-medium">
        <div class="font-bold mb-1 flex items-center space-x-1">
            <span>⚠️</span>
            <span>入力内容にエラーがあります</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($schedule->is_linked_to_timetable)
    <div class="p-4 sm:p-5 bg-amber-50 border border-amber-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start space-x-3">
            <span class="text-2xl p-2 bg-white rounded-xl shadow-2xs border border-amber-200 shrink-0">🏮</span>
            <div>
                <h3 class="text-xs sm:text-sm font-bold text-amber-900">この予定は運行スケジュール（タイムテーブル）と自動連動しています</h3>
                <p class="text-xs text-amber-700 mt-0.5 leading-relaxed">
                    運行日・開催時間枠・名称などは「タイムテーブル編集画面」で変更すると、本スケジュールへ自動的に反映されます。
                </p>
            </div>
        </div>
        @if($schedule->timetable_id)
        <a href="{{ route('timetables.edit', $schedule->timetable_id) }}"
            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center space-x-1.5 shrink-0 whitespace-nowrap">
            <span>⏱️</span>
            <span>運行タイムテーブルを編集</span>
        </a>
        @endif
    </div>
    @endif

    <!-- 入力フォーム -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <form action="{{ route('schedules.update', $schedule) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- タイトル & カテゴリ -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="title" class="block text-xs font-bold text-slate-700 mb-1">
                        タイトル（予定名・会議名） <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="title" name="title" value="{{ old('title', $schedule->title) }}" required
                        placeholder="例: 第1回 幹事会、道路使用許可申請、出店者説明会など"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="category" class="block text-xs font-bold text-slate-700 mb-1">
                        区分（カテゴリ） <span class="text-rose-500">*</span>
                    </label>
                    <select id="category" name="category" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category', $schedule->category) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 日程（開始日・終了日） & 時間帯（開始時刻・終了時刻） -->
            <div class="p-5 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-4">
                <div class="text-xs font-bold text-slate-800 flex items-center space-x-1">
                    <span>📅</span>
                    <span>開催日・期間および時間帯</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label for="start_date" class="block text-[11px] font-bold text-slate-600 mb-1">
                            開始日・開催日 <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="start_date" name="start_date"
                            value="{{ old('start_date', $schedule->start_date?->format('Y-m-d')) }}" required
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    </div>

                    <div>
                        <label for="end_date" class="block text-[11px] font-bold text-slate-600 mb-1">
                            終了日 <span class="text-slate-400 font-normal">（複数日期間の場合）</span>
                        </label>
                        <input type="date" id="end_date" name="end_date"
                            value="{{ old('end_date', $schedule->end_date?->format('Y-m-d')) }}"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    </div>

                    <div>
                        <label for="start_time" class="block text-[11px] font-bold text-slate-600 mb-1">
                            開始時刻 <span class="text-slate-400 font-normal">（任意）</span>
                        </label>
                        <input type="time" id="start_time" name="start_time"
                            value="{{ old('start_time', $schedule->start_time ? substr($schedule->start_time, 0, 5) : '') }}"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    </div>

                    <div>
                        <label for="end_time" class="block text-[11px] font-bold text-slate-600 mb-1">
                            終了時刻 <span class="text-slate-400 font-normal">（任意）</span>
                        </label>
                        <input type="time" id="end_time" name="end_time"
                            value="{{ old('end_time', $schedule->end_time ? substr($schedule->end_time, 0, 5) : '') }}"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    </div>
                </div>
            </div>

            <!-- 場所 & 担当者 & ステータス -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="location" class="block text-xs font-bold text-slate-700 mb-1">
                        開催場所・実施場所
                    </label>
                    <input type="text" id="location" name="location" value="{{ old('location', $schedule->location) }}"
                        placeholder="例: コミュニティルーム、保土ケ谷警察署等"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="staff_id" class="block text-xs font-bold text-slate-700 mb-1">
                        担当・主催スタッフ
                    </label>
                    <select id="staff_id" name="staff_id"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">未設定（後から指定）</option>
                        @foreach($staffs as $staff)
                        <option value="{{ $staff->id }}" {{ (string) old('staff_id', $schedule->staff_id) === (string) $staff->id ? 'selected' : '' }}>
                            {{ $staff->name }} @if($staff->affiliation)({{ $staff->affiliation }})@endif
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 mb-1">
                        ステータス <span class="text-rose-500">*</span>
                    </label>
                    <select id="status" name="status" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ old('status', $schedule->status) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 詳細説明・議題 -->
            <div>
                <label for="description" class="block text-xs font-bold text-slate-700 mb-1">
                    詳細説明・議題・内容・アジェンダ
                </label>
                <textarea id="description" name="description" rows="4"
                    placeholder="会議のアジェンダ、準備事項、必要な持ち物などを入力してください"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $schedule->description) }}</textarea>
            </div>

            <!-- LINE告知特記メモ -->
            <div>
                <label for="notice_note" class="block text-xs font-bold text-slate-700 mb-1">
                    共有告知特記メモ <span class="text-slate-400 font-normal">（出欠返信期日や注意事項など、LINE共有テキストに反映されます）</span>
                </label>
                <textarea id="notice_note" name="notice_note" rows="2"
                    placeholder="例: 出欠のご連絡は前日17時までにお願いいたします。"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('notice_note', $schedule->notice_note) }}</textarea>
            </div>

            <!-- 送信ボタン -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('schedules.show', $schedule) }}"
                    class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition">
                    キャンセル
                </a>
                <button type="submit"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition cursor-pointer">
                    変更を保存する
                </button>
            </div>
        </form>
    </div>

</div>
</x-layouts.app>

