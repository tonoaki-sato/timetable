/**
 * 人員アサイン（タスク別配置）ワークスペース対話スクリプト
 * CSP完全準拠 (Vanilla JS ES Modules)
 */

document.addEventListener('DOMContentLoaded', () => {
    initAssignmentWorkspace();
});

function initAssignmentWorkspace() {
    const workspaceEl = document.getElementById('assignment-workspace');
    if (!workspaceEl) return;

    const timetableId = workspaceEl.getAttribute('data-timetable-id');
    const initialTaskId = workspaceEl.getAttribute('data-initial-task-id');

    let currentTaskId = initialTaskId ? parseInt(initialTaskId, 10) : null;
    let taskData = null;
    let selectedStaffIds = [];
    let staffFilterTab = 'all';
    let staffSearchQuery = '';
    let staffAffiliation = '';

    let taskFilterTab = 'shortage'; // 初期は不足タスクにフォーカス
    let taskLaneFilter = '';
    let taskSearchQuery = '';

    const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // 各タスクカードのプログレスバー初期化（CSP準拠: HTMLインラインスタイルを排除しJSで設定）
    document.querySelectorAll('[data-task-progress-bar]').forEach(bar => {
        const percent = bar.getAttribute('data-percent') || '0';
        bar.style.width = `${percent}%`;
    });

    // =========================================================================
    // 1. 左ペイン: タスク一覧のフィルタリング & 選択
    // =========================================================================

    const filterTasks = () => {
        const cards = document.querySelectorAll('[data-task-card]');
        let visibleCount = 0;
        let shortageVisibleCount = 0;
        let satisfiedVisibleCount = 0;

        cards.forEach(card => {
            const isSatisfied = card.getAttribute('data-is-satisfied') === '1';
            const laneId = card.getAttribute('data-lane-id');
            const searchText = card.getAttribute('data-search-text') || '';

            // 充足タブ
            let matchTab = true;
            if (taskFilterTab === 'shortage') matchTab = !isSatisfied;
            else if (taskFilterTab === 'satisfied') matchTab = isSatisfied;

            // 班フィルター
            const matchLane = !taskLaneFilter || laneId === taskLaneFilter;

            // 検索クエリ
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

    // タスクタブ切り替え
    const taskTabs = document.querySelectorAll('[data-task-tab]');
    taskTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            taskTabs.forEach(t => {
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

    // タスクカードのクリック選択
    const selectTask = async (taskId) => {
        if (!taskId) return;
        currentTaskId = taskId;
        selectedStaffIds = [];

        // カードのアクティブハイライト更新
        document.querySelectorAll('[data-task-card]').forEach(card => {
            const cId = parseInt(card.getAttribute('data-task-id'), 10);
            if (cId === currentTaskId) {
                card.classList.add('border-indigo-600', 'bg-indigo-50/40', 'ring-2', 'ring-indigo-500/20');
                card.classList.remove('border-slate-200', 'bg-white');
            } else {
                card.classList.remove('border-indigo-600', 'bg-indigo-50/40', 'ring-2', 'ring-indigo-500/20');
                card.classList.add('border-slate-200', 'bg-white');
            }
        });

        // URLパラメータの同期 (リロードなし)
        const url = new URL(window.location);
        url.searchParams.set('task_id', currentTaskId);
        window.history.replaceState({}, '', url);

        // 右ペインのロードと描画
        showLoading();
        await fetchTaskData(currentTaskId);
    };

    // タスクカードイベント登録
    document.querySelectorAll('[data-task-card]').forEach(card => {
        card.addEventListener('click', () => {
            const tId = parseInt(card.getAttribute('data-task-id'), 10);
            selectTask(tId);
        });
    });

    // 初期フィルタリング実行
    filterTasks();

    // =========================================================================
    // 2. 右ペイン: タスク詳細 & アサインデータの取得と描画
    // =========================================================================

    const showLoading = () => {
        const loadingEl = document.getElementById('detail-loading');
        const contentEl = document.getElementById('detail-content');
        if (loadingEl) loadingEl.classList.remove('hidden');
        if (contentEl) contentEl.classList.add('hidden');
    };

    const hideLoading = () => {
        const loadingEl = document.getElementById('detail-loading');
        const contentEl = document.getElementById('detail-content');
        if (loadingEl) loadingEl.classList.add('hidden');
        if (contentEl) contentEl.classList.remove('hidden');
    };

    const showAlert = (message, type = 'error') => {
        const alertArea = document.getElementById('assign-alert-area');
        if (!alertArea) return;

        const isSuccess = type === 'success';
        const isWarning = type === 'warning';
        const bgClass = isSuccess ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
            : (isWarning ? 'bg-amber-50 border-amber-300 text-amber-900' : 'bg-rose-50 border-rose-200 text-rose-800');

        alertArea.innerHTML = `
            <div class="p-2.5 rounded-lg border text-xs flex items-center justify-between ${bgClass}">
                <div class="flex items-center space-x-1.5 min-w-0">
                    <span class="shrink-0">${isSuccess ? '✔' : (isWarning ? '⚠️' : '🚨')}</span>
                    <span class="truncate">${escapeHtml(message)}</span>
                </div>
                <button type="button" data-action="dismiss-alert" class="text-xs font-bold px-1.5 text-slate-400 hover:text-slate-700">✕</button>
            </div>
        `;
        alertArea.classList.remove('hidden');

        alertArea.querySelector('[data-action="dismiss-alert"]')?.addEventListener('click', () => {
            alertArea.classList.add('hidden');
        });
    };

    const clearAlert = () => {
        const alertArea = document.getElementById('assign-alert-area');
        if (alertArea) {
            alertArea.innerHTML = '';
            alertArea.classList.add('hidden');
        }
    };

    const fetchTaskData = async (taskId) => {
        try {
            const res = await fetch(`/timetable-tasks/${taskId}/assign-modal-data`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error('タスク情報の取得に失敗しました');
            taskData = await res.json();
            hideLoading();
            renderRightPane();
        } catch (err) {
            const loadingEl = document.getElementById('detail-loading');
            if (loadingEl) {
                loadingEl.innerHTML = `
                    <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs inline-block text-left">
                        <p class="font-bold mb-1">エラーが発生しました</p>
                        <p>${escapeHtml(err.message || '読み込みに失敗しました')}</p>
                        <button type="button" id="btn-retry-task" class="mt-2 px-3 py-1 bg-white border border-rose-300 rounded text-xs font-bold text-rose-700 hover:bg-rose-50 cursor-pointer">再試行</button>
                    </div>
                `;
                document.getElementById('btn-retry-task')?.addEventListener('click', () => selectTask(taskId));
            }
        }
    };

    const renderRightPane = () => {
        if (!taskData) return;
        clearAlert();

        const task = taskData.task;
        const assignments = taskData.assignments || [];
        const candidates = taskData.candidate_staff || [];

        // 1. ヘッダー情報
        const titleEl = document.getElementById('detail-task-title');
        const timeEl = document.getElementById('detail-task-time');
        const locationEl = document.getElementById('detail-task-location');
        const statusEl = document.getElementById('detail-task-status');
        const poolBtnWrapper = document.getElementById('detail-pool-btn-wrapper');
        const taskLink = document.getElementById('detail-task-link');

        const fullTitle = task.lane ? `${task.lane} ${task.title}` : task.title;
        if (titleEl) titleEl.textContent = fullTitle;
        if (timeEl) timeEl.textContent = `⏱️ ${task.start_time} 〜 ${task.end_time}`;
        if (locationEl) {
            if (task.location) {
                locationEl.textContent = `📍 ${task.location}`;
                locationEl.classList.remove('hidden');
            } else {
                locationEl.classList.add('hidden');
            }
        }
        if (statusEl) {
            statusEl.innerHTML = task.is_satisfied
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/40 font-mono">✔ ${task.assigned_count}/${escapeHtml(task.required_people_label)} 充足</span>`
                : `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/25 text-amber-200 border border-amber-400/50 font-mono">⚠️ ${task.assigned_count}/${escapeHtml(task.required_people_label)} (不足${task.shortage_count}名)</span>`;
        }

        if (poolBtnWrapper) {
            if (!task.is_satisfied && task.lane) {
                poolBtnWrapper.innerHTML = `
                    <div class="flex items-center space-x-1.5">
                        <button type="button" id="btn-assign-lane-pool"
                            title="「${escapeHtml(task.lane)}」の空き専属メンバーから不足分を自動配置"
                            class="px-2.5 py-1 bg-amber-400 hover:bg-amber-300 text-slate-950 rounded-lg text-[11px] font-bold transition flex items-center space-x-1 cursor-pointer shadow-xs">
                            <span>⚡</span>
                            <span>班から自動配置</span>
                        </button>
                        <span class="hidden sm:inline-block text-[10px] text-indigo-200/90 bg-slate-800/80 border border-slate-700 px-1.5 py-0.5 rounded" title="担当班の固定・所属スタッフから、重複のない空きメンバーを不足人数分だけ自動で配置します">
                            ※ 担当班の空き枠から自動補充
                        </span>
                    </div>
                `;
                document.getElementById('btn-assign-lane-pool')?.addEventListener('click', handleAssignLanePool);
            } else {
                poolBtnWrapper.innerHTML = '';
            }
        }

        if (taskLink) {
            taskLink.href = `/timetable-tasks?timetable_id=${timetableId}#task-${task.id}`;
        }

        // 2. 現在配置中のスタッフ一覧
        renderCurrentAssignments(assignments);

        // 3. 候補スタッフ一覧
        renderCandidateStaff(candidates);

        // 4. アクションボタン・カウンターの更新
        updateSelectionCounters();
    };

    const renderCurrentAssignments = (assignments) => {
        const countBadge = document.getElementById('current-assignments-count-badge');
        const listEl = document.getElementById('current-assignments-list');
        if (countBadge) countBadge.textContent = `${assignments.length}名`;
        if (!listEl) return;

        if (assignments.length === 0) {
            listEl.innerHTML = `
                <div class="col-span-full px-3 py-2.5 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-center text-xs text-slate-400">
                    まだスタッフが配置されていません。下の一覧から選択してアサインしてください。
                </div>
            `;
            return;
        }

        listEl.innerHTML = assignments.map(a => `
            <div class="px-2.5 py-1.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between text-xs hover:border-slate-300 transition">
                <div class="min-w-0 pr-1 flex items-center space-x-1.5">
                    <span class="font-bold text-slate-900 truncate">${escapeHtml(a.staff_name)}</span>
                    <span class="px-1.5 py-0.2 rounded text-[9px] font-medium bg-white text-slate-600 border border-slate-200 shrink-0">
                        ${a.is_lane_lead ? '👑 班長' : escapeHtml(a.role_description)}
                    </span>
                    ${a.affiliation ? `<span class="text-[10px] text-slate-400 truncate hidden sm:inline">(${escapeHtml(a.affiliation)})</span>` : ''}
                </div>
                ${a.is_lane_lead ? `
                    <span class="px-1.5 py-0.5 text-slate-400 bg-slate-200/60 rounded text-[11px] font-medium shrink-0 cursor-not-allowed select-none" title="班長は解除できません">
                        解除不可
                    </span>
                ` : `
                    <button type="button" data-action="remove-staff" data-staff-id="${a.staff_id}" data-staff-name="${escapeHtml(a.staff_name)}"
                        class="px-1.5 py-0.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded text-[11px] font-bold transition cursor-pointer shrink-0"
                        title="配置を解除">
                        解除
                    </button>
                `}
            </div>
        `).join('');

        listEl.querySelectorAll('[data-action="remove-staff"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const sId = parseInt(e.currentTarget.getAttribute('data-staff-id'), 10);
                const sName = e.currentTarget.getAttribute('data-staff-name');
                handleRemoveStaff(sId, sName);
            });
        });
    };

    const getFilteredCandidates = () => {
        if (!taskData) return [];
        const candidates = taskData.candidate_staff || [];
        const q = staffSearchQuery.toLowerCase();

        return candidates.filter(c => {
            const matchQ = !q
                || c.name.toLowerCase().includes(q)
                || (c.kana && c.kana.toLowerCase().includes(q))
                || (c.affiliation && c.affiliation.toLowerCase().includes(q));

            const matchAff = !staffAffiliation || (c.affiliation || '') === staffAffiliation;

            let matchTab = true;
            if (staffFilterTab === 'recommended') {
                matchTab = c.priority_tier === 1 && !c.is_already_assigned;
            } else if (staffFilterTab === 'available') {
                matchTab = !c.is_conflicted && !c.is_already_assigned;
            } else if (staffFilterTab === 'checked_in') {
                matchTab = c.is_checked_in && !c.is_already_assigned;
            }

            return matchQ && matchAff && matchTab;
        });
    };

    const renderCandidateStaff = () => {
        const candidates = taskData ? taskData.candidate_staff || [] : [];
        const totalLabel = document.getElementById('candidates-total-label');
        if (totalLabel) totalLabel.textContent = `（全 ${candidates.length} 名）`;

        const listEl = document.getElementById('candidate-staff-list');
        if (!listEl) return;

        const filtered = getFilteredCandidates();

        if (filtered.length === 0) {
            listEl.innerHTML = `
                <div class="py-12 text-center text-slate-400 text-xs">
                    条件に一致する候補スタッフがいません。
                </div>
            `;
            return;
        }

        listEl.innerHTML = filtered.map(c => {
            const isSelected = selectedStaffIds.includes(c.id);
            const isAssigned = c.is_already_assigned;

            // バッジの構築
            let badgeHtml = '';
            if (c.is_lane_lead) {
                badgeHtml += `<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">👑 班長</span> `;
            }
            if (c.is_same_lane && !c.is_conflicted) {
                badgeHtml += `<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">🌟 推奨</span> `;
            } else if (!c.is_conflicted) {
                badgeHtml += `<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">🟢 空き</span> `;
            }
            if (c.is_conflicted) {
                badgeHtml += `<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="${escapeHtml(c.conflicted_label)}">⚠️ 重複中</span> `;
            }
            if (c.is_checked_in) {
                badgeHtml += `<span class="px-1.5 py-0.2 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">📍 受付済</span> `;
            }

            const hoursToday = c.minutes_today ? (c.minutes_today / 60).toFixed(1) : '0';

            return `
                <label data-candidate-card data-staff-id="${c.id}"
                    class="flex items-center justify-between p-2 rounded-xl border text-xs transition cursor-pointer select-none ${isAssigned ? 'bg-slate-50/60 border-slate-200 opacity-60 cursor-not-allowed' : (isSelected ? 'bg-indigo-50/60 border-indigo-500 ring-1 ring-indigo-500' : 'bg-white border-slate-200 hover:border-indigo-200 hover:bg-slate-50/70')}">
                    <div class="flex items-center space-x-2.5 min-w-0 flex-1">
                        <input type="checkbox" data-staff-checkbox value="${c.id}" ${isSelected ? 'checked' : ''} ${isAssigned ? 'disabled' : ''}
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0 cursor-pointer">

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center space-x-1.5">
                                <span class="font-bold text-slate-900 truncate">${escapeHtml(c.name)}</span>
                                ${c.kana ? `<span class="text-[10px] text-slate-400 truncate hidden sm:inline">(${escapeHtml(c.kana)})</span>` : ''}
                            </div>
                            <div class="text-[10px] text-slate-500 flex items-center space-x-2 mt-0.5">
                                <span class="truncate">${escapeHtml(c.affiliation || '所属なし')}</span>
                                <span>・</span>
                                <span>本日稼働: <strong class="font-mono text-slate-700">${hoursToday}h</strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-1.5 shrink-0 pl-2">
                        ${badgeHtml}
                        ${c.is_conflicted ? `<span class="text-[10px] text-rose-500 hidden md:inline truncate max-w-[100px]" title="${escapeHtml(c.conflicted_label)}">${escapeHtml(c.conflicted_task_title)}</span>` : ''}
                    </div>
                </label>
            `;
        }).join('');

        // チェックボックスイベント
        listEl.querySelectorAll('[data-staff-checkbox]').forEach(cb => {
            cb.addEventListener('change', (e) => {
                const sId = parseInt(e.target.value, 10);
                if (e.target.checked) {
                    if (!selectedStaffIds.includes(sId)) selectedStaffIds.push(sId);
                } else {
                    selectedStaffIds = selectedStaffIds.filter(id => id !== sId);
                }
                renderCandidateStaff();
                updateSelectionCounters();
            });
        });
    };

    const updateSelectionCounters = () => {
        const count = selectedStaffIds.length;
        const countEl = document.getElementById('assign-selected-count');
        const btnCountEl = document.getElementById('assign-btn-count');
        const submitBtn = document.getElementById('btn-submit-bulk-assign');
        const clearBtn = document.getElementById('btn-clear-selection');

        if (countEl) countEl.textContent = count;
        if (btnCountEl) btnCountEl.textContent = count;

        if (clearBtn) {
            clearBtn.classList.toggle('hidden', count === 0);
        }

        if (submitBtn) {
            if (count > 0) {
                submitBtn.disabled = false;
                submitBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5 bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer shrink-0';
            } else {
                submitBtn.disabled = true;
                submitBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5 bg-slate-200 text-slate-400 cursor-not-allowed shrink-0';
            }
        }
    };

    // 候補スタッフタブ
    const staffTabs = document.querySelectorAll('[data-staff-tab]');
    staffTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            staffTabs.forEach(t => {
                t.classList.remove('bg-slate-100', 'text-slate-900');
                t.classList.add('text-slate-600');
            });
            tab.classList.remove('text-slate-600');
            tab.classList.add('bg-slate-100', 'text-slate-900');

            staffFilterTab = tab.getAttribute('data-staff-tab');
            renderCandidateStaff();
        });
    });

    // 候補スタッフ検索 & 所属
    const staffSearchInput = document.getElementById('staff-search-input');
    if (staffSearchInput) {
        staffSearchInput.addEventListener('input', (e) => {
            staffSearchQuery = e.target.value.trim();
            renderCandidateStaff();
        });
    }

    const staffAffSelect = document.getElementById('staff-affiliation-filter');
    if (staffAffSelect) {
        staffAffSelect.addEventListener('change', (e) => {
            staffAffiliation = e.target.value;
            renderCandidateStaff();
        });
    }

    // アシスト操作: 不足分を自動選択
    const btnShortageAuto = document.getElementById('btn-select-shortage-auto');
    if (btnShortageAuto) {
        btnShortageAuto.addEventListener('click', () => {
            if (!taskData) return;
            const shortage = taskData.task.shortage_count || 0;
            if (shortage <= 0) {
                showAlert('このタスクはすでに人員が充足しています。', 'success');
                return;
            }

            // 推奨順・空きスタッフから不足人数分を抽出
            const available = (taskData.candidate_staff || []).filter(c => !c.is_already_assigned && !c.is_conflicted);
            const toSelect = available.slice(0, shortage).map(c => c.id);

            if (toSelect.length === 0) {
                showAlert('現在空いているスタッフがいません。他班のスタッフや重複スタッフを個別に選択してください。', 'warning');
                return;
            }

            selectedStaffIds = toSelect;
            renderCandidateStaff();
            updateSelectionCounters();
            showAlert(`空きスタッフから ${toSelect.length} 名を自動選択しました。`, 'success');
        });
    }

    // アシスト操作: 表示中を全選択
    const btnSelectAll = document.getElementById('btn-select-all-filtered');
    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', () => {
            const filtered = getFilteredCandidates().filter(c => !c.is_already_assigned);
            filtered.forEach(c => {
                if (!selectedStaffIds.includes(c.id)) selectedStaffIds.push(c.id);
            });
            renderCandidateStaff();
            updateSelectionCounters();
        });
    }

    // アシスト操作: 選択解除
    const btnClear = document.getElementById('btn-clear-selection');
    if (btnClear) {
        btnClear.addEventListener('click', () => {
            selectedStaffIds = [];
            renderCandidateStaff();
            updateSelectionCounters();
        });
    }

    // =========================================================================
    // 3. アクション送信: 一括アサイン・解除・班プール自動配置
    // =========================================================================

    // 一括アサイン送信
    const submitBtn = document.getElementById('btn-submit-bulk-assign');
    if (submitBtn) {
        submitBtn.addEventListener('click', () => submitBulkAssign(false));
    }

    const submitBulkAssign = async (isForced = false) => {
        if (!currentTaskId || selectedStaffIds.length === 0) return;

        const roleDesc = document.getElementById('assign-role-description')?.value || '一般担当';
        const payload = {
            staff_ids: selectedStaffIds,
            role_description: roleDesc,
            force: isForced ? 1 : 0
        };

        try {
            submitBtn.disabled = true;
            submitBtn.textContent = 'アサイン処理中...';

            const res = await fetch(`/timetable-tasks/${currentTaskId}/assign-bulk`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (res.status === 409 && data.has_conflict) {
                // 重複検知
                const conflictNames = (data.conflicts || []).map(c => c.staff_name).join(', ');
                const alertArea = document.getElementById('assign-alert-area');
                if (alertArea) {
                    alertArea.innerHTML = `
                        <div class="p-3 bg-amber-50 border border-amber-300 rounded-xl text-amber-900 text-xs space-y-2">
                            <p class="font-bold flex items-center space-x-1">
                                <span>⚠️</span>
                                <span>時間帯が重複しているスタッフが含まれています</span>
                            </p>
                            <p class="text-[11px]">${escapeHtml(data.message)}</p>
                            <div class="flex items-center space-x-2 pt-1">
                                <button type="button" id="btn-force-assign-confirm"
                                    class="px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold text-xs shadow-xs transition cursor-pointer">
                                    重複を承認してアサインする
                                </button>
                                <button type="button" id="btn-force-assign-cancel"
                                    class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-medium transition cursor-pointer">
                                    キャンセル
                                </button>
                            </div>
                        </div>
                    `;
                    alertArea.classList.remove('hidden');

                    document.getElementById('btn-force-assign-confirm')?.addEventListener('click', () => {
                        submitBulkAssign(true);
                    });
                    document.getElementById('btn-force-assign-cancel')?.addEventListener('click', () => {
                        clearAlert();
                    });
                }
                updateSelectionCounters();
                return;
            }

            if (!res.ok) {
                throw new Error(data.message || 'アサイン処理に失敗しました');
            }

            // 成功
            showAlert(data.message || 'スタッフをアサインしました。', 'success');
            selectedStaffIds = [];

            // データを再取得して右ペインと左ペインを更新
            await fetchTaskData(currentTaskId);
            syncLeftTaskCard(currentTaskId, taskData.task, taskData.assignments);
            updateSummaryCounts();

        } catch (err) {
            showAlert(err.message || '通信エラーが発生しました', 'error');
        } finally {
            updateSelectionCounters();
        }
    };

    // スタッフ解除処理
    const handleRemoveStaff = async (staffId, staffName) => {
        if (!confirm(`スタッフ「${staffName}」の配置を解除しますか？`)) return;

        try {
            const res = await fetch(`/timetable-tasks/${currentTaskId}/staff/${staffId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.message || '解除に失敗しました');

            showAlert(data.message || '配置を解除しました。', 'success');

            // 再描画と左ペインの同期
            await fetchTaskData(currentTaskId);
            syncLeftTaskCard(currentTaskId, taskData.task, taskData.assignments);
            updateSummaryCounts();

        } catch (err) {
            showAlert(err.message || '解除に失敗しました', 'error');
        }
    };

    // 班プール自動配置
    const handleAssignLanePool = async () => {
        if (!currentTaskId) return;

        try {
            const res = await fetch(`/timetable-tasks/${currentTaskId}/assign-lane-pool`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.message || '自動配置に失敗しました');

            showAlert(data.message || '班メンバーを自動配置しました。', 'success');

            // 再描画と左ペインの同期
            await fetchTaskData(currentTaskId);
            syncLeftTaskCard(currentTaskId, taskData.task, taskData.assignments);
            updateSummaryCounts();

        } catch (err) {
            showAlert(err.message || '自動配置処理に失敗しました', 'error');
        }
    };

    // =========================================================================
    // 4. 左ペインのリアルタイム同期 & サマリー計算
    // =========================================================================

    const syncLeftTaskCard = (taskId, task, assignments) => {
        const card = document.querySelector(`[data-task-card][data-task-id="${taskId}"]`);
        if (!card) return;

        const isSatisfied = !!task.is_satisfied;
        const assignedCount = task.assigned_count;
        const shortageCount = task.shortage_count;
        const requiredCount = task.required_people_count;
        const percent = requiredCount > 0 ? Math.min(100, Math.round((assignedCount / requiredCount) * 100)) : 100;

        card.setAttribute('data-is-satisfied', isSatisfied ? '1' : '0');
        card.setAttribute('data-shortage-count', shortageCount);

        // バッジ更新
        const badge = card.querySelector('[data-task-status-badge]');
        if (badge) {
            badge.className = `inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold font-mono border ${isSatisfied ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-300'}`;
            badge.textContent = isSatisfied ? `✔ 充足 (${assignedCount})` : `⚠️ 不足 ${shortageCount}名`;
        }

        // プログレスバー更新
        const bar = card.querySelector('[data-task-progress-bar]');
        if (bar) {
            bar.style.width = `${percent}%`;
            bar.className = `${isSatisfied ? 'bg-emerald-500' : (assignedCount > 0 ? 'bg-amber-500' : 'bg-slate-300')} h-full transition-all duration-300`;
        }

        // 人数ラベル
        const peopleLabel = card.querySelector('[data-task-people-label]');
        if (peopleLabel) {
            peopleLabel.textContent = `👥 ${assignedCount}/${task.required_people_label}`;
        }

        // プレビューチップ
        const preview = card.querySelector('[data-task-assignments-preview]');
        if (preview) {
            preview.innerHTML = assignments.map(a => `
                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] bg-slate-100 text-slate-700 border border-slate-200 truncate max-w-[90px]"
                    title="${escapeHtml(a.staff_name)} (${escapeHtml(a.role_description || '担当')})">
                    ${escapeHtml(a.staff_name)}
                </span>
            `).join('');
        }

        // フィルター状態を維持しながら再評価
        filterTasks();
    };

    const updateSummaryCounts = () => {
        const cards = document.querySelectorAll('[data-task-card]');
        let total = cards.length;
        let satisfied = 0;
        let shortage = 0;

        cards.forEach(c => {
            if (c.getAttribute('data-is-satisfied') === '1') {
                satisfied++;
            } else {
                shortage++;
            }
        });

        const tabShortage = document.getElementById('tab-shortage-count');
        const tabAll = document.getElementById('tab-all-count');
        const tabSatisfied = document.getElementById('tab-satisfied-count');
        const sumShortage = document.getElementById('summary-shortage-tasks');
        const sumSatisfied = document.getElementById('summary-satisfied-tasks');

        if (tabShortage) tabShortage.textContent = shortage;
        if (tabAll) tabAll.textContent = total;
        if (tabSatisfied) tabSatisfied.textContent = satisfied;

        if (sumShortage) {
            sumShortage.textContent = `${shortage} 件`;
            sumShortage.className = `font-bold ${shortage > 0 ? 'text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded' : 'text-slate-600'} font-mono`;
        }
        if (sumSatisfied) sumSatisfied.textContent = `${satisfied} 件`;
    };

    // 初期タスクの選択
    if (currentTaskId) {
        selectTask(currentTaskId);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}

