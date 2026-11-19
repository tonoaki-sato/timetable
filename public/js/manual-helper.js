/**
 * マニュアル画面用ヘルパースクリプト
 * CSP準拠（外部ファイル・DOM安全操作・イベント委譲・インラインstyle禁止）
 */
(function () {
    'use strict';

    function openImageModal(src, caption) {
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
            textSpan.textContent = caption || '画像プレビュー';
            cap.appendChild(iconSpan);
            cap.appendChild(textSpan);
        }

        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    function closeImageModal() {
        const modal = document.getElementById('imageModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    // グローバル互換性
    window.openImageModal = openImageModal;
    window.closeImageModal = closeImageModal;

    // キーボードショートカット
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeImageModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('manualSearchInput');
        const searchClear = document.getElementById('manualSearchClear');
        const searchCount = document.getElementById('manualSearchCount');
        const searchEmpty = document.getElementById('manualSearchEmpty');
        const sections = document.querySelectorAll('.manual-section');
        const navLinks = document.querySelectorAll('.manual-nav-link');
        const scenarioFilterBtns = document.querySelectorAll('[data-scenario-filter]');
        const scenarioCards = document.querySelectorAll('.scenario-card');
        const scenarioCountBadge = document.getElementById('scenarioCountBadge');

        function applyManualSearch(rawValue) {
            const query = (rawValue || '').trim().toLowerCase();
            let matchCount = 0;

            sections.forEach(function (sec) {
                if (!query) {
                    sec.classList.remove('hidden');
                    matchCount++;
                    return;
                }

                const text = (sec.textContent || '').toLowerCase();
                if (text.includes(query)) {
                    sec.classList.remove('hidden');
                    matchCount++;
                } else {
                    sec.classList.add('hidden');
                }
            });

            if (searchClear) {
                searchClear.classList.toggle('hidden', query === '');
            }

            if (searchCount) {
                if (!query) {
                    searchCount.textContent = '全 ' + sections.length + ' セクション';
                } else {
                    searchCount.textContent = matchCount + ' / ' + sections.length + ' 件一致';
                }
            }

            if (searchEmpty) {
                searchEmpty.classList.toggle('hidden', !(query !== '' && matchCount === 0));
            }
        }

        function applyScenarioFilter(category) {
            const targetCategory = category || 'all';
            let visibleCount = 0;

            scenarioCards.forEach(function (card) {
                const cardCategory = card.getAttribute('data-scenario-category') || '';
                const matched = targetCategory === 'all' || cardCategory === targetCategory;
                card.classList.toggle('hidden', !matched);
                if (matched) {
                    visibleCount++;
                }
            });

            scenarioFilterBtns.forEach(function (btn) {
                const isActive = (btn.getAttribute('data-scenario-filter') || 'all') === targetCategory;
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                if (isActive) {
                    btn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600', 'shadow-2xs');
                    btn.classList.remove('bg-white', 'text-slate-700', 'border-slate-300', 'hover:bg-slate-50');
                } else {
                    btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600', 'shadow-2xs');
                    btn.classList.add('bg-white', 'text-slate-700', 'border-slate-300', 'hover:bg-slate-50');
                }
            });

            if (scenarioCountBadge) {
                scenarioCountBadge.textContent = visibleCount + ' シナリオ表示中';
            }
        }

        // クリックイベントの委譲処理（CSP準拠・インラインイベント全廃）
        document.addEventListener('click', function (e) {
            // 1. 印刷ボタン
            if (e.target.closest('[data-action="print"]')) {
                window.print();
                return;
            }

            // 2. モーダル画像の拡大表示
            const imgTrigger = e.target.closest('[data-modal-image]');
            if (imgTrigger) {
                const src = imgTrigger.getAttribute('src');
                const caption = imgTrigger.getAttribute('data-caption') || imgTrigger.getAttribute('alt') || '画像プレビュー';
                openImageModal(src, caption);
                return;
            }

            // 3. モーダルを閉じるボタン、または外枠背景タップ
            if (e.target.closest('[data-action="close-modal"]')) {
                closeImageModal();
                return;
            }

            // モーダル外枠背景タップ（モーダル自体がクリックされた場合）
            const modal = document.getElementById('imageModal');
            if (modal && e.target === modal) {
                closeImageModal();
                return;
            }

            // 4. 検索クリアボタン
            if (e.target.closest('#manualSearchClear') || e.target.closest('[data-action="reset-manual-search"]')) {
                if (searchInput) {
                    searchInput.value = '';
                    applyManualSearch('');
                    searchInput.focus();
                }
                return;
            }

            // 5. シナリオカテゴリフィルタ
            const filterBtn = e.target.closest('[data-scenario-filter]');
            if (filterBtn) {
                applyScenarioFilter(filterBtn.getAttribute('data-scenario-filter'));
            }
        });

        // 検索入力によるハイライト/フィルタ
        if (searchInput) {
            searchInput.addEventListener('input', function (e) {
                applyManualSearch(e.target.value);
            });
            applyManualSearch(searchInput.value);
        }

        // 初期シナリオ件数反映
        if (scenarioFilterBtns.length > 0 && scenarioCards.length > 0) {
            applyScenarioFilter('all');
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
