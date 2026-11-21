import { confirmDialog } from './utils/ui.js';

/**
 * 拠点管理機能スクリプト (CSP準拠・外部JavaScriptモジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    initDragAndDropReorder();
    initLocationModals();
    initTypeModal();
});

/**
 * 1. ドラッグ＆ドロップによる表示順並び替え
 */
function initDragAndDropReorder() {
    const container = document.getElementById('locations-container');
    if (!container) return;

    let draggedItem = null;

    container.addEventListener('dragstart', (e) => {
        const item = e.target.closest('[data-location-id]');
        if (!item) return;

        draggedItem = item;
        item.classList.add('opacity-40', 'bg-indigo-50/70');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', item.dataset.locationId || '');
    });

    container.addEventListener('dragend', (e) => {
        const item = e.target.closest('[data-location-id]');
        if (item) {
            item.classList.remove('opacity-40', 'bg-indigo-50/70');
        }
        draggedItem = null;
    });

    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const targetItem = e.target.closest('[data-location-id]');
        if (!targetItem || targetItem === draggedItem) return;

        const rect = targetItem.getBoundingClientRect();
        const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
        container.insertBefore(draggedItem, next ? targetItem.nextSibling : targetItem);
    });

    container.addEventListener('drop', async (e) => {
        e.preventDefault();
        if (!draggedItem) return;

        const items = Array.from(container.querySelectorAll('[data-location-id]'));
        const locationIds = items.map((el) => parseInt(el.dataset.locationId, 10)).filter(Boolean);
        const reorderUrl = container.dataset.reorderUrl;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!reorderUrl || !csrfToken) return;

        try {
            const res = await fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ location_ids: locationIds }),
            });

            const data = await res.json();
            if (data.success) {
                showToast(data.message || '拠点の表示順を更新しました。', 'success');
            } else {
                showToast(data.message || '並び順の更新に失敗しました。', 'error');
            }
        } catch (err) {
            console.error('Reorder error:', err);
            showToast('通信エラーが発生しました。', 'error');
        }
    });
}

/**
 * 2. 拠点マスター 新規登録・編集・詳細モーダルおよび削除確認
 */
function initLocationModals() {
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

    const schedulesContainer = document.getElementById('schedules-form-container');
    const schedulesBody = document.getElementById('schedules-form-body');
    const schedulesEmptyMsg = document.getElementById('schedules-empty-message');
    const btnAddSchedule = document.getElementById('add-schedule-row-btn');

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

    let scheduleRowCounter = 0;

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

    const updateSchedulesEmptyState = () => {
        if (!schedulesBody || !schedulesEmptyMsg) return;
        const hasRows = schedulesBody.querySelectorAll('[data-schedule-row]').length > 0;
        schedulesEmptyMsg.classList.toggle('hidden', hasRows);
    };

    /**
     * 終日フラグに応じて利用枠フォームセクション表示と入力欄活性状態を切り替え
     * 非表示の required input によるフォーム送信ブロックを防止する
     */
    const setSchedulesFormVisibility = () => {
        if (!schedulesContainer || !checkboxAllDay) return;
        const isAllDay = checkboxAllDay.checked;
        schedulesContainer.classList.toggle('hidden', isAllDay);
        schedulesContainer.querySelectorAll('input, select').forEach((el) => {
            el.disabled = isAllDay;
        });
    };

    /**
     * 追加・編集モーダル内の利用枠行を XSS 安全な DOM API で構築
     */
    const createScheduleRow = (schedule = {}) => {
        const idx = scheduleRowCounter++;
        const tr = document.createElement('tr');
        tr.setAttribute('data-schedule-row', 'true');
        tr.className = schedule.is_past
            ? 'bg-slate-50/80 hover:bg-slate-100/70 transition'
            : 'hover:bg-slate-50/70 transition';

        // 1. 開始〜終了日時セル
        const tdDates = document.createElement('td');
        tdDates.className = 'py-2 px-2.5 align-top';

        if (schedule.id) {
            const hiddenId = document.createElement('input');
            hiddenId.type = 'hidden';
            hiddenId.name = `schedules[${idx}][id]`;
            hiddenId.value = String(schedule.id);
            tdDates.appendChild(hiddenId);
        }

        const dateWrap = document.createElement('div');
        dateWrap.className = 'flex flex-wrap items-center gap-1 font-mono';

        const inputStart = document.createElement('input');
        inputStart.type = 'datetime-local';
        inputStart.name = `schedules[${idx}][start_at]`;
        inputStart.value = schedule.start_at || '';
        inputStart.required = true;
        inputStart.className = 'px-2 py-1 bg-white border border-slate-300 rounded text-[11px] text-slate-800 focus:outline-hidden focus:ring-1 focus:ring-indigo-500 font-mono';

        const separator = document.createElement('span');
        separator.className = 'text-slate-400';
        separator.textContent = '〜';

        const inputEnd = document.createElement('input');
        inputEnd.type = 'datetime-local';
        inputEnd.name = `schedules[${idx}][end_at]`;
        inputEnd.value = schedule.end_at || '';
        inputEnd.required = true;
        inputEnd.className = 'px-2 py-1 bg-white border border-slate-300 rounded text-[11px] text-slate-800 focus:outline-hidden focus:ring-1 focus:ring-indigo-500 font-mono';

        dateWrap.append(inputStart, separator, inputEnd);

        if (schedule.is_past) {
            const pastBadge = document.createElement('span');
            pastBadge.className = 'px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 text-[10px] font-sans font-bold';
            pastBadge.textContent = '過去';
            dateWrap.appendChild(pastBadge);
        }
        tdDates.appendChild(dateWrap);

        // 2. 利用ステータスセル
        const tdStatus = document.createElement('td');
        tdStatus.className = 'py-2 px-2.5 align-top';
        const selectStatus = document.createElement('select');
        selectStatus.name = `schedules[${idx}][availability_status]`;
        selectStatus.required = true;
        selectStatus.className = 'px-2 py-1 bg-white border border-slate-300 rounded text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-1 focus:ring-indigo-500';

        [
            { value: 'available', label: '利用可（確保済）' },
            { value: 'unavailable', label: '未確保（調整中）' },
        ].forEach((opt) => {
            const optionEl = document.createElement('option');
            optionEl.value = opt.value;
            optionEl.textContent = opt.label;
            if ((schedule.availability_status || 'available') === opt.value) {
                optionEl.selected = true;
            }
            selectStatus.appendChild(optionEl);
        });
        tdStatus.appendChild(selectStatus);

        // 3. 削除ボタンセル
        const tdRemove = document.createElement('td');
        tdRemove.className = 'py-2 px-2.5 align-top text-right whitespace-nowrap';
        const btnRemove = document.createElement('button');
        btnRemove.type = 'button';
        btnRemove.setAttribute('data-action', 'remove-schedule-row');
        btnRemove.className = 'px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded text-xs font-bold transition cursor-pointer';
        btnRemove.textContent = '✕';
        btnRemove.title = 'この利用枠を削除';
        btnRemove.addEventListener('click', () => {
            tr.remove();
            updateSchedulesEmptyState();
        });
        tdRemove.appendChild(btnRemove);

        tr.append(tdDates, tdStatus, tdRemove);
        return tr;
    };

    const renderFormSchedules = (schedules = []) => {
        if (!schedulesBody) return;
        schedulesBody.innerHTML = '';
        schedules.forEach((sch) => {
            schedulesBody.appendChild(createScheduleRow(sch));
        });
        updateSchedulesEmptyState();
        setSchedulesFormVisibility();
    };

    /**
     * 詳細モーダル用の利用枠リストを XSS 安全に描画
     */
    const renderDetailScheduleGroup = (items, listEl, emptyEl, countEl, isPastGroup) => {
        if (!listEl || !emptyEl || !countEl) return;

        listEl.innerHTML = '';
        countEl.textContent = `${items.length}件`;

        if (items.length === 0) {
            listEl.classList.add('hidden');
            emptyEl.classList.remove('hidden');
            return;
        }

        listEl.classList.remove('hidden');
        emptyEl.classList.add('hidden');

        items.forEach((item) => {
            const card = document.createElement('div');
            card.className = isPastGroup
                ? 'p-2.5 rounded-xl border border-slate-200 bg-slate-50/80 text-slate-600 space-y-1'
                : 'p-2.5 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-1';

            const topRow = document.createElement('div');
            topRow.className = 'flex flex-wrap items-center justify-between gap-2';

            const leftGroup = document.createElement('div');
            leftGroup.className = 'flex flex-wrap items-center gap-1.5';

            const timeSpan = document.createElement('span');
            timeSpan.className = isPastGroup
                ? 'font-mono font-bold text-slate-600 text-xs'
                : 'font-mono font-bold text-slate-800 text-xs';
            timeSpan.textContent = `📅 ${item.start_at_display} 〜 ${item.end_at_display}`;
            leftGroup.appendChild(timeSpan);

            if (item.is_current) {
                const currentBadge = document.createElement('span');
                currentBadge.className = 'px-1.5 py-0.5 rounded bg-emerald-600 text-white text-[10px] font-bold';
                currentBadge.textContent = '現在';
                leftGroup.appendChild(currentBadge);
            }

            const statusBadge = document.createElement('span');
            statusBadge.className = `inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border ${item.status_badge_class || 'bg-slate-100 text-slate-600 border-slate-200'}`;
            statusBadge.textContent = item.status_label || '';

            topRow.append(leftGroup, statusBadge);
            card.appendChild(topRow);

            listEl.appendChild(card);
        });
    };

    if (checkboxAllDay) {
        checkboxAllDay.addEventListener('change', setSchedulesFormVisibility);
    }

    if (btnAddSchedule && schedulesBody) {
        btnAddSchedule.addEventListener('click', () => {
            const defaultDate = btnAddSchedule.dataset.defaultDate || '';
            const row = createScheduleRow({
                start_at: defaultDate ? `${defaultDate}T09:00` : '',
                end_at: defaultDate ? `${defaultDate}T17:00` : '',
                availability_status: 'available',
                is_past: false,
            });
            schedulesBody.appendChild(row);
            updateSchedulesEmptyState();
            setSchedulesFormVisibility();
        });
    }

    // 閉じるボタンおよびBackdropクリックの共通イベント
    document.querySelectorAll('.btn-close-modal, .modal-backdrop').forEach((el) => {
        el.addEventListener('click', () => {
            closeModal(detailModal);
            closeModal(formModal);
        });
    });

    // ESCキーでモーダルを閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal(detailModal);
            closeModal(formModal);
        }
    });

    // 新規登録モーダルを開く
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

            renderFormSchedules([]);
            openModal(formModal);
            inputName?.focus();
        });
    }

    // 編集モーダルを開く
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

            renderFormSchedules(Array.isArray(schedules) ? schedules : []);
            openModal(formModal);
            inputName?.focus();
        });
    });

    // 閲覧専用詳細モーダルを開く
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

    // 削除確認ダイアログ
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

/**
 * 3. 拠点タイプ（種別マスタ）登録・編集モーダル
 */
function initTypeModal() {
    const modal = document.getElementById('type-modal');
    const form = document.getElementById('type-modal-form');
    const modalTitle = document.getElementById('type-modal-title');
    const methodContainer = document.getElementById('type-form-method');

    if (!modal || !form) {
        return;
    }

    // 開くボタン
    document.querySelectorAll('[data-action="open-type-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            const storeUrl = btn.dataset.storeUrl;
            const updateUrl = btn.dataset.updateUrl;

            if (mode === 'create') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>🏷️</span><span>新しい拠点タイプの追加</span>';
                }
                form.action = storeUrl || form.action;
                if (methodContainer) {
                    methodContainer.innerHTML = '';
                }
                form.reset();
            } else if (mode === 'edit') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>🏷️</span><span>拠点タイプの編集</span>';
                }
                form.action = updateUrl;
                if (methodContainer) {
                    methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                }

                try {
                    const type = JSON.parse(btn.dataset.type || '{}');
                    const labelInput = document.getElementById('type_label');
                    const nameInput = document.getElementById('type_name');
                    const descInput = document.getElementById('type_description');
                    const orderInput = document.getElementById('type_display_order');
                    const activeInput = document.getElementById('type_is_active');

                    if (labelInput) labelInput.value = type.label || '';
                    if (nameInput) nameInput.value = type.name || '';
                    if (descInput) descInput.value = type.description || '';
                    if (orderInput) orderInput.value = type.display_order ?? 10;
                    if (activeInput) activeInput.checked = Boolean(type.is_active);
                } catch (e) {
                    console.error('Failed to parse type data', e);
                }
            }

            modal.classList.remove('hidden');
        });
    });

    // 閉じるボタン
    document.querySelectorAll('[data-action="close-type-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    });

    // 背景クリックで閉じる
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
}

/**
 * トースト通知の表示
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-emerald-800' : 'bg-red-800';
    toast.className = `${bgColor} text-white px-4 py-2.5 rounded-xl text-xs font-bold shadow-lg transition-all transform translate-y-2 opacity-0 pointer-events-auto flex items-center space-x-2`;
    toast.innerHTML = `<span>${type === 'success' ? '✅' : '⚠️'}</span><span>${message}</span>`;

    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
