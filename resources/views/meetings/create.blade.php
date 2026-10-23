<x-layouts.app title="新しい会議・打合せの登録">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- ナビゲーション・戻る -->
        <div>
            <a href="{{ route('meetings.index') }}"
                class="inline-flex items-center space-x-1 text-xs font-medium text-slate-500 hover:text-slate-800 transition">
                <span>←</span>
                <span>会議・打ち合わせ一覧に戻る</span>
            </a>
        </div>

        <!-- ヘッダー -->
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center space-x-2">
                <span>🤝</span>
                <span>新しい会議・打合せを登録</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                日程や場所、アジェンダを入力すると、全体LINE用の案内テキストが自動生成されます。
            </p>
        </div>

        <!-- バリデーションエラー表示 -->
        @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs sm:text-sm space-y-1">
            <div class="font-bold flex items-center space-x-1">
                <span>⚠️</span>
                <span>入力内容をご確認ください：</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-xs">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- フォーム本体 & LINEプレビュー（2カラム構成） -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- 左側: 入力フォーム (7カラム) -->
            <div class="lg:col-span-7 bg-white p-6 sm:p-7 rounded-2xl border border-slate-200 shadow-sm">
                <form action="{{ route('meetings.store') }}" method="POST" class="space-y-5" id="meetingForm">
                    @csrf

                    <!-- 会議名 -->
                    <div>
                        <label for="title" class="block text-xs font-bold text-slate-700 mb-1">
                            会議・打ち合わせ名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" value="{{ old('title') }}" required
                            placeholder="例: 第1回 幹事会 / ござ市部門打ち合わせ"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- 開催日 & 時間帯 -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label for="meeting_date" class="block text-xs font-bold text-slate-700 mb-1">
                                開催日 <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="meeting_date" name="meeting_date" value="{{ old('meeting_date', now()->format('Y-m-d')) }}" required
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>

                        <div>
                            <label for="start_time" class="block text-xs font-bold text-slate-700 mb-1">
                                開始時刻 <span class="text-red-500">*</span>
                            </label>
                            <input type="time" id="start_time" name="start_time" value="{{ old('start_time', '18:00') }}" required
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>

                        <div>
                            <label for="end_time" class="block text-xs font-bold text-slate-700 mb-1">
                                終了時刻 <span class="text-slate-400 font-normal">（任意）</span>
                            </label>
                            <input type="time" id="end_time" name="end_time" value="{{ old('end_time', '20:00') }}"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>
                    </div>

                    <!-- 開催場所 & 主催者 -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="location" class="block text-xs font-bold text-slate-700 mb-1">
                                開催場所・会場
                            </label>
                            <input type="text" id="location" name="location" value="{{ old('location', 'コミュニティルーム') }}" required
                                placeholder="例: コミュニティルーム / Zoom"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="staff_id" class="block text-xs font-bold text-slate-700 mb-1">
                                主催者（スタッフ） <span class="text-slate-400 font-normal">（任意・後から設定可能）</span>
                            </label>
                            <select id="staff_id" name="staff_id"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">未定（後から設定する）</option>
                                @foreach($staffs as $staff)
                                <option value="{{ $staff->id }}" {{ (string) old('staff_id') === (string) $staff->id ? 'selected' : '' }} data-name="{{ $staff->name }}">
                                    {{ $staff->name }} @if($staff->affiliation)({{ $staff->affiliation }})@endif
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 内容・議題・持ち物 -->
                    <div>
                        <label for="description" class="block text-xs font-bold text-slate-700 mb-1">
                            内容・議題・持ち物など <span class="text-slate-400 font-normal">（任意）</span>
                        </label>
                        <textarea id="description" name="description" rows="4"
                            placeholder="・年間会議日程案の策定&#10;・前年度反省申し送り事項の確認&#10;・各部門担当の仮決め協議"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-xs sm:text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('description') }}</textarea>
                    </div>

                    <!-- LINE用特記メモ -->
                    <div>
                        <label for="notice_note" class="block text-xs font-bold text-slate-700 mb-1">
                            LINE用特記メモ（返信期日・連絡先等） <span class="text-slate-400 font-normal">（任意）</span>
                        </label>
                        <input type="text" id="notice_note" name="notice_note" value="{{ old('notice_note') }}"
                            placeholder="例: 出欠のご返信は○月○日(水)までにお願いいたします。"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- ボタンエリア -->
                    <div class="pt-4 border-t border-slate-200 flex items-center justify-end space-x-3">
                        <a href="{{ route('meetings.index') }}"
                            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-medium transition">
                            キャンセル
                        </a>
                        <button type="submit"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition cursor-pointer">
                            会議を登録する
                        </button>
                    </div>
                </form>
            </div>

            <!-- 右側: リアルタイム LINEプレビュー (5カラム) -->
            <div class="lg:col-span-5 bg-gradient-to-b from-slate-900 to-slate-800 text-white p-5 sm:p-6 rounded-2xl shadow-lg border border-slate-700 sticky top-24">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-700">
                    <div class="flex items-center space-x-2">
                        <span class="text-lg">💬</span>
                        <span class="font-bold text-xs uppercase tracking-wider text-emerald-400">LINE告知文 リアルタイムプレビュー</span>
                    </div>
                    <span class="text-[10px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded border border-slate-600 font-mono">自動生成</span>
                </div>

                <p class="text-[11px] text-slate-400 mb-3">
                    左側の入力に合わせて告知メッセージが自動で整形されます。登録後にワンクリックでコピーできます。
                </p>

                <div class="bg-slate-950/70 p-4 rounded-xl border border-slate-800/80 font-mono text-xs text-slate-200 whitespace-pre-wrap leading-relaxed select-all" id="linePreviewBox">
                    入力するとここにプレビューが表示されます...
                </div>
            </div>

        </div>

    </div>

    <!-- リアルタイムプレビュースクリプト -->
    <script>
        function updateLinePreview() {
            const title = document.getElementById('title').value || '（会議名）';
            const dateVal = document.getElementById('meeting_date').value;
            const startTime = document.getElementById('start_time').value || '18:00';
            const endTime = document.getElementById('end_time').value;
            const location = document.getElementById('location').value || '（開催場所）';

            const hostSelect = document.getElementById('staff_id');
            const selectedOption = hostSelect ? hostSelect.options[hostSelect.selectedIndex] : null;
            const hostName = selectedOption && selectedOption.dataset.name ? selectedOption.dataset.name : '未定（後から設定）';

            const desc = document.getElementById('description').value;
            const note = document.getElementById('notice_note').value;

            let dateStr = '（開催日）';
            if (dateVal) {
                const d = new Date(dateVal + 'T00:00:00');
                const weekdayNames = ['日', '月', '火', '水', '木', '金', '土'];
                const w = weekdayNames[d.getDay()];
                dateStr = `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日(${w})`;
            }

            const timeRange = endTime ? `${startTime} 〜 ${endTime}` : `${startTime} 〜`;

            let lines = [
                '【会議開催のお知らせ】',
                `「${title}」の開催日程が決まりました。`,
                '',
                `📅 日時：${dateStr} ${timeRange}`,
                `📍 場所：${location}`,
                `👤 主催：${hostName}`
            ];

            if (desc && desc.trim()) {
                lines.push('');
                lines.push('📝 議題・内容：');
                lines.push(desc.trim());
            }

            if (note && note.trim()) {
                lines.push('');
                lines.push('📌 連絡事項：');
                lines.push(note.trim());
            }

            lines.push('--------------------------------');
            lines.push('ご参加よろしくお願いいたします！');

            document.getElementById('linePreviewBox').textContent = lines.join('\n');
        }

        ['title', 'meeting_date', 'start_time', 'end_time', 'location', 'staff_id', 'description', 'notice_note'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', updateLinePreview);
                el.addEventListener('change', updateLinePreview);
            }
        });

        updateLinePreview();
    </script>
</x-layouts.app>