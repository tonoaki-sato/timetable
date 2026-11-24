import { clamp, minutesToHhmm, snapMinutes } from './utils.js';

/**
 * ガントバーのクリック・横移動ドラッグ・左右リサイズ操作コントローラー
 */
export function initGanttBarDrag({
    rootEl,
    getBounds,
    getPxPerMin,
    canEditBar = () => true,
    onBarClick,
    onBarTimeChange,
}) {
    if (!rootEl) return;

    let dragState = null;

    const applyBarVisual = (barEl, sMin, eMin) => {
        const bounds = getBounds();
        const pxPerMin = getPxPerMin();
        const left = Math.round((sMin - bounds.startMin) * pxPerMin);
        const width = Math.max(24, Math.round((eMin - sMin) * pxPerMin));
        barEl.style.left = `${left}px`;
        barEl.style.width = `${width}px`;

        const timeLabel = barEl.querySelector('[data-bar-time-label]');
        if (timeLabel) {
            timeLabel.textContent = `${minutesToHhmm(sMin)}〜${minutesToHhmm(eMin)}`;
        }
    };

    rootEl.addEventListener('pointerdown', (e) => {
        if (e.button !== 0) return;
        const barEl = e.target.closest('[data-gantt-bar]');
        if (!barEl) return;

        // バー内の明示的なボタンリンク等はドラッグ対象外
        if (e.target.closest('a, button[data-bar-action]')) return;

        const resizeHandle = e.target.closest('[data-resize-edge]');
        const mode = resizeHandle ? resizeHandle.dataset.resizeEdge : 'move';

        const origStart = parseInt(barEl.dataset.startMin || '0', 10);
        const origEnd = parseInt(barEl.dataset.endMin || '0', 10);

        dragState = {
            pointerId: e.pointerId,
            barEl,
            taskId: parseInt(barEl.dataset.taskId || '0', 10),
            mode,
            editable: canEditBar(barEl),
            startX: e.clientX,
            origStart,
            origEnd,
            curStart: origStart,
            curEnd: origEnd,
            moved: false,
        };
    });

    document.addEventListener('pointermove', (e) => {
        if (!dragState || e.pointerId !== dragState.pointerId) return;

        const dxPx = e.clientX - dragState.startX;
        if (!dragState.moved && Math.abs(dxPx) > 4) {
            if (!dragState.editable) {
                return;
            }
            dragState.moved = true;
            dragState.barEl.classList.add('ring-2', 'ring-indigo-600', 'z-30', 'opacity-90');
        }

        if (!dragState.moved) return;

        const bounds = getBounds();
        const pxPerMin = getPxPerMin();
        const deltaMin = snapMinutes(dxPx / pxPerMin, 15);
        const duration = dragState.origEnd - dragState.origStart;

        let newStart = dragState.origStart;
        let newEnd = dragState.origEnd;

        if (dragState.mode === 'move') {
            newStart = clamp(dragState.origStart + deltaMin, bounds.startMin, bounds.endMin - duration);
            newEnd = newStart + duration;
        } else if (dragState.mode === 'start') {
            newStart = clamp(dragState.origStart + deltaMin, bounds.startMin, dragState.origEnd - 15);
        } else if (dragState.mode === 'end') {
            newEnd = clamp(dragState.origEnd + deltaMin, dragState.origStart + 15, bounds.endMin);
        }

        dragState.curStart = newStart;
        dragState.curEnd = newEnd;
        applyBarVisual(dragState.barEl, newStart, newEnd);
    });

    document.addEventListener('pointerup', (e) => {
        if (!dragState || e.pointerId !== dragState.pointerId) return;

        const state = dragState;
        dragState = null;

        state.barEl.classList.remove('ring-2', 'ring-indigo-600', 'z-30', 'opacity-90');

        if (!state.moved) {
            onBarClick?.(state.barEl);
            return;
        }

        if (state.curStart === state.origStart && state.curEnd === state.origEnd) {
            return;
        }

        const revert = () => {
            state.barEl.dataset.startMin = String(state.origStart);
            state.barEl.dataset.endMin = String(state.origEnd);
            applyBarVisual(state.barEl, state.origStart, state.origEnd);
        };

        state.barEl.dataset.startMin = String(state.curStart);
        state.barEl.dataset.endMin = String(state.curEnd);

        onBarTimeChange?.({
            barEl: state.barEl,
            taskId: state.taskId,
            origStartMin: state.origStart,
            origEndMin: state.origEnd,
            newStartMin: state.curStart,
            newEndMin: state.curEnd,
            newStartTime: minutesToHhmm(state.curStart),
            newEndTime: minutesToHhmm(state.curEnd),
            revert,
        });
    });

    document.addEventListener('pointercancel', () => {
        if (!dragState) return;
        const state = dragState;
        dragState = null;
        state.barEl.classList.remove('ring-2', 'ring-indigo-600', 'z-30', 'opacity-90');
        applyBarVisual(state.barEl, state.origStart, state.origEnd);
    });
}
