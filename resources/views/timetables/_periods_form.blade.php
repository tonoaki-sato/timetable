@php
    $currentRoadPeriods = old('road_periods', $timetable->road_periods ?? []);
    if (!is_array($currentRoadPeriods)) {
        $currentRoadPeriods = [];
    }

    $currentFestivalPeriods = old('festival_periods', $timetable->festival_periods ?? []);
    if (!is_array($currentFestivalPeriods)) {
        $currentFestivalPeriods = [];
    }
@endphp

<!-- 運行状況・時間帯指定セクション -->
<div class="space-y-6 pt-4 border-t border-slate-200">

    <!-- 1. 道路の状況（時間帯指定） -->
    <div class="bg-slate-50 p-4 sm:p-5 rounded-xl border border-slate-200 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h4 class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                    <span>🚧</span>
                    <span>道路の状況（交通規制・通行止め）</span>
                </h4>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    時間帯を設定しない場合、この運行日程は終日「通常交通（規制なし）」となります。
                </p>
            </div>
            <button type="button" id="btn-preset-road"
                class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition self-start sm:self-auto cursor-pointer">
                <span>⚡</span>
                <span>プリセット: 10:00〜18:00 通行止め</span>
            </button>
        </div>

        <!-- 道路時間帯リスト -->
        <div id="road-periods-container" class="space-y-2">
            @foreach($currentRoadPeriods as $index => $period)
            <div class="period-row grid grid-cols-12 gap-2 items-center bg-white p-2 rounded-lg border border-slate-200 shadow-2xs">
                <input type="hidden" name="road_periods[{{ $index }}][status]" value="{{ $period['status'] ?? 'closed' }}">
                <div class="col-span-3 sm:col-span-3">
                    <label class="block text-[10px] text-slate-400 font-bold mb-0.5">開始時刻</label>
                    <input type="time" name="road_periods[{{ $index }}][start_time]" value="{{ substr($period['start_time'] ?? '', 0, 5) }}" required
                        class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs font-mono">
                </div>
                <div class="col-span-3 sm:col-span-3">
                    <label class="block text-[10px] text-slate-400 font-bold mb-0.5">終了時刻</label>
                    <input type="time" name="road_periods[{{ $index }}][end_time]" value="{{ substr($period['end_time'] ?? '', 0, 5) }}" required
                        class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs font-mono">
                </div>
                <div class="col-span-5 sm:col-span-5">
                    <label class="block text-[10px] text-slate-400 font-bold mb-0.5">表示文言</label>
                    <input type="text" name="road_periods[{{ $index }}][label]" value="{{ $period['label'] ?? '通行止め' }}" required
                        placeholder="例: 通行止め"
                        class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs">
                </div>
                <div class="col-span-1 sm:col-span-1 flex items-end justify-center pt-3">
                    <button type="button" class="btn-remove-row text-slate-400 hover:text-red-600 transition p-1 text-sm font-bold" title="削除">
                        ✕
                    </button>
                </div>
            </div>
            @endforeach
        </div>

        <button type="button" id="btn-add-road-row"
            class="inline-flex items-center space-x-1 px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer">
            <span>➕</span>
            <span>道路規制の時間帯を追加</span>
        </button>
    </div>

    <!-- 2. まつりの状況（時間帯指定） -->
    <div class="bg-slate-50 p-4 sm:p-5 rounded-xl border border-slate-200 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h4 class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                    <span>🏮</span>
                    <span>まつりの状況（イベント進行状況）</span>
                </h4>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    時間帯を設定しない場合、この運行日程はまつり本番運行なし（準備日等）となります。
                </p>
            </div>
            <button type="button" id="btn-preset-festival"
                class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-lg text-xs font-bold transition self-start sm:self-auto cursor-pointer">
                <span>⚡</span>
                <span>プリセット: 9:00受付開始 ＆ 11:00〜17:00開催中</span>
            </button>
        </div>

        <!-- まつり時間帯リスト -->
        <div id="festival-periods-container" class="space-y-2">
            @foreach($currentFestivalPeriods as $index => $period)
            <div class="period-row grid grid-cols-12 gap-2 items-center bg-white p-2 rounded-lg border border-slate-200 shadow-2xs">
                <input type="hidden" name="festival_periods[{{ $index }}][status]" value="{{ $period['status'] ?? 'active' }}">
                <div class="col-span-3 sm:col-span-3">
                    <label class="block text-[10px] text-slate-400 font-bold mb-0.5">開始時刻</label>
                    <input type="time" name="festival_periods[{{ $index }}][start_time]" value="{{ substr($period['start_time'] ?? '', 0, 5) }}" required
                        class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs font-mono">
                </div>
                <div class="col-span-3 sm:col-span-3">
                    <label class="block text-[10px] text-slate-400 font-bold mb-0.5">終了時刻</label>
                    <input type="time" name="festival_periods[{{ $index }}][end_time]" value="{{ substr($period['end_time'] ?? '', 0, 5) }}" required
                        class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs font-mono">
                </div>
                <div class="col-span-5 sm:col-span-5">
                    <label class="block text-[10px] text-slate-400 font-bold mb-0.5">表示文言</label>
                    <input type="text" name="festival_periods[{{ $index }}][label]" value="{{ $period['label'] ?? '開催中' }}" required
                        placeholder="例: 開催中"
                        class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs">
                </div>
                <div class="col-span-1 sm:col-span-1 flex items-end justify-center pt-3">
                    <button type="button" class="btn-remove-row text-slate-400 hover:text-red-600 transition p-1 text-sm font-bold" title="削除">
                        ✕
                    </button>
                </div>
            </div>
            @endforeach
        </div>

        <button type="button" id="btn-add-festival-row"
            class="inline-flex items-center space-x-1 px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer">
            <span>➕</span>
            <span>まつり時間帯を追加</span>
        </button>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const roadContainer = document.getElementById('road-periods-container');
    const festivalContainer = document.getElementById('festival-periods-container');

    function createRow(type, index, start = '', end = '', label = '', status = '') {
        const defaultLabel = type === 'road' ? '通行止め' : '開催中';
        const defaultStatus = type === 'road' ? 'closed' : 'active';
        const finalLabel = label || defaultLabel;
        const finalStatus = status || defaultStatus;

        const row = document.createElement('div');
        row.className = 'period-row grid grid-cols-12 gap-2 items-center bg-white p-2 rounded-lg border border-slate-200 shadow-2xs';
        row.innerHTML = `
            <input type="hidden" name="${type}_periods[${index}][status]" value="${finalStatus}">
            <div class="col-span-3 sm:col-span-3">
                <label class="block text-[10px] text-slate-400 font-bold mb-0.5">開始時刻</label>
                <input type="time" name="${type}_periods[${index}][start_time]" value="${start}" required
                    class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs font-mono">
            </div>
            <div class="col-span-3 sm:col-span-3">
                <label class="block text-[10px] text-slate-400 font-bold mb-0.5">終了時刻</label>
                <input type="time" name="${type}_periods[${index}][end_time]" value="${end}" required
                    class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs font-mono">
            </div>
            <div class="col-span-5 sm:col-span-5">
                <label class="block text-[10px] text-slate-400 font-bold mb-0.5">表示文言</label>
                <input type="text" name="${type}_periods[${index}][label]" value="${finalLabel}" required
                    placeholder="例: ${finalLabel}"
                    class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs">
            </div>
            <div class="col-span-1 sm:col-span-1 flex items-end justify-center pt-3">
                <button type="button" class="btn-remove-row text-slate-400 hover:text-red-600 transition p-1 text-sm font-bold" title="削除">
                    ✕
                </button>
            </div>
        `;
        return row;
    }

    // 行削除イベント
    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('btn-remove-row')) {
            const row = e.target.closest('.period-row');
            if (row) {
                row.remove();
            }
        }
    });

    // 道路時間帯追加
    document.getElementById('btn-add-road-row')?.addEventListener('click', function () {
        const nextIndex = roadContainer.querySelectorAll('.period-row').length;
        roadContainer.appendChild(createRow('road', nextIndex, '10:00', '18:00', '通行止め', 'closed'));
    });

    // 道路プリセット（10:00〜18:00 通行止め）
    document.getElementById('btn-preset-road')?.addEventListener('click', function () {
        roadContainer.innerHTML = '';
        roadContainer.appendChild(createRow('road', 0, '10:00', '18:00', '通行止め', 'closed'));
    });

    // まつり時間帯追加
    document.getElementById('btn-add-festival-row')?.addEventListener('click', function () {
        const nextIndex = festivalContainer.querySelectorAll('.period-row').length;
        festivalContainer.appendChild(createRow('festival', nextIndex, '11:00', '17:00', '開催中', 'active'));
    });

    // まつりプリセット（9:00〜11:00 ござ市受付開始 / 11:00〜17:00 開催中）
    document.getElementById('btn-preset-festival')?.addEventListener('click', function () {
        festivalContainer.innerHTML = '';
        festivalContainer.appendChild(createRow('festival', 0, '09:00', '11:00', 'ござ市受付開始', 'active'));
        festivalContainer.appendChild(createRow('festival', 1, '11:00', '17:00', '開催中', 'active'));
    });
});
</script>
