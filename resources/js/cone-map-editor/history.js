/**
 * Undo / Redo ヒストリー管理
 */
export function createConeMapHistory(ctx, deps) {
    function pushHistory(action) {
        ctx.state.undoStack.push(action);
        if (ctx.state.undoStack.length > 30) ctx.state.undoStack.shift();
        ctx.state.redoStack = [];
        deps.inspector.updateSummaryAndInspector();
    }

    async function applyAction(action, isUndo) {
        if (action.type === 'create_cone') {
            if (isUndo) {
                await deps.api.deleteCone(action.snapshot.id, false);
            } else {
                const created = await deps.api.createCone(action.snapshot, false);
                if (created) action.snapshot = { ...created };
            }
        } else if (action.type === 'update_cone') {
            const target = ctx.state.cones.find((c) => c.id === action.before.id);
            if (target) await deps.api.updateCone(target, isUndo ? action.before : action.after, false);
        } else if (action.type === 'bulk_update_cones') {
            await deps.api.bulkUpdateCones(isUndo ? action.before : action.after, false);
        } else if (action.type === 'delete_cone') {
            if (isUndo) {
                const oldId = action.snapshot.id;
                const recreated = await deps.api.createCone(action.snapshot, false);
                if (recreated) {
                    action.snapshot = { ...recreated };
                    for (const bar of action.connectedBars || []) {
                        const startId = bar.start_cone_id === oldId ? recreated.id : bar.start_cone_id;
                        const endId = bar.end_cone_id === oldId ? recreated.id : bar.end_cone_id;
                        if (ctx.state.cones.some((c) => c.id === startId) && ctx.state.cones.some((c) => c.id === endId)) {
                            await deps.api.createBar(
                                { start_cone_id: startId, end_cone_id: endId, bar_style: bar.bar_style, notes: bar.notes },
                                false,
                            );
                        }
                    }
                }
            } else {
                await deps.api.deleteCone(action.snapshot.id, false);
            }
        } else if (action.type === 'create_bar') {
            if (isUndo) {
                await deps.api.deleteBar(action.snapshot.id, false);
            } else {
                const created = await deps.api.createBar(action.snapshot, false);
                if (created) action.snapshot = { ...created };
            }
        } else if (action.type === 'update_bar') {
            const target = ctx.state.bars.find((b) => b.id === action.before.id);
            if (target) await deps.api.updateBar(target, isUndo ? action.before : action.after, false);
        } else if (action.type === 'delete_bar') {
            if (isUndo) {
                const created = await deps.api.createBar(action.snapshot, false);
                if (created) action.snapshot = { ...created };
            } else {
                await deps.api.deleteBar(action.snapshot.id, false);
            }
        } else if (action.type === 'create_cone_line') {
            if (isUndo) {
                for (const c of action.cones) {
                    if (ctx.state.cones.some((item) => item.id === c.id)) {
                        await deps.api.deleteCone(c.id, false, false);
                    }
                }
                deps.renderer.renderAll();
                const latest = await fetch(ctx.config.dataUrl, { headers: { Accept: 'application/json' } }).then((r) => r.json());
                deps.api.applySummary(latest);
            } else {
                // 始点〜終点を取り直して再作成
                if (action.cones.length >= 2) {
                    const first = action.cones[0];
                    const last = action.cones[action.cones.length - 1];
                    const res = await deps.api.createConeLine(
                        {
                            start_x: first.pos_x,
                            start_y: first.pos_y,
                            end_x: last.pos_x,
                            end_y: last.pos_y,
                            color: first.color,
                            bar_style: action.bars[0]?.bar_style || 'yellow_black',
                            pitch_mode: 'even_max_2m',
                        },
                        false,
                    );
                    action.cones = (res.cones || []).map((c) => ({ ...c }));
                    action.bars = (res.bars || []).map((b) => ({ ...b }));
                }
            }
        } else if (action.type === 'create_barricade') {
            if (isUndo) {
                await deps.api.deleteBarricade(action.snapshot.id, false);
            } else {
                const created = await deps.api.createBarricade(action.snapshot, false);
                if (created) action.snapshot = { ...created };
            }
        } else if (action.type === 'update_barricade') {
            const target = ctx.state.barricades.find((b) => b.id === action.before.id);
            if (target) await deps.api.updateBarricade(target, isUndo ? action.before : action.after, false);
        } else if (action.type === 'bulk_update_barricades') {
            await deps.api.bulkUpdateBarricades(isUndo ? action.before : action.after, false);
        } else if (action.type === 'delete_barricade') {
            if (isUndo) {
                const created = await deps.api.createBarricade(action.snapshot, false);
                if (created) action.snapshot = { ...created };
            } else {
                await deps.api.deleteBarricade(action.snapshot.id, false);
            }
        } else if (action.type === 'create_callout') {
            if (isUndo) {
                await deps.api.deleteCallout(action.snapshot.id, false);
            } else {
                const created = await deps.api.createCallout(action.snapshot, false);
                if (created) action.snapshot = { ...created };
            }
        } else if (action.type === 'update_callout') {
            const target = ctx.state.callouts.find((c) => c.id === action.before.id);
            if (target) await deps.api.updateCallout(target, isUndo ? action.before : action.after, false);
        } else if (action.type === 'delete_callout') {
            if (isUndo) {
                const created = await deps.api.createCallout(action.snapshot, false);
                if (created) action.snapshot = { ...created };
            } else {
                await deps.api.deleteCallout(action.snapshot.id, false);
            }
        }
    }

    async function performUndo() {
        const action = ctx.state.undoStack.pop();
        if (!action) return;
        ctx.state.redoStack.push(action);
        await applyAction(action, true);
        deps.inspector.updateSummaryAndInspector();
    }

    async function performRedo() {
        const action = ctx.state.redoStack.pop();
        if (!action) return;
        ctx.state.undoStack.push(action);
        await applyAction(action, false);
        deps.inspector.updateSummaryAndInspector();
    }

    return {
        pushHistory,
        performUndo,
        performRedo,
    };
}
