/**
 * 作業タスク新規登録スクリプト (CSP完全準拠)
 */

document.addEventListener('DOMContentLoaded', () => {
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
 * 2つの時刻文字列 (HH:MM) の差分（分）を計算する（最低15分、デフォルト60分）
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
 * 作業タスク入力枠の追加・複製・削除、インデックス管理および時間帯重複チェック
 */
function initBulkTaskRowsManager() {
    const form = document.getElementById('bulk-task-create-form');
    const tasksContainer = document.getElementById('tasks-container');
    const countBadge = document.getElementById('task-count-badge');
    const submitBtn = document.getElementById('submit-tasks-btn');

    if (!tasksContainer) return;

    /**
     * 時間帯の重複チェック
     * @returns {boolean} 重複がある場合は true
     */
    function checkTimeOverlaps() {
        const cards = Array.from(tasksContainer.querySelectorAll('.task-item-card'));
        const cardData = cards.map((card, idx) => {
            const startInput = card.querySelector('[data-field="start_time"]');
            const endInput = card.querySelector('[data-field="end_time"]');
            const statusSelect = card.querySelector('[data-field="status"]');
            const warningEl = card.querySelector('.task-overlap-warning');

            return {
                card,
                index: idx,
                start: startInput?.value || '',
                end: endInput?.value || '',
                status: statusSelect?.value || 'not_started',
                warningEl,
                overlapsWith: [],
            };
        });

        let hasOverlap = false;

        // ペアごとに重複判定 (startA < endB && endA > startB)
        for (let i = 0; i < cardData.length; i++) {
            const a = cardData[i];
            if (a.status === 'cancelled' || !a.start || !a.end || a.start >= a.end) {
                continue;
            }

            for (let j = i + 1; j < cardData.length; j++) {
                const b = cardData[j];
                if (b.status === 'cancelled' || !b.start || !b.end || b.start >= b.end) {
                    continue;
                }

                if (a.start < b.end && a.end > b.start) {
                    a.overlapsWith.push(j + 1);
                    b.overlapsWith.push(i + 1);
                    hasOverlap = true;
                }
            }
        }

        // 各カードのUI表示更新
        cardData.forEach((item) => {
            if (item.overlapsWith.length > 0) {
                item.card.classList.add('border-rose-400', 'bg-rose-50/20');
                if (item.warningEl) {
                    item.warningEl.textContent = `⚠️ タスク #${item.overlapsWith.join(', #')} と時間帯が重複しています`;
                    item.warningEl.classList.remove('hidden');
                }
            } else {
                item.card.classList.remove('border-rose-400', 'bg-rose-50/20');
                if (item.warningEl) {
                    item.warningEl.classList.add('hidden');
                }
            }
        });

        return hasOverlap;
    }

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

        checkTimeOverlaps();
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

        if (titleInput) titleInput.value = '';
        if (locationInput) locationInput.value = '';
        if (startInput) startInput.value = nextStartTime;
        if (endInput) endInput.value = nextEndTime;
        if (peopleInput) peopleInput.value = '2';
        if (atLeastInput) atLeastInput.checked = false;
        if (contentInput) contentInput.value = '';
        if (statusSelect) statusSelect.value = 'not_started';
        if (criticalInput) criticalInput.checked = false;

        clone.classList.remove('border-rose-400', 'bg-rose-50/20');
        const warningEl = clone.querySelector('.task-overlap-warning');
        if (warningEl) warningEl.classList.add('hidden');

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

        clone.classList.remove('border-rose-400', 'bg-rose-50/20');
        const warningEl = clone.querySelector('.task-overlap-warning');
        if (warningEl) warningEl.classList.add('hidden');

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

    // 入力変更時の重複チェック連動
    tasksContainer.addEventListener('input', (e) => {
        if (e.target.matches('[data-field="start_time"], [data-field="end_time"]')) {
            checkTimeOverlaps();
        }
    });

    tasksContainer.addEventListener('change', (e) => {
        if (e.target.matches('[data-field="start_time"], [data-field="end_time"], [data-field="status"]')) {
            checkTimeOverlaps();
        }
    });

    // 送信時チェック
    if (form) {
        form.addEventListener('submit', (e) => {
            if (checkTimeOverlaps()) {
                e.preventDefault();
                alert('時間帯が重複している作業タスクがあります。開始時刻・終了時刻を確認してください。');
            }
        });
    }

    // 初期チェック
    checkTimeOverlaps();
}
