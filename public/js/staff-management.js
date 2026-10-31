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

        // CSV取込
        const csvImportBtn = document.getElementById('csvImportBtn');
        const csvFileInput = document.getElementById('csvFileInput');
        const csvImportForm = document.getElementById('csvImportForm');
        if (csvImportBtn && csvFileInput && csvImportForm) {
            csvImportBtn.addEventListener('click', function () {
                csvFileInput.click();
            });
            csvFileInput.addEventListener('change', function () {
                if (csvFileInput.files.length > 0) {
                    csvImportForm.submit();
                }
            });
        }

        // 2. スタッフ個別QRコードモーダル
        const qrModal = document.getElementById('qrModal');
        const qrModalCloseBtn = document.getElementById('qrModalCloseBtn');
        const qrModalBackdrop = document.getElementById('qrModalBackdrop');
        const qrModalStaffName = document.getElementById('qrModalStaffName');
        const qrModalStaffUrl = document.getElementById('qrModalStaffUrl');
        const qrModalImage = document.getElementById('qrModalImage');
        const qrModalCopyBtn = document.getElementById('qrModalCopyBtn');

        function openQrModal(name, url) {
            if (!qrModal) return;
            if (qrModalStaffName) qrModalStaffName.textContent = name;
            if (qrModalStaffUrl) qrModalStaffUrl.value = url;
            if (qrModalImage) {
                // QRコードAPIで画像を生成表示
                qrModalImage.src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(url);
            }
            qrModal.classList.remove('hidden');
        }

        function closeQrModal() {
            if (!qrModal) return;
            qrModal.classList.add('hidden');
        }

        if (qrModalCloseBtn) qrModalCloseBtn.addEventListener('click', closeQrModal);
        if (qrModalBackdrop) qrModalBackdrop.addEventListener('click', closeQrModal);

        // 各行のQRボタン
        document.querySelectorAll('.open-qr-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const name = btn.dataset.staffName || '';
                const url = btn.dataset.staffUrl || '';
                openQrModal(name, url);
            });
        });

        // モーダル内のURLコピー
        if (qrModalCopyBtn && qrModalStaffUrl) {
            qrModalCopyBtn.addEventListener('click', async function () {
                try {
                    await navigator.clipboard.writeText(qrModalStaffUrl.value);
                    const originalText = qrModalCopyBtn.innerHTML;
                    qrModalCopyBtn.innerHTML = '<span>✅</span><span>コピー完了</span>';
                    setTimeout(() => {
                        qrModalCopyBtn.innerHTML = originalText;
                    }, 2000);
                } catch (e) {
                    qrModalStaffUrl.select();
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
        // 3. セルフ登録設定モーダル
        const registrationSettingModal = document.getElementById('registrationSettingModal');
        const openRegistrationSettingModalBtn = document.getElementById('openRegistrationSettingModalBtn');
        const registrationSettingModalCloseBtn = document.getElementById('registrationSettingModalCloseBtn');
        const registrationSettingModalCancelBtn = document.getElementById('registrationSettingModalCancelBtn');
        const registrationSettingModalBackdrop = document.getElementById('registrationSettingModalBackdrop');

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

        if (openRegistrationSettingModalBtn) {
            openRegistrationSettingModalBtn.addEventListener('click', openRegistrationSettingModal);
        }
        if (registrationSettingModalCloseBtn) {
            registrationSettingModalCloseBtn.addEventListener('click', closeRegistrationSettingModal);
        }
        if (registrationSettingModalCancelBtn) {
            registrationSettingModalCancelBtn.addEventListener('click', closeRegistrationSettingModal);
        }
        if (registrationSettingModalBackdrop) {
            registrationSettingModalBackdrop.addEventListener('click', closeRegistrationSettingModal);
        }
    });
})();
