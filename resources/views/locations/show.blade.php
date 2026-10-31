<x-layouts.app title="{{ $location->name }} - 拠点詳細">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- 1. ヘッダーエリア -->
    <x-page-header
        title="{{ $location->name }}"
        icon="📍"
        description="{{ $evaluation->detail }}">
        <x-slot:breadcrumbs>
            <a href="{{ route('locations.index') }}" class="hover:underline">拠点管理</a>
            <span>/</span>
            <span class="text-slate-800 font-bold">{{ $location->name }}</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                {{ $location->type?->label ?? '未分類' }}
            </span>
            <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $evaluation->badgeClass() }}">
                <span>{{ $evaluation->icon() }}</span>
                <span>現在の状態: {{ $evaluation->label() }}</span>
            </span>
            @if($location->is_all_day)
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    終日利用可
                </span>
            @endif
        </x-slot:badges>

        <x-slot:actions>
            <a href="{{ route('locations.index') }}"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                <span>← 一覧に戻る</span>
            </a>
            <a href="{{ route('locations.edit', $location) }}"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition">
                <span>✏️ 拠点を編集</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 2. メモ欄カード（フリーテキスト） -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-5 space-y-2">
        <h2 class="text-xs font-bold text-slate-700 flex items-center space-x-1.5">
            <span>📝</span>
            <span>拠点メモ（管理連絡先・鍵情報・注意事項など）</span>
        </h2>
        @if($location->notes)
            <div class="p-3.5 bg-slate-50 rounded-xl text-xs text-slate-700 whitespace-pre-wrap leading-relaxed border border-slate-100 font-sans">
                {{ $location->notes }}
            </div>
        @else
            <p class="text-xs text-slate-400 italic">メモは登録されていません。</p>
        @endif
    </div>

    <!-- 3. 利用可能時間帯スロット一覧カード -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
                    <span>⏱️</span>
                    <span>利用可能時間帯スロット（年月日時分）</span>
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    枠ごとに開始日時・終了日時および利用申請ステータスを設定します。
                </p>
            </div>
            <button type="button"
                    data-action="open-schedule-modal"
                    data-mode="create"
                    data-store-url="{{ route('locations.schedules.store', $location) }}"
                    class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer self-start sm:self-auto">
                <span>➕</span>
                <span>時間帯を追加</span>
            </button>
        </div>

        @if($location->schedules->isEmpty())
            <div class="p-8 text-center text-slate-400 text-xs">
                <div class="text-3xl mb-2">⏱️</div>
                <p class="font-bold text-slate-600 mb-1">利用可能時間帯がまだ登録されていません</p>
                <p class="text-slate-400 mb-3">「時間帯を追加」ボタンから年月日時分単位で利用枠を登録してください。</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-slate-100">
                    <thead class="bg-slate-50/75 border-b border-slate-200 text-slate-500 font-bold text-[11px]">
                        <tr>
                            <th class="py-2.5 px-4">利用可能日時 (年月日時分)</th>
                            <th class="py-2.5 px-4">枠名 / タイトル</th>
                            <th class="py-2.5 px-4">利用申請ステータス</th>
                            <th class="py-2.5 px-4">メモ / 申請特記事項</th>
                            <th class="py-2.5 px-4 text-right">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($location->schedules as $schedule)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-2.5 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                                    <div class="flex items-center space-x-1.5">
                                        <span>📅</span>
                                        <span>{{ $schedule->start_at->format('Y/m/d H:i') }}</span>
                                        <span class="text-slate-400">〜</span>
                                        <span>{{ $schedule->end_at->format($schedule->start_at->format('Y-m-d') === $schedule->end_at->format('Y-m-d') ? 'H:i' : 'Y/m/d H:i') }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-4 text-slate-900 font-bold whitespace-nowrap">
                                    {{ $schedule->title ?: '—' }}
                                </td>
                                <td class="py-2.5 px-4 whitespace-nowrap">
                                    <form method="POST" action="{{ route('locations.schedules.update-status', [$location, $schedule]) }}" class="inline-flex items-center space-x-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <select name="application_status"
                                                data-action="auto-submit-status"
                                                class="text-xs font-bold py-1 px-2 rounded-lg border focus:ring-1 focus:ring-indigo-500 cursor-pointer {{ $schedule->application_status_badge_class }}">
                                            @foreach(\App\Models\LocationSchedule::APPLICATION_STATUSES as $statusKey => $statusLabel)
                                                <option value="{{ $statusKey }}" {{ $schedule->application_status === $statusKey ? 'selected' : '' }}>
                                                    {{ $statusLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="py-2.5 px-4 text-slate-600">
                                    {{ $schedule->note ?: '—' }}
                                </td>
                                <td class="py-2.5 px-4 text-right space-x-1 whitespace-nowrap">
                                    <button type="button"
                                            data-action="open-schedule-modal"
                                            data-mode="edit"
                                            data-update-url="{{ route('locations.schedules.update', [$location, $schedule]) }}"
                                            data-schedule="{{ json_encode($schedule) }}"
                                            class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[11px] font-bold transition cursor-pointer">
                                        編集
                                    </button>
                                    <form method="POST" action="{{ route('locations.schedules.destroy', [$location, $schedule]) }}"
                                          data-confirm="この時間帯枠を削除しますか？" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded text-[11px] font-medium transition cursor-pointer">
                                            削除
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

<!-- スケジュール登録・編集モーダル (既存標準モーダルデザイン・CSP準拠) -->
<div id="schedule-modal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-5 sm:p-6 shadow-2xl border border-slate-200 text-left space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="schedule-modal-title" class="text-sm sm:text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>⏱️</span>
                <span>利用時間帯の追加</span>
            </h3>
            <button type="button" data-action="close-schedule-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">✕</button>
        </div>

        <form id="schedule-modal-form" method="POST" action="{{ route('locations.schedules.store', $location) }}" class="space-y-4">
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
                <label for="modal_application_status" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">
                    利用申請ステータス <span class="text-rose-500">*</span>
                </label>
                <select name="application_status" id="modal_application_status" required
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(\App\Models\LocationSchedule::APPLICATION_STATUSES as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-500 mt-1">※「未申請」の場合は実行委員会として利用不可状態となります。</p>
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
