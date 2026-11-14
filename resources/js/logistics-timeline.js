/**
 * 搬出入タイムライン スクリプト (CSP完全準拠・バニラJS)
 */
document.addEventListener('DOMContentLoaded', () => {
    initLogisticsTimelineFilter();
});

/**
 * 機材・資材行の絞り込みフィルター（キーワード検索、区分、進捗ステータス）
 */
function initLogisticsTimelineFilter() {
    const searchInput = document.getElementById('logistics-timeline-search');
    const typeButtons = document.querySelectorAll('[data-filter-type]');
    const statusButtons = document.querySelectorAll('[data-filter-status]');
    const rows = document.querySelectorAll('tr.equipment-row');

    if (rows.length === 0) {
        return;
    }

    let currentSearch = '';
    let currentType = 'all';
    let currentStatus = 'all';

    function applyFilter() {
        const q = currentSearch.toLowerCase();

        rows.forEach((row) => {
            const name = (row.dataset.equipmentName || '').toLowerCase();
            const storage = (row.dataset.equipmentStorage || '').toLowerCase();
            const type = row.dataset.equipmentType || '';
            const hasPlanned = row.dataset.hasPlanned === '1';
            const hasInProgress = row.dataset.hasInProgress === '1';
            const hasCompleted = row.dataset.hasCompleted === '1';

            const matchSearch = !q || name.includes(q) || storage.includes(q);
            const matchType = currentType === 'all' || type === currentType;

            let matchStatus = true;
            if (currentStatus === 'planned') {
                matchStatus = hasPlanned;
            } else if (currentStatus === 'in_progress') {
                matchStatus = hasInProgress;
            } else if (currentStatus === 'completed') {
                matchStatus = hasCompleted;
            }

            if (matchSearch && matchType && matchStatus) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            currentSearch = e.target.value.trim();
            applyFilter();
        });
    }

    typeButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            currentType = btn.getAttribute('data-filter-type') || 'all';

            typeButtons.forEach((b) => {
                b.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600');
                b.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
            });

            btn.classList.remove('bg-white', 'text-slate-700', 'border-slate-200');
            btn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');

            applyFilter();
        });
    });

    statusButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            currentStatus = btn.getAttribute('data-filter-status') || 'all';

            statusButtons.forEach((b) => {
                b.classList.remove('bg-slate-800', 'text-white', 'border-slate-800');
                b.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
            });

            btn.classList.remove('bg-white', 'text-slate-700', 'border-slate-200');
            btn.classList.add('bg-slate-800', 'text-white', 'border-slate-800');

            applyFilter();
        });
    });
}
