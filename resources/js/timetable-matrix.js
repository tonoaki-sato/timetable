/**
 * タイムテーブル（マトリクス進行表）対話スクリプト
 */
document.addEventListener('DOMContentLoaded', () => {
    initLaneModal();
    initGridScroll();
    initLaneFilters();
    initLiveClock();
    initCurrentTimeIndicator();
    initQuickAssignModal();
});

/**
 * ヘッダーの現在時刻を毎秒リアルタイム更新
 */
function initLiveClock() {
    const clockEl = document.getElementById('matrix-live-clock');
    if (!clockEl) return;

    function update() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        clockEl.textContent = `${hours}:${minutes}:${seconds}`;
    }

    update();
    setInterval(update, 1000);
}

/**
 * タイムテーブル上の現在時刻赤ボーダーラインをリアルタイムに計算・移動
 */
function initCurrentTimeIndicator() {
    const container = document.getElementById('timetable-grid-container');
    const lineEl = document.getElementById('matrix-current-time-line');
    const timeLabel = document.getElementById('matrix-line-time-label');
    if (!container || !lineEl) return;

    function updatePosition() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const todayStr = `${year}-${month}-${day}`;

        const targetDate = container.getAttribute('data-target-date');
        // 日程が指定されており、当日でない場合は非表示（targetDate未設定時は表示許容）
        if (targetDate && targetDate !== todayStr) {
            lineEl.classList.add('hidden');
            return;
        }

        const currentHours = now.getHours();
        const currentMinutes = now.getMinutes();
        const currentSeconds = now.getSeconds();
        const currentTimeStr = `${String(currentHours).padStart(2, '0')}:${String(currentMinutes).padStart(2, '0')}`;

        // 30分スロットの特定
        const slotMinute = currentMinutes >= 30 ? 30 : 0;
        const currentSlotTime = `${String(currentHours).padStart(2, '0')}:${String(slotMinute).padStart(2, '0')}`;

        // 次のスロット
        let nextSlotHours = currentHours;
        let nextSlotMinute = slotMinute === 30 ? 0 : 30;
        if (slotMinute === 30) {
            nextSlotHours++;
        }
        const nextSlotTime = `${String(nextSlotHours).padStart(2, '0')}:${String(nextSlotMinute).padStart(2, '0')}`;

        const currentRow = container.querySelector(`tr[data-slot-time="${currentSlotTime}"]`);
        if (!currentRow) {
            // 現在時刻がスロット範囲外（早朝または夜間終了後）
            lineEl.classList.add('hidden');
            return;
        }

        // 次のスロット行（または行の高さから推定）
        const nextRow = container.querySelector(`tr[data-slot-time="${nextSlotTime}"]`);
        const currentTop = currentRow.offsetTop;
        const slotHeight = nextRow ? (nextRow.offsetTop - currentTop) : currentRow.offsetHeight;

        // 30分（1800秒）に対する経過割合
        const elapsedSeconds = ((currentMinutes % 30) * 60) + currentSeconds;
        const progress = elapsedSeconds / 1800;

        const calculatedTop = currentTop + (slotHeight * progress);

        lineEl.style.top = `${calculatedTop}px`;
        if (timeLabel) {
            timeLabel.textContent = currentTimeStr;
        }
        lineEl.classList.remove('hidden');
    }

    updatePosition();
    setInterval(updatePosition, 1000);
    window.addEventListener('resize', updatePosition);
}

function initLaneModal() {
    const modal = document.getElementById('laneModal');
    if (!modal) return;

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="open-lane-modal"]')) {
            modal.classList.remove('hidden');
        } else if (e.target.closest('[data-action="close-lane-modal"]') || e.target === modal) {
            modal.classList.add('hidden');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
        }
    });
}

function initGridScroll() {
    const container = document.getElementById('timetable-grid-container');
    if (!container) return;

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-scroll-grid]');
        if (!btn) return;
        const amount = parseInt(btn.getAttribute('data-scroll-grid'), 10);
        if (!isNaN(amount)) {
            container.scrollBy({ left: amount, behavior: 'smooth' });
        }
    });
}

function initLaneFilters() {
    const filterContainer = document.getElementById('lane-filter-chips');
    if (!filterContainer) return;

    // チップクリック時のトグル
    filterContainer.addEventListener('click', (e) => {
        const chip = e.target.closest('.lane-chip');
        if (!chip) return;
        const laneId = chip.getAttribute('data-lane-id');
        if (laneId) {
            toggleLaneColumn(laneId);
        }
    });

    // プリセットボタン
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-lane-preset]');
        if (!btn) return;
        const preset = btn.getAttribute('data-lane-preset');
        if (preset) {
            filterLanesPreset(preset);
        }
    });
}

function toggleLaneColumn(laneId) {
    const chip = document.getElementById('chip-lane-' + laneId);
    const elements = document.querySelectorAll('.lane-col-' + laneId);
    if (!chip || elements.length === 0) return;

    const isCurrentlyVisible = !elements[0].classList.contains('hidden');

    if (isCurrentlyVisible) {
        elements.forEach(el => el.classList.add('hidden'));
        chip.classList.add('opacity-40', 'bg-slate-100');
        chip.classList.remove('border-indigo-400');
        const check = chip.querySelector('span:first-child');
        if (check) check.textContent = '✕';
    } else {
        elements.forEach(el => el.classList.remove('hidden'));
        chip.classList.remove('opacity-40', 'bg-slate-100');
        chip.classList.add('border-indigo-400');
        const check = chip.querySelector('span:first-child');
        if (check) check.textContent = '✔';
    }

    updateVisibleCount();
    resetPresetButtons();
}

function filterLanesPreset(preset) {
    const chips = document.querySelectorAll('.lane-chip');

    chips.forEach(chip => {
        const laneId = chip.getAttribute('data-lane-id');
        const isCore = chip.getAttribute('data-is-core') === '1';
        const leadId = chip.getAttribute('data-lead-id');
        const elements = document.querySelectorAll('.lane-col-' + laneId);

        let shouldShow = false;

        if (preset === 'all') {
            shouldShow = true;
        } else if (preset === 'core') {
            shouldShow = isCore;
        } else if (preset === 'mine') {
            shouldShow = leadId !== '';
        }

        if (shouldShow) {
            elements.forEach(el => el.classList.remove('hidden'));
            chip.classList.remove('opacity-40', 'bg-slate-100');
            const check = chip.querySelector('span:first-child');
            if (check) check.textContent = '✔';
        } else {
            elements.forEach(el => el.classList.add('hidden'));
            chip.classList.add('opacity-40', 'bg-slate-100');
            const check = chip.querySelector('span:first-child');
            if (check) check.textContent = '✕';
        }
    });

    // プリセットボタンスタイル更新
    document.querySelectorAll('[data-lane-preset]').forEach(btn => {
        btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600');
        btn.classList.add('bg-white', 'text-slate-700', 'border-slate-300');
    });
    const activeBtn = document.querySelector(`[data-lane-preset="${preset}"]`);
    if (activeBtn) {
        activeBtn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
        activeBtn.classList.remove('bg-white', 'text-slate-700', 'border-slate-300');
    }

    updateVisibleCount();
}

function resetPresetButtons() {
    document.querySelectorAll('[data-lane-preset]').forEach(btn => {
        btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600');
        btn.classList.add('bg-white', 'text-slate-700', 'border-slate-300');
    });
}

function updateVisibleCount() {
    const total = document.querySelectorAll('.lane-chip').length;
    const hidden = document.querySelectorAll('.lane-chip.opacity-40').length;
    const visible = total - hidden;
    const indicator = document.getElementById('lane-count-indicator');
    if (indicator) {
        indicator.textContent = `全 ${total} 班中 ${visible} 班表示中`;
    }
}

/**
 * XSSサニタイズ用エスケープ
 */
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * クイックアサイン スライドオーバーモーダル (CSP完全準拠)
 */
function initQuickAssignModal() {
    const modal = document.getElementById('quickAssignModal');
    if (!modal) return;

    let currentTaskId = null;
    let taskData = null;
    let selectedStaffIds = [];
    let searchQuery = '';
    let statusFilter = 'all';

    const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // モーダルを開く
    const openModal = async (taskId) => {
        currentTaskId = taskId;
        selectedStaffIds = [];
        searchQuery = '';
        statusFilter = 'all';
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        renderLoading();
        await fetchAndRenderData();
    };

    // モーダルを閉じる
    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        currentTaskId = null;
        taskData = null;
    };

    // データの取得と描画
    const fetchAndRenderData = async () => {
        try {
            const res = await fetch(`/timetable-tasks/${currentTaskId}/assign-modal-data`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error('タスク情報の取得に失敗しました');
            taskData = await res.json();
            renderContent();
        } catch (err) {
            renderError(err.message || 'データ読み込みに失敗しました');
        }
    };

    const renderLoading = () => {
        const body = document.getElementById('quickAssignModalBody');
        if (body) {
            body.innerHTML = `
                <div class="py-16 text-center text-slate-400 text-xs">
                    <span class="inline-block animate-spin text-2xl mb-2">⏳</span>
                    <p>データを読み込み中...</p>
                </div>
            `;
        }
    };

    const renderError = (msg) => {
        const body = document.getElementById('quickAssignModalBody');
        if (body) {
            body.innerHTML = `
                <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs">
                    <p class="font-bold mb-1">エラーが発生しました</p>
                    <p>${escapeHtml(msg)}</p>
                    <button type="button" data-action="retry-quick-assign" class="mt-3 px-3 py-1 bg-white border border-red-300 rounded text-xs text-red-700 font-bold hover:bg-red-50 cursor-pointer">再試行</button>
                </div>
            `;
        }
    };

    const renderContent = () => {
        if (!taskData) return;
        const task = taskData.task;
        const assignments = taskData.assignments || [];

        // ヘッダー情報
        const headerTitle = document.getElementById('quickAssignTaskTitle');
        const headerTime = document.getElementById('quickAssignTaskTime');
        const headerLane = document.getElementById('quickAssignTaskLane');
        const detailLink = document.getElementById('quickAssignDetailLink');

        if (headerTitle) headerTitle.textContent = task.title;
        if (headerTime) headerTime.textContent = `${task.start_time} 〜 ${task.end_time}`;
        if (headerLane) headerLane.textContent = task.lane || '班未設定';
        if (detailLink) detailLink.href = `/timetable-tasks/${task.id}`;

        const body = document.getElementById('quickAssignModalBody');
        if (!body) return;

        // 充足ステータス
        const satisfiedBadge = task.is_satisfied
            ? `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">✔ 充足</span>`
            : `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">⚠️ 不足 ${task.shortage_count}名</span>`;

        const lanePoolBtn = (!task.is_satisfied && task.lane)
            ? `<button type="button" data-action="assign-lane-pool" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 rounded-lg text-xs font-bold transition flex items-center space-x-1 cursor-pointer">
                <span>⚡</span>
                <span>「${escapeHtml(task.lane)}」から自動配置</span>
               </button>`
            : '';

        // 配置済みスタッフ一覧
        let assignedListHtml = '';
        if (assignments.length === 0) {
            assignedListHtml = `<div class="p-4 bg-slate-50 rounded-lg border border-dashed border-slate-200 text-center text-xs text-slate-400">スタッフはまだ配置されていません。</div>`;
        } else {
            assignedListHtml = assignments.map(a => `
                <div class="p-2 bg-white rounded-lg border border-slate-200 flex items-center justify-between text-xs hover:border-slate-300 transition">
                    <div class="min-w-0 pr-2">
                        <div class="flex items-center space-x-1.5">
                            <span class="font-bold text-slate-900">${escapeHtml(a.staff_name)}</span>
                            ${a.staff_kana ? `<span class="text-[10px] text-slate-400 font-normal">(${escapeHtml(a.staff_kana)})</span>` : ''}
                            <span class="px-1.5 py-0.2 rounded text-[9px] bg-slate-100 text-slate-600 border border-slate-200">${escapeHtml(a.role_description)}</span>
                        </div>
                        <div class="text-[10px] text-slate-500">${escapeHtml(a.affiliation || '所属なし')}</div>
                    </div>
                    <button type="button" data-action="remove-staff" data-staff-id="${a.staff_id}" class="px-2 py-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded text-xs transition cursor-pointer shrink-0">
                        解除 &times;
                    </button>
                </div>
            `).join('');
        }

        body.innerHTML = `
            <div id="quickAssignAlertArea" class="hidden"></div>

            <!-- 人員充足ステータス & 班自動配置 -->
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <span class="text-[10px] text-slate-500 uppercase tracking-wider font-bold block">必要人員 & 充足状況</span>
                    <div class="flex items-center space-x-2 mt-0.5">
                        <span class="font-mono text-base font-bold ${task.is_satisfied ? 'text-emerald-600' : 'text-amber-600'}">
                            ${task.assigned_count} / ${escapeHtml(task.required_people_label)}
                        </span>
                        ${satisfiedBadge}
                    </div>
                </div>
                ${lanePoolBtn}
            </div>

            <!-- 現在配置中のスタッフ -->
            <div>
                <h4 class="text-xs font-bold text-slate-700 mb-2 flex items-center justify-between">
                    <span>👥 現在配置中のスタッフ (${assignments.length}名)</span>
                </h4>
                <div class="space-y-1.5">${assignedListHtml}</div>
            </div>

            <!-- 候補スタッフから追加 -->
            <div class="border-t border-slate-200 pt-3 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-slate-800">➕ スタッフを追加配置する</h4>
                    <span class="text-[11px] text-slate-500">選択: <strong id="quickAssignSelectedCount" class="text-indigo-600 font-mono">${selectedStaffIds.length}</strong> 名</span>
                </div>

                <!-- フィルター -->
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" id="quickAssignSearchInput" value="${escapeHtml(searchQuery)}" placeholder="🔍 氏名・所属で検索..."
                        class="px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <select id="quickAssignStatusSelect"
                        class="px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="all" ${statusFilter === 'all' ? 'selected' : ''}>全員表示</option>
                        <option value="recommended" ${statusFilter === 'recommended' ? 'selected' : ''}>🌟 推奨（同班＆空き）</option>
                        <option value="available" ${statusFilter === 'available' ? 'selected' : ''}>🟢 空きのみ</option>
                    </select>
                </div>

                <!-- 候補リスト -->
                <div id="quickAssignCandidateListContainer" class="max-h-56 overflow-y-auto border border-slate-200 rounded-lg divide-y divide-slate-100 bg-white">
                </div>

                <!-- 担当役割 & 配置実行ボタン -->
                <div class="flex items-center space-x-2 pt-1">
                    <input type="text" id="quickAssignRoleInput" placeholder="担当役割（任意）"
                        class="flex-1 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <button type="button" id="quickAssignSubmitBtn" data-action="submit-bulk-assign"
                        class="px-4 py-1.5 rounded-lg text-xs font-bold shadow-sm transition ${selectedStaffIds.length > 0 ? 'bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer' : 'bg-slate-200 text-slate-400 cursor-not-allowed'}"
                        ${selectedStaffIds.length === 0 ? 'disabled' : ''}>
                        配置する
                    </button>
                </div>
            </div>
        `;

        renderCandidateList();
        bindInputEvents();
    };

    const renderCandidateList = () => {
        const container = document.getElementById('quickAssignCandidateListContainer');
        if (!container || !taskData) return;

        const candidates = taskData.candidate_staff || [];
        const q = searchQuery.toLowerCase();

        const filtered = candidates.filter(c => {
            const matchQ = !q || c.name.toLowerCase().includes(q) || (c.kana && c.kana.toLowerCase().includes(q)) || (c.affiliation && c.affiliation.toLowerCase().includes(q));
            let matchStatus = true;
            if (statusFilter === 'available') matchStatus = !c.is_conflicted && !c.is_already_assigned;
            else if (statusFilter === 'recommended') matchStatus = c.priority_tier === 1 && !c.is_already_assigned;
            return matchQ && matchStatus;
        });

        if (filtered.length === 0) {
            container.innerHTML = `<div class="p-4 text-center text-xs text-slate-400">該当するスタッフがいません。</div>`;
            return;
        }

        container.innerHTML = filtered.map(c => {
            const isSelected = selectedStaffIds.includes(c.id);
            const isAssigned = c.is_already_assigned;
            const isConflicted = c.is_conflicted;

            let rowClass = 'p-2 flex items-center justify-between text-xs select-none transition ';
            if (isAssigned) {
                rowClass += 'opacity-50 bg-slate-50 cursor-not-allowed';
            } else if (isSelected) {
                rowClass += 'bg-indigo-50/70 border-l-4 border-indigo-600 cursor-pointer';
            } else if (isConflicted) {
                rowClass += 'bg-amber-50/50 hover:bg-slate-50 cursor-pointer';
            } else {
                rowClass += 'hover:bg-slate-50 cursor-pointer';
            }

            let badgeHtml = '';
            if (c.is_checked_in) {
                badgeHtml += `<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-sky-100 text-sky-800 border border-sky-300">着</span>`;
            }
            if (isConflicted) {
                badgeHtml += `<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-300">⚠️ ${escapeHtml(c.conflicted_label)}</span>`;
            } else if (c.is_same_lane) {
                badgeHtml += `<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800">🌟 推奨</span>`;
            } else {
                badgeHtml += `<span class="px-1.5 py-0.2 rounded text-[9px] text-slate-500">🟢 空き</span>`;
            }

            return `
                <div class="${rowClass}" data-action="toggle-candidate" data-staff-id="${c.id}" data-assigned="${isAssigned ? '1' : '0'}">
                    <div class="flex items-center space-x-2 min-w-0">
                        <input type="checkbox" value="${c.id}" ${isSelected ? 'checked' : ''} ${isAssigned ? 'disabled' : ''}
                            class="candidate-checkbox h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 cursor-pointer pointer-events-none">
                        <div class="min-w-0">
                            <div class="flex items-center space-x-1">
                                <span class="font-bold text-slate-900 truncate">${escapeHtml(c.name)}</span>
                                <span class="text-[10px] text-slate-500 truncate">(${escapeHtml(c.affiliation || '無所属')})</span>
                            </div>
                        </div>
                    </div>
                    <div class="shrink-0 flex items-center space-x-1">${badgeHtml}</div>
                </div>
            `;
        }).join('');
    };

    const bindInputEvents = () => {
        const searchInput = document.getElementById('quickAssignSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                searchQuery = e.target.value;
                renderCandidateList();
            });
        }

        const statusSelect = document.getElementById('quickAssignStatusSelect');
        if (statusSelect) {
            statusSelect.addEventListener('change', (e) => {
                statusFilter = e.target.value;
                renderCandidateList();
            });
        }
    };

    const showAlert = (msg, type = 'error') => {
        const alertArea = document.getElementById('quickAssignAlertArea');
        if (!alertArea) return;
        const bg = type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700';
        alertArea.className = `p-3 rounded-xl border text-xs mb-3 flex items-center justify-between ${bg}`;
        alertArea.innerHTML = `
            <span>${escapeHtml(msg)}</span>
            <button type="button" data-action="dismiss-alert" class="font-bold ml-2 text-sm leading-none cursor-pointer">&times;</button>
        `;
    };

    // イベント委譲（クリック）
    document.addEventListener('click', async (e) => {
        // 1. クイックアサインモーダルを開く
        const openBtn = e.target.closest('[data-action="open-quick-assign"]');
        if (openBtn) {
            const taskId = openBtn.getAttribute('data-task-id');
            if (taskId) {
                await openModal(taskId);
            }
            return;
        }

        // 2. モーダルを閉じる
        if (e.target.closest('[data-action="close-quick-assign"]')) {
            closeModal();
            return;
        }

        // 3. モーダル外側クリックで閉じる
        if (e.target === modal) {
            closeModal();
            return;
        }

        // 4. アラート閉じる
        if (e.target.closest('[data-action="dismiss-alert"]')) {
            const alertArea = document.getElementById('quickAssignAlertArea');
            if (alertArea) alertArea.classList.add('hidden');
            return;
        }

        // 5. 再試行ボタン
        if (e.target.closest('[data-action="retry-quick-assign"]')) {
            if (currentTaskId) {
                renderLoading();
                await fetchAndRenderData();
            }
            return;
        }

        // 6. 候補スタッフ行の選択トグル
        const candidateRow = e.target.closest('[data-action="toggle-candidate"]');
        if (candidateRow) {
            if (candidateRow.dataset.assigned === '1') return;
            const staffId = parseInt(candidateRow.dataset.staffId, 10);
            if (!staffId) return;

            if (selectedStaffIds.includes(staffId)) {
                selectedStaffIds = selectedStaffIds.filter(id => id !== staffId);
            } else {
                selectedStaffIds.push(staffId);
            }

            const countEl = document.getElementById('quickAssignSelectedCount');
            if (countEl) countEl.textContent = selectedStaffIds.length;

            const submitBtn = document.getElementById('quickAssignSubmitBtn');
            if (submitBtn) {
                if (selectedStaffIds.length > 0) {
                    submitBtn.disabled = false;
                    submitBtn.className = 'px-4 py-1.5 rounded-lg text-xs font-bold shadow-sm transition bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer';
                } else {
                    submitBtn.disabled = true;
                    submitBtn.className = 'px-4 py-1.5 rounded-lg text-xs font-bold shadow-sm transition bg-slate-200 text-slate-400 cursor-not-allowed';
                }
            }

            renderCandidateList();
            return;
        }

        // 7. 一括配置の実行
        if (e.target.closest('[data-action="submit-bulk-assign"]')) {
            if (selectedStaffIds.length === 0 || !currentTaskId) return;
            const roleInput = document.getElementById('quickAssignRoleInput');
            const roleDescription = roleInput ? roleInput.value : '';

            try {
                const res = await fetch(`/timetable-tasks/${currentTaskId}/assign-bulk`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({
                        staff_ids: selectedStaffIds,
                        role_description: roleDescription,
                        force: 0
                    })
                });

                const data = await res.json();
                if (!res.ok) {
                    showAlert(data.message || '配置処理に失敗しました', 'error');
                    return;
                }

                selectedStaffIds = [];
                await fetchAndRenderData();
                showAlert(data.message, 'success');
            } catch (err) {
                showAlert(err.message || '通信エラーが発生しました', 'error');
            }
            return;
        }

        // 8. 班メンバー自動配置
        if (e.target.closest('[data-action="assign-lane-pool"]')) {
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
                if (!res.ok) {
                    showAlert(data.message || '自動配置に失敗しました', 'error');
                    return;
                }

                await fetchAndRenderData();
                showAlert(data.message, 'success');
            } catch (err) {
                showAlert(err.message || '通信エラーが発生しました', 'error');
            }
            return;
        }

        // 9. スタッフ解除
        const removeBtn = e.target.closest('[data-action="remove-staff"]');
        if (removeBtn) {
            const staffId = removeBtn.getAttribute('data-staff-id');
            if (!staffId || !currentTaskId) return;
            if (!confirm('このスタッフの割り当てを解除しますか？')) return;

            try {
                const res = await fetch(`/timetable-tasks/${currentTaskId}/staff/${staffId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    }
                });

                const data = await res.json();
                if (!res.ok) {
                    showAlert(data.message || '解除に失敗しました', 'error');
                    return;
                }

                await fetchAndRenderData();
                showAlert(data.message, 'success');
            } catch (err) {
                showAlert(err.message || '通信エラーが発生しました', 'error');
            }
            return;
        }

        // 10. 閉じて画面更新
        if (e.target.closest('[data-action="reload-matrix"]')) {
            closeModal();
            window.location.reload();
            return;
        }
    });

    // Escキーで閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}
