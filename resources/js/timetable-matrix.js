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
    let affiliationFilter = '';

    const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // モーダルを開く
    const openModal = async (taskId) => {
        currentTaskId = taskId;
        selectedStaffIds = [];
        searchQuery = '';
        statusFilter = 'all';
        affiliationFilter = '';
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
                <div class="p-4 m-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs">
                    <p class="font-bold mb-1">エラーが発生しました</p>
                    <p>${escapeHtml(msg)}</p>
                    <button type="button" data-action="retry-quick-assign" class="mt-3 px-3 py-1 bg-white border border-red-300 rounded text-xs text-red-700 font-bold hover:bg-red-50 cursor-pointer">再試行</button>
                </div>
            `;
        }
    };

    // 現在フィルタリングされている候補スタッフ一覧を取得
    const getFilteredCandidates = () => {
        if (!taskData) return [];
        const candidates = taskData.candidate_staff || [];
        const q = searchQuery.toLowerCase();

        return candidates.filter(c => {
            const matchQ = !q
                || c.name.toLowerCase().includes(q)
                || (c.kana && c.kana.toLowerCase().includes(q))
                || (c.affiliation && c.affiliation.toLowerCase().includes(q));
            const matchAff = !affiliationFilter || (c.affiliation || '') === affiliationFilter;
            let matchStatus = true;
            if (statusFilter === 'available') matchStatus = !c.is_conflicted && !c.is_already_assigned;
            else if (statusFilter === 'recommended') matchStatus = c.priority_tier === 1 && !c.is_already_assigned;
            else if (statusFilter === 'surplus') matchStatus = c.is_surplus_fixed && !c.is_already_assigned;
            else if (statusFilter === 'checked_in') matchStatus = c.is_checked_in && !c.is_already_assigned;

            return matchQ && matchAff && matchStatus;
        });
    };

    const updateSelectionCountersAndButtons = () => {
        const count = selectedStaffIds.length;
        const countEl = document.getElementById('quickAssignSelectedCount');
        const submitCountEl = document.getElementById('quickAssignSubmitCount');
        const clearBtn = document.getElementById('quickAssignClearBtn');
        const submitBtn = document.getElementById('quickAssignSubmitBtn');

        if (countEl) countEl.textContent = count;
        if (submitCountEl) submitCountEl.textContent = count;

        if (clearBtn) {
            if (count > 0) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }
        }

        if (submitBtn) {
            if (count > 0) {
                submitBtn.disabled = false;
                submitBtn.className = 'px-4 py-2 rounded-lg text-xs font-bold shadow-sm transition flex items-center space-x-1 bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer shrink-0';
            } else {
                submitBtn.disabled = true;
                submitBtn.className = 'px-4 py-2 rounded-lg text-xs font-bold shadow-sm transition flex items-center space-x-1 bg-slate-200 text-slate-400 cursor-not-allowed shrink-0';
            }
        }
    };

    const renderContent = () => {
        if (!taskData) return;
        const task = taskData.task;
        const assignments = taskData.assignments || [];
        const candidates = taskData.candidate_staff || [];

        // 所属団体リストの抽出
        const affiliations = [...new Set(candidates.map(c => c.affiliation).filter(Boolean))];

        // 1. ヘッダー情報と人員充足バッジ・自動配置ボタンの更新
        const headerTitle = document.getElementById('quickAssignTaskTitle');
        const headerTime = document.getElementById('quickAssignTaskTime');
        const headerLane = document.getElementById('quickAssignTaskLane');
        const headerLocation = document.getElementById('quickAssignTaskLocation');
        const headerStatus = document.getElementById('quickAssignHeaderStatus');
        const headerPoolBtn = document.getElementById('quickAssignHeaderPoolBtn');
        const detailLink = document.getElementById('quickAssignDetailLink');

        if (headerTitle) headerTitle.textContent = task.title;
        if (headerTime) headerTime.textContent = `⏱️ ${task.start_time} 〜 ${task.end_time}`;
        if (headerLane) headerLane.textContent = `🏷️ ${task.lane || '班未設定'}`;
        if (headerLocation) {
            if (task.location) {
                headerLocation.textContent = `📍 ${task.location}`;
                headerLocation.classList.remove('hidden');
            } else {
                headerLocation.classList.add('hidden');
            }
        }
        if (headerStatus) {
            headerStatus.innerHTML = task.is_satisfied
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/40 font-mono">✔ ${task.assigned_count}/${escapeHtml(task.required_people_label)} 充足</span>`
                : `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/25 text-amber-200 border border-amber-400/50 font-mono">⚠️ ${task.assigned_count}/${escapeHtml(task.required_people_label)} (不足${task.shortage_count}名)</span>`;
        }
        if (headerPoolBtn) {
            headerPoolBtn.innerHTML = (!task.is_satisfied && task.lane)
                ? `<button type="button" data-action="assign-lane-pool" title="この班の空きメンバーから不足分を自動配置" class="px-2.5 py-1 bg-amber-400 hover:bg-amber-300 text-slate-950 rounded-lg text-[11px] font-bold transition flex items-center space-x-1 cursor-pointer shadow-xs">
                    <span>⚡</span>
                    <span>「${escapeHtml(task.lane)}」自動配置</span>
                   </button>`
                : '';
        }
        if (detailLink) detailLink.href = `/timetable-tasks/${task.id}`;

        const body = document.getElementById('quickAssignModalBody');
        if (!body) return;

        // 2. 現在配置中のスタッフ一覧（2列スリムグリッド）
        let assignedListHtml = '';
        if (assignments.length === 0) {
            assignedListHtml = `<div class="px-3 py-2 bg-slate-50 rounded-lg border border-dashed border-slate-200 text-center text-[11px] text-slate-400">まだスタッフが配置されていません。下の一覧から選択して配置してください。</div>`;
        } else {
            assignedListHtml = `<div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-28 overflow-y-auto pr-0.5">` + assignments.map(a => `
                <div class="px-2.5 py-1.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between text-xs hover:border-slate-300 transition">
                    <div class="min-w-0 pr-1.5 flex items-center space-x-1.5">
                        <span class="font-bold text-slate-900 truncate">${escapeHtml(a.staff_name)}</span>
                        <span class="px-1.5 py-0.2 rounded text-[9px] bg-white text-slate-600 border border-slate-200 shrink-0">${escapeHtml(a.role_description)}</span>
                        ${a.affiliation ? `<span class="text-[10px] text-slate-400 truncate hidden sm:inline">(${escapeHtml(a.affiliation)})</span>` : ''}
                    </div>
                    <button type="button" data-action="remove-staff" data-staff-id="${a.staff_id}" title="割り当て解除" class="px-1.5 py-0.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded text-[11px] font-bold transition cursor-pointer shrink-0">
                        解除
                    </button>
                </div>
            `).join('') + `</div>`;
        }

        const affiliationOptionsHtml = affiliations.map(aff =>
            `<option value="${escapeHtml(aff)}" ${affiliationFilter === aff ? 'selected' : ''}>${escapeHtml(aff)}</option>`
        ).join('');

        body.innerHTML = `
            <!-- 上部固定領域: アラート & 現在配置中のスタッフ -->
            <div class="px-4 pt-3 pb-2.5 border-b border-slate-200 space-y-2.5 shrink-0 bg-white">
                <div id="quickAssignAlertArea" class="hidden"></div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <h4 class="text-xs font-bold text-slate-700 flex items-center space-x-1">
                            <span>👥</span>
                            <span>現在配置中のスタッフ (${assignments.length}名)</span>
                        </h4>
                    </div>
                    ${assignedListHtml}
                </div>
            </div>

            <!-- 中央ヘッダー & フィルターバー (固定) -->
            <div class="px-4 pt-2.5 pb-2 bg-slate-50/80 border-b border-slate-200 space-y-2 shrink-0">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h4 class="text-xs font-bold text-slate-800 flex items-center space-x-1">
                        <span>➕</span>
                        <span>スタッフを追加配置する</span>
                        <span class="text-[10px] font-normal text-slate-400">（全 ${candidates.length} 名）</span>
                    </h4>
                    <div class="flex items-center space-x-1.5">
                        <span class="text-[11px] text-slate-600 font-medium">
                            選択中: <strong id="quickAssignSelectedCount" class="text-indigo-600 font-mono text-xs">${selectedStaffIds.length}</strong> 名
                        </span>
                        ${(!task.is_satisfied && task.shortage_count > 0) ? `
                        <button type="button" data-action="select-shortage-auto"
                            class="px-2 py-0.5 bg-amber-100 hover:bg-amber-200 text-amber-900 border border-amber-300 rounded text-[11px] font-bold transition cursor-pointer">
                            ⚡ 不足${task.shortage_count}名を自動選択
                        </button>` : ''}
                        <button type="button" data-action="select-all-filtered"
                            class="px-2 py-0.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded text-[11px] font-medium transition cursor-pointer">
                            表示中を全選択
                        </button>
                        <button type="button" id="quickAssignClearBtn" data-action="clear-selection"
                            class="${selectedStaffIds.length > 0 ? '' : 'hidden'} px-2 py-0.5 bg-white hover:bg-slate-100 text-slate-500 border border-slate-200 rounded text-[11px] font-medium transition cursor-pointer">
                            解除
                        </button>
                    </div>
                </div>

                <!-- 検索・状態・所属フィルター (3カラム) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5">
                    <input type="text" id="quickAssignSearchInput" value="${escapeHtml(searchQuery)}" placeholder="🔍 氏名・カナ・所属で検索..."
                        class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <select id="quickAssignStatusSelect"
                        class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="all" ${statusFilter === 'all' ? 'selected' : ''}>すべての状態</option>
                        <option value="recommended" ${statusFilter === 'recommended' ? 'selected' : ''}>🌟 推奨（同班＆空き）</option>
                        <option value="surplus" ${statusFilter === 'surplus' ? 'selected' : ''}>✨ 他班の余剰スタッフ（融通可）</option>
                        <option value="available" ${statusFilter === 'available' ? 'selected' : ''}>🟢 空きスタッフのみ</option>
                        <option value="checked_in" ${statusFilter === 'checked_in' ? 'selected' : ''}>📍 現場到着済のみ</option>
                    </select>
                    <select id="quickAssignAffiliationSelect"
                        class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">すべての所属団体</option>
                        ${affiliationOptionsHtml}
                    </select>
                </div>
            </div>

            <!-- 候補スタッフ一覧（独立スクロール領域・2列高密度グリッド） -->
            <div class="flex-1 min-h-0 overflow-y-auto bg-slate-50/50 p-3">
                <div id="quickAssignCandidateListContainer" class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                </div>
            </div>

            <!-- 下部常時表示アクションバー（担当役割 & 配置するボタンが隠れない固定枠） -->
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center gap-2 shrink-0 shadow-[0_-2px_10px_rgba(0,0,0,0.03)]">
                <input type="text" id="quickAssignRoleInput" placeholder="担当役割（任意、例: チーフ、案内、運搬）"
                    class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <button type="button" id="quickAssignSubmitBtn" data-action="submit-bulk-assign"
                    class="px-4 py-2 rounded-lg text-xs font-bold shadow-sm transition flex items-center space-x-1 shrink-0 ${selectedStaffIds.length > 0 ? 'bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer' : 'bg-slate-200 text-slate-400 cursor-not-allowed'}"
                    ${selectedStaffIds.length === 0 ? 'disabled' : ''}>
                    <span>➕</span>
                    <span>選択した <strong id="quickAssignSubmitCount">${selectedStaffIds.length}</strong> 名を配置する</span>
                </button>
            </div>
        `;

        renderCandidateList();
        bindInputEvents();
    };

    const renderCandidateList = () => {
        const container = document.getElementById('quickAssignCandidateListContainer');
        if (!container || !taskData) return;

        const filtered = getFilteredCandidates();

        if (filtered.length === 0) {
            container.innerHTML = `<div class="col-span-full p-6 text-center text-xs text-slate-400">該当するスタッフがいません。</div>`;
            return;
        }

        container.innerHTML = filtered.map(c => {
            const isSelected = selectedStaffIds.includes(c.id);
            const isAssigned = c.is_already_assigned;
            const isConflicted = c.is_conflicted;

            let cardClass = 'px-2.5 py-2 rounded-lg border text-xs select-none transition flex flex-col justify-between gap-1 ';
            if (isAssigned) {
                cardClass += 'opacity-55 bg-slate-100 border-slate-200 cursor-not-allowed';
            } else if (isSelected) {
                cardClass += 'bg-indigo-50/90 border-indigo-600 ring-1 ring-indigo-500 cursor-pointer';
            } else if (isConflicted) {
                cardClass += 'bg-amber-50/60 border-amber-200/80 hover:border-amber-300 cursor-pointer';
            } else if (c.is_surplus_fixed) {
                cardClass += 'bg-purple-50/60 border-purple-200 hover:border-purple-300 cursor-pointer';
            } else {
                cardClass += 'bg-white border-slate-200 hover:border-indigo-300 hover:shadow-2xs cursor-pointer';
            }

            let topRightBadges = '';
            if (c.is_checked_in) {
                topRightBadges += `<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-sky-100 text-sky-800 border border-sky-300" title="現場到着受付済み">着</span>`;
            }
            if (isAssigned) {
                topRightBadges += `<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-200 text-slate-600">配置済</span>`;
            }

            let statusBadge = '';
            if (isConflicted) {
                statusBadge = `<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-300 truncate max-w-[115px]" title="${escapeHtml(c.conflicted_label)}">⚠️ ${escapeHtml(c.conflicted_task_title || '重複中')}</span>`;
            } else if (c.is_same_lane) {
                statusBadge = `<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">🌟 同班推奨</span>`;
            } else if (c.is_surplus_fixed) {
                statusBadge = `<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-purple-100 text-purple-800 border border-purple-300" title="他班で待機中または定員超過のため融通可能">✨ 他班余剰</span>`;
            } else {
                statusBadge = `<span class="px-1.5 py-0.2 rounded text-[9px] font-medium bg-slate-100 text-slate-600 border border-slate-200">🟢 空き</span>`;
            }

            const hours = Math.round(((c.minutes_today || 0) / 60) * 10) / 10;

            return `
                <div class="${cardClass}" data-action="toggle-candidate" data-staff-id="${c.id}" data-assigned="${isAssigned ? '1' : '0'}">
                    <div class="flex items-center justify-between gap-1.5 min-w-0">
                        <div class="flex items-center space-x-2 min-w-0">
                            <input type="checkbox" value="${c.id}" ${isSelected ? 'checked' : ''} ${isAssigned ? 'disabled' : ''}
                                class="candidate-checkbox h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 cursor-pointer pointer-events-none shrink-0">
                            <span class="font-bold text-slate-900 truncate" title="${escapeHtml(c.name)}">${escapeHtml(c.name)}</span>
                            ${c.kana ? `<span class="text-[10px] text-slate-400 font-normal truncate hidden xl:inline">(${escapeHtml(c.kana)})</span>` : ''}
                        </div>
                        <div class="flex items-center space-x-1 shrink-0">${topRightBadges}</div>
                    </div>

                    <div class="flex items-center justify-between gap-1.5 pl-5 text-[10px]">
                        <span class="text-slate-500 truncate" title="${escapeHtml(c.affiliation || '所属なし')}">
                            ${escapeHtml(c.affiliation || '所属なし')}
                        </span>
                        <div class="flex items-center space-x-1.5 shrink-0">
                            ${statusBadge}
                            <span class="font-mono text-slate-400">${hours}h</span>
                        </div>
                    </div>
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

        const affiliationSelect = document.getElementById('quickAssignAffiliationSelect');
        if (affiliationSelect) {
            affiliationSelect.addEventListener('change', (e) => {
                affiliationFilter = e.target.value;
                renderCandidateList();
            });
        }
    };

    const showAlert = (msg, type = 'error', allowForce = false) => {
        const alertArea = document.getElementById('quickAssignAlertArea');
        if (!alertArea) return;
        const bg = type === 'error'
            ? 'bg-amber-50 border-amber-300 text-amber-900'
            : 'bg-emerald-50 border-emerald-200 text-emerald-800';
        alertArea.className = `p-2.5 rounded-xl border text-xs flex flex-col gap-2 ${bg}`;

        const forceBtnHtml = allowForce
            ? `<div class="flex justify-end">
                <button type="button" data-action="submit-bulk-assign-force" class="px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-[11px] font-bold transition cursor-pointer">
                    ⚠️ 重複を承認してそのまま配置する
                </button>
               </div>`
            : '';

        alertArea.innerHTML = `
            <div class="flex items-start justify-between gap-2">
                <span>${escapeHtml(msg)}</span>
                <button type="button" data-action="dismiss-alert" class="font-bold text-sm leading-none cursor-pointer shrink-0">&times;</button>
            </div>
            ${forceBtnHtml}
        `;
    };

    const executeBulkAssign = async (force = 0) => {
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
                    force: force
                })
            });

            const data = await res.json();
            if (!res.ok) {
                showAlert(data.message || '配置処理に失敗しました', 'error', Boolean(data.has_conflict));
                return;
            }

            selectedStaffIds = [];
            await fetchAndRenderData();
            showAlert(data.message, 'success');
        } catch (err) {
            showAlert(err.message || '通信エラーが発生しました', 'error');
        }
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

        // 6. 候補スタッフカードの選択トグル
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

            updateSelectionCountersAndButtons();
            renderCandidateList();
            return;
        }

        // 6-b-2. 不足分を自動選択（優先度順）
        if (e.target.closest('[data-action="select-shortage-auto"]')) {
            const shortage = taskData?.task?.shortage_count || 0;
            if (shortage > 0) {
                selectedStaffIds = [];
                const filtered = getFilteredCandidates();
                // 優先度順（未アサインかつ重複なしを優先）
                const eligible = filtered.filter(c => !c.is_already_assigned && !c.is_conflicted);
                const toPick = eligible.slice(0, shortage);
                toPick.forEach(c => selectedStaffIds.push(c.id));

                updateSelectionCountersAndButtons();
                renderCandidateList();
            }
            return;
        }

        // 6-b. 表示中を全選択
        if (e.target.closest('[data-action="select-all-filtered"]')) {
            const filtered = getFilteredCandidates();
            filtered.forEach(c => {
                if (!c.is_already_assigned && !selectedStaffIds.includes(c.id)) {
                    selectedStaffIds.push(c.id);
                }
            });
            updateSelectionCountersAndButtons();
            renderCandidateList();
            return;
        }

        // 6-c. 選択解除
        if (e.target.closest('[data-action="clear-selection"]')) {
            selectedStaffIds = [];
            updateSelectionCountersAndButtons();
            renderCandidateList();
            return;
        }

        // 7. 一括配置の実行（通常 / 重複強制承認）
        if (e.target.closest('[data-action="submit-bulk-assign"]')) {
            await executeBulkAssign(0);
            return;
        }

        if (e.target.closest('[data-action="submit-bulk-assign-force"]')) {
            await executeBulkAssign(1);
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
