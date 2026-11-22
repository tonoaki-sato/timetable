import { showToast } from '../utils/ui.js';

/**
 * グローバル横断検索モーダル (Ctrl+K / Cmd+K)
 */
export function initQuickSearch() {
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
                    Accept: 'application/json',
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
