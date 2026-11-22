/**
 * 人員配置 UI の状態管理・絞り込みロジック（純粋関数）
 */

/**
 * 初期状態オブジェクトを生成する
 */
export function createAssignmentState() {
    return {
        taskId: null,
        taskData: null,
        selectedStaffIds: [],
        searchQuery: '',
        statusFilter: 'all',
        affiliationFilter: '',
    };
}

/**
 * タスク切替時に状態をリセットする
 *
 * @param {object} state
 * @param {number|string|null} taskId
 * @param {{ resetFilters?: boolean }} options
 */
export function resetStateForTask(state, taskId, { resetFilters = false } = {}) {
    state.taskId = taskId;
    state.taskData = null;
    state.selectedStaffIds = [];
    if (resetFilters) {
        state.searchQuery = '';
        state.statusFilter = 'all';
        state.affiliationFilter = '';
    }
}

/**
 * 候補スタッフ一覧を条件で絞り込む
 *
 * @param {Array<object>} candidates
 * @param {{ searchQuery?: string, statusFilter?: string, affiliationFilter?: string }} filters
 * @returns {Array<object>}
 */
export function filterCandidates(candidates, { searchQuery = '', statusFilter = 'all', affiliationFilter = '' } = {}) {
    const list = Array.isArray(candidates) ? candidates : [];
    const q = String(searchQuery || '').trim().toLowerCase();

    return list.filter((c) => {
        const matchQ = !q
            || (c.name && c.name.toLowerCase().includes(q))
            || (c.kana && c.kana.toLowerCase().includes(q))
            || (c.affiliation && c.affiliation.toLowerCase().includes(q));

        const matchAff = !affiliationFilter || (c.affiliation || '') === affiliationFilter;

        let matchStatus = true;
        if (statusFilter === 'available') {
            matchStatus = !c.is_conflicted && !c.is_already_assigned;
        } else if (statusFilter === 'recommended') {
            matchStatus = c.priority_tier === 1 && !c.is_already_assigned;
        } else if (statusFilter === 'surplus') {
            matchStatus = Boolean(c.is_surplus_fixed) && !c.is_already_assigned;
        } else if (statusFilter === 'checked_in') {
            matchStatus = Boolean(c.is_checked_in) && !c.is_already_assigned;
        }

        return matchQ && matchAff && matchStatus;
    });
}

/**
 * 指定スタッフIDの選択状態をトグルする
 *
 * @param {object} state
 * @param {number} staffId
 */
export function toggleStaffSelection(state, staffId) {
    const id = parseInt(staffId, 10);
    if (!id) {
        return;
    }
    if (state.selectedStaffIds.includes(id)) {
        state.selectedStaffIds = state.selectedStaffIds.filter((item) => item !== id);
    } else {
        state.selectedStaffIds = [...state.selectedStaffIds, id];
    }
}

/**
 * 不足人数分だけ優先順（未配置かつ非重複）に候補IDを抽出する
 *
 * @param {Array<object>} pool
 * @param {number} shortageCount
 * @returns {number[]}
 */
export function selectShortageCandidates(pool, shortageCount) {
    const count = Math.max(0, parseInt(shortageCount, 10) || 0);
    if (count <= 0) {
        return [];
    }
    return (pool || [])
        .filter((c) => !c.is_already_assigned && !c.is_conflicted)
        .slice(0, count)
        .map((c) => c.id);
}

/**
 * 表示中の未配置スタッフを重複なく選択リストへ追加する
 *
 * @param {number[]} selectedStaffIds
 * @param {Array<object>} candidates
 * @returns {number[]}
 */
export function selectAllEligible(selectedStaffIds, candidates) {
    const next = [...selectedStaffIds];
    (candidates || []).forEach((c) => {
        if (!c.is_already_assigned && !next.includes(c.id)) {
            next.push(c.id);
        }
    });
    return next;
}

/**
 * 候補スタッフから所属団体の一覧（重複排除済み）を抽出する
 *
 * @param {Array<object>} candidates
 * @returns {string[]}
 */
export function extractAffiliations(candidates) {
    return [...new Set((candidates || []).map((c) => c.affiliation).filter(Boolean))];
}
