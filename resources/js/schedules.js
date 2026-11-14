/**
 * スケジュール管理（文面コピー & 区分トグルフィルター）スクリプト (CSP完全準拠・Vanilla JS)
 */
document.addEventListener('DOMContentLoaded', () => {
    initScheduleCopyButtons();
    initCategoryFilters();
});

/**
 * 区分チップによる一覧表示切り替え（トグル方式）
 */
function initCategoryFilters() {
    const filterContainer = document.getElementById('category-filter-chips');
    if (!filterContainer) {
        return;
    }

    // チップクリック時のトグル
    filterContainer.addEventListener('click', (e) => {
        const chip = e.target.closest('.category-chip');
        if (!chip) return;
        const categoryId = chip.getAttribute('data-category-id');
        if (categoryId) {
            toggleCategoryRows(categoryId);
        }
    });

    // 全選択・全解除ボタン
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="select-all-categories"]')) {
            setAllCategoriesVisibility(true);
            return;
        }
        if (e.target.closest('[data-action="deselect-all-categories"]')) {
            setAllCategoriesVisibility(false);
        }
    });

    updateCategoryFilterState();
}

function setCategoryChipState(chip, visible) {
    const check = chip.querySelector('span:first-child');
    const categoryId = chip.getAttribute('data-category-id');
    if (!categoryId) return;

    const rows = document.querySelectorAll(`.schedule-category-${categoryId}`);

    if (visible) {
        rows.forEach((row) => row.classList.remove('hidden'));
        chip.classList.remove('opacity-40', 'bg-slate-100');
        chip.classList.add('bg-white', 'border-indigo-400');
        if (check) check.textContent = '✔';
    } else {
        rows.forEach((row) => row.classList.add('hidden'));
        chip.classList.add('opacity-40', 'bg-slate-100');
        chip.classList.remove('bg-white', 'border-indigo-400');
        if (check) check.textContent = '✕';
    }
}

function setAllCategoriesVisibility(visible) {
    document.querySelectorAll('.category-chip').forEach((chip) => {
        setCategoryChipState(chip, visible);
    });

    updateCategoryFilterState();
}

function toggleCategoryRows(categoryId) {
    const chip = document.getElementById(`chip-category-${categoryId}`);
    if (!chip) return;

    const isCurrentlyVisible = !chip.classList.contains('opacity-40');
    setCategoryChipState(chip, !isCurrentlyVisible);
    updateCategoryFilterState();
}

function updateCategoryFilterState() {
    const chips = Array.from(document.querySelectorAll('.category-chip'));
    const visibleChips = chips.filter((chip) => !chip.classList.contains('opacity-40'));
    const total = chips.length;
    const visible = visibleChips.length;

    const emptyState = document.getElementById('schedule-filter-empty-state');
    if (emptyState) {
        const rows = document.querySelectorAll('.schedule-row');
        const hasRows = rows.length > 0;
        emptyState.classList.toggle('hidden', !(hasRows && visible === 0));
    }

    // URLクエリパラメータとの同期 (history.replaceState)
    const url = new URL(window.location.href);
    if (total === 0 || visible === total || visible === 0) {
        url.searchParams.delete('category');
    } else {
        const selectedIds = visibleChips
            .map((chip) => chip.getAttribute('data-category-id'))
            .filter(Boolean)
            .join(',');
        url.searchParams.set('category', selectedIds);
    }
    window.history.replaceState({}, '', url.toString());

    // 検索フォーム内 hidden input との同期
    const formCategoryInput = document.getElementById('search-form-category');
    if (formCategoryInput) {
        if (visible === total || visible === 0) {
            formCategoryInput.value = '';
        } else {
            formCategoryInput.value = visibleChips
                .map((chip) => chip.getAttribute('data-category-id'))
                .filter(Boolean)
                .join(',');
        }
    }
}

/**
 * 文面コピーボタン（一覧画面 & 詳細画面）
 */
function initScheduleCopyButtons() {
    // 一覧画面でのコピーボタン (data-action="copy-schedule-line")
    document.addEventListener('click', (e) => {
        const button = e.target.closest('[data-action="copy-schedule-line"]');
        if (!button) return;

        const text = button.getAttribute('data-line-text');
        if (!text) return;

        navigator.clipboard.writeText(text).then(() => {
            const originalHtml = button.innerHTML;
            button.innerHTML = '<span>✔</span><span class="text-emerald-800">コピー完了!</span>';
            button.classList.add('bg-emerald-200');
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.classList.remove('bg-emerald-200');
            }, 2000);
        }).catch(() => {
            alert('クリップボードへのコピーに失敗しました。手動でコピーしてください。');
        });
    });

    // 詳細画面での文面コピー (data-action="copy-current-line")
    document.addEventListener('click', (e) => {
        const button = e.target.closest('[data-action="copy-current-line"]');
        if (!button) return;

        const textBox = document.getElementById('lineTextBox');
        const text = textBox ? textBox.textContent : '';
        if (!text) return;

        navigator.clipboard.writeText(text).then(() => {
            const label = document.getElementById('copyBtnLabel');
            const originalText = label ? label.textContent : '';
            if (label) label.textContent = 'コピーしました！';
            button.classList.remove('bg-emerald-600', 'hover:bg-emerald-500');
            button.classList.add('bg-white', 'text-slate-900');

            setTimeout(() => {
                if (label) label.textContent = originalText;
                button.classList.remove('bg-white', 'text-slate-900');
                button.classList.add('bg-emerald-600', 'hover:bg-emerald-500');
            }, 2000);
        }).catch(() => {
            alert('クリップボードへのコピーに失敗しました。');
        });
    });
}
