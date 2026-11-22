import { showToast } from '../utils/ui.js';

/**
 * 資材チップによる一覧表示切り替え＆搬出入指示書PDF URL連動
 */
function setEquipmentChipState(chip, panel, visible) {
    const check = chip.querySelector('span:first-child');

    if (visible) {
        panel?.classList.remove('hidden');
        chip.classList.remove('opacity-40', 'bg-slate-100');
        chip.classList.add('bg-white', 'border-indigo-400');
        if (check) check.textContent = '✔';
    } else {
        panel?.classList.add('hidden');
        chip.classList.add('opacity-40', 'bg-slate-100');
        chip.classList.remove('bg-white', 'border-indigo-400');
        if (check) check.textContent = '✕';
    }
}

function updateEquipmentFilterState() {
    const chips = Array.from(document.querySelectorAll('.equipment-chip'));
    const visibleChips = chips.filter((chip) => !chip.classList.contains('opacity-40'));
    const total = chips.length;
    const visible = visibleChips.length;

    const indicator = document.getElementById('equipment-count-indicator');
    if (indicator) {
        indicator.textContent = visible === total
            ? `全 ${total} 資材表示中`
            : `全 ${total} 資材中 ${visible} 資材表示中`;
    }

    const emptyState = document.getElementById('logistics-filter-empty-state');
    if (emptyState) {
        emptyState.classList.toggle('hidden', !(total > 0 && visible === 0));
    }

    const pdfBtn = document.getElementById('btn-download-logistics-pdf');
    if (pdfBtn) {
        const basePrintUrl = pdfBtn.getAttribute('data-base-print-url');
        if (basePrintUrl) {
            if (total === 0 || visible === total || visible === 0) {
                pdfBtn.setAttribute('href', basePrintUrl);
            } else {
                const selectedIds = visibleChips
                    .map((chip) => chip.getAttribute('data-equipment-id'))
                    .filter(Boolean)
                    .join(',');
                const url = new URL(basePrintUrl, window.location.origin);
                url.searchParams.set('equipment_ids', selectedIds);
                pdfBtn.setAttribute('href', `${url.pathname}${url.search}`);
            }
        }
    }
}

function setAllEquipmentsVisibility(visible) {
    document.querySelectorAll('.equipment-chip').forEach((chip) => {
        const equipmentId = chip.getAttribute('data-equipment-id');
        if (!equipmentId) return;
        const panel = document.getElementById(`equipment-${equipmentId}`);
        setEquipmentChipState(chip, panel, visible);
    });

    updateEquipmentFilterState();
}

function toggleEquipmentPanel(equipmentId) {
    const chip = document.getElementById(`chip-equipment-${equipmentId}`);
    const panel = document.getElementById(`equipment-${equipmentId}`);
    if (!chip || !panel) return;

    const isCurrentlyVisible = !panel.classList.contains('hidden');
    setEquipmentChipState(chip, panel, !isCurrentlyVisible);
    updateEquipmentFilterState();
}

export function initEquipmentFilters() {
    const filterContainer = document.getElementById('equipment-filter-chips');
    if (!filterContainer) {
        return;
    }

    filterContainer.addEventListener('click', (e) => {
        const chip = e.target.closest('.equipment-chip');
        if (!chip) return;
        const equipmentId = chip.getAttribute('data-equipment-id');
        if (equipmentId) {
            toggleEquipmentPanel(equipmentId);
        }
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="select-all-equipments"]')) {
            setAllEquipmentsVisibility(true);
            return;
        }
        if (e.target.closest('[data-action="deselect-all-equipments"]')) {
            setAllEquipmentsVisibility(false);
        }
    });

    const pdfBtn = document.getElementById('btn-download-logistics-pdf');
    if (pdfBtn) {
        pdfBtn.addEventListener('click', (e) => {
            const chips = document.querySelectorAll('.equipment-chip');
            const visibleChips = document.querySelectorAll('.equipment-chip:not(.opacity-40)');
            if (chips.length > 0 && visibleChips.length === 0) {
                e.preventDefault();
                showToast('印刷対象の資材を1つ以上選択してください。', 'warning');
            }
        });
    }

    updateEquipmentFilterState();
}
