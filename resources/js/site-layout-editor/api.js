import { confirmDialog } from '../utils/ui.js';
import { setSaveStatus, showToast } from './context.js';
import { renderHygieneCirclesAndCheck } from './hygiene.js';
import { findPlacementNodeElement } from './svg-renderer.js';

/**
 * 配置オブジェクトCRUD・一括更新・リセット・クリア・白地図切替 API通信層
 */
export function createPlacementApi(ctx, deps) {
    const { storeUrl, bulkUrl, resetUrl, clearUrl, baseMapUpdateUrl, csrfToken } = ctx.config;

    async function apiCreatePlacement(payload, recordHistory = false) {
        setSaveStatus(ctx, 'saving');
        try {
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload),
            });
            if (!res.ok) throw new Error('作成に失敗しました');
            const data = await res.json();
            if (data.success && data.placement) {
                ctx.state.placements.push(data.placement);
                deps.renderer.renderPlacementNode(data.placement);
                deps.inspector.setSingleSelection(data.placement.id);
                renderHygieneCirclesAndCheck(ctx);
                deps.inspector.syncSidebarLayoutItems();
                if (recordHistory) deps.history.pushHistory({ type: 'create', created: { ...data.placement } });
                setSaveStatus(ctx, 'saved');
                return data.placement;
            }
        } catch (err) {
            console.error(err);
            setSaveStatus(ctx, 'error');
        }
        return null;
    }

    async function applySingleUpdate(placement, patch, recordHistory = true, beforeOverride = null) {
        const before = beforeOverride || [
            {
                id: placement.id,
                pos_x: placement.pos_x,
                pos_y: placement.pos_y,
                width: placement.width,
                height: placement.height,
                rotation: placement.rotation,
                is_rotation_locked: placement.is_rotation_locked,
                display_text: placement.display_text,
                color_theme: placement.color_theme,
            },
        ];
        Object.assign(placement, patch);
        deps.renderer.renderPlacementNode(placement);
        renderHygieneCirclesAndCheck(ctx);
        deps.inspector.updateSummaryAndInspector();

        setSaveStatus(ctx, 'saving');
        try {
            const res = await fetch(`${storeUrl}/${placement.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(patch),
            });
            if (!res.ok) throw new Error('更新失敗');
            const data = await res.json();
            if (data.placement) Object.assign(placement, data.placement);
            if (recordHistory) {
                const after = [
                    {
                        id: placement.id,
                        pos_x: placement.pos_x,
                        pos_y: placement.pos_y,
                        width: placement.width,
                        height: placement.height,
                        rotation: placement.rotation,
                        is_rotation_locked: placement.is_rotation_locked,
                        display_text: placement.display_text,
                        color_theme: placement.color_theme,
                    },
                ];
                deps.history.pushHistory({ type: 'update', before, after });
            }
            deps.renderer.renderPlacementNode(placement);
            deps.inspector.syncSidebarLayoutItems();
            setSaveStatus(ctx, 'saved');
        } catch (err) {
            console.error(err);
            setSaveStatus(ctx, 'error');
        }
    }

    async function apiBulkUpdate(items, recordHistory = false, beforeSnapshot = null) {
        if (!items || items.length === 0) return;
        setSaveStatus(ctx, 'saving');
        try {
            const res = await fetch(bulkUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ items }),
            });
            if (!res.ok) throw new Error('一括更新失敗');
            const data = await res.json();
            if (data.success && Array.isArray(data.placements)) {
                ctx.state.placements = data.placements;
                deps.renderer.renderAllPlacements();
                if (recordHistory && beforeSnapshot) {
                    deps.history.pushHistory({ type: 'update', before: beforeSnapshot, after: items });
                }
            }
            setSaveStatus(ctx, 'saved');
        } catch (err) {
            console.error(err);
            setSaveStatus(ctx, 'error');
        }
    }

    async function apiDeletePlacement(id, recordHistory = true, refreshUi = true) {
        const target = ctx.state.placements.find((p) => p.id === id);
        if (!target) return;
        const snapshot = { ...target };

        setSaveStatus(ctx, 'saving');
        try {
            const res = await fetch(`${storeUrl}/${id}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            if (!res.ok) throw new Error('削除失敗');
            ctx.state.placements = ctx.state.placements.filter((p) => p.id !== id);
            ctx.state.selectedIds.delete(id);
            const node = findPlacementNodeElement(ctx, id);
            if (node) node.remove();
            if (refreshUi) {
                renderHygieneCirclesAndCheck(ctx);
                deps.inspector.updateSummaryAndInspector();
                deps.inspector.syncSidebarLayoutItems();
            }
            if (recordHistory) {
                deps.history.pushHistory({ type: 'delete', snapshot });
                showToast(ctx, `「${snapshot.display_text || 'オブジェクト'}」を削除しました`, deps.history.performUndo);
            }
            setSaveStatus(ctx, 'saved');
        } catch (err) {
            console.error(err);
            setSaveStatus(ctx, 'error');
        }
    }

    async function deleteSelectedPlacements() {
        const ids = [...ctx.state.selectedIds];
        if (ids.length === 0) return;

        await Promise.all(ids.map((id) => apiDeletePlacement(id, true, false)));
        renderHygieneCirclesAndCheck(ctx);
        deps.inspector.updateSummaryAndInspector();
        deps.inspector.syncSidebarLayoutItems();
    }

    function bindResetAndClearActions() {
        const { btnResetPreset, btnClearPlacements, selectTimetableBaseMap } = ctx.dom;

        if (btnResetPreset) {
            btnResetPreset.addEventListener('click', async () => {
                if (
                    !(await confirmDialog('現在の配置をリセットし、標準見本配置（260927版・全111件）に復元しますか？', {
                        variant: 'danger',
                        title: '配置リセットの確認',
                        confirmText: '復元する',
                    }))
                ) {
                    return;
                }
                setSaveStatus(ctx, 'saving');
                try {
                    const res = await fetch(resetUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken } });
                    if (!res.ok) throw new Error('復元に失敗しました');
                    const data = await res.json();
                    if (data.success && Array.isArray(data.placements)) {
                        ctx.state.placements = data.placements;
                        ctx.state.selectedIds.clear();
                        ctx.state.undoStack = [];
                        ctx.state.redoStack = [];
                        ctx.state.inspDisplayTextBefore = null;
                        deps.renderer.renderAllPlacements();
                        showToast(ctx, '標準見本配置へ復元しました');
                    }
                    setSaveStatus(ctx, 'saved');
                } catch (err) {
                    console.error(err);
                    setSaveStatus(ctx, 'error');
                }
            });
        }

        if (selectTimetableBaseMap && baseMapUpdateUrl) {
            selectTimetableBaseMap.addEventListener('change', async () => {
                const newId = selectTimetableBaseMap.value;
                const prevId = selectTimetableBaseMap.dataset.currentBaseMapId;
                if (!newId || newId === prevId) return;

                const selectedLabel = selectTimetableBaseMap.options[selectTimetableBaseMap.selectedIndex]?.textContent?.trim() || '選択した白地図';
                const ok = await confirmDialog(
                    `使用する白地図を「${selectedLabel}」に切り替えますか？\n切り替え後は白地図エディタを再読み込みします。`,
                    { variant: 'primary', icon: '🗺️', title: '使用白地図の切り替え', confirmText: '切り替える' },
                );
                if (!ok) {
                    selectTimetableBaseMap.value = prevId;
                    return;
                }

                setSaveStatus(ctx, 'saving');
                try {
                    const res = await fetch(baseMapUpdateUrl, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({ site_base_map_id: parseInt(newId, 10) }),
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        showToast(ctx, data.message || '白地図の切り替えに失敗しました');
                        selectTimetableBaseMap.value = prevId;
                        setSaveStatus(ctx, 'error');
                        return;
                    }
                    window.location.reload();
                } catch (err) {
                    console.error('Failed to switch base map:', err);
                    showToast(ctx, '通信エラーが発生しました');
                    selectTimetableBaseMap.value = prevId;
                    setSaveStatus(ctx, 'error');
                }
            });
        }

        if (btnClearPlacements) {
            btnClearPlacements.addEventListener('click', async () => {
                if (ctx.state.placements.length === 0) {
                    showToast(ctx, '配置オブジェクトはありません');
                    return;
                }
                if (
                    !(await confirmDialog(
                        `現在の全配置オブジェクト（${ctx.state.placements.length}件）をすべて削除し、白地図のみの初期状態に戻しますか？`,
                        { variant: 'danger', title: '全クリアの確認', confirmText: 'すべて削除する' },
                    ))
                ) {
                    return;
                }
                setSaveStatus(ctx, 'saving');
                try {
                    const res = await fetch(clearUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken } });
                    if (!res.ok) throw new Error('クリアに失敗しました');
                    const data = await res.json();
                    if (data.success) {
                        ctx.state.placements = [];
                        ctx.state.selectedIds.clear();
                        ctx.state.undoStack = [];
                        ctx.state.redoStack = [];
                        ctx.state.inspDisplayTextBefore = null;
                        deps.renderer.renderAllPlacements();
                        showToast(ctx, 'すべての配置オブジェクトをクリアしました');
                    }
                    setSaveStatus(ctx, 'saved');
                } catch (err) {
                    console.error(err);
                    setSaveStatus(ctx, 'error');
                }
            });
        }
    }

    return {
        apiCreatePlacement,
        applySingleUpdate,
        apiBulkUpdate,
        apiDeletePlacement,
        deleteSelectedPlacements,
        bindResetAndClearActions,
    };
}
