import { initDetailPane } from './timetable-assignments/detail-pane.js';
import { initTaskListPane } from './timetable-assignments/task-list-pane.js';

/**
 * 人員配置（タスク別事前配置）ワークスペース エントリポイント
 * CSP完全準拠 (Vanilla JS ES Modules)
 */
document.addEventListener('DOMContentLoaded', () => {
    const workspaceEl = document.getElementById('assignment-workspace');
    if (!workspaceEl) return;

    const timetableId = workspaceEl.getAttribute('data-timetable-id');
    const initialTaskId = workspaceEl.getAttribute('data-initial-task-id');

    let taskListPane = null;
    const detailPane = initDetailPane({
        timetableId,
        onTaskMutated: (taskData) => {
            if (taskListPane && taskData?.task) {
                taskListPane.syncLeftTaskCard(taskData.task.id, taskData.task, taskData.assignments || []);
                taskListPane.updateSummaryCounts();
            }
        },
    });

    taskListPane = initTaskListPane({
        onSelectTask: (taskId) => detailPane.loadTask(taskId),
    });

    if (initialTaskId) {
        taskListPane.selectTask(initialTaskId);
    }
});
