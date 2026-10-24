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
                                <div class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-300">
                                    <span>✅</span>
                                    <span>受付済み (${timeStr})</span>
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

        // 2. LINEワンタップ連携処理 (UC-106 パターンB)
        const lineBindBtn = document.getElementById('lineBindBtn');
        if (lineBindBtn) {
            lineBindBtn.addEventListener('click', async function () {
                const endpoint = lineBindBtn.dataset.bindEndpoint;
                const liffId = lineBindBtn.dataset.liffId;
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

                if (!endpoint) return;

                if (!liffId || typeof liff === 'undefined') {
                    alert('LINE連携機能の初期化情報が見つかりません。');
                    return;
                }

                lineBindBtn.disabled = true;
                const originalText = lineBindBtn.innerHTML;
                lineBindBtn.innerHTML = '<span>⏳</span><span>連携中...</span>';

                try {
                    await liff.init({ liffId: liffId });

                    if (!liff.isLoggedIn()) {
                        liff.login({ redirectUri: window.location.href });
                        return;
                    }

                    const profile = await liff.getProfile();
                    const payload = {
                        line_user_id: profile.userId,
                        line_display_name: profile.displayName || null,
                        line_picture_url: profile.pictureUrl || null
                    };

                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (response.ok) {
                        const data = await response.json();
                        // バナーを非表示にしてヘッダーにバッジを追加
                        const banner = document.getElementById('lineBindBanner');
                        if (banner) banner.remove();

                        const profileArea = document.getElementById('headerProfileArea');
                        if (profileArea && data.line_display_name) {
                            const badge = document.createElement('span');
                            badge.id = 'headerLineBadge';
                            badge.className = 'inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-[#06C755]/20 text-[#06C755] border border-[#06C755]/30';
                            badge.textContent = data.line_display_name;
                            profileArea.appendChild(badge);
                        }

                        alert('LINEアカウントとの連携が完了しました！');
                    } else {
                        const errData = await response.json().catch(() => ({}));
                        alert(errData.message || 'LINE連携に失敗しました。');
                        lineBindBtn.disabled = false;
                        lineBindBtn.innerHTML = originalText;
                    }
                } catch (err) {
                    console.error('Line bind error:', err);
                    alert('LINE連携処理中にエラーが発生しました。');
                    lineBindBtn.disabled = false;
                    lineBindBtn.innerHTML = originalText;
                }
            });
        }

        // 3. 現場タスク着手・完了報告処理 (UC-403)
        const assignmentStatusBtns = document.querySelectorAll('.assignmentStatusBtn');
        assignmentStatusBtns.forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const endpoint = btn.dataset.endpoint;
                const status = btn.dataset.status;
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

                if (!endpoint || !status) return;

                btn.disabled = true;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span>⏳</span><span>更新中...</span>';

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
                        // 画面全体を再読み込みして最新のNow & Nextおよびタイムラインを反映
                        window.location.reload();
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

        // 5. 最新情報に更新ボタン
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                window.location.reload();
            });
        }
    });
})();
