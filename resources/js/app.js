import { confirmDialog, showToast } from './utils/ui.js';

// まつり総合管理システム アプリケーションスクリプト
document.addEventListener('DOMContentLoaded', () => {
    initRealtimeClock();
    initDashboardRealtime();
    initGlobalEventHandlers();
    initNavigation();
    initQuickSearch();
    initTimetablePeriodsForm();
    initProgressBars();
});

/**
 * CSP対応: 宣言的属性（data-*）に基づくグローバルイベントハンドラ
 */
function initGlobalEventHandlers() {
    // 1. フォーム送信前の確認ダイアログ (data-confirm) & 二重送信防止 (data-disable-on-submit)
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form) return;
        const message = form.getAttribute('data-confirm');
        if (message && form.dataset.confirmBypassed !== '1') {
            e.preventDefault();
            e.stopImmediatePropagation();
            confirmDialog(message, { variant: 'danger' }).then((ok) => {
                if (ok) {
                    form.dataset.confirmBypassed = '1';
                    form.requestSubmit();
                }
            });
            return;
        }
        if (form.dataset.confirmBypassed === '1') {
            delete form.dataset.confirmBypassed;
        }

        if (form.hasAttribute('data-disable-on-submit')) {
            const loadingText = form.getAttribute('data-disable-on-submit') || '送信中...';
            const submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitBtns.forEach((btn) => {
                btn.disabled = true;
                btn.classList.add('opacity-70', 'cursor-not-allowed');
                if (btn.tagName === 'BUTTON') {
                    btn.textContent = loadingText;
                } else {
                    btn.value = loadingText;
                }
            });
        }
    });

    // 2. ボタン・リンク単位の確認ダイアログ (button[data-confirm], a[data-confirm])
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-confirm], a[data-confirm]');
        if (!btn) return;
        if (btn.dataset.confirmBypassed === '1') {
            delete btn.dataset.confirmBypassed;
            return;
        }
        const message = btn.getAttribute('data-confirm');
        if (!message) return;

        e.preventDefault();
        e.stopImmediatePropagation();
        confirmDialog(message, { variant: 'danger' }).then((ok) => {
            if (!ok) return;
            if (btn.tagName === 'A' && btn.href) {
                window.location.href = btn.href;
                return;
            }
            if (btn.form) {
                btn.form.dataset.confirmBypassed = '1';
                btn.dataset.confirmBypassed = '1';
                btn.form.requestSubmit(btn);
                return;
            }
            btn.dataset.confirmBypassed = '1';
            btn.click();
        });
    });

    // 3. select変更時の自動フォーム送信 (data-auto-submit) & ページ遷移 (data-action="navigate-on-change")
    document.addEventListener('change', (e) => {
        const target = e.target;
        if (target && target.hasAttribute('data-auto-submit')) {
            target.form?.submit();
        }
        if (target && target.matches('select[data-action="navigate-on-change"]') && target.value) {
            window.location.href = target.value;
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

        // 7. 入力値クリア (data-clear-target="inputId")
        const clearBtn = e.target.closest('[data-clear-target]');
        if (clearBtn) {
            const targetId = clearBtn.getAttribute('data-clear-target');
            const targetInput = document.getElementById(targetId);
            if (targetInput) {
                targetInput.value = '';
                targetInput.focus();
            }
            return;
        }

        // 8. アコーディオン一括開閉 (data-action="toggle-accordions", data-target="#containerId")
        const accordionBtn = e.target.closest('[data-action="toggle-accordions"]');
        if (accordionBtn) {
            const containerSelector = accordionBtn.getAttribute('data-target') || '#task-accordions';
            const container = document.querySelector(containerSelector);
            if (container) {
                const detailsList = container.querySelectorAll('details');
                const shouldOpen = accordionBtn.getAttribute('data-state') !== 'open';
                detailsList.forEach(d => {
                    d.open = shouldOpen;
                });
                accordionBtn.setAttribute('data-state', shouldOpen ? 'open' : 'closed');
                const label = accordionBtn.querySelector('[data-label]');
                if (label) {
                    label.textContent = shouldOpen ? 'すべて折りたたむ' : 'すべて開く';
                }
                const icon = accordionBtn.querySelector('[data-icon]');
                if (icon) {
                    icon.textContent = shouldOpen ? '🔼' : '🔽';
                }
            }
            return;
        }

        // 9. アラート通知閉じる (data-dismiss-alert)
        const dismissAlertBtn = e.target.closest('[data-dismiss-alert]');
        if (dismissAlertBtn) {
            const alertBox = dismissAlertBtn.closest('[data-alert-box]');
            if (alertBox) {
                alertBox.remove();
            }
            return;
        }

        // 10. パスワード表示/非表示トグル (data-toggle-password="inputId")
        const togglePwBtn = e.target.closest('[data-toggle-password]');
        if (togglePwBtn) {
            const targetId = togglePwBtn.getAttribute('data-toggle-password');
            const input = targetId ? document.getElementById(targetId) : null;
            if (input) {
                const isMasked = input.getAttribute('type') === 'password';
                input.setAttribute('type', isMasked ? 'text' : 'password');
                togglePwBtn.setAttribute('aria-pressed', isMasked ? 'true' : 'false');
                togglePwBtn.setAttribute('aria-label', isMasked ? 'パスワードを隠す' : 'パスワードを表示');
                togglePwBtn.textContent = isMasked ? '隠す' : '表示';
            }
            return;
        }

        // 11. ページ再読み込みアクション (data-action="reload")
        if (e.target.closest('[data-action="reload"]')) {
            window.location.reload();
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

    // Escキーで閉じる（ドロップダウン / モバイルメニュー / 汎用ダイアログモーダル）
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            const mobileMenu = document.getElementById('mobileMenuModal');
            if (mobileMenu) mobileMenu.classList.add('hidden');
            document.querySelectorAll('[role="dialog"].fixed.inset-0:not(.hidden)').forEach((dialog) => {
                if (dialog.id === 'mobileMenuModal' || dialog.id === 'appConfirmModal') return;
                dialog.classList.add('hidden');
            });
        }
    });
}

/**
 * グローバル横断検索モーダル (Ctrl+K / Cmd+K)
 */
function initQuickSearch() {
    const modal = document.getElementById('quickSearchModal');
    const input = document.getElementById('quick-search-input');
    const resultsEl = document.getElementById('quick-search-results');
    const statusEl = document.getElementById('quick-search-status');
    if (!modal || !input || !resultsEl) return;

    const searchUrl = modal.dataset.searchUrl || '/search/quick';
    let debounceTimer = null;
    let activeController = null;
    let selectedIndex = -1;

    const openModal = () => {
        modal.classList.remove('hidden');
        selectedIndex = -1;
        setTimeout(() => {
            input.focus();
            input.select();
        }, 10);
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        selectedIndex = -1;
    };

    const updateActiveHighlight = () => {
        const links = resultsEl.querySelectorAll('a[data-quick-result-item]');
        links.forEach((link, idx) => {
            if (idx === selectedIndex) {
                link.classList.add('bg-indigo-50', 'border-indigo-200');
                link.scrollIntoView({ block: 'nearest' });
            } else {
                link.classList.remove('bg-indigo-50', 'border-indigo-200');
            }
        });
    };

    const renderEmptyPrompt = (message) => {
        resultsEl.innerHTML = '';
        const box = document.createElement('div');
        box.className = 'py-8 text-center text-xs text-slate-400';
        box.textContent = message;
        resultsEl.appendChild(box);
        if (statusEl) statusEl.textContent = '↑↓ キーで移動 · Enter で開く';
    };

    const renderResults = (payload) => {
        resultsEl.innerHTML = '';
        selectedIndex = -1;

        const groups = Array.isArray(payload.groups) ? payload.groups : [];
        if (groups.length === 0) {
            renderEmptyPrompt(`「${payload.query || ''}」に一致するデータは見つかりませんでした。`);
            if (statusEl) statusEl.textContent = '0件ヒット';
            return;
        }

        groups.forEach((group) => {
            const section = document.createElement('div');
            section.className = 'py-1.5';

            const heading = document.createElement('div');
            heading.className = 'px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center space-x-1';
            heading.textContent = `${group.icon || ''} ${group.label || ''} (${group.items?.length || 0})`;
            section.appendChild(heading);

            const list = document.createElement('div');
            list.className = 'space-y-0.5';

            (group.items || []).forEach((item) => {
                const link = document.createElement('a');
                link.href = item.url || '#';
                link.setAttribute('data-quick-result-item', '1');
                link.className = 'flex items-center justify-between gap-2 px-3 py-2 rounded-xl border border-transparent hover:bg-indigo-50/70 transition';

                const left = document.createElement('div');
                left.className = 'min-w-0 flex-1';

                const title = document.createElement('div');
                title.className = 'text-xs font-bold text-slate-800 truncate';
                title.textContent = `${item.icon || ''} ${item.title || ''}`;

                const sub = document.createElement('div');
                sub.className = 'text-[11px] text-slate-500 truncate mt-0.5';
                sub.textContent = item.subtitle || '';

                left.appendChild(title);
                left.appendChild(sub);
                link.appendChild(left);

                if (item.badge) {
                    const badge = document.createElement('span');
                    badge.className = 'shrink-0 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200';
                    badge.textContent = item.badge;
                    link.appendChild(badge);
                }

                list.appendChild(link);
            });

            section.appendChild(list);
            resultsEl.appendChild(section);
        });

        if (statusEl) statusEl.textContent = `${payload.total || 0}件ヒット · ↑↓ キーで移動 · Enter で開く`;
    };

    const runSearch = async (query) => {
        const trimmed = query.trim();
        if (trimmed === '') {
            if (activeController) activeController.abort();
            renderEmptyPrompt('検索キーワードを入力してください（スタッフ・出展者・機材・予定・タスク）');
            return;
        }

        if (activeController) activeController.abort();
        activeController = new AbortController();

        if (statusEl) statusEl.textContent = '検索中...';

        try {
            const res = await fetch(`${searchUrl}?q=${encodeURIComponent(trimmed)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: activeController.signal,
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            renderResults(data);
        } catch (err) {
            if (err.name === 'AbortError') return;
            renderEmptyPrompt('検索中にエラーが発生しました。');
            showToast('クイック検索の通信に失敗しました。', 'error');
        }
    };

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="open-quick-search"]')) {
            openModal();
            return;
        }
        if (e.target.closest('[data-close-quick-search]')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (modal.classList.contains('hidden')) {
                openModal();
            } else {
                closeModal();
            }
        }
    });

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            runSearch(input.value);
        }, 220);
    });

    input.addEventListener('keydown', (e) => {
        const links = Array.from(resultsEl.querySelectorAll('a[data-quick-result-item]'));
        if (links.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = (selectedIndex + 1) % links.length;
            updateActiveHighlight();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + links.length) % links.length;
            updateActiveHighlight();
        } else if (e.key === 'Enter' && selectedIndex >= 0 && links[selectedIndex]) {
            e.preventDefault();
            window.location.href = links[selectedIndex].href;
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
 * ダッシュボードのサーバー時刻・接続同期（軽量ハートビート）
 */
function initDashboardRealtime() {
    const rootEl = document.getElementById('dashboard-realtime-root');
    if (!rootEl || window.__dashboardRealtimeInitialized) return;
    window.__dashboardRealtimeInitialized = true;

    const realtimeUrl = rootEl.dataset.realtimeUrl;
    if (!realtimeUrl) return;

    const statusTextEl = document.getElementById('dashboard-realtime-status');
    const refreshBtn = document.getElementById('dashboard-manual-refresh');
    const refreshIcon = document.getElementById('dashboard-refresh-icon');

    let isFetching = false;

    async function fetchHeartbeat() {
        if (isFetching) return;
        isFetching = true;

        if (refreshIcon) refreshIcon.classList.add('animate-spin');
        if (statusTextEl) statusTextEl.textContent = '同期中...';

        try {
            const response = await fetch(realtimeUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            if (typeof data.server_time_ms === 'number') {
                window.__dashboardTimeOffset = data.server_time_ms - Date.now();
            }
            if (statusTextEl) {
                statusTextEl.textContent = data.updated_at_label ? `${data.updated_at_label} 同期済` : '正常稼働中';
            }
        } catch (err) {
            if (statusTextEl) statusTextEl.textContent = '再接続待機中';
        } finally {
            isFetching = false;
            if (refreshIcon) refreshIcon.classList.remove('animate-spin');
        }
    }

    refreshBtn?.addEventListener('click', () => {
        fetchHeartbeat();
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
        const nextIndex = Date.now();
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
        const nextIndex = Date.now();
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

/**
 * CSP対応: data-percent 属性に基づく動的プログレスバーの初期化 (CSSOM)
 */
function initProgressBars(root = document) {
    const bars = root.querySelectorAll('.progress-bar-fill[data-percent]');
    bars.forEach((bar) => {
        const rawPercent = parseFloat(bar.getAttribute('data-percent')) || 0;
        const clampedPercent = Math.min(100, Math.max(0, rawPercent));
        bar.style.width = `${clampedPercent}%`;
    });
}

window.initProgressBars = initProgressBars;
