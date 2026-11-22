import { confirmDialog } from '../utils/ui.js';
import { createScheduleManager, renderDetailScheduleGroup } from './schedule-rows.js';

/**
 * 2. 拠点マスター 新規登録・編集・詳細モーダルおよび削除確認
 */
export function initLocationModals() {
    const detailModal = document.getElementById('modal-location-detail');
    const formModal = document.getElementById('modal-location-form');
    const formMutation = document.getElementById('form-location-mutation');
    const formMethod = document.getElementById('form-method');
    const formModalTitle = document.getElementById('form-modal-title');

    const inputName = document.getElementById('location_name');
    const selectType = document.getElementById('location_type_id');
    const checkboxAllDay = document.getElementById('location_is_all_day');
    const checkboxActive = document.getElementById('location_is_active');
    const inputNotes = document.getElementById('location_notes');

    const detailTitle = document.getElementById('detail-modal-title');
    const detailName = document.getElementById('detail-location-name');
    const detailInactiveBadge = document.getElementById('detail-inactive-badge');
    const detailTypeLabel = document.getElementById('detail-type-label');
    const detailIsAllDay = document.getElementById('detail-is-all-day');
    const detailDailyStatus = document.getElementById('detail-daily-status');
    const detailNotes = document.getElementById('detail-notes');
    const detailSchedulesAllDay = document.getElementById('detail-schedules-all-day');
    const detailSchedulesSplit = document.getElementById('detail-schedules-split');
    const detailUpcomingCount = document.getElementById('detail-upcoming-count');
    const detailUpcomingList = document.getElementById('detail-upcoming-list');
    const detailUpcomingEmpty = document.getElementById('detail-upcoming-empty');
    const detailPastCount = document.getElementById('detail-past-count');
    const detailPastList = document.getElementById('detail-past-list');
    const detailPastEmpty = document.getElementById('detail-past-empty');

    const scheduleManager = createScheduleManager({
        checkboxAllDay,
        schedulesContainer: document.getElementById('schedules-form-container'),
        schedulesBody: document.getElementById('schedules-form-body'),
        schedulesEmptyMsg: document.getElementById('schedules-empty-message'),
        btnAddSchedule: document.getElementById('add-schedule-row-btn'),
    });

    const openModal = (modal) => {
        if (modal) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
    };

    const closeModal = (modal) => {
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    };

    document.querySelectorAll('.btn-close-modal, .modal-backdrop').forEach((el) => {
        el.addEventListener('click', () => {
            closeModal(detailModal);
            closeModal(formModal);
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal(detailModal);
            closeModal(formModal);
        }
    });

    const btnCreate = document.getElementById('btn-open-create-location');
    if (btnCreate) {
        btnCreate.addEventListener('click', () => {
            if (!formMutation || btnCreate.disabled) return;
            formMutation.action = btnCreate.dataset.storeUrl || '';
            if (formMethod) formMethod.value = 'POST';
            if (formModalTitle) formModalTitle.textContent = '📍 拠点の新規登録';

            if (inputName) inputName.value = '';
            if (selectType && selectType.options.length > 0) selectType.selectedIndex = 0;
            if (checkboxAllDay) checkboxAllDay.checked = false;
            if (checkboxActive) checkboxActive.checked = true;
            if (inputNotes) inputNotes.value = '';

            scheduleManager.renderFormSchedules([]);
            openModal(formModal);
            inputName?.focus();
        });
    }

    document.querySelectorAll('.btn-edit-location').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!formMutation) return;
            formMutation.action = btn.dataset.updateUrl || '';
            if (formMethod) formMethod.value = 'PUT';
            if (formModalTitle) formModalTitle.textContent = `✏️ 拠点の編集: ${btn.dataset.name || ''}`;

            if (inputName) inputName.value = btn.dataset.name || '';
            if (selectType) selectType.value = btn.dataset.locationTypeId || '';
            if (checkboxAllDay) checkboxAllDay.checked = btn.dataset.isAllDay === '1';
            if (checkboxActive) checkboxActive.checked = btn.dataset.isActive !== '0';
            if (inputNotes) inputNotes.value = btn.dataset.notes || '';

            let schedules = [];
            try {
                schedules = JSON.parse(btn.dataset.schedules || '[]');
            } catch (e) {
                console.error('Failed to parse schedules payload', e);
            }

            scheduleManager.renderFormSchedules(Array.isArray(schedules) ? schedules : []);
            openModal(formModal);
            inputName?.focus();
        });
    });

    document.querySelectorAll('.btn-view-location').forEach((btn) => {
        btn.addEventListener('click', () => {
            const name = btn.dataset.name || '';
            const typeLabel = btn.dataset.typeLabel || '未分類';
            const isAllDay = btn.dataset.isAllDay === '1';
            const isActive = btn.dataset.isActive !== '0';
            const notes = btn.dataset.notes || '—';
            const schedulesCount = btn.dataset.schedulesCount || '0';
            const dailyStatusLabel = btn.dataset.dailyStatusLabel || '—';
            const dailyStatusIcon = btn.dataset.dailyStatusIcon || '';
            const dailyStatusClass = btn.dataset.dailyStatusClass || 'bg-slate-100 text-slate-600 border-slate-200';

            if (detailTitle) detailTitle.textContent = `📍 拠点詳細: ${name}`;
            if (detailName) detailName.textContent = name;
            if (detailInactiveBadge) detailInactiveBadge.classList.toggle('hidden', isActive);
            if (detailTypeLabel) detailTypeLabel.textContent = typeLabel;
            if (detailIsAllDay) {
                detailIsAllDay.textContent = isAllDay
                    ? '終日利用可（常設保管等）'
                    : `時間枠指定 (${schedulesCount}枠設定中)`;
            }
            if (detailDailyStatus) {
                detailDailyStatus.className = `inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[11px] font-bold border mt-0.5 ${dailyStatusClass}`;
                detailDailyStatus.textContent = dailyStatusIcon ? `${dailyStatusIcon} ${dailyStatusLabel}` : dailyStatusLabel;
            }
            if (detailNotes) detailNotes.textContent = notes || '—';

            let detailSchedules = { upcoming: [], past: [] };
            try {
                detailSchedules = JSON.parse(btn.dataset.detailSchedules || '{"upcoming":[],"past":[]}');
            } catch (e) {
                console.error('Failed to parse detail schedules payload', e);
            }

            if (detailSchedulesAllDay && detailSchedulesSplit) {
                detailSchedulesAllDay.classList.toggle('hidden', !isAllDay);
                detailSchedulesSplit.classList.toggle('hidden', isAllDay);
            }

            if (!isAllDay) {
                renderDetailScheduleGroup(
                    Array.isArray(detailSchedules.upcoming) ? detailSchedules.upcoming : [],
                    detailUpcomingList,
                    detailUpcomingEmpty,
                    detailUpcomingCount,
                    false
                );
                renderDetailScheduleGroup(
                    Array.isArray(detailSchedules.past) ? detailSchedules.past : [],
                    detailPastList,
                    detailPastEmpty,
                    detailPastCount,
                    true
                );
            }

            openModal(detailModal);
        });
    });

    document.querySelectorAll('.btn-delete-location').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const locationName = btn.dataset.locationName || 'この拠点';
            const confirmed = await confirmDialog(
                `拠点「${locationName}」を削除してもよろしいですか？\n※ 紐づく時間帯スケジュール枠も同時に削除されます。`,
                { variant: 'danger', title: '拠点の削除確認', confirmText: '削除する' }
            );
            if (confirmed) {
                btn.closest('form')?.submit();
            }
        });
    });
}
