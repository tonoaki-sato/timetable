/**
 * 現場マイタイムテーブル用スクリプト
 * CSP準拠（外部ファイル・イベントリスナー・即時関数）
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // 1. 到着チェックイン処理
        const checkInBtn = document.getElementById('checkInBtn');
        if (checkInBtn) {
            checkInBtn.addEventListener('click', async function () {
                const endpoint = checkInBtn.dataset.endpoint;
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

                if (!endpoint) return;

                checkInBtn.disabled = true;
                const originalText = checkInBtn.innerHTML;
                checkInBtn.innerHTML = '<span>⏳</span><span>受付中...</span>';

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        const timeStr = data.time || new Date().toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' });

                        // 受付済みバッジに置換
                        const container = checkInBtn.parentElement;
                        if (container) {
                            container.innerHTML = `
                                <div class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                                    <span>✅</span>
                                    <span>受付済 (${timeStr})</span>
                                </div>
                            `;
                        }
                    } else {
                        alert('チェックインの記録に失敗しました。電波の良い場所で再度お試しください。');
                        checkInBtn.disabled = false;
                        checkInBtn.innerHTML = originalText;
                    }
                } catch (err) {
                    console.error('Checkin error:', err);
                    alert('通信エラーが発生しました。電波の良い場所で再度お試しください。');
                    checkInBtn.disabled = false;
                    checkInBtn.innerHTML = originalText;
                }
            });
        }

        // 2. 現場タスク着手・完了報告処理 (UC-403, イベント委譲で動的DOM置換にも対応)
        document.addEventListener('click', async function (e) {
            const btn = e.target.closest('.assignmentStatusBtn');
            if (!btn) return;

            const endpoint = btn.dataset.endpoint;
            const status = btn.dataset.status;
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

            if (!endpoint || !status) return;

            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span>⏳</span>';

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: status })
                });

                if (response.ok) {
                    await fetchRealtimeTimeline();
                } else {
                    const err = await response.json().catch(() => ({}));
                    alert(err.message || 'ステータスの更新に失敗しました。');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                console.error('Status update error:', err);
                alert('通信エラーが発生しました。電波の良い場所でお試しください。');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });

        // 3. 詳細アコーディオン開閉（イベント委譲）
        document.addEventListener('click', function (e) {
            const toggleBtn = e.target.closest('.taskDetailToggleBtn');
            if (!toggleBtn) return;

            const item = toggleBtn.closest('.rounded-xl');
            if (!item) return;

            const panel = item.querySelector('.taskDetailPanel');
            const icon = toggleBtn.querySelector('svg');

            if (panel) {
                const isHidden = panel.classList.contains('hidden');
                panel.classList.toggle('hidden');
                if (icon) {
                    icon.classList.toggle('rotate-180', isHidden);
                }
            }
        });

        // 4. URLコピーボタン
        const copyUrlBtn = document.getElementById('copyUrlBtn');
        if (copyUrlBtn) {
            copyUrlBtn.addEventListener('click', async function () {
                const url = window.location.href;
                try {
                    await navigator.clipboard.writeText(url);
                    const originalText = copyUrlBtn.innerHTML;
                    copyUrlBtn.innerHTML = '<span>✅</span><span>URLをコピーしました！</span>';
                    setTimeout(() => {
                        copyUrlBtn.innerHTML = originalText;
                    }, 2500);
                } catch (e) {
                    prompt('以下のURLをコピーして保存してください:', url);
                }
            });
        }

        // 5. select変更時の自動フォーム送信 (data-auto-submit, CSP対応)
        document.addEventListener('change', function (e) {
            const target = e.target;
            if (target && target.hasAttribute('data-auto-submit')) {
                target.form?.submit();
            }
        });

        // 5. リアルタイム自動更新エンジン
        let isSyncing = false;
        const syncText = document.getElementById('myshiftSyncText');

        async function fetchRealtimeTimeline() {
            if (isSyncing) return;
            isSyncing = true;

            if (syncText) {
                syncText.textContent = '同期中...';
            }

            try {
                const response = await fetch(window.location.href, {
                    method: 'GET',
                    headers: {
                        'Accept': 'text/html',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const text = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(text, 'text/html');

                    // タイムライン一覧の差し替え
                    const newContainer = doc.getElementById('myshiftTimelineContainer');
                    const currentContainer = document.getElementById('myshiftTimelineContainer');

                    if (newContainer && currentContainer) {
                        // 展開されていたパネルの状態（インデックス）を保持
                        const openIndices = new Set();
                        currentContainer.querySelectorAll('.rounded-xl').forEach((row, idx) => {
                            const panel = row.querySelector('.taskDetailPanel');
                            if (panel && !panel.classList.contains('hidden')) {
                                openIndices.add(idx);
                            }
                        });

                        currentContainer.innerHTML = newContainer.innerHTML;

                        // 展開状態を復元
                        if (openIndices.size > 0) {
                            currentContainer.querySelectorAll('.rounded-xl').forEach((row, idx) => {
                                if (openIndices.has(idx)) {
                                    const panel = row.querySelector('.taskDetailPanel');
                                    const icon = row.querySelector('.taskDetailToggleBtn svg');
                                    if (panel) panel.classList.remove('hidden');
                                    if (icon) icon.classList.add('rotate-180');
                                }
                            });
                        }
                    }

                    if (syncText) {
                        const now = new Date();
                        const timeStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
                        syncText.textContent = `${timeStr} 同期済`;
                    }
                }
            } catch (err) {
                console.warn('Realtime sync warning:', err);
                if (syncText) {
                    syncText.textContent = '再同期待機中';
                }
            } finally {
                isSyncing = false;
            }
        }

        // 毎分00秒・30秒の境界タイミングで自動同期
        let lastTriggeredSlot = null;
        setInterval(() => {
            if (document.hidden) return;

            const now = new Date();
            const sec = now.getSeconds();

            if (sec === 0 || sec === 30) {
                const slotKey = `${now.getHours()}:${now.getMinutes()}:${sec}`;
                if (lastTriggeredSlot !== slotKey) {
                    lastTriggeredSlot = slotKey;
                    fetchRealtimeTimeline();
                }
            }
        }, 1000);

        // スマホ画面の復帰（タブ復帰・スリープ解除）時に即時同期
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                fetchRealtimeTimeline();
            }
        });
    });
})();
