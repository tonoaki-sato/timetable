/**
 * 班別作業タスク一括編集スクリプト (CSP完全準拠)
 */

document.addEventListener('DOMContentLoaded', () => {
    initBulkTaskEditManager();
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
 * 作業タスク一括編集マネージャー
 */
function initBulkTaskEditManager() {
    const form = document.getElementById('bulk-task-edit-form');
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
     * 全タスクカードの name 属性インデックスと表示番号を同期
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

        checkTimeOverlaps();
    }

    /**
     * 新規タスク枠の追加
     */
    function addBlankTaskCard() {
        const cards = tasksContainer.querySelectorAll('.task-item-card');
        const lastCard = cards[cards.length - 1];

        let nextStartTime = '08:00';
        let nextEndTime = '09:30';

        if (lastCard) {
            const prevEndTime = lastCard.querySelector('[data-field="end_time"]')?.value || '09:30';
            nextStartTime = prevEndTime;
            nextEndTime = addMinutesToTime(nextStartTime, 60);
        }

        const templateSource = lastCard || document.querySelector('.task-item-card');
        if (!templateSource) return;

        const clone = templateSource.cloneNode(true);
        setupNewTaskCard(clone, nextStartTime, nextEndTime);

        tasksContainer.appendChild(clone);
        reindexTaskCards();

        const titleInput = clone.querySelector('[data-field="title"]');
        titleInput?.focus();
    }

    /**
     * 指定タスク枠を複製して直後に挿入
     */
    function duplicateTaskCard(sourceCard) {
        if (!sourceCard) return;

        const clone = sourceCard.cloneNode(true);

        const srcStart = sourceCard.querySelector('[data-field="start_time"]')?.value || '08:00';
        const srcEnd = sourceCard.querySelector('[data-field="end_time"]')?.value || '09:30';
        const duration = getDurationMinutes(srcStart, srcEnd);

        const nextStart = srcEnd;
        const nextEnd = addMinutesToTime(nextStart, duration);

        setupNewTaskCard(clone, nextStart, nextEnd);

        // 複製元の値（タイトル・場所・人数・フラグ等）を引き継ぐ
        const srcTitle = sourceCard.querySelector('[data-field="title"]')?.value || '';
        const srcLocation = sourceCard.querySelector('[data-field="location"]')?.value || '';
        const srcPeople = sourceCard.querySelector('[data-field="required_people_count"]')?.value || '2';
        const srcAtLeast = sourceCard.querySelector('[data-field="is_at_least"]')?.checked || false;
        const srcContent = sourceCard.querySelector('[data-field="content"]')?.value || '';
        const srcStatus = sourceCard.querySelector('[data-field="status"]')?.value || 'not_started';
        const srcCritical = sourceCard.querySelector('[data-field="is_critical"]')?.checked || false;

        const titleInput = clone.querySelector('[data-field="title"]');
        const locationInput = clone.querySelector('[data-field="location"]');
        const peopleInput = clone.querySelector('[data-field="required_people_count"]');
        const atLeastInput = clone.querySelector('[data-field="is_at_least"]');
        const contentInput = clone.querySelector('[data-field="content"]');
        const statusSelect = clone.querySelector('[data-field="status"]');
        const criticalInput = clone.querySelector('[data-field="is_critical"]');

        if (titleInput) titleInput.value = srcTitle ? `${srcTitle} (コピー)` : '';
        if (locationInput) locationInput.value = srcLocation;
        if (peopleInput) peopleInput.value = srcPeople;
        if (atLeastInput) atLeastInput.checked = srcAtLeast;
        if (contentInput) contentInput.value = srcContent;
        if (statusSelect) statusSelect.value = srcStatus;
        if (criticalInput) criticalInput.checked = srcCritical;

        sourceCard.insertAdjacentElement('afterend', clone);
        reindexTaskCards();

        titleInput?.focus();
        titleInput?.select();
    }

    /**
     * 新規追加カードの初期化共通処理
     */
    function setupNewTaskCard(card, startTime, endTime) {
        card.dataset.isExisting = '0';
        delete card.dataset.taskId;

        // 既存のID hidden 要素を削除
        const existingIdInput = card.querySelector('input[data-field="id"]');
        if (existingIdInput) {
            existingIdInput.remove();
        }

        // バッジを「新規追加枠」に変更
        const headerLeft = card.querySelector('.task-index-badge')?.parentElement;
        if (headerLeft) {
            const oldStatusBadge = headerLeft.querySelector('span:not(.task-index-badge):not(.task-overlap-warning)');
            if (oldStatusBadge) oldStatusBadge.remove();

            const newBadge = document.createElement('span');
            newBadge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
            newBadge.textContent = '新規追加枠';
            headerLeft.appendChild(newBadge);
        }

        // ヘッダー右側のアクションに「✕ 取り消し」ボタンを追加（既存でなければ）
        const headerRight = card.querySelector('[data-action="duplicate-task-row"]')?.parentElement;
        if (headerRight && !headerRight.querySelector('[data-action="remove-new-task-row"]')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.setAttribute('data-action', 'remove-new-task-row');
            cancelBtn.title = 'この追加枠を取り消す';
            cancelBtn.className = 'btn-remove-task px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 rounded text-[11px] font-bold transition flex items-center space-x-1 cursor-pointer';
            cancelBtn.innerHTML = '<span>✕</span><span>取り消し</span>';
            headerRight.appendChild(cancelBtn);
        }

        // 重複警告の初期化
        card.classList.remove('border-rose-400', 'bg-rose-50/20');
        const warningEl = card.querySelector('.task-overlap-warning');
        if (warningEl) warningEl.classList.add('hidden');

        // 時刻セット
        const startInput = card.querySelector('[data-field="start_time"]');
        const endInput = card.querySelector('[data-field="end_time"]');
        if (startInput) startInput.value = startTime;
        if (endInput) endInput.value = endTime;
    }

    /**
     * 新規追加タスク枠の取り消し（既存タスクは削除不可）
     */
    function removeNewTaskCard(card) {
        if (!card) return;
        if (card.dataset.isExisting === '1') {
            return; // 既存タスクは削除不可
        }

        card.remove();
        reindexTaskCards();
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

        const removeBtn = e.target.closest('[data-action="remove-new-task-row"]');
        if (removeBtn) {
            e.preventDefault();
            const card = removeBtn.closest('.task-item-card');
            removeNewTaskCard(card);
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
