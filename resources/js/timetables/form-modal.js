import { createPeriodRowsController } from './period-rows.js';

/**
 * 運行情報 登録・編集・複製モーダル制御
 */
export function initTimetableFormModal() {
    const modal = document.getElementById('modal-timetable-form');
    if (!modal) return;

    const form = document.getElementById('form-timetable-mutation');
    const formMethod = document.getElementById('timetable-form-method');
    const modalTitle = document.getElementById('timetable-modal-title');
    const modalSubtitle = document.getElementById('timetable-modal-subtitle');
    const modalIcon = document.getElementById('timetable-modal-icon');
    const btnSaveLabel = document.getElementById('btn-save-label');
    const btnSaveIcon = document.getElementById('btn-save-icon');

    const inputTargetDate = document.getElementById('modal_target_date');
    const inputStatus = document.getElementById('modal_status');
    const inputName = document.getElementById('modal_name');
    const inputStartTime = document.getElementById('modal_start_time');
    const inputEndTime = document.getElementById('modal_end_time');
    const inputDescription = document.getElementById('modal_description');

    const sectionClone = document.getElementById('section-clone-options');
    const inputSourceId = document.getElementById('modal_source_timetable_id');
    const cloneSourceName = document.getElementById('clone-source-name');
    const cloneSourceDate = document.getElementById('clone-source-date');

    const copyLanes = document.getElementById('modal_copy_lanes');
    const copyLaneStaff = document.getElementById('modal_copy_lane_staff');
    const copyTasks = document.getElementById('modal_copy_tasks');
    const copyTaskAssignments = document.getElementById('modal_copy_task_assignments');

    const { setRoadPeriods, setFestivalPeriods } = createPeriodRowsController(modal);

    const openModal = () => {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    modal.querySelectorAll('.btn-close-timetable-modal, .modal-backdrop').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    function syncCloneOptionDependencies() {
        if (!copyLanes || !copyLaneStaff || !copyTasks || !copyTaskAssignments) return;

        const setChildState = (checkbox, enabled) => {
            checkbox.disabled = !enabled;
            if (!enabled) checkbox.checked = false;
            const wrapper = checkbox.closest('[data-clone-option-item]');
            if (wrapper) wrapper.classList.toggle('opacity-50', !enabled);
        };

        const lanesEnabled = copyLanes.checked;
        setChildState(copyLaneStaff, lanesEnabled);
        setChildState(copyTasks, lanesEnabled);

        const tasksEnabled = lanesEnabled && copyTasks.checked;
        setChildState(copyTaskAssignments, tasksEnabled);
    }

    if (copyLanes) copyLanes.addEventListener('change', syncCloneOptionDependencies);
    if (copyTasks) copyTasks.addEventListener('change', syncCloneOptionDependencies);

    const btnCreate = document.getElementById('btn-open-create-timetable');
    if (btnCreate) {
        btnCreate.addEventListener('click', () => {
            if (!form) return;
            form.action = btnCreate.dataset.storeUrl || '/timetables';
            formMethod.value = 'POST';

            if (inputSourceId) inputSourceId.value = '';
            if (sectionClone) sectionClone.classList.add('hidden');

            const today = new Date().toISOString().split('T')[0];
            if (inputTargetDate) inputTargetDate.value = today;
            if (inputStatus) inputStatus.value = 'draft';
            if (inputName) inputName.value = '';
            if (inputStartTime) inputStartTime.value = '08:00';
            if (inputEndTime) inputEndTime.value = '18:00';
            if (inputDescription) inputDescription.value = '';

            if (modalTitle) modalTitle.textContent = '新規運行情報登録';
            if (modalSubtitle) modalSubtitle.textContent = 'まつり開催日の基本枠および規制時間帯を設定します。';
            if (modalIcon) modalIcon.textContent = '⏱️';
            if (btnSaveLabel) btnSaveLabel.textContent = '登録する';
            if (btnSaveIcon) btnSaveIcon.textContent = '💾';

            setRoadPeriods([{ start_time: '10:00', end_time: '18:00', label: '通行止め', status: 'closed' }]);
            setFestivalPeriods([{ start_time: '11:00', end_time: '17:00', label: '開催中', status: 'active' }]);

            openModal();
            inputName?.focus();
        });
    }

    document.querySelectorAll('.btn-clone-timetable').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!form) return;
            const sourceId = btn.dataset.sourceId;
            form.action = btn.dataset.storeUrl || '/timetables';
            formMethod.value = 'POST';

            if (inputSourceId) inputSourceId.value = sourceId || '';
            if (sectionClone) sectionClone.classList.remove('hidden');

            if (cloneSourceName) cloneSourceName.textContent = btn.dataset.name || '—';
            if (cloneSourceDate) cloneSourceDate.textContent = btn.dataset.targetDate || '—';

            const today = new Date().toISOString().split('T')[0];
            if (inputTargetDate) inputTargetDate.value = today;
            if (inputStatus) inputStatus.value = 'draft';
            if (inputName) inputName.value = `${btn.dataset.name || ''} (複製)`;
            if (inputStartTime) inputStartTime.value = btn.dataset.startTime || '08:00';
            if (inputEndTime) inputEndTime.value = btn.dataset.endTime || '18:00';
            if (inputDescription) inputDescription.value = btn.dataset.description || '';

            if (modalTitle) modalTitle.textContent = '過去運行をもとに新規登録';
            if (modalSubtitle) modalSubtitle.textContent = `「${btn.dataset.name || ''}」の構成を引き継いで新しい運行情報を作成します。`;
            if (modalIcon) modalIcon.textContent = '📋';
            if (btnSaveLabel) btnSaveLabel.textContent = '複製して登録する';
            if (btnSaveIcon) btnSaveIcon.textContent = '📋';

            syncCloneOptionDependencies();

            openModal();
            inputTargetDate?.focus();
        });
    });

    document.querySelectorAll('.btn-edit-timetable').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!form) return;
            form.action = btn.dataset.updateUrl || '';
            formMethod.value = 'PUT';

            const name = btn.dataset.name || '';
            if (modalTitle) modalTitle.textContent = `運行情報の編集: ${name}`;
            if (modalSubtitle) modalSubtitle.textContent = `「${name}」の基本設定および規制時間帯を更新します。`;
            if (modalIcon) modalIcon.textContent = '✏️';
            if (btnSaveLabel) btnSaveLabel.textContent = '変更内容を保存する';
            if (btnSaveIcon) btnSaveIcon.textContent = '💾';

            if (sectionClone) sectionClone.classList.add('hidden');

            if (inputTargetDate) inputTargetDate.value = btn.dataset.targetDate || '';
            if (inputStatus) inputStatus.value = btn.dataset.status || 'draft';
            if (inputName) inputName.value = name;
            if (inputStartTime) inputStartTime.value = btn.dataset.startTime || '08:00';
            if (inputEndTime) inputEndTime.value = btn.dataset.endTime || '18:00';
            if (inputDescription) inputDescription.value = btn.dataset.description || '';

            try {
                const roadPeriods = JSON.parse(btn.dataset.roadPeriods || '[]');
                setRoadPeriods(roadPeriods);
            } catch (err) {
                setRoadPeriods([]);
            }

            try {
                const festivalPeriods = JSON.parse(btn.dataset.festivalPeriods || '[]');
                setFestivalPeriods(festivalPeriods);
            } catch (err) {
                setFestivalPeriods([]);
            }

            openModal();
            inputName?.focus();
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'create') {
        const createBtn = document.getElementById('btn-open-create-timetable');
        if (createBtn) createBtn.click();
    } else if (urlParams.get('edit')) {
        const editId = urlParams.get('edit');
        const editBtn = document.querySelector(`.btn-edit-timetable[data-timetable-id="${editId}"]`) || document.getElementById('btn-open-edit-timetable');
        if (editBtn) editBtn.click();
    } else if (urlParams.get('clone')) {
        const cloneId = urlParams.get('clone');
        const cloneBtn = document.querySelector(`.btn-clone-timetable[data-source-id="${cloneId}"]`);
        if (cloneBtn) {
            cloneBtn.click();
        } else if (btnCreate) {
            btnCreate.click();
        }
    }
}
