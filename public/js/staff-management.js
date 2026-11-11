/**
 * 本部スタッフ管理画面用スクリプト
 * CSP準拠（外部ファイル・イベントリスナー・即時関数）
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // 1. LINE募集案内文のコピー
        const copyInviteBtn = document.getElementById('copyInviteBtn');
        if (copyInviteBtn) {
            copyInviteBtn.addEventListener('click', async function () {
                const joinUrl = copyInviteBtn.dataset.joinUrl || window.location.origin + '/join';
                const message = [
                    '【まつりスタッフ登録のお願い】',
                    'スタッフ・ボランティアの皆様、お疲れ様です！',
                    '以下のリンクより、スタッフ登録をお願いいたします（お名前の入力のみ・約15秒）。',
                    '登録後、ご自身の当日タイムテーブル・担当シフトを確認できます。',
                    '',
                    '👉 スタッフ登録はこちら：',
                    joinUrl,
                    '',
                    'ご協力よろしくお願いいたします！'
                ].join('\n');

                try {
                    await navigator.clipboard.writeText(message);
                    const originalText = copyInviteBtn.innerHTML;
                    copyInviteBtn.innerHTML = '<span>✅</span><span>案内文をコピーしました！</span>';
                    setTimeout(() => {
                        copyInviteBtn.innerHTML = originalText;
                    }, 2500);
                } catch (e) {
                    prompt('以下の案内文をコピーして共有してください:', message);
                }
            });
        }

        // 詳細画面でのURLコピー
        const copyShiftUrlBtn = document.getElementById('copyShiftUrlBtn');
        const staffShiftUrlInput = document.getElementById('staffShiftUrlInput');
        const copyShiftUrlBtnText = document.getElementById('copyShiftUrlBtnText');
        if (copyShiftUrlBtn && staffShiftUrlInput) {
            copyShiftUrlBtn.addEventListener('click', async function () {
                try {
                    await navigator.clipboard.writeText(staffShiftUrlInput.value);
                    if (copyShiftUrlBtnText) {
                        const originalText = copyShiftUrlBtnText.textContent;
                        copyShiftUrlBtnText.textContent = 'コピー完了！';
                        setTimeout(() => {
                            copyShiftUrlBtnText.textContent = originalText;
                        }, 2000);
                    }
                } catch (e) {
                    staffShiftUrlInput.select();
                }
            });
        }
        // 3. セルフ登録設定モーダル (CSP準拠・イベント委譲)
        const registrationSettingModal = document.getElementById('registrationSettingModal');

        function openRegistrationSettingModal() {
            if (registrationSettingModal) {
                registrationSettingModal.classList.remove('hidden');
            }
        }

        function closeRegistrationSettingModal() {
            if (registrationSettingModal) {
                registrationSettingModal.classList.add('hidden');
            }
        }

        document.addEventListener('click', function (e) {
            // モーダルを開く
            if (e.target.closest('#openRegistrationSettingModalBtn') || e.target.closest('.open-registration-setting-trigger')) {
                openRegistrationSettingModal();
                return;
            }

            // モーダルを閉じる
            if (e.target.closest('#registrationSettingModalCloseBtn') ||
                e.target.closest('#registrationSettingModalCancelBtn') ||
                e.target.closest('#registrationSettingModalBackdrop')) {
                closeRegistrationSettingModal();
                return;
            }

            // 日時入力クリアボタン
            const clearBtn = e.target.closest('[data-clear-target]');
            if (clearBtn) {
                const targetId = clearBtn.getAttribute('data-clear-target');
                const targetInput = document.getElementById(targetId);
                if (targetInput) {
                    targetInput.value = '';
                    targetInput.focus();
                }
            }
        });
    });
})();
