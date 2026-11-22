/**
 * 作業タスク 時間計算・データ取得・同一班時間帯重複判定ヘルパー
 */

/**
 * 時刻文字列 (HH:MM) に指定分を加算した時刻文字列を返す
 */
export function addMinutesToTime(timeStr, minutesToAdd = 60) {
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
 * 一覧上の全タスクデータを取得
 * @returns {Array<Object>}
 */
export function getAllTasks() {
    const rows = document.querySelectorAll('[data-task-row]');
    const tasks = [];
    rows.forEach((row) => {
        const raw = row.getAttribute('data-task');
        if (raw) {
            try {
                tasks.push(JSON.parse(raw));
            } catch (e) {
                // ignore JSON parse error
            }
        }
    });
    return tasks;
}

/**
 * 同一レーン内の既存タスクと時間帯重複する一覧を取得
 * @returns {Array<Object>}
 */
export function findOverlapsWithLaneTasks(laneId, startTime, endTime, excludeTaskId = null) {
    if (!laneId || !startTime || !endTime || startTime >= endTime) {
        return [];
    }

    const allTasks = getAllTasks();
    const matches = [];
    for (const task of allTasks) {
        if (String(task.timetable_lane_id) !== String(laneId)) continue;
        if (excludeTaskId && String(task.id) === String(excludeTaskId)) continue;
        if (task.status === 'cancelled') continue;

        const tStart = task.start_time;
        const tEnd = task.end_time;
        if (!tStart || !tEnd) continue;

        // 重複判定: start < other.end && end > other.start
        if (startTime < tEnd && endTime > tStart) {
            matches.push(task);
        }
    }
    return matches;
}

/**
 * 単一タスクモーダル用の重複警告表示を更新
 */
export function updateSingleOverlapWarning(warningEl, matches, { subject, actionLabel }) {
    if (!warningEl) {
        return false;
    }

    if (matches.length > 0) {
        const warningSpan = warningEl.querySelector('.warning-text');
        if (warningSpan) {
            if (matches.length === 1) {
                const conflictingTask = matches[0];
                warningSpan.textContent = `${subject}「${conflictingTask.title}」（${conflictingTask.start_time}〜${conflictingTask.end_time}）と時間帯が重なっています（並行タスクとして${actionLabel}できます）。`;
            } else {
                const summary = matches.map(t => `「${t.title}」(${t.start_time}〜${t.end_time})`).join('・');
                warningSpan.textContent = `${subject} ${summary} と時間帯が重なっています（並行タスクとして${actionLabel}できます）。`;
            }
        }
        warningEl.classList.remove('hidden');
        return true;
    }

    warningEl.classList.add('hidden');
    return false;
}
