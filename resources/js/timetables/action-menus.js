/**
 * 運行情報一覧 管理メニュープルダウン（ドロップダウン）開閉制御
 */
export function initTimetableActionMenus() {
    const containers = document.querySelectorAll('.timetable-menu-container');
    if (containers.length === 0) {
        return;
    }

    function closeAllMenus() {
        document.querySelectorAll('.timetable-menu-dropdown').forEach((menu) => {
            menu.classList.add('hidden');
            menu.style.top = '';
            menu.style.left = '';
        });
        document.querySelectorAll('.timetable-menu-trigger').forEach((trigger) => {
            trigger.setAttribute('aria-expanded', 'false');
        });
    }

    containers.forEach((container) => {
        const trigger = container.querySelector('.timetable-menu-trigger');
        const dropdown = container.querySelector('.timetable-menu-dropdown');
        if (!trigger || !dropdown) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isCurrentlyOpen = !dropdown.classList.contains('hidden');

            closeAllMenus();

            if (!isCurrentlyOpen) {
                dropdown.classList.remove('hidden');
                trigger.setAttribute('aria-expanded', 'true');

                const rect = trigger.getBoundingClientRect();
                const menuWidth = dropdown.offsetWidth || 176;
                const menuHeight = dropdown.offsetHeight || 190;
                const gap = 4;

                let top = rect.bottom + gap;
                let left = rect.right - menuWidth;

                const spaceBelow = window.innerHeight - rect.bottom;
                const spaceAbove = rect.top;
                if (spaceBelow < menuHeight + gap && spaceAbove > menuHeight + gap) {
                    top = rect.top - menuHeight - gap;
                }

                if (left < 8) {
                    left = 8;
                } else if (left + menuWidth > window.innerWidth - 8) {
                    left = window.innerWidth - menuWidth - 8;
                }

                dropdown.style.top = `${Math.round(top)}px`;
                dropdown.style.left = `${Math.round(left)}px`;
            }
        });
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.timetable-menu-container')) {
            closeAllMenus();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllMenus();
        }
    });

    window.addEventListener('scroll', closeAllMenus, { capture: true, passive: true });
    window.addEventListener('resize', closeAllMenus);
}
