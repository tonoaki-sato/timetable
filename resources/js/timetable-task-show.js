/**
 * 作業タスク詳細・人員アサイン画面スクリプト (CSP完全準拠・バニラJS)
 */
document.addEventListener('DOMContentLoaded', () => {
    initBulkAssignSection();
    initSwapModal();
});

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
 * 一括選択アサインUI（検索・フィルター・全選択）
 */
function initBulkAssignSection() {
    const searchInput = document.getElementById('staff-search-input');
    const statusSelect = document.getElementById('staff-filter-status');
    const affiliationSelect = document.getElementById('staff-filter-affiliation');
    const rows = document.querySelectorAll('.bulk-candidate-row');
    const countLabel = document.getElementById('selected-count-label');
    const countLabelSubmit = document.getElementById('selected-count-submit-label');
    const submitBtn = document.getElementById('btn-submit-bulk-assign');
    const selectAllBtn = document.getElementById('btn-select-all-filtered');
    const clearSelectionBtn = document.getElementById('btn-clear-selection');
    const emptyNotice = document.getElementById('candidate-empty-notice');

    if (rows.length === 0) return;

    let currentSearch = '';
    let currentStatus = 'all';
    let currentAffiliation = '';

    function applyFilter() {
        const q = currentSearch.toLowerCase();
        let visibleCount = 0;

        rows.forEach(row => {
            const name = (row.dataset.name || '').toLowerCase();
            const kana = (row.dataset.kana || '').toLowerCase();
            const affiliation = row.dataset.affiliation || '';
            const isConflicted = row.dataset.isConflicted === '1';
            const isSameLane = row.dataset.isSameLane === '1';
            const isCheckedIn = row.dataset.isCheckedIn === '1';
            const isAssigned = row.dataset.isAssigned === '1';

            // 1. 検索マッチ
            const matchSearch = !q || name.includes(q) || kana.includes(q) || affiliation.toLowerCase().includes(q);

            // 2. 所属マッチ
            const matchAff = !currentAffiliation || affiliation === currentAffiliation;

            // 3. 状態マッチ
            let matchStatus = true;
            if (currentStatus === 'available') {
                matchStatus = !isConflicted && !isAssigned;
            } else if (currentStatus === 'recommended') {
                matchStatus = isSameLane && !isConflicted && !isAssigned;
            } else if (currentStatus === 'checked_in') {
                matchStatus = isCheckedIn && !isAssigned;
            }

            if (matchSearch && matchAff && matchStatus) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        if (emptyNotice) {
            if (visibleCount === 0) {
                emptyNotice.classList.remove('hidden');
            } else {
                emptyNotice.classList.add('hidden');
            }
        }
    }

    function updateSelectionState() {
        const checkedBoxes = document.querySelectorAll('.bulk-candidate-checkbox:checked');
        const count = checkedBoxes.length;

        if (countLabel) countLabel.textContent = count;
        if (countLabelSubmit) countLabelSubmit.textContent = count;

        if (clearSelectionBtn) {
            if (count > 0) {
                clearSelectionBtn.classList.remove('hidden');
            } else {
                clearSelectionBtn.classList.add('hidden');
            }
        }

        if (submitBtn) {
            if (count > 0) {
                submitBtn.disabled = false;
                submitBtn.className = 'px-5 py-2 rounded-lg text-xs font-bold shadow-sm transition flex items-center space-x-1.5 bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer';
            } else {
                submitBtn.disabled = true;
                submitBtn.className = 'px-5 py-2 rounded-lg text-xs font-bold shadow-sm transition flex items-center space-x-1.5 bg-slate-200 text-slate-400 cursor-not-allowed';
            }
        }

        // 行のハイライト更新
        rows.forEach(row => {
            const cb = row.querySelector('.bulk-candidate-checkbox');
            if (!cb) return;
            if (cb.checked) {
                row.classList.add('bg-indigo-50/70', 'border-l-4', 'border-indigo-600');
            } else {
                row.classList.remove('bg-indigo-50/70', 'border-l-4', 'border-indigo-600');
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            currentSearch = e.target.value.trim();
            applyFilter();
        });
    }

    if (statusSelect) {
        statusSelect.addEventListener('change', (e) => {
            currentStatus = e.target.value;
            applyFilter();
        });
    }

    if (affiliationSelect) {
        affiliationSelect.addEventListener('change', (e) => {
            currentAffiliation = e.target.value;
            applyFilter();
        });
    }

    // 行クリックでチェックボックス反転
    rows.forEach(row => {
        row.addEventListener('click', (e) => {
            if (row.dataset.isAssigned === '1') return;
            const cb = row.querySelector('.bulk-candidate-checkbox');
            if (!cb) return;

            // チェックボックス自体をクリックした場合はデフォルト挙動に任せる
            if (e.target !== cb) {
                cb.checked = !cb.checked;
            }
            updateSelectionState();
        });
    });

    // 表示中を全選択
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            rows.forEach(row => {
                if (!row.classList.contains('hidden') && row.dataset.isAssigned !== '1') {
                    const cb = row.querySelector('.bulk-candidate-checkbox');
                    if (cb) cb.checked = true;
                }
            });
            updateSelectionState();
        });
    }

    // 選択解除
    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', () => {
            rows.forEach(row => {
                const cb = row.querySelector('.bulk-candidate-checkbox');
                if (cb) cb.checked = false;
            });
            updateSelectionState();
        });
    }
}

/**
 * スタッフ交代・ワンタップ代替補充モーダル
 */
function initSwapModal() {
    const modal = document.getElementById('swapModal');
    if (!modal) return;

    const oldStaffNameEl = document.getElementById('swapOldStaffName');
    const oldStaffRoleEl = document.getElementById('swapOldStaffRole');
    const oldStaffIdInput = document.getElementById('swapOldStaffIdInput');
    const roleInput = document.getElementById('swapRoleInput');
    const searchInput = document.getElementById('swapSearchInput');
    const submitBtn = document.getElementById('swapSubmitBtn');
    const candidateRows = document.querySelectorAll('.swap-candidate-row');
    const emptyNotice = document.getElementById('swap-candidate-empty-notice');

    let currentOldStaffId = null;

    function openModal(oldId, oldName, oldRole) {
        currentOldStaffId = oldId;

        if (oldStaffNameEl) oldStaffNameEl.textContent = oldName;
        if (oldStaffRoleEl) oldStaffRoleEl.textContent = `（${oldRole || '一般担当'}）`;
        if (oldStaffIdInput) oldStaffIdInput.value = oldId;
        if (roleInput) roleInput.value = oldRole || '担当';
        if (searchInput) searchInput.value = '';

        // ラジオボタンのクリア
        document.querySelectorAll('input[name="new_staff_id"]').forEach(r => r.checked = false);
        candidateRows.forEach(r => r.classList.remove('bg-indigo-50/80', 'border-l-4', 'border-l-indigo-600'));

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold shadow transition flex items-center space-x-1.5 bg-slate-200 text-slate-400 cursor-not-allowed';
        }

        applySwapFilter('');

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        currentOldStaffId = null;
    }

    function applySwapFilter(q) {
        const query = (q || '').toLowerCase();
        let visibleCount = 0;

        candidateRows.forEach(row => {
            const staffId = parseInt(row.dataset.staffId, 10);
            const name = (row.dataset.name || '').toLowerCase();
            const kana = (row.dataset.kana || '').toLowerCase();
            const affiliation = (row.dataset.affiliation || '').toLowerCase();

            // 交代元本人、または既にアサインされている場合は除外
            if (staffId === currentOldStaffId || row.dataset.isAssigned === '1') {
                row.classList.add('hidden');
                return;
            }

            const match = !query || name.includes(query) || kana.includes(query) || affiliation.includes(query);
            if (match) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        if (emptyNotice) {
            if (visibleCount === 0) {
                emptyNotice.classList.remove('hidden');
            } else {
                emptyNotice.classList.add('hidden');
            }
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            applySwapFilter(e.target.value.trim());
        });
    }

    // 候補クリックでラジオ選択
    candidateRows.forEach(row => {
        row.addEventListener('click', (e) => {
            const radio = row.querySelector('input[type="radio"]');
            if (!radio) return;

            radio.checked = true;

            candidateRows.forEach(r => r.classList.remove('bg-indigo-50/80', 'border-l-4', 'border-l-indigo-600'));
            row.classList.add('bg-indigo-50/80', 'border-l-4', 'border-l-indigo-600');

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold shadow transition flex items-center space-x-1.5 bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer';
            }
        });
    });

    // イベント委譲（クリック）
    document.addEventListener('click', (e) => {
        // 1. 交代モーダルを開くボタン
        const openBtn = e.target.closest('[data-action="open-swap-modal"]');
        if (openBtn) {
            const oldId = parseInt(openBtn.dataset.staffId, 10);
            const oldName = openBtn.dataset.staffName || '';
            const oldRole = openBtn.dataset.staffRole || '';
            if (oldId) {
                openModal(oldId, oldName, oldRole);
            }
            return;
        }

        // 2. 交代モーダルを閉じるボタン（キャンセルボタン等）
        if (e.target.closest('[data-action="close-swap-modal"]')) {
            closeModal();
            return;
        }

        // 3. モーダル外側クリックで閉じる
        if (e.target === modal) {
            closeModal();
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
