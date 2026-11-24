/**
 * 班別タイムライン フィルター制御
 */
export function applyLaneTimelineFilters({
    rootEl,
    searchQuery,
    taskTypeFilter,
    shortageFilter,
    selectedLaneIds,
}) {
    if (!rootEl) return;
    const q = (searchQuery || '').trim().toLowerCase();
    const rows = rootEl.querySelectorAll('[data-gantt-row]');

    rows.forEach((row) => {
        const laneId = row.dataset.laneId || '';
        const laneName = row.dataset.laneName || '';
        const laneChecked = selectedLaneIds.has(laneId);

        if (!laneChecked) {
            row.classList.add('hidden');
            return;
        }

        const bars = Array.from(row.querySelectorAll('[data-gantt-bar]'));
        let anyBarVisible = false;

        bars.forEach((bar) => {
            const barType = bar.dataset.taskType || 'spot';
            const isSatisfied = bar.dataset.isSatisfied === '1';
            const searchText = bar.dataset.searchText || '';

            const matchType = taskTypeFilter === 'all' || barType === taskTypeFilter;
            const matchShortage = shortageFilter === 'all'
                || (shortageFilter === 'shortage' && !isSatisfied)
                || (shortageFilter === 'satisfied' && isSatisfied);
            const matchSearch = !q || searchText.includes(q) || laneName.includes(q);

            const visible = matchType && matchShortage && matchSearch;
            bar.classList.toggle('hidden', !visible);
            if (visible) anyBarVisible = true;
        });

        // 検索や絞り込み中は該当バーがある行（または検索語が班名に一致する行）を表示
        const hasActiveFilter = Boolean(q) || taskTypeFilter !== 'all' || shortageFilter !== 'all';
        const matchRow = !hasActiveFilter || anyBarVisible || (Boolean(q) && laneName.includes(q) && taskTypeFilter === 'all' && shortageFilter === 'all');

        row.classList.toggle('hidden', !matchRow);
    });
}
