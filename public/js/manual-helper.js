/**
 * マニュアル画面用ヘルパースクリプト
 * CSP準拠（外部ファイル・DOM安全操作）
 */
(function () {
    'use strict';

    window.openImageModal = function (src, caption) {
        const modal = document.getElementById('imageModal');
        const img = document.getElementById('modalImage');
        const cap = document.getElementById('modalCaption');

        if (img) {
            img.src = src;
        }

        if (cap) {
            cap.textContent = '';
            const iconSpan = document.createElement('span');
            iconSpan.textContent = '🖼️ ';
            const textSpan = document.createElement('span');
            textSpan.textContent = caption;
            cap.appendChild(iconSpan);
            cap.appendChild(textSpan);
        }

        if (modal) {
            modal.classList.remove('hidden');
        }
    };

    window.closeImageModal = function () {
        const modal = document.getElementById('imageModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            window.closeImageModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('manualSearchInput');
        const sections = document.querySelectorAll('.manual-section');
        const navLinks = document.querySelectorAll('.manual-nav-link');

        // 検索入力によるハイライト/フィルタ
        if (searchInput) {
            searchInput.addEventListener('input', function (e) {
                const query = e.target.value.trim().toLowerCase();
                if (!query) {
                    sections.forEach(function (sec) {
                        sec.style.display = '';
                    });
                    return;
                }
                sections.forEach(function (sec) {
                    const text = sec.textContent.toLowerCase();
                    if (text.includes(query)) {
                        sec.style.display = '';
                    } else {
                        sec.style.display = 'none';
                    }
                });
            });
        }

        // スクロール追従による目次アクティブ表示
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        const id = entry.target.getAttribute('id');
                        navLinks.forEach(function (link) {
                            if (link.getAttribute('href') === '#' + id) {
                                link.classList.add('bg-indigo-50', 'text-indigo-600', 'font-bold');
                                link.classList.remove('text-slate-600');
                            } else {
                                link.classList.remove('bg-indigo-50', 'text-indigo-600', 'font-bold');
                                link.classList.add('text-slate-600');
                            }
                        });
                    }
                });
            }, { rootMargin: '-20% 0px -70% 0px' });

            sections.forEach(function (section) {
                observer.observe(section);
            });
        }
    });
})();
