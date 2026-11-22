/**
 * 道路規制・まつり時間帯の動的生成ヘルパー（単調増加カウンタ & XSS安全DOM構築）
 */
function createTimeField(labelText, inputName, inputValue) {
    const col = document.createElement('div');
    col.className = 'col-span-3 sm:col-span-3';

    const label = document.createElement('label');
    label.className = 'block text-[10px] text-slate-400 font-bold mb-0.5';
    label.textContent = labelText;

    const input = document.createElement('input');
    input.type = 'time';
    input.name = inputName;
    input.value = inputValue;
    input.required = true;
    input.className = 'w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs font-mono';

    col.appendChild(label);
    col.appendChild(input);
    return col;
}

function createPeriodRow(type, index, period = {}) {
    const isRoad = type === 'road';
    const prefix = isRoad ? 'road_periods' : 'festival_periods';
    const defaultStatus = isRoad ? 'closed' : 'active';
    const defaultStart = isRoad ? '10:00' : '11:00';
    const defaultEnd = isRoad ? '18:00' : '17:00';
    const defaultLabel = isRoad ? '通行止め' : '開催中';

    const row = document.createElement('div');
    row.className = 'period-row grid grid-cols-12 gap-2 items-center bg-white p-2 rounded-lg border border-slate-200 shadow-2xs';

    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = `${prefix}[${index}][status]`;
    statusInput.value = period.status || defaultStatus;
    row.appendChild(statusInput);

    row.appendChild(createTimeField('開始時刻', `${prefix}[${index}][start_time]`, (period.start_time || defaultStart).substring(0, 5)));
    row.appendChild(createTimeField('終了時刻', `${prefix}[${index}][end_time]`, (period.end_time || defaultEnd).substring(0, 5)));

    const labelCol = document.createElement('div');
    labelCol.className = 'col-span-5 sm:col-span-5';

    const labelEl = document.createElement('label');
    labelEl.className = 'block text-[10px] text-slate-400 font-bold mb-0.5';
    labelEl.textContent = '表示文言';

    const labelInput = document.createElement('input');
    labelInput.type = 'text';
    labelInput.name = `${prefix}[${index}][label]`;
    labelInput.value = period.label || defaultLabel;
    labelInput.required = true;
    labelInput.placeholder = `例: ${defaultLabel}`;
    labelInput.className = 'w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs';

    labelCol.appendChild(labelEl);
    labelCol.appendChild(labelInput);
    row.appendChild(labelCol);

    const removeCol = document.createElement('div');
    removeCol.className = 'col-span-1 sm:col-span-1 flex items-end justify-center pt-3';

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn-remove-row text-slate-400 hover:text-red-600 transition p-1 text-sm font-bold cursor-pointer';
    removeBtn.title = '削除';
    removeBtn.textContent = '✕';

    removeCol.appendChild(removeBtn);
    row.appendChild(removeCol);

    return row;
}

export function createPeriodRowsController(modal) {
    const roadContainer = document.getElementById('road-periods-container');
    const festivalContainer = document.getElementById('festival-periods-container');
    const btnAddRoad = document.getElementById('btn-add-road-row');
    const btnAddFestival = document.getElementById('btn-add-festival-row');
    const btnPresetRoad = document.getElementById('btn-preset-road');
    const btnPresetFestival = document.getElementById('btn-preset-festival');

    let roadRowSeq = roadContainer ? roadContainer.querySelectorAll('.period-row').length : 0;
    let festivalRowSeq = festivalContainer ? festivalContainer.querySelectorAll('.period-row').length : 0;

    function setRoadPeriods(periods = []) {
        if (!roadContainer) return;
        roadContainer.innerHTML = '';
        roadRowSeq = 0;
        if (periods.length === 0) return;
        periods.forEach((p) => {
            roadContainer.appendChild(createPeriodRow('road', roadRowSeq++, p));
        });
    }

    function setFestivalPeriods(periods = []) {
        if (!festivalContainer) return;
        festivalContainer.innerHTML = '';
        festivalRowSeq = 0;
        if (periods.length === 0) return;
        periods.forEach((p) => {
            festivalContainer.appendChild(createPeriodRow('festival', festivalRowSeq++, p));
        });
    }

    modal.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.btn-remove-row');
        if (removeBtn) {
            removeBtn.closest('.period-row')?.remove();
        }
    });

    if (btnAddRoad) {
        btnAddRoad.addEventListener('click', () => {
            if (!roadContainer) return;
            roadContainer.appendChild(createPeriodRow('road', roadRowSeq++));
        });
    }

    if (btnAddFestival) {
        btnAddFestival.addEventListener('click', () => {
            if (!festivalContainer) return;
            festivalContainer.appendChild(createPeriodRow('festival', festivalRowSeq++));
        });
    }

    if (btnPresetRoad) {
        btnPresetRoad.addEventListener('click', () => {
            setRoadPeriods([{ start_time: '10:00', end_time: '18:00', label: '通行止め', status: 'closed' }]);
        });
    }

    if (btnPresetFestival) {
        btnPresetFestival.addEventListener('click', () => {
            setFestivalPeriods([
                { start_time: '11:00', end_time: '17:00', label: '開催中', status: 'active' },
            ]);
        });
    }

    return { setRoadPeriods, setFestivalPeriods };
}
