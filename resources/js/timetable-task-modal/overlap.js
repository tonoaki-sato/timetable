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
 * 一覧上またはタイムライン上の全タスクデータを取得
 * @returns {Array<Object>}
 */
export function getAllTasks() {
    const tasks = [];
    const rows = document.querySelectorAll('[data-task-row]');
    if (rows.length > 0) {
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

    const jsonContainer = document.getElementById('lane-timeline-tasks-data');
    if (jsonContainer && jsonContainer.textContent) {
        try {
            const parsed = JSON.parse(jsonContainer.textContent);
            if (Array.isArray(parsed)) {
                return parsed;
            }
        } catch (e) {
            // ignore JSON parse error
        }
    }
    return tasks;
}

/**
 * 同一レーン内の既存タスクと時間帯重複する一覧を取得（同種別・異種別に分類）
 * @returns {{ sameTypeMatches: Array<Object>, differentTypeMatches: Array<Object> }}
 */
export function findOverlapsWithLaneTasks(laneId, startTime, endTime, excludeTaskId = null, taskType = 'spot') {
    if (!laneId || !startTime || !endTime || startTime >= endTime) {
        return { sameTypeMatches: [], differentTypeMatches: [] };
    }

    const allTasks = getAllTasks();
    const sameTypeMatches = [];
    const differentTypeMatches = [];

    for (const task of allTasks) {
        if (String(task.timetable_lane_id) !== String(laneId)) continue;
        if (excludeTaskId && String(task.id) === String(excludeTaskId)) continue;
        if (task.status === 'cancelled') continue;

        const tStart = task.start_time;
        const tEnd = task.end_time;
        if (!tStart || !tEnd) continue;

        // 重複判定: start < other.end && end > other.start
        if (startTime < tEnd && endTime > tStart) {
            const existingType = task.task_type || 'spot';
            if (existingType === taskType) {
                sameTypeMatches.push(task);
            } else {
                differentTypeMatches.push(task);
            }
        }
    }

    return { sameTypeMatches, differentTypeMatches };
}

/**
 * 単一タスクモーダル用の重複警告・並行案内表示を更新
 */
export function updateSingleOverlapWarning(warningEl, overlaps, { subject, actionLabel, taskType = 'spot' }) {
    if (!warningEl) {
        return false;
    }

    const { sameTypeMatches = [], differentTypeMatches = [] } = Array.isArray(overlaps)
        ? { sameTypeMatches: overlaps, differentTypeMatches: [] }
        : (overlaps || {});

    const warningSpan = warningEl.querySelector('.warning-text');

    if (sameTypeMatches.length > 0) {
        warningEl.classList.remove('bg-sky-50', 'border-sky-200', 'text-sky-800');
        warningEl.classList.add('bg-amber-50', 'border-amber-200/90', 'text-amber-800');
        if (warningSpan) {
            if (sameTypeMatches.length === 1) {
                const conflictingTask = sameTypeMatches[0];
                warningSpan.textContent = `${subject}（同種）「${conflictingTask.title}」（${conflictingTask.start_time}〜${conflictingTask.end_time}）と時間帯が重なっています（並行タスクとして${actionLabel}できます）。`;
            } else {
                const summary = sameTypeMatches.map(t => `「${t.title}」(${t.start_time}〜${t.end_time})`).join('・');
                warningSpan.textContent = `${subject}（同種）${summary} と時間帯が重なっています（並行タスクとして${actionLabel}できます）。`;
            }
        }
        warningEl.classList.remove('hidden');
        return true;
    }

    if (differentTypeMatches.length > 0) {
        warningEl.classList.remove('bg-amber-50', 'border-amber-200/90', 'text-amber-800');
        warningEl.classList.add('bg-sky-50', 'border-sky-200', 'text-sky-800');
        const currentLabel = taskType === 'routine' ? '定常タスク' : 'スポットタスク';
        const otherLabel = taskType === 'routine' ? 'スポットタスク' : '定常タスク';
        if (warningSpan) {
            const summary = differentTypeMatches.slice(0, 2).map(t => `「${t.title}」(${t.start_time}〜${t.end_time})`).join('・');
            const suffix = differentTypeMatches.length > 2 ? ` ほか${differentTypeMatches.length - 2}件` : '';
            warningSpan.textContent = `${otherLabel} ${summary}${suffix} と時間帯が重なっていますが、${currentLabel}として問題なく並行${actionLabel}できます。`;
        }
        warningEl.classList.remove('hidden');
        return false;
    }

    warningEl.classList.add('hidden');
    return false;
}
