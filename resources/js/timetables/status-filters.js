/**
 * 運行情報一覧 ステータス絞り込みトグル切り替え制御
 */
export function initTimetableStatusFilters() {
    const filterContainer = document.getElementById('timetable-filter-chips');
    if (!filterContainer) {
        return;
    }

    const allChip = document.getElementById('chip-status-all');
    const statusChips = filterContainer.querySelectorAll('.timetable-status-chip:not(#chip-status-all)');
    const rows = document.querySelectorAll('.timetable-row');
    const emptyRow = document.getElementById('timetable-filter-empty-state');
    const visibleCountSpan = document.getElementById('visible-timetable-count');

    function isChipActive(chip) {
        return !chip.classList.contains('opacity-40');
    }

    function isAllSelected() {
        return Array.from(statusChips).every((c) => isChipActive(c));
    }

    function setChipState(chip, active) {
        if (!chip) return;
        const iconSpan = chip.querySelector('.chip-icon');

        if (active) {
            chip.classList.remove('opacity-40', 'bg-slate-100', 'text-slate-600', 'border-slate-300');
            chip.classList.add('bg-white', 'text-indigo-700', 'border-indigo-400', 'shadow-2xs');
            if (iconSpan) {
                iconSpan.textContent = '✔';
                iconSpan.classList.remove('text-slate-400');
                iconSpan.classList.add('text-emerald-600');
            }
        } else {
            chip.classList.remove('bg-white', 'text-indigo-700', 'border-indigo-400', 'shadow-2xs');
            chip.classList.add('opacity-40', 'bg-slate-100', 'text-slate-600', 'border-slate-300');
            if (iconSpan) {
                iconSpan.textContent = '✕';
                iconSpan.classList.remove('text-emerald-600');
                iconSpan.classList.add('text-slate-400');
            }
        }
    }

    function updateTableVisibility() {
        const activeStatuses = new Set();
        statusChips.forEach((chip) => {
            if (isChipActive(chip)) {
                activeStatuses.add(chip.getAttribute('data-status'));
            }
        });

        setChipState(allChip, isAllSelected());

        let visibleCount = 0;
        rows.forEach((row) => {
            const rowStatus = row.getAttribute('data-status');
            if (activeStatuses.has(rowStatus)) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        if (visibleCountSpan) {
            visibleCountSpan.textContent = String(visibleCount);
        }

        if (emptyRow) {
            if (visibleCount === 0 && rows.length > 0) {
                emptyRow.classList.remove('hidden');
            } else {
                emptyRow.classList.add('hidden');
            }
        }
    }

    const urlParams = new URLSearchParams(window.location.search);
    const initialStatusParam = urlParams.get('status');

    if (initialStatusParam) {
        statusChips.forEach((chip) => {
            const status = chip.getAttribute('data-status');
            const isActive = initialStatusParam === 'all' || initialStatusParam === status;
            setChipState(chip, isActive);
        });
    } else {
        statusChips.forEach((chip) => {
            const status = chip.getAttribute('data-status');
            const isActive = status === 'draft' || status === 'active';
            setChipState(chip, isActive);
        });
    }

    updateTableVisibility();

    filterContainer.addEventListener('click', (e) => {
        const chip = e.target.closest('.timetable-status-chip');
        if (!chip) return;

        const status = chip.getAttribute('data-status');

        if (status === 'all') {
            const areAllSelected = isAllSelected();
            statusChips.forEach((c) => setChipState(c, !areAllSelected));
            setChipState(allChip, !areAllSelected);
        } else {
            const isCurrentlyActive = isChipActive(chip);
            setChipState(chip, !isCurrentlyActive);
            setChipState(allChip, isAllSelected());
        }

        updateTableVisibility();
    });
}
