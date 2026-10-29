/**
 * 作業タスク新規登録（複数タスク一括登録対応）スクリプト (CSP完全準拠)
 */

document.addEventListener('DOMContentLoaded', () => {
    initLaneSelectorSync();
    initBulkTaskRowsManager();
});

/**
 * 時刻文字列 (HH:MM) に指定分を加算した時刻文字列を返す
 */
function addMinutesToTime(timeStr, minutesToAdd = 60) {
    if (!timeStr || !/^\d{2}:\d{2}$/.test(timeStr)) {
        return '09:30';
    }
    const [hours, minutes] = timeStr.split(':').map(Number);
    const totalMinutes = Math.min(23 * 60 + 59, Math.max(0, hours * 60 + minutes + minutesToAdd));
    const newH = String(Math.floor(totalMinutes / 60)).padStart(2, '0');
    const newM = String(totalMinutes % 60).padStart(2, '0');
    return `${newH}:${newM}`;
}

/**
 * 2つの時刻文字列 (HH:MM) の差分（分）を計算する（最低30分）
 */
function getDurationMinutes(startTime, endTime) {
    if (!/^\d{2}:\d{2}$/.test(startTime || '') || !/^\d{2}:\d{2}$/.test(endTime || '')) {
        return 60;
    }
    const [sH, sM] = startTime.split(':').map(Number);
    const [eH, eM] = endTime.split(':').map(Number);
    const diff = (eH * 60 + eM) - (sH * 60 + sM);
    return diff >= 15 ? diff : 60;
}

/**
 * 1. 担当班（レーン）変更時の主担当スタッフ表示・推奨開始時刻連動
 */
function initLaneSelectorSync() {
    const laneSelect = document.getElementById('lane_id');
    const leadBanner = document.getElementById('lead-staff-banner');
    const noLeadBanner = document.getElementById('no-lead-staff-banner');
    const leadNameEl = document.getElementById('lead-staff-name');
    const leadAffEl = document.getElementById('lead-staff-affiliation');
    const fixedCountEl = document.getElementById('fixed-staff-count');
    const fixedNamesEl = document.getElementById('fixed-staff-names');
    const tasksContainer = document.getElementById('tasks-container');

    if (!laneSelect) return;

    laneSelect.addEventListener('change', () => {
        const selectedOption = laneSelect.options[laneSelect.selectedIndex];
        if (!selectedOption) return;

        const leadName = selectedOption.dataset.leadName || '';
        const leadAff = selectedOption.dataset.leadAff || '';
        const fixedCount = selectedOption.dataset.fixedCount || '0';
        const fixedNames = selectedOption.dataset.fixedNames || '';
        const suggestedStart = selectedOption.dataset.suggestedStart || '';

        if (fixedCountEl) fixedCountEl.textContent = fixedCount;
        if (fixedNamesEl) fixedNamesEl.textContent = fixedNames || '（設定なし）';

        if (leadName || parseInt(fixedCount, 10) > 0) {
            if (leadNameEl) leadNameEl.textContent = leadName || '未設定';
            if (leadAffEl) {
                if (leadAff) {
                    leadAffEl.textContent = `(${leadAff})`;
                    leadAffEl.classList.remove('hidden');
                } else {
                    leadAffEl.textContent = '';
                    leadAffEl.classList.add('hidden');
                }
            }
            leadBanner?.classList.remove('hidden');
            if (leadName) {
                noLeadBanner?.classList.add('hidden');
            } else {
                noLeadBanner?.classList.remove('hidden');
            }
        } else {
            leadBanner?.classList.add('hidden');
            noLeadBanner?.classList.remove('hidden');
        }

        // タスク枠が1件のみで作業名がまだ未入力の場合、推奨開始時刻を1枠目に反映
        if (tasksContainer && suggestedStart) {
            const cards = tasksContainer.querySelectorAll('.task-item-card');
            if (cards.length === 1) {
                const firstCard = cards[0];
                const titleInput = firstCard.querySelector('[data-field="title"]');
                const startInput = firstCard.querySelector('[data-field="start_time"]');
                const endInput = firstCard.querySelector('[data-field="end_time"]');

                if (titleInput && !titleInput.value.trim() && startInput && endInput) {
                    startInput.value = suggestedStart;
                    endInput.value = addMinutesToTime(suggestedStart, suggestedStart === '08:00' ? 90 : 60);
                }
            }
        }
    });
}

/**
 * 2. 作業タスク入力枠の追加・複製・削除およびインデックス管理
 */
function initBulkTaskRowsManager() {
    const tasksContainer = document.getElementById('tasks-container');
    const countBadge = document.getElementById('task-count-badge');
    const submitBtn = document.getElementById('submit-tasks-btn');

    if (!tasksContainer) return;

    /**
     * 全タスクカードの name 属性インデックスと表示番号・ボタン状態を同期
     */
    function reindexTaskCards() {
        const cards = Array.from(tasksContainer.querySelectorAll('.task-item-card'));
        const total = cards.length;

        cards.forEach((card, idx) => {
            card.dataset.taskIndex = String(idx);

            const badge = card.querySelector('.task-index-badge');
            if (badge) {
                badge.textContent = `タスク #${idx + 1}`;
            }

            const removeBtn = card.querySelector('.btn-remove-task');
            if (removeBtn) {
                if (total <= 1) {
                    removeBtn.classList.add('hidden');
                } else {
                    removeBtn.classList.remove('hidden');
                }
            }

            // data-field を持つ全入力要素の name を tasks[idx][field] に更新
            card.querySelectorAll('[data-field]').forEach((input) => {
                const fieldName = input.getAttribute('data-field');
                if (fieldName) {
                    input.setAttribute('name', `tasks[${idx}][${fieldName}]`);
                }
            });
        });

        if (countBadge) {
            countBadge.textContent = `${total} 件`;
        }

        if (submitBtn) {
            submitBtn.textContent = total > 1
                ? `${total}件の作業タスクを一括登録する`
                : '作業タスクを登録する';
        }
    }

    /**
     * 新規タスク枠の追加（末尾の終了時刻を次の開始時刻に引き継ぐ）
     */
    function addBlankTaskCard() {
        const cards = tasksContainer.querySelectorAll('.task-item-card');
        const lastCard = cards[cards.length - 1];
        if (!lastCard) return;

        const prevEndTime = lastCard.querySelector('[data-field="end_time"]')?.value || '09:30';
        const nextStartTime = prevEndTime;
        const nextEndTime = addMinutesToTime(nextStartTime, 60);

        const clone = lastCard.cloneNode(true);

        const titleInput = clone.querySelector('[data-field="title"]');
        const locationInput = clone.querySelector('[data-field="location"]');
        const startInput = clone.querySelector('[data-field="start_time"]');
        const endInput = clone.querySelector('[data-field="end_time"]');
        const peopleInput = clone.querySelector('[data-field="required_people_count"]');
        const atLeastInput = clone.querySelector('[data-field="is_at_least"]');
        const contentInput = clone.querySelector('[data-field="content"]');
        const statusSelect = clone.querySelector('[data-field="status"]');
        const criticalInput = clone.querySelector('[data-field="is_critical"]');
        const reviewInput = clone.querySelector('[data-field="requires_review"]');

        if (titleInput) titleInput.value = '';
        if (locationInput) locationInput.value = '';
        if (startInput) startInput.value = nextStartTime;
        if (endInput) endInput.value = nextEndTime;
        if (peopleInput) peopleInput.value = '2';
        if (atLeastInput) atLeastInput.checked = false;
        if (contentInput) contentInput.value = '';
        if (statusSelect) statusSelect.value = 'not_started';
        if (criticalInput) criticalInput.checked = false;
        if (reviewInput) reviewInput.checked = false;

        tasksContainer.appendChild(clone);
        reindexTaskCards();

        titleInput?.focus();
    }

    /**
     * 指定タスク枠を複製し、直後の時間帯にシフトして挿入
     */
    function duplicateTaskCard(sourceCard) {
        if (!sourceCard) return;

        const clone = sourceCard.cloneNode(true);

        const srcStart = sourceCard.querySelector('[data-field="start_time"]')?.value || '08:00';
        const srcEnd = sourceCard.querySelector('[data-field="end_time"]')?.value || '09:30';
        const duration = getDurationMinutes(srcStart, srcEnd);

        const nextStart = srcEnd;
        const nextEnd = addMinutesToTime(nextStart, duration);

        const cloneStart = clone.querySelector('[data-field="start_time"]');
        const cloneEnd = clone.querySelector('[data-field="end_time"]');
        const srcStatus = sourceCard.querySelector('[data-field="status"]');
        const cloneStatus = clone.querySelector('[data-field="status"]');

        if (cloneStart) cloneStart.value = nextStart;
        if (cloneEnd) cloneEnd.value = nextEnd;
        if (srcStatus && cloneStatus) {
            cloneStatus.value = srcStatus.value;
        }

        sourceCard.insertAdjacentElement('afterend', clone);
        reindexTaskCards();

        const titleInput = clone.querySelector('[data-field="title"]');
        if (titleInput) {
            titleInput.focus();
            titleInput.select();
        }
    }

    // クリックイベント委譲
    document.addEventListener('click', (e) => {
        const addBtn = e.target.closest('[data-action="add-task-row"]');
        if (addBtn) {
            e.preventDefault();
            addBlankTaskCard();
            return;
        }

        const dupBtn = e.target.closest('[data-action="duplicate-task-row"]');
        if (dupBtn) {
            e.preventDefault();
            const card = dupBtn.closest('.task-item-card');
            duplicateTaskCard(card);
            return;
        }

        const removeBtn = e.target.closest('[data-action="remove-task-row"]');
        if (removeBtn) {
            e.preventDefault();
            const cards = tasksContainer.querySelectorAll('.task-item-card');
            if (cards.length <= 1) return;

            const card = removeBtn.closest('.task-item-card');
            if (card) {
                card.remove();
                reindexTaskCards();
            }
        }
    });
}

