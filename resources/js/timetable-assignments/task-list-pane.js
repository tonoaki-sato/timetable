import { escapeHtml } from '../utils/html.js';

/**
 * 人員事前配置ワークスペース 左ペイン（タスク選択・フィルタ・サマリー同期）
 * Strict CSP準拠（CSSOM経由でプログレスバー幅を設定）
 *
 * @param {{ onSelectTask?: (taskId: number) => void|Promise<void> }} options
 */
export function initTaskListPane({ onSelectTask } = {}) {
    let taskFilterTab = 'shortage'; // 初期は不足タスクにフォーカス
    let taskLaneFilter = '';
    let taskSearchQuery = '';

    // 各タスクカードのプログレスバー初期化（CSP準拠: HTMLインラインスタイルを排除しJSで設定）
    document.querySelectorAll('[data-task-progress-bar]').forEach((bar) => {
        const percent = bar.getAttribute('data-percent') || '0';
        bar.style.width = `${percent}%`;
    });

    const filterTasks = () => {
        const cards = document.querySelectorAll('[data-task-card]');
        let visibleCount = 0;

        cards.forEach((card) => {
            const isSatisfied = card.getAttribute('data-is-satisfied') === '1';
            const laneId = card.getAttribute('data-lane-id');
            const searchText = card.getAttribute('data-search-text') || '';

            let matchTab = true;
            if (taskFilterTab === 'shortage') matchTab = !isSatisfied;
            else if (taskFilterTab === 'satisfied') matchTab = isSatisfied;

            const matchLane = !taskLaneFilter || laneId === taskLaneFilter;
            const matchQuery = !taskSearchQuery || searchText.includes(taskSearchQuery.toLowerCase());

            const isVisible = matchTab && matchLane && matchQuery;
            if (isVisible) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        const emptyEl = document.getElementById('task-filter-empty');
        if (emptyEl) {
            emptyEl.classList.toggle('hidden', visibleCount > 0);
        }
    };

    const selectTask = (taskId) => {
        const parsedId = parseInt(taskId, 10);
        if (!parsedId) return;

        // カードのアクティブハイライト更新
        document.querySelectorAll('[data-task-card]').forEach((card) => {
            const cId = parseInt(card.getAttribute('data-task-id'), 10);
            if (cId === parsedId) {
                card.classList.add('border-indigo-600', 'bg-indigo-50/40', 'ring-2', 'ring-indigo-500/20');
                card.classList.remove('border-slate-200', 'bg-white');
            } else {
                card.classList.remove('border-indigo-600', 'bg-indigo-50/40', 'ring-2', 'ring-indigo-500/20');
                card.classList.add('border-slate-200', 'bg-white');
            }
        });

        // URLパラメータの同期 (リロードなし)
        const url = new URL(window.location);
        url.searchParams.set('task_id', parsedId);
        window.history.replaceState({}, '', url);

        onSelectTask?.(parsedId);
    };

    // タスクタブ切り替え
    const taskTabs = document.querySelectorAll('[data-task-tab]');
    taskTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            taskTabs.forEach((t) => {
                t.classList.remove('bg-white', 'text-indigo-700', 'shadow-2xs');
                t.classList.add('text-slate-600');
            });
            tab.classList.remove('text-slate-600');
            tab.classList.add('bg-white', 'text-indigo-700', 'shadow-2xs');

            taskFilterTab = tab.getAttribute('data-task-tab');
            filterTasks();
        });
    });

    // 班フィルター
    const laneSelect = document.getElementById('task-lane-filter');
    if (laneSelect) {
        laneSelect.addEventListener('change', (e) => {
            taskLaneFilter = e.target.value;
            filterTasks();
        });
    }

    // タスク検索インプット
    const taskSearchInput = document.getElementById('task-search-input');
    const taskSearchClear = document.getElementById('task-search-clear');
    if (taskSearchInput) {
        taskSearchInput.addEventListener('input', (e) => {
            taskSearchQuery = e.target.value.trim();
            if (taskSearchClear) {
                taskSearchClear.classList.toggle('hidden', !taskSearchQuery);
            }
            filterTasks();
        });
    }
    if (taskSearchClear) {
        taskSearchClear.addEventListener('click', () => {
            if (taskSearchInput) taskSearchInput.value = '';
            taskSearchQuery = '';
            taskSearchClear.classList.add('hidden');
            filterTasks();
        });
    }

    // タスクカードイベント登録
    document.querySelectorAll('[data-task-card]').forEach((card) => {
        card.addEventListener('click', () => {
            const tId = parseInt(card.getAttribute('data-task-id'), 10);
            selectTask(tId);
        });
    });

    // 初期フィルタリング実行
    filterTasks();

    const syncLeftTaskCard = (taskId, task, assignments) => {
        const card = document.querySelector(`[data-task-card][data-task-id="${taskId}"]`);
        if (!card) return;

        const isSatisfied = Boolean(task.is_satisfied);
        const assignedCount = task.assigned_count;
        const shortageCount = task.shortage_count;
        const requiredCount = task.required_people_count;
        const percent = requiredCount > 0 ? Math.min(100, Math.round((assignedCount / requiredCount) * 100)) : 100;

        card.setAttribute('data-is-satisfied', isSatisfied ? '1' : '0');
        card.setAttribute('data-shortage-count', shortageCount);
        card.setAttribute('data-assigned-count', assignedCount);

        const badge = card.querySelector('[data-task-status-badge]');
        if (badge) {
            badge.className = `inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold font-mono border ${isSatisfied ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-300'}`;
            badge.textContent = isSatisfied ? `✔ 充足 (${assignedCount})` : `⚠️ 不足 ${shortageCount}名`;
        }

        const bar = card.querySelector('[data-task-progress-bar]');
        if (bar) {
            bar.style.width = `${percent}%`;
            bar.className = `${isSatisfied ? 'bg-emerald-500' : (assignedCount > 0 ? 'bg-amber-500' : 'bg-slate-300')} h-full transition-all duration-300`;
        }

        const peopleLabel = card.querySelector('[data-task-people-label]');
        if (peopleLabel) {
            peopleLabel.textContent = `👥 ${assignedCount}/${task.required_people_label}`;
        }

        const preview = card.querySelector('[data-task-assignments-preview]');
        if (preview) {
            preview.innerHTML = (assignments || []).map((a) => `
                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] bg-slate-100 text-slate-700 border border-slate-200 truncate max-w-[90px]"
                    title="${escapeHtml(a.staff_name)} (${escapeHtml(a.role_description || '担当')})">
                    ${escapeHtml(a.staff_name)}
                </span>
            `).join('');
        }

        filterTasks();
    };

    const updateSummaryCounts = () => {
        const cards = document.querySelectorAll('[data-task-card]');
        const total = cards.length;
        let satisfied = 0;
        let shortage = 0;
        let totalAssigned = 0;

        cards.forEach((c) => {
            if (c.getAttribute('data-is-satisfied') === '1') {
                satisfied++;
            } else {
                shortage++;
            }
            totalAssigned += parseInt(c.getAttribute('data-assigned-count') || '0', 10) || 0;
        });

        const tabShortage = document.getElementById('tab-shortage-count');
        const tabAll = document.getElementById('tab-all-count');
        const tabSatisfied = document.getElementById('tab-satisfied-count');
        const sumShortage = document.getElementById('summary-shortage-tasks');
        const sumSatisfied = document.getElementById('summary-satisfied-tasks');
        const sumAssigned = document.getElementById('summary-total-assigned');

        if (tabShortage) tabShortage.textContent = shortage;
        if (tabAll) tabAll.textContent = total;
        if (tabSatisfied) tabSatisfied.textContent = satisfied;

        if (sumShortage) {
            sumShortage.textContent = `${shortage} 件`;
            sumShortage.className = `font-bold ${shortage > 0 ? 'text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded' : 'text-slate-600'} font-mono`;
        }
        if (sumSatisfied) sumSatisfied.textContent = `${satisfied} 件`;
        if (sumAssigned) sumAssigned.textContent = `${totalAssigned}名`;
    };

    return {
        selectTask,
        syncLeftTaskCard,
        updateSummaryCounts,
    };
}
