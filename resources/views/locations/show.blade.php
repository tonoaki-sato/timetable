<x-layouts.app :title="'拠点詳細 - ' . $location->name">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$location->name"
        icon="📍"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）における拠点「' . $location->name . '」の基本仕様、利用可能時間帯、現在の利用状況およびスケジュール管理です。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>運行情報一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>📅</span>
                <span>{{ $timetable->name }}</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.locations.index', $timetable) }}" class="hover:text-indigo-600 transition truncate">
                📍 拠点一覧
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">{{ $location->name }}</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.locations.edit', [$timetable, $location]) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>✏️</span>
                <span>基本情報を編集</span>
            </a>
            <a href="{{ route('timetables.locations.index', $timetable) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>拠点一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="locations" />

    <!-- アラートメッセージ -->
    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>✅</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('status'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>✅</span>
        <span>{{ session('status') }}</span>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- 左カラム (2/3): 基本仕様 & 利用可能時間帯スロット一覧 -->
        <div class="lg:col-span-2 space-y-4">

            <!-- 1. 基本仕様カード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                        <span>📍</span>
                        <span>拠点の基本仕様</span>
                    </h2>
                    <a href="{{ route('timetables.locations.edit', [$timetable, $location]) }}"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-bold transition">
                        ✏️ 編集する
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">拠点名</span>
                        <span class="font-bold text-slate-800 flex items-center space-x-1 mt-0.5">
                            <span>📍</span>
                            <span>{{ $location->name }}</span>
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">拠点種別</span>
                        <div class="mt-0.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border bg-slate-100 text-slate-700 border-slate-200">
                                {{ $location->type?->label ?? '未分類' }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">利用可能形態</span>
                        <div class="mt-0.5">
                            @if($location->is_all_day)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border bg-blue-50 text-blue-700 border-blue-200">
                                    終日利用可
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border bg-amber-50 text-amber-700 border-amber-200">
                                    時間枠指定
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 拠点メモ・案内文 -->
                <div class="pt-3 border-t border-slate-100">
                    <span class="text-slate-400 block text-[11px] mb-1 font-bold">拠点メモ・案内事項</span>
                    <div class="p-3 bg-slate-50 rounded-xl text-xs text-slate-700 leading-relaxed whitespace-pre-wrap">
                        {{ $location->notes ?: '拠点メモは登録されていません。「基本情報を編集」から注意事項や利用案内を登録できます。' }}
                    </div>
                </div>
            </div>

            <!-- 2. 利用可能時間帯スロット一覧カード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                            <span>⏱️</span>
                            <span>利用可能時間帯スロット（年月日時分）</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono font-normal">
                                全 {{ $location->schedules->count() }} 枠
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            年月日時分単位で利用枠を登録し、申請状況（承認済/申請中/規制中など）を管理します。
                        </p>
                    </div>

                    <button type="button"
                            data-action="open-schedule-modal"
                            data-mode="create"
                            data-store-url="{{ route('timetables.locations.schedules.store', [$timetable, $location]) }}"
                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0 cursor-pointer">
                        <span>➕</span>
                        <span>時間帯を追加</span>
                    </button>
                </div>

                @if($location->schedules->isEmpty())
                <div class="p-8 text-center space-y-2">
                    <span class="text-3xl block">📭</span>
                    <p class="text-xs font-bold text-slate-600">利用可能時間帯がまだ登録されていません。</p>
                    <p class="text-[11px] text-slate-400">右上の「➕ 時間帯を追加」ボタンから年月日時分単位で利用枠を登録してください。</p>
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                        <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-4 py-3 whitespace-nowrap">利用可能日時 (年月日時分)</th>
                                <th scope="col" class="px-4 py-3 whitespace-nowrap">枠名 / タイトル</th>
                                <th scope="col" class="px-4 py-3 whitespace-nowrap">利用可否ステータス</th>
                                <th scope="col" class="px-4 py-3 whitespace-nowrap">メモ / 特記事項</th>
                                <th scope="col" class="px-4 py-3 whitespace-nowrap text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($location->schedules as $schedule)
                            <tr class="hover:bg-slate-50/80 transition cursor-default">
                                <td class="px-4 py-3 font-mono font-bold text-slate-800 whitespace-nowrap">
                                    <div class="flex items-center space-x-1.5">
                                        <span>📅</span>
                                        <span>{{ $schedule->start_at->format('Y/m/d H:i') }}</span>
                                        <span class="text-slate-400">〜</span>
                                        <span>{{ $schedule->end_at->format($schedule->start_at->format('Y-m-d') === $schedule->end_at->format('Y-m-d') ? 'H:i' : 'Y/m/d H:i') }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-900 font-bold whitespace-nowrap">
                                    {{ $schedule->title ?: '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <form method="POST" action="{{ route('timetables.locations.schedules.update-status', [$timetable, $location, $schedule]) }}" class="inline-flex items-center space-x-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <select name="availability_status"
                                                data-action="auto-submit-status"
                                                class="text-xs font-bold py-1 px-2 rounded-lg border focus:ring-1 focus:ring-indigo-500 cursor-pointer {{ $schedule->availability_status_badge_class }}">
                                            @foreach(\App\Models\LocationSchedule::AVAILABILITY_STATUSES as $statusKey => $statusLabel)
                                                <option value="{{ $statusKey }}" {{ $schedule->availability_status === $statusKey ? 'selected' : '' }}>
                                                    {{ $statusLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-slate-600 max-w-xs truncate" title="{{ $schedule->note }}">
                                    {{ $schedule->note ?: '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right space-x-1">
                                    <button type="button"
                                            data-action="open-schedule-modal"
                                            data-mode="edit"
                                            data-update-url="{{ route('timetables.locations.schedules.update', [$timetable, $location, $schedule]) }}"
                                            data-schedule="{{ json_encode($schedule) }}"
                                            class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition inline-flex items-center space-x-1 shadow-2xs cursor-pointer">
                                        <span>✏️</span>
                                        <span>編集</span>
                                    </button>
                                    <form method="POST" action="{{ route('timetables.locations.schedules.destroy', [$timetable, $location, $schedule]) }}"
                                          data-confirm="この時間帯枠を削除しますか？" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-2.5 py-1 bg-white hover:bg-rose-50 text-rose-600 border border-slate-200 hover:border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer inline-flex items-center space-x-1 shadow-2xs">
                                            <span>🗑️</span>
                                            <span>削除</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

        </div>

        <!-- 右カラム (1/3): 現在のステータス評価 & 拠点削除 -->
        <div class="space-y-4">

            <!-- 3. 現在のステータス評価カード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-1.5">
                        <span>📊</span>
                        <span>現在の利用可能判定</span>
                    </h2>
                </div>

                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700">判定結果</span>
                        <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $evaluation->badgeClass() }}">
                            <span>{{ $evaluation->icon() }}</span>
                            <span>{{ $evaluation->label() }}</span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        {{ $evaluation->detail }}
                    </p>
                </div>
            </div>

            <!-- 4. 拠点削除カード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
                <h2 class="font-bold text-sm text-rose-700 flex items-center space-x-1.5">
                    <span>⚠️</span>
                    <span>拠点の削除</span>
                </h2>
                <p class="text-xs text-slate-500 leading-relaxed">
                    拠点「{{ $location->name }}」を削除すると、登録済みのすべての時間帯枠データも合わせて削除されます。
                </p>
                <form action="{{ route('timetables.locations.destroy', [$timetable, $location]) }}" method="POST"
                    data-confirm="拠点「{{ $location->name }}」を削除しますか？&#10;（関連する時間帯データもすべて削除されます）">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="w-full px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer">
                        <span>🗑️</span>
                        <span>この拠点を完全に削除する</span>
                    </button>
                </form>
            </div>

        </div>

    </div>

</div>

<!-- スケジュール登録・編集モーダル -->
<div id="schedule-modal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-5 sm:p-6 shadow-2xl border border-slate-200 text-left space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="schedule-modal-title" class="text-sm sm:text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>⏱️</span>
                <span>利用時間帯の追加</span>
            </h3>
            <button type="button" data-action="close-schedule-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">✕</button>
        </div>

        <form id="schedule-modal-form" method="POST" action="{{ route('timetables.locations.schedules.store', [$timetable, $location]) }}" class="space-y-4">
            @csrf
            <div id="schedule-form-method"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="modal_start_at" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">開始日時 <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="start_at" id="modal_start_at" required
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
                <div>
                    <label for="modal_end_at" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">終了日時 <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="end_at" id="modal_end_at" required
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
            </div>

            <div>
                <label for="modal_availability_status" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">
                    利用可否ステータス <span class="text-rose-500">*</span>
                </label>
                <select name="availability_status" id="modal_availability_status" required
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(\App\Models\LocationSchedule::AVAILABILITY_STATUSES as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-500 mt-1">※「利用不可」の場合は実行委員会として利用不可状態となります。</p>
            </div>

            <div>
                <label for="modal_title" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">枠名 / タイトル（任意）</label>
                <input type="text" name="title" id="modal_title" placeholder="例: 午前枠, 夜間保管枠, 昼間設営枠"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label for="modal_note" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">申請メモ / 特記事項（任意）</label>
                <textarea name="note" id="modal_note" rows="2" placeholder="例: 道路使用許可申請中、区役所許可番号など"
                          class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" data-action="close-schedule-modal"
                        class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                    キャンセル
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    保存する
                </button>
            </div>
        </form>
    </div>
</div>

@vite(['resources/js/locations.js'])
</x-layouts.app>
