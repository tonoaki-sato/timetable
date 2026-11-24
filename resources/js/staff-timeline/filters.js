/**
 * スタッフタイムライン 絞り込み＆グルーピング制御
 */
export function applyStaffTimelineFiltersAndGrouping({
    rowsContainer,
    searchQuery,
    includeLeaders,
    statusFilter,
    taskTypeFilter,
    groupMode,
}) {
    if (!rowsContainer) return;

    const q = (searchQuery || '').trim().toLowerCase();
    const rows = Array.from(rowsContainer.querySelectorAll('[data-gantt-row]'));

    // 既存のグループ見出し行をリセット
    rowsContainer.querySelectorAll('[data-group-header]').forEach((h) => h.remove());

    rows.forEach((row) => {
        const name = (row.dataset.staffName || '').toLowerCase();
        const kana = (row.dataset.staffKana || '').toLowerCase();
        const affiliation = (row.dataset.staffAffiliation || '').toLowerCase();
        const assignedCount = parseInt(row.dataset.assignedCount || '0', 10);
        const isCheckedIn = row.dataset.checkedIn === '1';
        const isLeader = row.dataset.isLeader === '1';

        const bars = Array.from(row.querySelectorAll('[data-gantt-bar]'));
        let anyBarMatchesText = false;
        let visibleBarCount = 0;

        bars.forEach((bar) => {
            const barType = bar.dataset.taskType || 'spot';
            const barSearch = bar.dataset.searchText || '';
            const matchType = taskTypeFilter === 'all' || barType === taskTypeFilter;
            const matchBarSearch = !q || barSearch.includes(q);
            if (matchBarSearch) anyBarMatchesText = true;

            // 種別フィルターのみバーを隠す（検索ヒット時はスタッフ全員またはタスク一致を残す）
            const barVisible = matchType && (!q || name.includes(q) || kana.includes(q) || affiliation.includes(q) || barSearch.includes(q));
            bar.classList.toggle('hidden', !barVisible);
            if (barVisible) visibleBarCount++;
        });

        const matchStaffText = !q || name.includes(q) || kana.includes(q) || affiliation.includes(q) || anyBarMatchesText;
        const matchLeader = includeLeaders ? true : !isLeader;

        let matchStatus = true;
        if (statusFilter === 'assigned') {
            matchStatus = assignedCount > 0;
        } else if (statusFilter === 'unassigned') {
            matchStatus = assignedCount === 0;
        } else if (statusFilter === 'checked_in') {
            matchStatus = isCheckedIn;
        }

        const matchTypeRow = taskTypeFilter === 'all' || visibleBarCount > 0;
        const visible = matchStaffText && matchLeader && matchStatus && matchTypeRow;
        row.classList.toggle('hidden', !visible);
    });

    // グルーピング処理
    if (groupMode === 'flat') {
        rows.sort((a, b) => (a.dataset.staffAffiliation || '').localeCompare(b.dataset.staffAffiliation || '') || (a.dataset.staffName || '').localeCompare(b.dataset.staffName || ''));
        rows.forEach((r) => rowsContainer.appendChild(r));
        return;
    }

    const groupKeyAttr = groupMode === 'lane' ? 'groupLane' : 'groupAffiliation';
    const groups = new Map();

    rows.forEach((row) => {
        const key = row.dataset[groupKeyAttr] || '未設定';
        if (!groups.has(key)) {
            groups.set(key, []);
        }
        groups.get(key).push(row);
    });

    const sortedKeys = Array.from(groups.keys()).sort((a, b) => {
        if (a === '未配属・フリー' || a === '所属なし') return 1;
        if (b === '未配属・フリー' || b === '所属なし') return -1;
        return a.localeCompare(b, 'ja');
    });

    sortedKeys.forEach((groupName) => {
        const groupRows = groups.get(groupName);
        const visibleCount = groupRows.filter((r) => !r.classList.contains('hidden')).length;

        const header = document.createElement('div');
        header.dataset.groupHeader = groupName;
        header.className = `flex items-center bg-slate-100 border-y border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-800 select-none ${visibleCount === 0 ? 'hidden' : ''}`;
        const labelPrefix = groupMode === 'lane' ? '🏷️ ' : '🏢 ';
        header.innerHTML = `
            <div class="sticky left-0 z-20 flex items-center space-x-2">
                <span>${labelPrefix}${escapeText(groupName)}</span>
                <span class="px-1.5 py-0.2 rounded-full bg-white border border-slate-300 text-[10px] font-mono text-slate-600">${visibleCount}名</span>
            </div>
        `;
        rowsContainer.appendChild(header);
        groupRows.forEach((r) => rowsContainer.appendChild(r));
    });
}

function escapeText(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}
