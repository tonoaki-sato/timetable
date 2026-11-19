/**
 * 運行情報新規登録画面（過去運行からの複製作成）スクリプト
 * CSP準拠（外部Viteモジュール）
 */
document.addEventListener('DOMContentLoaded', () => {
    initCloneSourceSelector();
    initCloneOptionDependencies();
});

/**
 * 複製元の運行情報セレクト変更時にプリセット付きで画面を再読み込みする
 */
function initCloneSourceSelector() {
    const select = document.getElementById('source_timetable_id');
    if (!select) return;

    select.addEventListener('change', () => {
        const baseUrl = select.dataset.createUrl;
        if (!baseUrl) return;

        const targetUrl = new URL(baseUrl, window.location.origin);
        if (select.value) {
            targetUrl.searchParams.set('source_timetable_id', select.value);
        }
        window.location.href = targetUrl.toString();
    });
}

/**
 * 引き継ぎオプションの親子チェックボックス連動（班 → 班員・タスク → アサイン）
 */
function initCloneOptionDependencies() {
    const copyLanes = document.getElementById('copy_lanes');
    const copyLaneStaff = document.getElementById('copy_lane_staff');
    const copyTasks = document.getElementById('copy_tasks');
    const copyTaskAssignments = document.getElementById('copy_task_assignments');

    if (!copyLanes || !copyLaneStaff || !copyTasks || !copyTaskAssignments) return;

    const setChildState = (checkbox, enabled) => {
        checkbox.disabled = !enabled;
        if (!enabled) {
            checkbox.checked = false;
        }
        const wrapper = checkbox.closest('[data-clone-option-item]');
        if (wrapper) {
            wrapper.classList.toggle('opacity-50', !enabled);
        }
    };

    const syncDependencies = () => {
        const lanesEnabled = copyLanes.checked;
        setChildState(copyLaneStaff, lanesEnabled);
        setChildState(copyTasks, lanesEnabled);

        const tasksEnabled = lanesEnabled && copyTasks.checked;
        setChildState(copyTaskAssignments, tasksEnabled);
    };

    copyLanes.addEventListener('change', syncDependencies);
    copyTasks.addEventListener('change', syncDependencies);

    syncDependencies();
}
