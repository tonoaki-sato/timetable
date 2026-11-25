import { confirmDialog } from '../utils/ui.js';
import { selectionKey, setSaveStatus, showToast } from './context.js';
import { findNodeElement } from './svg-renderer.js';

/**
 * カラーコーン・バー・バリケード・吹き出し API 通信層
 */
export function createConeMapApi(ctx, deps) {
    const {
        clearUrl,
        baseMapUpdateUrl,
        conesUrl,
        conesBulkUrl,
        barsUrl,
        coneLinesUrl,
        barricadesUrl,
        barricadesBulkUrl,
        calloutsUrl,
        csrfToken,
    } = ctx.config;

    function headers(withJson = true) {
        const h = { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken };
        if (withJson) h['Content-Type'] = 'application/json';
        return h;
    }

    function applySummary(data) {
        if (data && data.summary) {
            ctx.state.summary = data.summary;
        }
        deps.inspector.updateSummaryAndInspector();
        deps.inspector.syncCalloutsList();
    }

    async function apiRequest(url, method, body = null) {
        setSaveStatus(ctx, 'saving');
        const res = await fetch(url, {
            method,
            headers: headers(body !== null),
            body: body !== null ? JSON.stringify(body) : undefined,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            setSaveStatus(ctx, 'error');
            const msg = data.message || '保存に失敗しました';
            showToast(ctx, msg);
            throw new Error(msg);
        }
        setSaveStatus(ctx, 'saved');
        return data;
    }

    // --- コーン ---
    async function createCone(payload, recordHistory = true) {
        const data = await apiRequest(conesUrl, 'POST', payload);
        if (data.cone) {
            ctx.state.cones.push(data.cone);
            deps.renderer.renderNode('cone', data.cone);
            deps.inspector.setSingleSelection('cone', data.cone.id);
            applySummary(data);
            if (recordHistory) deps.history.pushHistory({ type: 'create_cone', snapshot: { ...data.cone } });
            return data.cone;
        }
        return null;
    }

    async function updateCone(cone, patch, recordHistory = true, beforeOverride = null) {
        const before = beforeOverride || {
            id: cone.id,
            pos_x: cone.pos_x,
            pos_y: cone.pos_y,
            color: cone.color,
            label: cone.label,
            notes: cone.notes,
        };
        Object.assign(cone, patch);
        deps.renderer.renderNode('cone', cone);
        deps.renderer.renderConnectedBarsForCone(cone.id);

        const data = await apiRequest(`${conesUrl}/${cone.id}`, 'PUT', patch);
        if (data.cone) Object.assign(cone, data.cone);
        if (patch.pos_x !== undefined || patch.pos_y !== undefined) {
            ctx.state.callouts.forEach((c) => {
                if (c.target_cone_id === cone.id) {
                    c.anchor_x = cone.pos_x;
                    c.anchor_y = cone.pos_y;
                }
            });
            deps.renderer.renderCalloutsForTarget('cone', cone.id);
        }
        deps.renderer.renderNode('cone', cone);
        deps.renderer.renderConnectedBarsForCone(cone.id);
        applySummary(data);

        if (recordHistory) {
            deps.history.pushHistory({
                type: 'update_cone',
                before,
                after: {
                    id: cone.id,
                    pos_x: cone.pos_x,
                    pos_y: cone.pos_y,
                    color: cone.color,
                    label: cone.label,
                    notes: cone.notes,
                },
            });
        }
        return cone;
    }

    async function bulkUpdateCones(items, recordHistory = true, beforeList = null) {
        if (!items || items.length === 0) return;
        const data = await apiRequest(conesBulkUrl, 'PUT', { items });
        if (Array.isArray(data.cones)) ctx.state.cones = data.cones;
        if (Array.isArray(data.callouts)) ctx.state.callouts = data.callouts;
        deps.renderer.renderAll();
        applySummary(data);
        if (recordHistory && beforeList) {
            deps.history.pushHistory({ type: 'bulk_update_cones', before: beforeList, after: items });
        }
    }

    async function deleteCone(id, recordHistory = true, refresh = true) {
        const target = ctx.state.cones.find((c) => c.id === id);
        if (!target) return;
        const connectedBarsSnapshot = ctx.state.bars
            .filter((b) => b.start_cone_id === id || b.end_cone_id === id)
            .map((b) => ({ ...b }));
        const snapshot = { ...target };

        const data = await apiRequest(`${conesUrl}/${id}`, 'DELETE');
        const deletedBarIds = new Set(data.deleted_bar_ids || []);
        ctx.state.cones = ctx.state.cones.filter((c) => c.id !== id);
        ctx.state.bars = ctx.state.bars.filter((b) => !deletedBarIds.has(b.id) && b.start_cone_id !== id && b.end_cone_id !== id);
        ctx.state.selectedItems.delete(selectionKey('cone', id));

        const node = findNodeElement(ctx, 'cone', id);
        if (node) node.remove();
        deletedBarIds.forEach((bid) => {
            ctx.state.selectedItems.delete(selectionKey('bar', bid));
            const bNode = findNodeElement(ctx, 'bar', bid);
            if (bNode) bNode.remove();
        });

        if (refresh) applySummary(data);
        if (recordHistory) {
            deps.history.pushHistory({
                type: 'delete_cone',
                snapshot,
                connectedBars: connectedBarsSnapshot,
            });
        }
    }

    // --- コーンバー ---
    async function createBar(payload, recordHistory = true) {
        const data = await apiRequest(barsUrl, 'POST', payload);
        if (data.bar) {
            ctx.state.bars.push(data.bar);
            deps.renderer.renderNode('bar', data.bar);
            deps.inspector.setSingleSelection('bar', data.bar.id);
            applySummary(data);
            if (recordHistory) deps.history.pushHistory({ type: 'create_bar', snapshot: { ...data.bar } });
            return data.bar;
        }
        return null;
    }

    async function updateBar(bar, patch, recordHistory = true) {
        const before = { id: bar.id, bar_style: bar.bar_style, notes: bar.notes };
        Object.assign(bar, patch);
        deps.renderer.renderNode('bar', bar);
        const data = await apiRequest(`${barsUrl}/${bar.id}`, 'PUT', patch);
        if (data.bar) Object.assign(bar, data.bar);
        deps.renderer.renderNode('bar', bar);
        applySummary(data);
        if (recordHistory) {
            deps.history.pushHistory({
                type: 'update_bar',
                before,
                after: { id: bar.id, bar_style: bar.bar_style, notes: bar.notes },
            });
        }
    }

    async function deleteBar(id, recordHistory = true, refresh = true) {
        const target = ctx.state.bars.find((b) => b.id === id);
        if (!target) return;
        const snapshot = { ...target };

        const data = await apiRequest(`${barsUrl}/${id}`, 'DELETE');
        ctx.state.bars = ctx.state.bars.filter((b) => b.id !== id);
        ctx.state.selectedItems.delete(selectionKey('bar', id));
        const node = findNodeElement(ctx, 'bar', id);
        if (node) node.remove();
        if (refresh) applySummary(data);
        if (recordHistory) deps.history.pushHistory({ type: 'delete_bar', snapshot });
    }

    // --- コーン連結列一括作成 ---
    async function createConeLine(payload, recordHistory = true) {
        const data = await apiRequest(coneLinesUrl, 'POST', payload);
        const createdCones = data.cones || [];
        const createdBars = data.bars || [];
        createdCones.forEach((c) => {
            ctx.state.cones.push(c);
            deps.renderer.renderNode('cone', c);
        });
        createdBars.forEach((b) => {
            ctx.state.bars.push(b);
            deps.renderer.renderNode('bar', b);
        });
        applySummary(data);
        if (recordHistory) {
            deps.history.pushHistory({
                type: 'create_cone_line',
                cones: createdCones.map((c) => ({ ...c })),
                bars: createdBars.map((b) => ({ ...b })),
            });
        }
        showToast(ctx, `コーン${createdCones.length}個（＋ウェイト${createdCones.length}個）とバー${createdBars.length}本を配置しました`);
        return data;
    }

    // --- バリケード ---
    async function createBarricade(payload, recordHistory = true) {
        const data = await apiRequest(barricadesUrl, 'POST', payload);
        if (data.barricade) {
            ctx.state.barricades.push(data.barricade);
            deps.renderer.renderNode('barricade', data.barricade);
            deps.inspector.setSingleSelection('barricade', data.barricade.id);
            applySummary(data);
            if (recordHistory) deps.history.pushHistory({ type: 'create_barricade', snapshot: { ...data.barricade } });
            return data.barricade;
        }
        return null;
    }

    async function updateBarricade(barricade, patch, recordHistory = true, beforeOverride = null) {
        const before = beforeOverride || {
            id: barricade.id,
            barricade_type: barricade.barricade_type,
            pos_x: barricade.pos_x,
            pos_y: barricade.pos_y,
            rotation: barricade.rotation,
            label: barricade.label,
            notes: barricade.notes,
        };
        Object.assign(barricade, patch);
        deps.renderer.renderNode('barricade', barricade);

        const data = await apiRequest(`${barricadesUrl}/${barricade.id}`, 'PUT', patch);
        if (data.barricade) Object.assign(barricade, data.barricade);
        if (patch.pos_x !== undefined || patch.pos_y !== undefined) {
            ctx.state.callouts.forEach((c) => {
                if (c.target_barricade_id === barricade.id) {
                    c.anchor_x = barricade.pos_x;
                    c.anchor_y = barricade.pos_y;
                }
            });
            deps.renderer.renderCalloutsForTarget('barricade', barricade.id);
        }
        deps.renderer.renderNode('barricade', barricade);
        applySummary(data);

        if (recordHistory) {
            deps.history.pushHistory({
                type: 'update_barricade',
                before,
                after: {
                    id: barricade.id,
                    barricade_type: barricade.barricade_type,
                    pos_x: barricade.pos_x,
                    pos_y: barricade.pos_y,
                    rotation: barricade.rotation,
                    label: barricade.label,
                    notes: barricade.notes,
                },
            });
        }
        return barricade;
    }

    async function bulkUpdateBarricades(items, recordHistory = true, beforeList = null) {
        if (!items || items.length === 0) return;
        const data = await apiRequest(barricadesBulkUrl, 'PUT', { items });
        if (Array.isArray(data.barricades)) ctx.state.barricades = data.barricades;
        if (Array.isArray(data.callouts)) ctx.state.callouts = data.callouts;
        deps.renderer.renderAll();
        applySummary(data);
        if (recordHistory && beforeList) {
            deps.history.pushHistory({ type: 'bulk_update_barricades', before: beforeList, after: items });
        }
    }

    async function deleteBarricade(id, recordHistory = true, refresh = true) {
        const target = ctx.state.barricades.find((b) => b.id === id);
        if (!target) return;
        const snapshot = { ...target };

        const data = await apiRequest(`${barricadesUrl}/${id}`, 'DELETE');
        ctx.state.barricades = ctx.state.barricades.filter((b) => b.id !== id);
        ctx.state.selectedItems.delete(selectionKey('barricade', id));
        const node = findNodeElement(ctx, 'barricade', id);
        if (node) node.remove();
        if (refresh) applySummary(data);
        if (recordHistory) deps.history.pushHistory({ type: 'delete_barricade', snapshot });
    }

    // --- 吹き出し ---
    async function createCallout(payload, recordHistory = true) {
        const data = await apiRequest(calloutsUrl, 'POST', payload);
        if (data.callout) {
            ctx.state.callouts.push(data.callout);
            deps.renderer.renderNode('callout', data.callout);
            deps.inspector.setSingleSelection('callout', data.callout.id);
            applySummary(data);
            if (recordHistory) deps.history.pushHistory({ type: 'create_callout', snapshot: { ...data.callout } });
            return data.callout;
        }
        return null;
    }

    async function updateCallout(callout, patch, recordHistory = true, beforeOverride = null) {
        const before = beforeOverride || {
            id: callout.id,
            target_cone_id: callout.target_cone_id,
            target_barricade_id: callout.target_barricade_id,
            anchor_x: callout.anchor_x,
            anchor_y: callout.anchor_y,
            box_x: callout.box_x,
            box_y: callout.box_y,
            box_width: callout.box_width,
            box_height: callout.box_height,
            title: callout.title,
            body: callout.body,
            color_theme: callout.color_theme,
            font_size: callout.font_size,
        };
        Object.assign(callout, patch);
        deps.renderer.renderNode('callout', callout);

        const data = await apiRequest(`${calloutsUrl}/${callout.id}`, 'PUT', patch);
        if (data.callout) Object.assign(callout, data.callout);
        deps.renderer.renderNode('callout', callout);
        applySummary(data);

        if (recordHistory) {
            deps.history.pushHistory({
                type: 'update_callout',
                before,
                after: {
                    id: callout.id,
                    target_cone_id: callout.target_cone_id,
                    target_barricade_id: callout.target_barricade_id,
                    anchor_x: callout.anchor_x,
                    anchor_y: callout.anchor_y,
                    box_x: callout.box_x,
                    box_y: callout.box_y,
                    box_width: callout.box_width,
                    box_height: callout.box_height,
                    title: callout.title,
                    body: callout.body,
                    color_theme: callout.color_theme,
                    font_size: callout.font_size,
                },
            });
        }
        return callout;
    }

    async function deleteCallout(id, recordHistory = true, refresh = true) {
        const target = ctx.state.callouts.find((c) => c.id === id);
        if (!target) return;
        const snapshot = { ...target };

        const data = await apiRequest(`${calloutsUrl}/${id}`, 'DELETE');
        ctx.state.callouts = ctx.state.callouts.filter((c) => c.id !== id);
        ctx.state.selectedItems.delete(selectionKey('callout', id));
        const node = findNodeElement(ctx, 'callout', id);
        if (node) node.remove();
        if (refresh) applySummary(data);
        if (recordHistory) deps.history.pushHistory({ type: 'delete_callout', snapshot });
    }

    async function deleteSelectedItems() {
        const items = [...ctx.state.selectedItems.values()];
        if (items.length === 0) return;

        // バーを先に、その後コーン・バリケード・吹き出しを削除
        const bars = items.filter((i) => i.kind === 'bar');
        const others = items.filter((i) => i.kind !== 'bar');

        for (const item of bars) {
            if (ctx.state.bars.some((b) => b.id === item.id)) {
                await deleteBar(item.id, true, false);
            }
        }
        for (const item of others) {
            if (item.kind === 'cone') await deleteCone(item.id, true, false);
            else if (item.kind === 'barricade') await deleteBarricade(item.id, true, false);
            else if (item.kind === 'callout') await deleteCallout(item.id, true, false);
        }

        const latest = await apiRequest(ctx.config.dataUrl, 'GET');
        applySummary(latest);
        showToast(ctx, `${items.length}件を削除しました`);
    }

    function bindClearAndBaseMapActions() {
        const { btnClearAll, selectBaseMap } = ctx.dom;

        if (selectBaseMap && baseMapUpdateUrl) {
            selectBaseMap.addEventListener('change', async () => {
                const newId = selectBaseMap.value;
                const prevId = selectBaseMap.dataset.currentBaseMapId;
                if (!newId || newId === prevId) return;

                const selectedLabel = selectBaseMap.options[selectBaseMap.selectedIndex]?.textContent?.trim() || '選択した白地図';
                const ok = await confirmDialog(
                    `使用する白地図を「${selectedLabel}」に切り替えますか？\n切り替え後はエディタを再読み込みします。`,
                    { variant: 'primary', icon: '🗺️', title: '使用白地図の切り替え', confirmText: '切り替える' },
                );
                if (!ok) {
                    selectBaseMap.value = prevId;
                    return;
                }

                try {
                    await apiRequest(baseMapUpdateUrl, 'PATCH', { site_base_map_id: parseInt(newId, 10) });
                    window.location.reload();
                } catch {
                    selectBaseMap.value = prevId;
                }
            });
        }

        if (btnClearAll) {
            btnClearAll.addEventListener('click', async () => {
                const total =
                    ctx.state.cones.length + ctx.state.bars.length + ctx.state.barricades.length + ctx.state.callouts.length;
                if (total === 0) {
                    showToast(ctx, '配置されているオブジェクトはありません');
                    return;
                }
                const ok = await confirmDialog(
                    `配置済みのカラーコーン(${ctx.state.cones.length}個)・コーンバー(${ctx.state.bars.length}本)・バリケード(${ctx.state.barricades.length}台)・吹き出し(${ctx.state.callouts.length}件)をすべて削除しますか？`,
                    { variant: 'danger', title: '全配置クリアの確認', confirmText: 'すべて削除する' },
                );
                if (!ok) return;

                const data = await apiRequest(clearUrl, 'POST');
                ctx.state.cones = [];
                ctx.state.bars = [];
                ctx.state.barricades = [];
                ctx.state.callouts = [];
                ctx.state.selectedItems.clear();
                ctx.state.undoStack = [];
                ctx.state.redoStack = [];
                deps.renderer.renderAll();
                applySummary(data);
                showToast(ctx, 'すべての配置をクリアしました');
            });
        }
    }

    return {
        createCone,
        updateCone,
        bulkUpdateCones,
        deleteCone,
        createBar,
        updateBar,
        deleteBar,
        createConeLine,
        createBarricade,
        updateBarricade,
        bulkUpdateBarricades,
        deleteBarricade,
        createCallout,
        updateCallout,
        deleteCallout,
        deleteSelectedItems,
        bindClearAndBaseMapActions,
        applySummary,
    };
}
