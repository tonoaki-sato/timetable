/**
 * 運行情報一覧画面（/timetables）
 * ステータス絞り込みトグルボタン & 管理メニュープルダウン操作スクリプト
 * (CSP完全準拠・Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    initTimetableStatusFilters();
    initTimetableActionMenus();
});

/**
 * ステータス絞り込みトグル切り替え制御
 */
function initTimetableStatusFilters() {
    const filterContainer = document.getElementById('timetable-filter-chips');
    if (!filterContainer) {
        return;
    }

    const allChip = document.getElementById('chip-status-all');
    const statusChips = filterContainer.querySelectorAll('.timetable-status-chip:not(#chip-status-all)');
    const rows = document.querySelectorAll('.timetable-row');
    const emptyRow = document.getElementById('timetable-filter-empty-state');
    const visibleCountSpan = document.getElementById('visible-timetable-count');

    // 初期状態の設定（URLに特定の status が指定されていない場合、draft と active を ON、completed を OFF）
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

    // チップクリックイベントのハンドリング
    filterContainer.addEventListener('click', (e) => {
        const chip = e.target.closest('.timetable-status-chip');
        if (!chip) return;

        const status = chip.getAttribute('data-status');

        if (status === 'all') {
            // 「すべての運行日」ボタン: 現在全選択なら全解除、そうでなければ全選択
            const areAllSelected = isAllSelected();
            statusChips.forEach((c) => setChipState(c, !areAllSelected));
            setChipState(allChip, !areAllSelected);
        } else {
            // 個別ステータスボタンのトグル
            const isCurrentlyActive = isChipActive(chip);
            setChipState(chip, !isCurrentlyActive);
            setChipState(allChip, isAllSelected());
        }

        updateTableVisibility();
    });

    /**
     * チップがアクティブ（ON）かどうか判定
     */
    function isChipActive(chip) {
        return !chip.classList.contains('opacity-40');
    }

    /**
     * 全ステータスチップがアクティブかどうか判定
     */
    function isAllSelected() {
        return Array.from(statusChips).every((c) => isChipActive(c));
    }

    /**
     * チップの ON / OFF スタイルおよびアイコン更新
     */
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

    /**
     * テーブル行の表示/非表示および件数の更新
     */
    function updateTableVisibility() {
        // 現在アクティブなステータス一覧を取得
        const activeStatuses = new Set();
        statusChips.forEach((chip) => {
            if (isChipActive(chip)) {
                activeStatuses.add(chip.getAttribute('data-status'));
            }
        });

        // 「すべての運行日」チップの表示状態を同期
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
}

/**
 * 管理メニューのプルダウン（ドロップダウン）開閉制御
 */
function initTimetableActionMenus() {
    const containers = document.querySelectorAll('.timetable-menu-container');
    if (containers.length === 0) {
        return;
    }

    containers.forEach((container) => {
        const trigger = container.querySelector('.timetable-menu-trigger');
        const dropdown = container.querySelector('.timetable-menu-dropdown');
        if (!trigger || !dropdown) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isCurrentlyOpen = !dropdown.classList.contains('hidden');

            // 他の開いている全メニューを閉じる
            closeAllMenus();

            if (!isCurrentlyOpen) {
                // ウィンドウ下端との距離を判定し、狭い場合は上向きに展開
                const rect = trigger.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                if (spaceBelow < 180) {
                    dropdown.classList.remove('top-full', 'mt-1');
                    dropdown.classList.add('bottom-full', 'mb-1');
                } else {
                    dropdown.classList.remove('bottom-full', 'mb-1');
                    dropdown.classList.add('top-full', 'mt-1');
                }

                dropdown.classList.remove('hidden');
                trigger.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // 外側クリックでメニューを閉じる
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.timetable-menu-container')) {
            closeAllMenus();
        }
    });

    // Escape キーでメニューを閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllMenus();
        }
    });

    function closeAllMenus() {
        document.querySelectorAll('.timetable-menu-dropdown').forEach((menu) => {
            menu.classList.add('hidden');
        });
        document.querySelectorAll('.timetable-menu-trigger').forEach((trigger) => {
            trigger.setAttribute('aria-expanded', 'false');
        });
    }
}
