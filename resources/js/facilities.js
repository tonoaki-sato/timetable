/**
 * インフラ設備一覧（/timetables/{timetable}/layouts/facilities）
 * 分類フィルター切り替え ＆ 設備編集モーダル操作スクリプト (CSP完全準拠・Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    initFacilityFilters();
    initFacilityEditModal();
});

/**
 * 表示施設分類フィルター機能 (logistics ページデザイン準拠)
 */
function initFacilityFilters() {
    const filterContainer = document.getElementById('facility-filter-chips');
    const facilityRows = document.querySelectorAll('.facility-row');
    const countIndicator = document.getElementById('facility-count-indicator');
    const noMatchingRow = document.getElementById('noMatchingRow');

    if (!filterContainer && facilityRows.length === 0) {
        return;
    }

    const totalCount = Number(countIndicator?.dataset.totalCount ?? facilityRows.length);
    const allCategoryKeys = ['water_supply', 'toilet_nursing', 'other'];

    // URLパラメータから初期選択状態を復元（例: ?categories=water_supply,toilet_nursing）
    const urlParams = new URLSearchParams(window.location.search);
    const categoriesParam = urlParams.get('categories');

    let activeCategories = new Set(
        categoriesParam !== null
            ? (categoriesParam ? categoriesParam.split(',').filter(k => allCategoryKeys.includes(k)) : [])
            : allCategoryKeys
    );

    const setFacilityChipState = (chip, visible) => {
        const check = chip.querySelector('span:first-child');
        if (visible) {
            chip.classList.remove('opacity-40', 'bg-slate-100');
            chip.classList.add('bg-white', 'border-slate-300');
            if (check) check.textContent = '✔';
        } else {
            chip.classList.add('opacity-40', 'bg-slate-100');
            chip.classList.remove('bg-white', 'border-slate-300');
            if (check) check.textContent = '✕';
        }
    };

    const updateStylesAndRows = () => {
        let visibleCount = 0;

        // チップのスタイル更新
        document.querySelectorAll('.facility-chip').forEach(chip => {
            const cat = chip.getAttribute('data-facility-category');
            const isActive = activeCategories.has(cat);
            setFacilityChipState(chip, isActive);
        });

        // テーブル行の表示切り替え
        facilityRows.forEach(row => {
            const rowCat = row.getAttribute('data-facility-category');
            if (activeCategories.has(rowCat)) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        // インジケータ表示の更新 (logistics と同形式: "全 N 施設表示中" または "全 N 施設中 M 施設表示中")
        if (countIndicator) {
            if (visibleCount === totalCount) {
                countIndicator.textContent = `全 ${totalCount} 施設表示中`;
            } else {
                countIndicator.textContent = `全 ${totalCount} 施設中 ${visibleCount} 施設表示中`;
            }
        }

        // 該当なし行の表示制御
        if (noMatchingRow) {
            if (visibleCount === 0 && facilityRows.length > 0) {
                noMatchingRow.classList.remove('hidden');
            } else {
                noMatchingRow.classList.add('hidden');
            }
        }

        // URLパラメータ同期
        const newUrl = new URL(window.location);
        if (activeCategories.size === allCategoryKeys.length) {
            newUrl.searchParams.delete('categories');
        } else {
            newUrl.searchParams.set('categories', Array.from(activeCategories).join(','));
        }
        window.history.replaceState({}, '', newUrl);
    };

    // チップクリックイベント（デリゲーション）
    filterContainer?.addEventListener('click', (e) => {
        const chip = e.target.closest('.facility-chip');
        if (!chip) return;
        const cat = chip.getAttribute('data-facility-category');
        if (activeCategories.has(cat)) {
            activeCategories.delete(cat);
        } else {
            activeCategories.add(cat);
        }
        updateStylesAndRows();
    });

    // 全選択 / 全解除
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="select-all-facilities"]')) {
            allCategoryKeys.forEach(k => activeCategories.add(k));
            updateStylesAndRows();
            return;
        }
        if (e.target.closest('[data-action="deselect-all-facilities"]')) {
            activeCategories.clear();
            updateStylesAndRows();
        }
    });

    updateStylesAndRows();
}

/**
 * 設備編集モーダル機能
 */
function initFacilityEditModal() {
    const modal = document.getElementById('facilityEditModal');
    const form = document.getElementById('facilityEditForm');
    if (!modal || !form) {
        return;
    }

    const codeSpan = document.getElementById('modalFacilityCode');
    const nameInput = document.getElementById('inputFacName');
    const typeSelect = document.getElementById('selectFacType');
    const statusSelect = document.getElementById('selectFacStatus');
    const locationInput = document.getElementById('inputFacLocation');
    const notesTextarea = document.getElementById('inputFacNotes');
    const closeBtn = document.getElementById('modalFacCloseBtn');
    const cancelBtn = document.getElementById('modalFacCancelBtn');

    const closeModal = () => {
        modal.classList.add('hidden');
    };

    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);

    document.querySelectorAll('[data-action="open-facility-modal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const code = btn.getAttribute('data-code');
            const name = btn.getAttribute('data-name');
            const type = btn.getAttribute('data-type');
            const status = btn.getAttribute('data-status');
            const location = btn.getAttribute('data-location') || '';
            const notes = btn.getAttribute('data-notes') || '';

            if (codeSpan) codeSpan.textContent = code;
            if (nameInput) nameInput.value = name;
            if (typeSelect) typeSelect.value = type;
            if (statusSelect) statusSelect.value = status;
            if (locationInput) locationInput.value = location;
            if (notesTextarea) notesTextarea.value = notes;

            const baseAction = form.getAttribute('data-base-action') || '';
            form.action = `${baseAction}/${id}`;
            modal.classList.remove('hidden');
        });
    });
}
