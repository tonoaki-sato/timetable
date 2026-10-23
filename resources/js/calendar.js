/**
 * 総合カレンダー 対話スクリプト
 */
document.addEventListener('DOMContentLoaded', () => {
    initCalendar();
});

function initCalendar() {
    let currentDetailEvent = null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const newEventModal = document.getElementById('newEventModal');
    const eventDetailModal = document.getElementById('eventDetailModal');
    const newEventForm = document.getElementById('newEventForm');

    // 1. クリックイベント委任（イベント詳細、セルからの新規追加、モーダル開閉、クイック操作）
    document.addEventListener('click', (e) => {
        // イベントピルクリック -> 詳細モーダル
        const pill = e.target.closest('.event-pill');
        if (pill && pill.dataset.eventJson) {
            try {
                const eventData = JSON.parse(pill.dataset.eventJson);
                openEventDetailModal(eventData);
            } catch (err) {
                console.error('Failed to parse event JSON', err);
            }
            return;
        }

        // セル内「＋」ボタンクリック -> 新規予定モーダル
        const addBtn = e.target.closest('.add-event-cell-btn');
        if (addBtn && addBtn.dataset.date) {
            openNewEventModal(addBtn.dataset.date);
            return;
        }

        // 新規登録モーダルを開く
        if (e.target.closest('[data-action="open-new-event-modal"]')) {
            openNewEventModal();
            return;
        }

        // 新規登録モーダルを閉じる
        if (e.target.closest('[data-action="close-new-event-modal"]')) {
            closeNewEventModal();
            return;
        }

        // 詳細モーダルを閉じる
        if (e.target.closest('[data-action="close-event-detail-modal"]')) {
            closeEventDetailModal();
            return;
        }

        // 単日／期間モード切り替え
        const modeBtn = e.target.closest('[data-date-mode]');
        if (modeBtn) {
            switchDateMode(modeBtn.getAttribute('data-date-mode'));
            return;
        }

        // 主催者クイック決定ボタン
        if (e.target.closest('[data-action="save-quick-assign-host"]')) {
            saveQuickAssignHost();
            return;
        }

        // LINE告知文コピーボタン
        if (e.target.closest('[data-action="copy-detail-line-text"]')) {
            copyDetailLineText();
            return;
        }
    });

    // 2. カテゴリ変更の監視
    if (newEventForm) {
        newEventForm.addEventListener('change', (e) => {
            if (e.target.name === 'category') {
                handleCategoryChange(e.target.value);
            }
        });
    }

    // 3. モーダル操作関数
    function openNewEventModal(date = null, category = 'committee') {
        if (!newEventModal || !newEventForm) return;
        newEventForm.reset();

        const defaultDate = date || newEventModal.getAttribute('data-default-date') || '';
        const eventDate = document.getElementById('eventDate');
        const eventStartDate = document.getElementById('eventStartDate');
        const eventEndDate = document.getElementById('eventEndDate');

        if (eventDate) eventDate.value = defaultDate;
        if (eventStartDate) eventStartDate.value = defaultDate;
        if (eventEndDate) eventEndDate.value = defaultDate;

        const categoryRadio = newEventForm.querySelector(`input[name="category"][value="${category}"]`);
        if (categoryRadio) {
            categoryRadio.checked = true;
            handleCategoryChange(category);
        }

        newEventModal.classList.remove('hidden');
    }

    function closeNewEventModal() {
        if (newEventModal) newEventModal.classList.add('hidden');
    }

    function switchDateMode(mode) {
        const singleBox = document.getElementById('singleDateFields');
        const periodBox = document.getElementById('periodDateFields');
        const btnSingle = document.getElementById('btnModeSingle');
        const btnPeriod = document.getElementById('btnModePeriod');
        const eventDate = document.getElementById('eventDate');
        const eventStartDate = document.getElementById('eventStartDate');
        const eventEndDate = document.getElementById('eventEndDate');

        if (mode === 'period') {
            singleBox?.classList.add('hidden');
            periodBox?.classList.remove('hidden');
            eventDate?.removeAttribute('required');
            eventStartDate?.setAttribute('required', 'required');
            eventEndDate?.setAttribute('required', 'required');

            btnPeriod?.classList.add('bg-white', 'text-indigo-700', 'shadow-xs');
            btnPeriod?.classList.remove('text-slate-600');
            btnSingle?.classList.remove('bg-white', 'text-indigo-700', 'shadow-xs');
            btnSingle?.classList.add('text-slate-600');
        } else {
            singleBox?.classList.remove('hidden');
            periodBox?.classList.add('hidden');
            eventDate?.setAttribute('required', 'required');
            eventStartDate?.removeAttribute('required');
            eventEndDate?.removeAttribute('required');

            btnSingle?.classList.add('bg-white', 'text-indigo-700', 'shadow-xs');
            btnSingle?.classList.remove('text-slate-600');
            btnPeriod?.classList.remove('bg-white', 'text-indigo-700', 'shadow-xs');
            btnPeriod?.classList.add('text-slate-600');
        }
    }

    function handleCategoryChange(category) {
        if (!newEventForm || !newEventModal) return;
        const noticeContainer = document.getElementById('noticeNoteContainer');
        const timeFields = document.getElementById('timeFields');
        const hostLabel = document.getElementById('hostFieldLabel');
        const modalIcon = document.getElementById('modalCategoryIcon');
        const modeSwitchContainer = document.getElementById('modeSwitchContainer');
        const statusFieldContainer = document.getElementById('statusFieldContainer');
        const eventStaffId = document.getElementById('eventStaffId');
        const eventDate = document.getElementById('eventDate');

        const icons = {
            committee: '🤝',
            permit: '📑',
            briefing: '📢',
            operation: '🎪',
            other: '📌',
        };
        if (modalIcon) modalIcon.textContent = icons[category] || '📅';

        const meetingsStoreUrl = newEventModal.getAttribute('data-meetings-store-url');
        const annualSchedulesStoreUrl = newEventModal.getAttribute('data-annual-schedules-store-url');

        if (category === 'committee') {
            if (meetingsStoreUrl) newEventForm.action = meetingsStoreUrl;
            noticeContainer?.classList.remove('hidden');
            timeFields?.classList.remove('hidden');
            switchDateMode('single');
            modeSwitchContainer?.classList.add('hidden');
            statusFieldContainer?.classList.add('hidden');
            if (hostLabel) hostLabel.innerHTML = '主催者（スタッフ） <span class="text-slate-400 font-normal">（任意・未定可）</span>';
            if (eventStaffId) eventStaffId.name = 'staff_id';
            if (eventDate) eventDate.name = 'meeting_date';
        } else {
            if (annualSchedulesStoreUrl) newEventForm.action = annualSchedulesStoreUrl;
            noticeContainer?.classList.add('hidden');
            if (hostLabel) hostLabel.innerHTML = '担当スタッフ <span class="text-slate-400 font-normal">（任意・未定可）</span>';
            if (eventStaffId) eventStaffId.name = 'staff_id';
            if (eventDate) eventDate.name = 'start_date';
            modeSwitchContainer?.classList.remove('hidden');
            statusFieldContainer?.classList.remove('hidden');
        }
    }

    function openEventDetailModal(event) {
        currentDetailEvent = event;
        if (!eventDetailModal) return;

        const detailIcon = document.getElementById('detailIcon');
        const detailCategoryLabel = document.getElementById('detailCategoryLabel');
        const detailTitle = document.getElementById('detailTitle');
        const detailDatetime = document.getElementById('detailDatetime');
        const detailLocation = document.getElementById('detailLocation');
        const staffEl = document.getElementById('detailAssignedStaff');
        const quickAssignContainer = document.getElementById('quickAssignHostContainer');
        const detailDescription = document.getElementById('detailDescription');
        const lineSection = document.getElementById('detailLineShareContainer');
        const detailLineText = document.getElementById('detailLineText');
        const detailShowLink = document.getElementById('detailShowLink');
        const detailEditLink = document.getElementById('detailEditLink');

        if (detailIcon) detailIcon.textContent = event.icon || '📅';
        if (detailCategoryLabel) detailCategoryLabel.textContent = event.category_label || '予定';
        if (detailTitle) detailTitle.textContent = event.title;

        let datetimeStr = event.start_date;
        if (event.is_period && event.end_date) {
            datetimeStr += ` 〜 ${event.end_date} (期間)`;
        } else if (event.time) {
            datetimeStr += ` ${event.time}`;
        }
        if (detailDatetime) detailDatetime.textContent = `📅 ${datetimeStr}`;
        if (detailLocation) detailLocation.textContent = event.location ? `📍 ${event.location}` : '';

        if (staffEl && quickAssignContainer) {
            if (event.has_unassigned_host) {
                staffEl.innerHTML = '<span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">主催者・担当者 未定（後から設定可）</span>';
                quickAssignContainer.classList.remove('hidden');
            } else {
                staffEl.innerHTML = `<span class="text-slate-900 font-bold">👤 ${event.staff_name || '未設定'}</span>`;
                quickAssignContainer.classList.add('hidden');
            }
        }

        if (detailDescription) detailDescription.textContent = event.description || '詳細内容の登録はありません。';

        if (lineSection && detailLineText) {
            if (event.source === 'meeting' && event.line_share_text) {
                lineSection.classList.remove('hidden');
                detailLineText.textContent = event.line_share_text;
            } else {
                lineSection.classList.add('hidden');
            }
        }

        if (detailShowLink) detailShowLink.href = event.show_url || '#';
        if (detailEditLink) detailEditLink.href = event.edit_url || '#';

        eventDetailModal.classList.remove('hidden');
    }

    function closeEventDetailModal() {
        if (eventDetailModal) eventDetailModal.classList.add('hidden');
        currentDetailEvent = null;
    }

    function saveQuickAssignHost() {
        if (!currentDetailEvent) return;
        const select = document.getElementById('quickAssignStaffSelect');
        const staffId = select?.value;
        if (!staffId) {
            alert('設定するスタッフを選択してください。');
            return;
        }

        const isMeeting = currentDetailEvent.source === 'meeting';
        const url = isMeeting
            ? currentDetailEvent.edit_url.replace('/edit', '')
            : currentDetailEvent.edit_url.replace('/edit', '');

        showToast('主催者を更新中...', '⏳');

        const payload = isMeeting
            ? {
                _method: 'PUT',
                staff_id: staffId,
                title: currentDetailEvent.title,
                meeting_date: currentDetailEvent.start_date,
                start_time: currentDetailEvent.start_time,
                location: currentDetailEvent.location,
            }
            : {
                _method: 'PUT',
                staff_id: staffId,
                title: currentDetailEvent.title,
                start_date: currentDetailEvent.start_date,
                category: currentDetailEvent.category,
                status: currentDetailEvent.status,
            };

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        })
            .then(() => {
                showToast('主催者を設定しました！', '✔');
                setTimeout(() => {
                    window.location.reload();
                }, 600);
            })
            .catch(() => {
                window.location.reload();
            });
    }

    function copyDetailLineText() {
        if (!currentDetailEvent || !currentDetailEvent.line_share_text) return;
        navigator.clipboard.writeText(currentDetailEvent.line_share_text).then(() => {
            const btn = document.getElementById('copyLineTextBtn');
            if (btn) {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span>✔</span><span>コピー完了!</span>';
                btn.classList.replace('bg-emerald-600', 'bg-emerald-800');
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.replace('bg-emerald-800', 'bg-emerald-600');
                }, 2000);
            }
            showToast('LINE告知文をクリップボードにコピーしました！', '📋');
        });
    }

    function showToast(message, icon = '✔') {
        const toast = document.getElementById('toastNotification');
        const msgEl = document.getElementById('toastMessage');
        const iconEl = document.getElementById('toastIcon');
        if (!toast) return;

        if (msgEl) msgEl.textContent = message;
        if (iconEl) iconEl.textContent = icon;
        toast.classList.remove('hidden', 'translate-y-2');
        setTimeout(() => {
            toast.classList.add('hidden', 'translate-y-2');
        }, 3000);
    }

    // Escキーでモーダルを閉じる
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeNewEventModal();
            closeEventDetailModal();
        }
    });
}
