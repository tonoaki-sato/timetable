// まつり総合管理システム アプリケーションスクリプト
document.addEventListener('DOMContentLoaded', () => {
    initRealtimeClock();
    initDashboardRealtime();
    initGlobalEventHandlers();
    initNavigation();
    initTimetablePeriodsForm();
});

/**
 * CSP対応: 宣言的属性（data-*）に基づくグローバルイベントハンドラ
 */
function initGlobalEventHandlers() {
    // 1. フォーム送信前の確認ダイアログ (data-confirm)
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form) return;
        const message = form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });

    // 2. ボタン単位の確認ダイアログ (button[data-confirm])
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-confirm], a[data-confirm]');
        if (!btn) return;
        const message = btn.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });

    // 3. select変更時の自動フォーム送信 (data-auto-submit)
    document.addEventListener('change', (e) => {
        const target = e.target;
        if (target && target.hasAttribute('data-auto-submit')) {
            target.form?.submit();
        }
    });

    // 4. 印刷アクション (data-action="print")
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="print"]')) {
            window.print();
        }
    });

    // 5. モバイルメニュー開閉 (data-toggle="mobile-menu")
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-toggle="mobile-menu"]')) {
            const modal = document.getElementById('mobileMenuModal');
            if (modal) {
                modal.classList.toggle('hidden');
            }
        }
    });

    // 6. 汎用ターゲット要素の表示切替 (data-toggle-target="#id" / data-close-target="#id")
    document.addEventListener('click', (e) => {
        const toggleBtn = e.target.closest('[data-toggle-target]');
        if (toggleBtn) {
            const selector = toggleBtn.getAttribute('data-toggle-target');
            if (selector) document.querySelector(selector)?.classList.toggle('hidden');
            return;
        }

        const closeBtn = e.target.closest('[data-close-target]');
        if (closeBtn) {
            const selector = closeBtn.getAttribute('data-close-target');
            if (selector) document.querySelector(selector)?.classList.add('hidden');
            return;
        }
    });
}

/**
 * ヘッダーナビゲーション（ドロップダウン & Escキー制御）
 */
function initNavigation() {
    const dropdowns = document.querySelectorAll('.nav-dropdown');

    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const menu = dropdown.querySelector('.dropdown-menu');

        if (!trigger || !menu) return;

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            const isCurrentlyOpen = !menu.classList.contains('hidden');

            // 他のすべてのドロップダウンを閉じる
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));

            if (!isCurrentlyOpen) {
                menu.classList.remove('hidden');
            }
        });
    });

    // 外側クリックでドロップダウンを閉じる
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        }
    });

    // Escキーで閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            const mobileMenu = document.getElementById('mobileMenuModal');
            if (mobileMenu) mobileMenu.classList.add('hidden');
        }
    });
}

/**
 * ダッシュボードの現在日時リアルタイム時計
 */
function initRealtimeClock() {
    const clockElement = document.getElementById('realtime-clock');
    if (!clockElement || window.__realtimeClockInitialized) return;
    window.__realtimeClockInitialized = true;

    const serverTimeMs = parseInt(clockElement.dataset.serverTime, 10);
    const clientTimeMs = Date.now();
    window.__dashboardTimeOffset = isNaN(serverTimeMs) ? 0 : (serverTimeMs - clientTimeMs);
    const dayNames = ['日', '月', '火', '水', '木', '金', '土'];

    function updateClock() {
        const offset = typeof window.__dashboardTimeOffset === 'number' ? window.__dashboardTimeOffset : 0;
        const now = new Date(Date.now() + offset);
        const month = now.getMonth() + 1;
        const date = now.getDate();
        const day = dayNames[now.getDay()];
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');

        clockElement.textContent = `${month}月${date}日 (${day}) ${hours}:${minutes}:${seconds}`;
    }

    updateClock();
    setInterval(updateClock, 1000);
}

/**
 * ダッシュボード主要3カード（道路交通規制・NOWタスク・NEXTタスク）のリアルタイム自動同期
 */
function initDashboardRealtime() {
    const rootEl = document.getElementById('dashboard-realtime-root');
    if (!rootEl || window.__dashboardRealtimeInitialized) return;
    window.__dashboardRealtimeInitialized = true;

    const realtimeUrl = rootEl.dataset.realtimeUrl;
    if (!realtimeUrl) return;

    const trafficContainer = document.getElementById('dashboard-card-traffic-regulation');
    const nowTasksContainer = document.getElementById('dashboard-card-now-tasks');
    const nextTasksContainer = document.getElementById('dashboard-card-next-tasks');
    const parkingContainer = document.getElementById('dashboard-card-parking-monitoring');
    const statusTextEl = document.getElementById('dashboard-realtime-status');
    const refreshBtn = document.getElementById('dashboard-manual-refresh');
    const refreshIcon = document.getElementById('dashboard-refresh-icon');

    let isFetching = false;
    let lastTriggeredSlotKey = null;

    function getTaskIdsSignature(container) {
        if (!container) return '';
        const ids = Array.from(container.querySelectorAll('[data-task-id]'))
            .map(el => el.getAttribute('data-task-id') || '')
            .filter(Boolean);
        return ids.length > 0 ? ids.join(',') : '__empty__';
    }

    function updateTaskCardWithAnimation(container, html) {
        if (!container || typeof html !== 'string') return;

        const prevTaskIds = new Set(
            Array.from(container.querySelectorAll('[data-task-id]'))
                .map(el => el.getAttribute('data-task-id'))
                .filter(Boolean)
        );
        const prevSignature = getTaskIdsSignature(container);

        container.innerHTML = html;

        const nextSignature = getTaskIdsSignature(container);
        if (prevSignature !== nextSignature) {
            container.classList.remove('animate-dashboard-card-swap');
            void container.offsetWidth;
            container.classList.add('animate-dashboard-card-swap');

            container.querySelectorAll('[data-task-id]').forEach(el => {
                const taskId = el.getAttribute('data-task-id');
                if (taskId && !prevTaskIds.has(taskId)) {
                    el.classList.add('animate-dashboard-task-enter');
                }
            });
        }
    }

    async function fetchRealtimeCards() {
        if (isFetching) return;
        isFetching = true;

        if (refreshIcon) {
            refreshIcon.classList.add('animate-spin');
        }
        if (statusTextEl) {
            statusTextEl.textContent = '同期中...';
        }

        try {
            const response = await fetch(realtimeUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (typeof data.server_time_ms === 'number') {
                window.__dashboardTimeOffset = data.server_time_ms - Date.now();
            }

            if (data.cards) {
                if (trafficContainer && typeof data.cards.traffic_regulation === 'string') {
                    trafficContainer.innerHTML = data.cards.traffic_regulation;
                }
                if (nowTasksContainer && typeof data.cards.now_tasks === 'string') {
                    updateTaskCardWithAnimation(nowTasksContainer, data.cards.now_tasks);
                }
                if (nextTasksContainer && typeof data.cards.next_tasks === 'string') {
                    updateTaskCardWithAnimation(nextTasksContainer, data.cards.next_tasks);
                }
                if (parkingContainer && typeof data.cards.parking_monitoring === 'string') {
                    parkingContainer.innerHTML = data.cards.parking_monitoring;
                }
            }

            if (statusTextEl) {
                statusTextEl.textContent = data.updated_at_label
                    ? `${data.updated_at_label} 同期済`
                    : '自動同期中';
            }
        } catch (err) {
            if (statusTextEl) {
                statusTextEl.textContent = '再接続待機中';
            }
        } finally {
            isFetching = false;
            if (refreshIcon) {
                refreshIcon.classList.remove('animate-spin');
            }
        }
    }

    // 1. 毎分00秒（分境界）および30秒のタイミングで自動同期
    setInterval(() => {
        if (document.hidden) return;

        const offset = typeof window.__dashboardTimeOffset === 'number' ? window.__dashboardTimeOffset : 0;
        const now = new Date(Date.now() + offset);
        const seconds = now.getSeconds();

        if (seconds === 0 || seconds === 30) {
            const slotKey = `${now.getHours()}:${now.getMinutes()}:${seconds}`;
            if (lastTriggeredSlotKey !== slotKey) {
                lastTriggeredSlotKey = slotKey;
                fetchRealtimeCards();
            }
        }
    }, 1000);

    // 2. バックグラウンドからタブ復帰した際に即時同期
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            fetchRealtimeCards();
        }
    });

    // 3. 手動更新ボタンクリック時に即時同期
    refreshBtn?.addEventListener('click', () => {
        fetchRealtimeCards();
    });
}

/**
 * タイムテーブル作成・編集画面の期間（道路・まつり時間帯）動的追加・削除 (CSP完全準拠)
 */
function initTimetablePeriodsForm() {
    const roadContainer = document.getElementById('road-periods-container');
    const festivalContainer = document.getElementById('festival-periods-container');
    if (!roadContainer && !festivalContainer) return;

    function createRow(type, index, start = '', end = '', label = '', status = '') {
        const defaultLabel = type === 'road' ? '通行止め' : '開催中';
        const defaultStatus = type === 'road' ? 'closed' : 'active';
        const finalLabel = label || defaultLabel;
        const finalStatus = status || defaultStatus;

        const row = document.createElement('div');
        row.className = 'period-row grid grid-cols-12 gap-2 items-center bg-white p-2 rounded-lg border border-slate-200 shadow-2xs';
        row.innerHTML = `
            <input type="hidden" name="${type}_periods[${index}][status]" value="${finalStatus}">
            <div class="col-span-3 sm:col-span-3">
                <label class="block text-[10px] text-slate-400 font-bold mb-0.5">開始時刻</label>
                <input type="time" name="${type}_periods[${index}][start_time]" value="${start}" required
                    class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs font-mono">
            </div>
            <div class="col-span-3 sm:col-span-3">
                <label class="block text-[10px] text-slate-400 font-bold mb-0.5">終了時刻</label>
                <input type="time" name="${type}_periods[${index}][end_time]" value="${end}" required
                    class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs font-mono">
            </div>
            <div class="col-span-5 sm:col-span-5">
                <label class="block text-[10px] text-slate-400 font-bold mb-0.5">表示文言</label>
                <input type="text" name="${type}_periods[${index}][label]" value="${finalLabel}" required
                    placeholder="例: ${finalLabel}"
                    class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs">
            </div>
            <div class="col-span-1 sm:col-span-1 flex items-end justify-center pt-3">
                <button type="button" class="btn-remove-row text-slate-400 hover:text-red-600 transition p-1 text-sm font-bold cursor-pointer" title="削除">
                    ✕
                </button>
            </div>
        `;
        return row;
    }

    // 行削除イベント（イベント委譲）
    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('btn-remove-row')) {
            const row = e.target.closest('.period-row');
            if (row) {
                row.remove();
            }
        }
    });

    // 道路時間帯追加
    document.getElementById('btn-add-road-row')?.addEventListener('click', function () {
        if (!roadContainer) return;
        const nextIndex = roadContainer.querySelectorAll('.period-row').length;
        roadContainer.appendChild(createRow('road', nextIndex, '10:00', '18:00', '通行止め', 'closed'));
    });

    // 道路プリセット（10:00〜18:00 通行止め）
    document.getElementById('btn-preset-road')?.addEventListener('click', function () {
        if (!roadContainer) return;
        roadContainer.innerHTML = '';
        roadContainer.appendChild(createRow('road', 0, '10:00', '18:00', '通行止め', 'closed'));
    });

    // まつり時間帯追加
    document.getElementById('btn-add-festival-row')?.addEventListener('click', function () {
        if (!festivalContainer) return;
        const nextIndex = festivalContainer.querySelectorAll('.period-row').length;
        festivalContainer.appendChild(createRow('festival', nextIndex, '11:00', '17:00', '開催中', 'active'));
    });

    // まつりプリセット（9:00〜11:00 ござ市受付開始 / 11:00〜17:00 開催中）
    document.getElementById('btn-preset-festival')?.addEventListener('click', function () {
        if (!festivalContainer) return;
        festivalContainer.innerHTML = '';
        festivalContainer.appendChild(createRow('festival', 0, '09:00', '11:00', 'ござ市受付開始', 'active'));
        festivalContainer.appendChild(createRow('festival', 1, '11:00', '17:00', '開催中', 'active'));
    });
}
