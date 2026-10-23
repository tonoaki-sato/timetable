/**
 * タイムテーブル（マトリクス進行表）対話スクリプト
 */
document.addEventListener('DOMContentLoaded', () => {
    initLaneModal();
    initGridScroll();
    initLaneFilters();
});

function initLaneModal() {
    const modal = document.getElementById('laneModal');
    if (!modal) return;

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="open-lane-modal"]')) {
            modal.classList.remove('hidden');
        } else if (e.target.closest('[data-action="close-lane-modal"]')) {
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

    // プリセットボタン
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-lane-preset]');
        if (!btn) return;
        const preset = btn.getAttribute('data-lane-preset');
        if (preset) {
            filterLanesPreset(preset);
        }
    });
}

function toggleLaneColumn(laneId) {
    const chip = document.getElementById('chip-lane-' + laneId);
    const elements = document.querySelectorAll('.lane-col-' + laneId);
    if (!chip || elements.length === 0) return;

    const isCurrentlyVisible = !elements[0].classList.contains('hidden');

    if (isCurrentlyVisible) {
        elements.forEach(el => el.classList.add('hidden'));
        chip.classList.add('opacity-40', 'bg-slate-100');
        chip.classList.remove('border-indigo-400');
        const check = chip.querySelector('span:first-child');
        if (check) check.textContent = '✕';
    } else {
        elements.forEach(el => el.classList.remove('hidden'));
        chip.classList.remove('opacity-40', 'bg-slate-100');
        chip.classList.add('border-indigo-400');
        const check = chip.querySelector('span:first-child');
        if (check) check.textContent = '✔';
    }

    updateVisibleCount();
    resetPresetButtons();
}

function filterLanesPreset(preset) {
    const chips = document.querySelectorAll('.lane-chip');

    chips.forEach(chip => {
        const laneId = chip.getAttribute('data-lane-id');
        const isCore = chip.getAttribute('data-is-core') === '1';
        const leadId = chip.getAttribute('data-lead-id');
        const elements = document.querySelectorAll('.lane-col-' + laneId);

        let shouldShow = false;

        if (preset === 'all') {
            shouldShow = true;
        } else if (preset === 'core') {
            shouldShow = isCore;
        } else if (preset === 'mine') {
            shouldShow = leadId !== '';
        }

        if (shouldShow) {
            elements.forEach(el => el.classList.remove('hidden'));
            chip.classList.remove('opacity-40', 'bg-slate-100');
            const check = chip.querySelector('span:first-child');
            if (check) check.textContent = '✔';
        } else {
            elements.forEach(el => el.classList.add('hidden'));
            chip.classList.add('opacity-40', 'bg-slate-100');
            const check = chip.querySelector('span:first-child');
            if (check) check.textContent = '✕';
        }
    });

    // プリセットボタンスタイル更新
    document.querySelectorAll('[data-lane-preset]').forEach(btn => {
        btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600');
        btn.classList.add('bg-white', 'text-slate-700', 'border-slate-300');
    });
    const activeBtn = document.querySelector(`[data-lane-preset="${preset}"]`);
    if (activeBtn) {
        activeBtn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
        activeBtn.classList.remove('bg-white', 'text-slate-700', 'border-slate-300');
    }

    updateVisibleCount();
}

function resetPresetButtons() {
    document.querySelectorAll('[data-lane-preset]').forEach(btn => {
        btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600');
        btn.classList.add('bg-white', 'text-slate-700', 'border-slate-300');
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
