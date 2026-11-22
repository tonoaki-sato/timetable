/**
 * 運行情報一覧画面（/timetables）および詳細画面（/timetables/{id}）
 * ステータス絞り込みトグルボタン、管理メニュープルダウン、および登録・編集モーダルフォーム操作スクリプト
 * (CSP完全準拠・Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    initTimetableStatusFilters();
    initTimetableActionMenus();
    initTimetableFormModal();
});

/**
 * ステータス絞り込みトグル切り替え制御
 */
function initTimetableStatusFilters() {
    const filterContainer = document.getElementById('timetable-filter-chips');
    if (!filterContainer) {
        return;
    }

    const allChip = document.getElementById('chip-status-all');
    const statusChips = filterContainer.querySelectorAll('.timetable-status-chip:not(#chip-status-all)');
    const rows = document.querySelectorAll('.timetable-row');
    const emptyRow = document.getElementById('timetable-filter-empty-state');
    const visibleCountSpan = document.getElementById('visible-timetable-count');

    // 初期状態の設定（URLに特定の status が指定されていない場合、draft と active を ON、completed を OFF）
    const urlParams = new URLSearchParams(window.location.search);
    const initialStatusParam = urlParams.get('status');

    if (initialStatusParam) {
        statusChips.forEach((chip) => {
            const status = chip.getAttribute('data-status');
            const isActive = initialStatusParam === 'all' || initialStatusParam === status;
            setChipState(chip, isActive);
        });
    } else {
        statusChips.forEach((chip) => {
            const status = chip.getAttribute('data-status');
            const isActive = status === 'draft' || status === 'active';
            setChipState(chip, isActive);
        });
    }

    updateTableVisibility();

    // チップクリックイベントのハンドリング
    filterContainer.addEventListener('click', (e) => {
        const chip = e.target.closest('.timetable-status-chip');
        if (!chip) return;

        const status = chip.getAttribute('data-status');

        if (status === 'all') {
            // 「すべての運行日」ボタン: 現在全選択なら全解除、そうでなければ全選択
            const areAllSelected = isAllSelected();
            statusChips.forEach((c) => setChipState(c, !areAllSelected));
            setChipState(allChip, !areAllSelected);
        } else {
            // 個別ステータスボタンのトグル
            const isCurrentlyActive = isChipActive(chip);
            setChipState(chip, !isCurrentlyActive);
            setChipState(allChip, isAllSelected());
        }

        updateTableVisibility();
    });

    /**
     * チップがアクティブ（ON）かどうか判定
     */
    function isChipActive(chip) {
        return !chip.classList.contains('opacity-40');
    }

    /**
     * 全ステータスチップがアクティブかどうか判定
     */
    function isAllSelected() {
        return Array.from(statusChips).every((c) => isChipActive(c));
    }

    /**
     * チップの ON / OFF スタイルおよびアイコン更新
     */
    function setChipState(chip, active) {
        if (!chip) return;
        const iconSpan = chip.querySelector('.chip-icon');

        if (active) {
            chip.classList.remove('opacity-40', 'bg-slate-100', 'text-slate-600', 'border-slate-300');
            chip.classList.add('bg-white', 'text-indigo-700', 'border-indigo-400', 'shadow-2xs');
            if (iconSpan) {
                iconSpan.textContent = '✔';
                iconSpan.classList.remove('text-slate-400');
                iconSpan.classList.add('text-emerald-600');
            }
        } else {
            chip.classList.remove('bg-white', 'text-indigo-700', 'border-indigo-400', 'shadow-2xs');
            chip.classList.add('opacity-40', 'bg-slate-100', 'text-slate-600', 'border-slate-300');
            if (iconSpan) {
                iconSpan.textContent = '✕';
                iconSpan.classList.remove('text-emerald-600');
                iconSpan.classList.add('text-slate-400');
            }
        }
    }

    /**
     * テーブル行の表示/非表示および件数の更新
     */
    function updateTableVisibility() {
        const activeStatuses = new Set();
        statusChips.forEach((chip) => {
            if (isChipActive(chip)) {
                activeStatuses.add(chip.getAttribute('data-status'));
            }
        });

        setChipState(allChip, isAllSelected());

        let visibleCount = 0;
        rows.forEach((row) => {
            const rowStatus = row.getAttribute('data-status');
            if (activeStatuses.has(rowStatus)) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        if (visibleCountSpan) {
            visibleCountSpan.textContent = String(visibleCount);
        }

        if (emptyRow) {
            if (visibleCount === 0 && rows.length > 0) {
                emptyRow.classList.remove('hidden');
            } else {
                emptyRow.classList.add('hidden');
            }
        }
    }
}

/**
 * 管理メニューのプルダウン（ドロップダウン）開閉制御
 */
function initTimetableActionMenus() {
    const containers = document.querySelectorAll('.timetable-menu-container');
    if (containers.length === 0) {
        return;
    }

    containers.forEach((container) => {
        const trigger = container.querySelector('.timetable-menu-trigger');
        const dropdown = container.querySelector('.timetable-menu-dropdown');
        if (!trigger || !dropdown) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isCurrentlyOpen = !dropdown.classList.contains('hidden');

            closeAllMenus();

            if (!isCurrentlyOpen) {
                dropdown.classList.remove('hidden');
                trigger.setAttribute('aria-expanded', 'true');

                const rect = trigger.getBoundingClientRect();
                const menuWidth = dropdown.offsetWidth || 176;
                const menuHeight = dropdown.offsetHeight || 190;
                const gap = 4;

                let top = rect.bottom + gap;
                let left = rect.right - menuWidth;

                const spaceBelow = window.innerHeight - rect.bottom;
                const spaceAbove = rect.top;
                if (spaceBelow < menuHeight + gap && spaceAbove > menuHeight + gap) {
                    top = rect.top - menuHeight - gap;
                }

                if (left < 8) {
                    left = 8;
                } else if (left + menuWidth > window.innerWidth - 8) {
                    left = window.innerWidth - menuWidth - 8;
                }

                dropdown.style.top = `${Math.round(top)}px`;
                dropdown.style.left = `${Math.round(left)}px`;
            }
        });
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.timetable-menu-container')) {
            closeAllMenus();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllMenus();
        }
    });

    window.addEventListener('scroll', closeAllMenus, { capture: true, passive: true });
    window.addEventListener('resize', closeAllMenus);

    function closeAllMenus() {
        document.querySelectorAll('.timetable-menu-dropdown').forEach((menu) => {
            menu.classList.add('hidden');
            menu.style.top = '';
            menu.style.left = '';
        });
        document.querySelectorAll('.timetable-menu-trigger').forEach((trigger) => {
            trigger.setAttribute('aria-expanded', 'false');
        });
    }
}

/**
 * 運行情報 登録・編集・複製モーダル制御
 */
function initTimetableFormModal() {
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

    const roadContainer = document.getElementById('road-periods-container');
    const festivalContainer = document.getElementById('festival-periods-container');
    const btnAddRoad = document.getElementById('btn-add-road-row');
    const btnAddFestival = document.getElementById('btn-add-festival-row');
    const btnPresetRoad = document.getElementById('btn-preset-road');
    const btnPresetFestival = document.getElementById('btn-preset-festival');

    const openModal = () => {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    // 閉じるボタンおよびBackdropクリックイベント
    modal.querySelectorAll('.btn-close-timetable-modal, .modal-backdrop').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // ----------------------------------------------------
    // 道路・まつり時間帯の動的生成ヘルパー（単調増加カウンタ & XSS安全DOM構築）
    // ----------------------------------------------------
    let roadRowSeq = roadContainer ? roadContainer.querySelectorAll('.period-row').length : 0;
    let festivalRowSeq = festivalContainer ? festivalContainer.querySelectorAll('.period-row').length : 0;

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

    // 行削除イベント委任
    modal.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.btn-remove-row');
        if (removeBtn) {
            removeBtn.closest('.period-row')?.remove();
        }
    });

    // 行追加ボタン（単調増加カウンタで削除後の添字衝突を防止）
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

    // プリセットボタン
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

    // ----------------------------------------------------
    // 複製オプション連動
    // ----------------------------------------------------
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

    // ----------------------------------------------------
    // 3. 新規登録モーダルを開く
    // ----------------------------------------------------
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

    // ----------------------------------------------------
    // 4. 複製モーダルを開く
    // ----------------------------------------------------
    document.querySelectorAll('.btn-clone-timetable').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!form) return;
            const sourceId = btn.dataset.sourceId;
            form.action = btn.dataset.storeUrl || '/timetables';
            formMethod.value = 'POST';

            if (inputSourceId) inputSourceId.value = sourceId || '';
            if (sectionClone) sectionClone.classList.remove('hidden');

            // 複製元情報カードの更新
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

    // ----------------------------------------------------
    // 5. 編集モーダルを開く
    // ----------------------------------------------------
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

            // 複製セクションは編集時は非表示
            if (sectionClone) sectionClone.classList.add('hidden');

            if (inputTargetDate) inputTargetDate.value = btn.dataset.targetDate || '';
            if (inputStatus) inputStatus.value = btn.dataset.status || 'draft';
            if (inputName) inputName.value = name;
            if (inputStartTime) inputStartTime.value = btn.dataset.startTime || '08:00';
            if (inputEndTime) inputEndTime.value = btn.dataset.endTime || '18:00';
            if (inputDescription) inputDescription.value = btn.dataset.description || '';

            // 時間帯設定の反映
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

    // ----------------------------------------------------
    // 6. URLクエリによる自動モーダル起動 (?action=create, ?edit=ID, ?clone=ID)
    // ----------------------------------------------------
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
