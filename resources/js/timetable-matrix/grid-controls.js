import { showToast } from '../utils/ui.js';

/**
 * タイムテーブル（マトリクス進行表）グリッド・UIコントロール
 */

/**
 * ヘッダーの現在時刻を毎秒リアルタイム更新
 */
function initLiveClock() {
    const clockEl = document.getElementById('matrix-live-clock');
    if (!clockEl) return;

    function update() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        clockEl.textContent = `${hours}:${minutes}:${seconds}`;
    }

    update();
    setInterval(update, 1000);
}

/**
 * タイムテーブル上の現在時刻赤ボーダーラインをリアルタイムに計算・移動
 */
function initCurrentTimeIndicator() {
    const container = document.getElementById('timetable-grid-container');
    const lineEl = document.getElementById('matrix-current-time-line');
    const timeLabel = document.getElementById('matrix-line-time-label');
    if (!container || !lineEl) return;

    const targetDate = container.getAttribute('data-target-date');
    const initialNow = new Date();
    const initialTodayStr = `${initialNow.getFullYear()}-${String(initialNow.getMonth() + 1).padStart(2, '0')}-${String(initialNow.getDate()).padStart(2, '0')}`;
    if (targetDate && targetDate !== initialTodayStr) {
        lineEl.classList.add('hidden');
        return;
    }

    function updatePosition() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const todayStr = `${year}-${month}-${day}`;

        // 日程が指定されており、当日でない場合は非表示（targetDate未設定時は表示許容）
        if (targetDate && targetDate !== todayStr) {
            lineEl.classList.add('hidden');
            return;
        }

        const currentHours = now.getHours();
        const currentMinutes = now.getMinutes();
        const currentSeconds = now.getSeconds();
        const currentTimeStr = `${String(currentHours).padStart(2, '0')}:${String(currentMinutes).padStart(2, '0')}`;

        // 30分スロットの特定
        const slotMinute = currentMinutes >= 30 ? 30 : 0;
        const currentSlotTime = `${String(currentHours).padStart(2, '0')}:${String(slotMinute).padStart(2, '0')}`;

        // 次のスロット
        let nextSlotHours = currentHours;
        let nextSlotMinute = slotMinute === 30 ? 0 : 30;
        if (slotMinute === 30) {
            nextSlotHours++;
        }
        const nextSlotTime = `${String(nextSlotHours).padStart(2, '0')}:${String(nextSlotMinute).padStart(2, '0')}`;

        const currentRow = container.querySelector(`tr[data-slot-time="${currentSlotTime}"]`);
        if (!currentRow) {
            // 現在時刻がスロット範囲外（早朝または夜間終了後）
            lineEl.classList.add('hidden');
            return;
        }

        // 次のスロット行（または行の高さから推定）
        const nextRow = container.querySelector(`tr[data-slot-time="${nextSlotTime}"]`);
        const currentTop = currentRow.offsetTop;
        const slotHeight = nextRow ? (nextRow.offsetTop - currentTop) : currentRow.offsetHeight;

        // 30分（1800秒）に対する経過割合
        const elapsedSeconds = ((currentMinutes % 30) * 60) + currentSeconds;
        const progress = elapsedSeconds / 1800;

        const calculatedTop = currentTop + (slotHeight * progress);

        lineEl.style.top = `${calculatedTop}px`;
        if (timeLabel) {
            timeLabel.textContent = currentTimeStr;
        }
        lineEl.classList.remove('hidden');
    }

    updatePosition();
    setInterval(updatePosition, 15000);
    window.addEventListener('resize', updatePosition);
}

function initLaneModal() {
    const modal = document.getElementById('laneModal');
    if (!modal) return;

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="open-lane-modal"]')) {
            modal.classList.remove('hidden');
        } else if (e.target.closest('[data-action="close-lane-modal"]') || e.target === modal) {
            modal.classList.add('hidden');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
        }
    });
}

function initGridScroll() {
    const container = document.getElementById('timetable-grid-container');
    if (!container) return;

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-scroll-grid]');
        if (!btn) return;
        const amount = parseInt(btn.getAttribute('data-scroll-grid'), 10);
        if (!isNaN(amount)) {
            container.scrollBy({ left: amount, behavior: 'smooth' });
        }
    });
}

function updateVisibleCount() {
    const total = document.querySelectorAll('.lane-chip').length;
    const hidden = document.querySelectorAll('.lane-chip.opacity-40').length;
    const visible = total - hidden;
    const indicator = document.getElementById('lane-count-indicator');
    if (indicator) {
        indicator.textContent = `全 ${total} 班中 ${visible} 班表示中`;
    }
}

function setAllLanesVisibility(visible) {
    const chips = document.querySelectorAll('.lane-chip');
    chips.forEach((chip) => {
        const laneId = chip.getAttribute('data-lane-id');
        if (!laneId) return;
        const elements = document.querySelectorAll('.lane-col-' + laneId);
        const check = chip.querySelector('span:first-child');

        if (visible) {
            elements.forEach((el) => el.classList.remove('hidden'));
            chip.classList.remove('opacity-40', 'bg-slate-100');
            chip.classList.add('border-indigo-400');
            if (check) check.textContent = '✔';
        } else {
            elements.forEach((el) => el.classList.add('hidden'));
            chip.classList.add('opacity-40', 'bg-slate-100');
            chip.classList.remove('border-indigo-400');
            if (check) check.textContent = '✕';
        }
    });

    updateVisibleCount();
}

function toggleLaneColumn(laneId) {
    const chip = document.getElementById('chip-lane-' + laneId);
    const elements = document.querySelectorAll('.lane-col-' + laneId);
    if (!chip || elements.length === 0) return;

    const isCurrentlyVisible = !elements[0].classList.contains('hidden');

    if (isCurrentlyVisible) {
        elements.forEach((el) => el.classList.add('hidden'));
        chip.classList.add('opacity-40', 'bg-slate-100');
        chip.classList.remove('border-indigo-400');
        const check = chip.querySelector('span:first-child');
        if (check) check.textContent = '✕';
    } else {
        elements.forEach((el) => el.classList.remove('hidden'));
        chip.classList.remove('opacity-40', 'bg-slate-100');
        chip.classList.add('border-indigo-400');
        const check = chip.querySelector('span:first-child');
        if (check) check.textContent = '✔';
    }

    updateVisibleCount();
}

function initLaneFilters() {
    const filterContainer = document.getElementById('lane-filter-chips');
    if (!filterContainer) return;

    // チップクリック時のトグル
    filterContainer.addEventListener('click', (e) => {
        const chip = e.target.closest('.lane-chip');
        if (!chip) return;
        const laneId = chip.getAttribute('data-lane-id');
        if (laneId) {
            toggleLaneColumn(laneId);
        }
    });

    // 全選択・全解除ボタン
    document.addEventListener('click', (e) => {
        const selectAllBtn = e.target.closest('[data-action="select-all-lanes"]');
        if (selectAllBtn) {
            setAllLanesVisibility(true);
            return;
        }

        const deselectAllBtn = e.target.closest('[data-action="deselect-all-lanes"]');
        if (deselectAllBtn) {
            setAllLanesVisibility(false);
            return;
        }
    });
}

/**
 * 画面で選択中の担当班を反映した進行表PDFダウンロード
 */
function initMatrixPdfDownload() {
    const btn = document.getElementById('btn-download-matrix-pdf');
    if (!btn) return;

    btn.addEventListener('click', () => {
        const timetableId = btn.getAttribute('data-timetable-id');
        if (!timetableId) return;

        // 現在表示中（.opacity-40 を持たない）のチップを収集
        const visibleChips = Array.from(document.querySelectorAll('.lane-chip:not(.opacity-40)'));
        if (visibleChips.length === 0) {
            showToast('印刷対象の担当班を1つ以上選択してください。', 'warning');
            return;
        }

        const laneIds = visibleChips
            .map((chip) => chip.getAttribute('data-lane-id'))
            .filter(Boolean)
            .join(',');

        const targetUrl = `/timetables/${timetableId}/print-matrix?lane_ids=${encodeURIComponent(laneIds)}`;
        window.location.href = targetUrl;
    });
}

/**
 * 進行表グリッド周りのコントロールを一括初期化する
 */
export function initMatrixGridControls() {
    initLaneModal();
    initGridScroll();
    initLaneFilters();
    initLiveClock();
    initCurrentTimeIndicator();
    initMatrixPdfDownload();
}
