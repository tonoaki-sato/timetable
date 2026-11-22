/**
 * 拠点利用枠（スケジュール）行および詳細スケジュール群のDOM構築
 */
export function createScheduleManager({
    checkboxAllDay,
    schedulesContainer,
    schedulesBody,
    schedulesEmptyMsg,
    btnAddSchedule,
}) {
    let scheduleRowCounter = 0;

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

    return { renderFormSchedules };
}

/**
 * 詳細モーダル用の利用枠リストを XSS 安全に描画
 */
export function renderDetailScheduleGroup(items, listEl, emptyEl, countEl, isPastGroup) {
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
}
