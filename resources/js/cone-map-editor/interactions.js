import { BARRICADE_SPECS, STREET_RAILS_X } from './constants.js';
import { selectionKey, showToast } from './context.js';
import { fitToScreen, getSvgCoords, updateTransform, zoomAround } from './viewport.js';

/**
 * キャンバス操作・スナップ・連結プレビュー・D&D・ショートカット管理
 */
export function createConeMapInteractions(ctx, deps) {
    const { scaleRatio } = ctx.config;

    function computeSnapPoint(rawX, rawY, excludeIds = new Set()) {
        deps.renderer.clearOverlays();
        if (!ctx.state.snapEnabled) return { x: rawX, y: rawY };

        let sx = rawX;
        let sy = rawY;
        const thresh = 2.0;

        for (const railX of STREET_RAILS_X) {
            if (Math.abs(rawX - railX) <= thresh) {
                sx = railX;
                deps.renderer.drawGuideLine(railX, 80, railX, 1100);
                break;
            }
        }

        for (const other of ctx.state.cones) {
            if (excludeIds.has(other.id)) continue;
            const ox = parseFloat(other.pos_x);
            const oy = parseFloat(other.pos_y);
            if (Math.abs(ox - rawX) <= thresh) {
                sx = ox;
                deps.renderer.drawGuideLine(ox, Math.min(sy, oy) - 12, ox, Math.max(sy, oy) + 12);
            }
            if (Math.abs(oy - rawY) <= thresh) {
                sy = oy;
                deps.renderer.drawGuideLine(Math.min(sx, ox) - 12, oy, Math.max(sx, ox) + 12, oy);
            }
        }

        return { x: Math.round(sx * 100) / 100, y: Math.round(sy * 100) / 100 };
    }

    function computeLinePreviewPoints(startX, startY, endX, endY) {
        const dx = endX - startX;
        const dy = endY - startY;
        const distPt = Math.hypot(dx, dy);
        const distM = distPt / scaleRatio;
        const barLenPt = 2.0 * scaleRatio;

        let ux = 1;
        let uy = 0;
        if (distPt >= 0.5) {
            ux = dx / distPt;
            uy = dy / distPt;
        }

        const points = [];
        if (ctx.state.selectedPitchMode === 'fixed_2m') {
            const spans = Math.min(40, Math.max(1, Math.round(distM / 2.0)));
            for (let i = 0; i <= spans; i++) {
                points.push({
                    x: Math.round((startX + ux * (i * barLenPt)) * 100) / 100,
                    y: Math.round((startY + uy * (i * barLenPt)) * 100) / 100,
                });
            }
        } else {
            const spans = Math.min(40, Math.max(1, Math.ceil(distM / 2.0)));
            for (let i = 0; i <= spans; i++) {
                const t = i / spans;
                points.push({
                    x: Math.round((startX + dx * t) * 100) / 100,
                    y: Math.round((startY + dy * t) * 100) / 100,
                });
            }
        }
        return points;
    }

    function updateModeBanner(customText = null) {
        const { modeBanner } = ctx.dom;
        if (!modeBanner) return;

        let msg = customText;
        if (!msg) {
            if (ctx.state.toolMode === 'place_cone') {
                msg = '🔺 単独コーン配置モード: 地図をクリックするとカラーコーン（＋ウェイト1個）を配置します';
            } else if (ctx.state.toolMode === 'place_cone_line') {
                msg = ctx.state.pendingLineStart
                    ? '🚧 連結終点をクリックしてください (Escでキャンセル)'
                    : '🚧 コーン連結配置モード: 始点をクリック（既存コーン近くで自動連結）';
            } else if (ctx.state.toolMode === 'connect_bar') {
                msg = ctx.state.pendingBarFirstConeId
                    ? '🔗 連結先の2個目のカラーコーンをクリックしてください (Escでキャンセル)'
                    : '🔗 バー連結モード: 1個目のカラーコーンをクリックしてください';
            } else if (ctx.state.toolMode === 'place_callout') {
                msg = '💬 吹き出し配置モード: 指し示したい地点をクリックして注意事項を配置します';
            }
        }

        if (msg) {
            modeBanner.textContent = msg;
            modeBanner.classList.remove('hidden');
        } else {
            modeBanner.classList.add('hidden');
        }
    }

    function setToolMode(mode) {
        ctx.state.toolMode = mode;
        ctx.state.pendingLineStart = null;
        if (mode !== 'connect_bar' && ctx.state.pendingBarFirstConeId !== null) {
            const oldId = ctx.state.pendingBarFirstConeId;
            ctx.state.pendingBarFirstConeId = null;
            const c = deps.inspector.findEntity('cone', oldId);
            if (c) deps.renderer.renderNode('cone', c);
        }
        ctx.state.pendingBarFirstConeId = null;
        deps.renderer.clearOverlays();

        const isPan = mode === 'pan' || ctx.state.spacePressed;
        ctx.dom.canvasContainer.style.cursor = isPan ? 'grab' : mode === 'select' ? 'default' : 'crosshair';

        ctx.dom.modeButtons.forEach((btn) => {
            const active = btn.dataset.mode === mode;
            btn.className = active
                ? 'cone-mode-btn px-2 py-1 rounded-md font-bold bg-indigo-600 text-white transition cursor-pointer'
                : 'cone-mode-btn px-2 py-1 rounded-md font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer';
        });

        updateModeBanner();
    }

    function toggleSelectPan() {
        setToolMode(ctx.state.toolMode === 'select' ? 'pan' : 'select');
    }

    function findNearestCone(svgX, svgY, maxDistPt = 6.0) {
        let best = null;
        let bestDist = maxDistPt;
        for (const c of ctx.state.cones) {
            const d = Math.hypot(parseFloat(c.pos_x) - svgX, parseFloat(c.pos_y) - svgY);
            if (d <= bestDist) {
                bestDist = d;
                best = c;
            }
        }
        return best;
    }

    function handleNodeMouseDown(e, kind, entity) {
        if (e.button !== 0 || ctx.state.toolMode === 'pan' || ctx.state.spacePressed) return;

        // バー連結モード中のコーンクリック
        if (ctx.state.toolMode === 'connect_bar' && kind === 'cone') {
            e.stopPropagation();
            if (ctx.state.pendingBarFirstConeId === null) {
                ctx.state.pendingBarFirstConeId = entity.id;
                deps.renderer.renderNode('cone', entity);
                updateModeBanner();
            } else {
                const firstId = ctx.state.pendingBarFirstConeId;
                ctx.state.pendingBarFirstConeId = null;
                const firstCone = deps.inspector.findEntity('cone', firstId);
                if (firstCone) deps.renderer.renderNode('cone', firstCone);
                updateModeBanner();
                if (firstId !== entity.id) {
                    deps.api
                        .createBar(
                            {
                                start_cone_id: firstId,
                                end_cone_id: entity.id,
                                bar_style: ctx.state.selectedBarStyle,
                            },
                            true,
                        )
                        .catch(() => {});
                }
            }
            return;
        }

        // コーン連結モード中に既存コーンをクリックした場合は始点/終点処理へ流す
        if (ctx.state.toolMode === 'place_cone_line' && kind === 'cone') {
            return;
        }

        if (ctx.state.toolMode !== 'select') return;
        e.stopPropagation();

        const key = selectionKey(kind, entity.id);
        if (e.shiftKey) {
            deps.inspector.toggleSelection(kind, entity.id);
        } else if (!ctx.state.selectedItems.has(key)) {
            deps.inspector.setSingleSelection(kind, entity.id);
        }

        if (kind === 'bar') return; // コーンバー自体は両端コーンに追従するため単体ドラッグ不可

        ctx.state.isMoving = true;
        const sc = getSvgCoords(ctx, e.clientX, e.clientY);
        ctx.state.moveStartSvgX = sc.x;
        ctx.state.moveStartSvgY = sc.y;
        ctx.state.moveOrigins.clear();

        ctx.state.selectedItems.forEach(({ kind: sKind, id: sId }, sKey) => {
            if (sKind === 'bar') return;
            const target = deps.inspector.findEntity(sKind, sId);
            if (!target) return;
            if (sKind === 'callout') {
                ctx.state.moveOrigins.set(sKey, {
                    kind: 'callout',
                    id: sId,
                    box_x: parseFloat(target.box_x),
                    box_y: parseFloat(target.box_y),
                });
            } else {
                ctx.state.moveOrigins.set(sKey, {
                    kind: sKind,
                    id: sId,
                    pos_x: parseFloat(target.pos_x),
                    pos_y: parseFloat(target.pos_y),
                });
            }
        });
    }

    function handleNodeDblClick(e, kind, entity) {
        e.stopPropagation();
        if (ctx.state.toolMode !== 'select' || ctx.state.spacePressed) return;
        if (kind === 'callout') {
            deps.inspector.openInlineCalloutEditor(entity);
        }
    }

    function handleCalloutAnchorMouseDown(e, callout) {
        deps.inspector.setSingleSelection('callout', callout.id);
        ctx.state.calloutAnchorDragId = callout.id;
    }

    async function duplicateSelected() {
        if (ctx.state.selectedItems.size !== 1) return;
        const { kind, id } = [...ctx.state.selectedItems.values()][0];
        const entity = deps.inspector.findEntity(kind, id);
        if (!entity) return;

        if (kind === 'cone') {
            await deps.api.createCone(
                {
                    pos_x: Math.round((parseFloat(entity.pos_x) + 2.0 * scaleRatio) * 100) / 100,
                    pos_y: parseFloat(entity.pos_y),
                    color: entity.color,
                    label: entity.label || null,
                    notes: entity.notes || null,
                },
                true,
            );
        } else if (kind === 'barricade') {
            const spec = BARRICADE_SPECS[entity.barricade_type] || BARRICADE_SPECS.type_a;
            const rad = ((parseInt(entity.rotation, 10) || 0) * Math.PI) / 180;
            const step = spec.lengthM * scaleRatio;
            await deps.api.createBarricade(
                {
                    barricade_type: entity.barricade_type,
                    pos_x: Math.round((parseFloat(entity.pos_x) + Math.cos(rad) * step) * 100) / 100,
                    pos_y: Math.round((parseFloat(entity.pos_y) + Math.sin(rad) * step) * 100) / 100,
                    rotation: parseInt(entity.rotation, 10) || 0,
                    label: entity.label || null,
                    notes: entity.notes || null,
                },
                true,
            );
        }
    }

    async function alignSelectedCones(mode) {
        const cones = [...ctx.state.selectedItems.values()]
            .filter((s) => s.kind === 'cone')
            .map((s) => deps.inspector.findEntity('cone', s.id))
            .filter(Boolean);
        if (cones.length < 2) return;

        const before = cones.map((c) => ({ id: c.id, pos_x: c.pos_x, pos_y: c.pos_y }));
        const items = [];

        if (mode === 'left') {
            const minX = Math.min(...cones.map((c) => parseFloat(c.pos_x)));
            cones.forEach((c) => items.push({ id: c.id, pos_x: minX, pos_y: c.pos_y }));
        } else if (mode === 'top') {
            const minY = Math.min(...cones.map((c) => parseFloat(c.pos_y)));
            cones.forEach((c) => items.push({ id: c.id, pos_x: c.pos_x, pos_y: minY }));
        } else if (mode === '2m') {
            const dxTotal = Math.max(...cones.map((c) => parseFloat(c.pos_x))) - Math.min(...cones.map((c) => parseFloat(c.pos_x)));
            const dyTotal = Math.max(...cones.map((c) => parseFloat(c.pos_y))) - Math.min(...cones.map((c) => parseFloat(c.pos_y)));
            const horizontal = dxTotal >= dyTotal;
            const sorted = [...cones].sort((a, b) => (horizontal ? a.pos_x - b.pos_x : a.pos_y - b.pos_y));
            const step = 2.0 * scaleRatio;
            const baseX = parseFloat(sorted[0].pos_x);
            const baseY = parseFloat(sorted[0].pos_y);
            sorted.forEach((c, idx) => {
                items.push({
                    id: c.id,
                    pos_x: horizontal ? Math.round((baseX + idx * step) * 100) / 100 : baseX,
                    pos_y: horizontal ? baseY : Math.round((baseY + idx * step) * 100) / 100,
                });
            });
        }

        await deps.api.bulkUpdateCones(items, true, before);
        showToast(ctx, '整列を適用しました');
    }

    function bindInteractionEvents() {
        const { canvasContainer, marqueeBox } = ctx.dom;
        const { state } = ctx;

        canvasContainer.addEventListener('contextmenu', (e) => {
            const tag = e.target?.tagName?.toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
            e.preventDefault();
            toggleSelectPan();
        });

        canvasContainer.addEventListener('mousedown', async (e) => {
            if (e.button !== 0) return;

            if (state.toolMode === 'pan' || state.spacePressed) {
                state.isPanning = true;
                state.panStartX = e.clientX - state.x;
                state.panStartY = e.clientY - state.y;
                canvasContainer.style.cursor = 'grabbing';
                return;
            }

            const sc = getSvgCoords(ctx, e.clientX, e.clientY);

            if (state.toolMode === 'place_cone') {
                const snapped = computeSnapPoint(sc.x, sc.y);
                deps.renderer.clearOverlays();
                await deps.api.createCone({ pos_x: snapped.x, pos_y: snapped.y, color: state.selectedConeColor }, true);
                return;
            }

            if (state.toolMode === 'place_cone_line') {
                if (!state.pendingLineStart) {
                    const near = findNearestCone(sc.x, sc.y);
                    state.pendingLineStart = near
                        ? { x: parseFloat(near.pos_x), y: parseFloat(near.pos_y), startConeId: near.id }
                        : { ...computeSnapPoint(sc.x, sc.y), startConeId: null };
                    deps.renderer.clearOverlays();
                    updateModeBanner();
                } else {
                    const start = state.pendingLineStart;
                    const end = computeSnapPoint(sc.x, sc.y);
                    state.pendingLineStart = null;
                    deps.renderer.clearOverlays();
                    updateModeBanner();
                    await deps.api.createConeLine(
                        {
                            start_x: start.x,
                            start_y: start.y,
                            end_x: end.x,
                            end_y: end.y,
                            color: state.selectedConeColor,
                            bar_style: state.selectedBarStyle,
                            pitch_mode: state.selectedPitchMode,
                            start_cone_id: start.startConeId,
                        },
                        true,
                    );
                }
                return;
            }

            if (state.toolMode === 'place_callout') {
                await deps.api.createCallout(
                    {
                        anchor_x: Math.round(sc.x * 100) / 100,
                        anchor_y: Math.round(sc.y * 100) / 100,
                        box_x: Math.round((sc.x + 14) * 100) / 100,
                        box_y: Math.round((sc.y - 34) * 100) / 100,
                        title: '注意事項',
                        body: 'ここに注意事項を入力',
                        color_theme: state.selectedCalloutColor,
                    },
                    true,
                );
                setToolMode('select');
                return;
            }

            if (state.toolMode === 'select') {
                state.isMarquee = true;
                state.marqueeStartClientX = e.clientX;
                state.marqueeStartClientY = e.clientY;
                if (!e.shiftKey) deps.inspector.clearSelection();
            }
        });

        let rafId = null;
        let pendingEv = null;

        function processMove(ev) {
            if (state.isPanning) {
                state.x = ev.clientX - state.panStartX;
                state.y = ev.clientY - state.panStartY;
                updateTransform(ctx, false);
                return;
            }

            if (state.toolMode === 'place_cone_line' && state.pendingLineStart) {
                const sc = getSvgCoords(ctx, ev.clientX, ev.clientY);
                const points = computeLinePreviewPoints(state.pendingLineStart.x, state.pendingLineStart.y, sc.x, sc.y);
                deps.renderer.renderLinePreview(points, state.selectedBarStyle);
                const newCones = state.pendingLineStart.startConeId ? points.length - 1 : points.length;
                const barsCount = points.length - 1;
                const segDist = points.length >= 2 ? Math.hypot(points[1].x - points[0].x, points[1].y - points[0].y) / scaleRatio : 0;
                updateModeBanner(
                    `🚧 連結プレビュー: 新規コーン${newCones}個（＋ウェイト${newCones}個）＋ コーンバー${barsCount}本 [間隔 ${segDist.toFixed(2)}m]`,
                );
                return;
            }

            if (state.isMarquee && marqueeBox) {
                const rect = canvasContainer.getBoundingClientRect();
                const x1 = Math.min(state.marqueeStartClientX, ev.clientX) - rect.left;
                const y1 = Math.min(state.marqueeStartClientY, ev.clientY) - rect.top;
                const w = Math.abs(ev.clientX - state.marqueeStartClientX);
                const h = Math.abs(ev.clientY - state.marqueeStartClientY);
                if (w > 4 || h > 4) {
                    marqueeBox.style.left = `${x1}px`;
                    marqueeBox.style.top = `${y1}px`;
                    marqueeBox.style.width = `${w}px`;
                    marqueeBox.style.height = `${h}px`;
                    marqueeBox.classList.remove('hidden');
                }
                return;
            }

            if (state.calloutAnchorDragId) {
                const c = deps.inspector.findEntity('callout', state.calloutAnchorDragId);
                if (!c) return;
                const sc = getSvgCoords(ctx, ev.clientX, ev.clientY);
                c.anchor_x = Math.round(sc.x * 100) / 100;
                c.anchor_y = Math.round(sc.y * 100) / 100;
                c.target_cone_id = null;
                c.target_barricade_id = null;
                deps.renderer.renderNode('callout', c);
                return;
            }

            if (state.calloutResizeId && state.calloutResizeOrig) {
                const c = deps.inspector.findEntity('callout', state.calloutResizeId);
                if (!c) return;
                const sc = getSvgCoords(ctx, ev.clientX, ev.clientY);
                c.box_width = Math.max(24, Math.round((state.calloutResizeOrig.bw + sc.x - state.calloutResizeStartSvgX) * 100) / 100);
                c.box_height = Math.max(14, Math.round((state.calloutResizeOrig.bh + sc.y - state.calloutResizeStartSvgY) * 100) / 100);
                deps.renderer.renderNode('callout', c);
                return;
            }

            if (state.isMoving) {
                const sc = getSvgCoords(ctx, ev.clientX, ev.clientY);
                const dx = sc.x - state.moveStartSvgX;
                const dy = sc.y - state.moveStartSvgY;

                const singleSnapCone = state.moveOrigins.size === 1 && [...state.moveOrigins.values()][0].kind === 'cone';

                state.moveOrigins.forEach((orig) => {
                    const target = deps.inspector.findEntity(orig.kind, orig.id);
                    if (!target) return;
                    if (orig.kind === 'callout') {
                        target.box_x = Math.round((orig.box_x + dx) * 100) / 100;
                        target.box_y = Math.round((orig.box_y + dy) * 100) / 100;
                        deps.renderer.renderNode('callout', target);
                    } else {
                        let nx = orig.pos_x + dx;
                        let ny = orig.pos_y + dy;
                        if (singleSnapCone && !ev.altKey) {
                            const snapped = computeSnapPoint(nx, ny, new Set([orig.id]));
                            nx = snapped.x;
                            ny = snapped.y;
                        } else {
                            nx = Math.round(nx * 100) / 100;
                            ny = Math.round(ny * 100) / 100;
                        }
                        target.pos_x = nx;
                        target.pos_y = ny;
                        deps.renderer.renderNode(orig.kind, target);
                        if (orig.kind === 'cone') {
                            deps.renderer.renderConnectedBarsForCone(orig.id);
                            state.callouts.forEach((c) => {
                                if (c.target_cone_id === orig.id) {
                                    c.anchor_x = nx;
                                    c.anchor_y = ny;
                                }
                            });
                            deps.renderer.renderCalloutsForTarget('cone', orig.id);
                        } else if (orig.kind === 'barricade') {
                            state.callouts.forEach((c) => {
                                if (c.target_barricade_id === orig.id) {
                                    c.anchor_x = nx;
                                    c.anchor_y = ny;
                                }
                            });
                            deps.renderer.renderCalloutsForTarget('barricade', orig.id);
                        }
                    }
                });
            }
        }

        window.addEventListener('mousemove', (e) => {
            const needMove =
                state.isPanning ||
                state.isMarquee ||
                state.isMoving ||
                state.calloutAnchorDragId !== null ||
                state.calloutResizeId !== null ||
                (state.toolMode === 'place_cone_line' && state.pendingLineStart !== null);
            if (!needMove) return;

            pendingEv = { clientX: e.clientX, clientY: e.clientY, altKey: e.altKey };
            if (rafId === null) {
                rafId = requestAnimationFrame(() => {
                    rafId = null;
                    if (pendingEv) {
                        const ev = pendingEv;
                        pendingEv = null;
                        processMove(ev);
                    }
                });
            }
        });

        window.addEventListener('mouseup', async (e) => {
            if (rafId !== null) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }
            if (pendingEv) {
                const ev = pendingEv;
                pendingEv = null;
                processMove(ev);
            }

            if (state.isPanning) {
                state.isPanning = false;
                canvasContainer.style.cursor = state.toolMode === 'pan' || state.spacePressed ? 'grab' : 'default';
                return;
            }

            if (state.isMarquee) {
                state.isMarquee = false;
                if (marqueeBox) marqueeBox.classList.add('hidden');
                const w = Math.abs(e.clientX - state.marqueeStartClientX);
                const h = Math.abs(e.clientY - state.marqueeStartClientY);
                if (w > 5 || h > 5) {
                    const p1 = getSvgCoords(ctx, state.marqueeStartClientX, state.marqueeStartClientY);
                    const p2 = getSvgCoords(ctx, e.clientX, e.clientY);
                    const rx1 = Math.min(p1.x, p2.x);
                    const rx2 = Math.max(p1.x, p2.x);
                    const ry1 = Math.min(p1.y, p2.y);
                    const ry2 = Math.max(p1.y, p2.y);

                    state.cones.forEach((c) => {
                        if (c.pos_x >= rx1 && c.pos_x <= rx2 && c.pos_y >= ry1 && c.pos_y <= ry2) {
                            state.selectedItems.set(selectionKey('cone', c.id), { kind: 'cone', id: c.id });
                        }
                    });
                    state.barricades.forEach((b) => {
                        if (b.pos_x >= rx1 && b.pos_x <= rx2 && b.pos_y >= ry1 && b.pos_y <= ry2) {
                            state.selectedItems.set(selectionKey('barricade', b.id), { kind: 'barricade', id: b.id });
                        }
                    });
                    state.callouts.forEach((c) => {
                        const bx = parseFloat(c.box_x);
                        const by = parseFloat(c.box_y);
                        const bw = parseFloat(c.box_width);
                        const bh = parseFloat(c.box_height);
                        if (bx + bw >= rx1 && bx <= rx2 && by + bh >= ry1 && by <= ry2) {
                            state.selectedItems.set(selectionKey('callout', c.id), { kind: 'callout', id: c.id });
                        }
                    });
                    deps.renderer.renderAll();
                }
                return;
            }

            if (state.calloutAnchorDragId) {
                const c = deps.inspector.findEntity('callout', state.calloutAnchorDragId);
                state.calloutAnchorDragId = null;
                if (c) {
                    await deps.api.updateCallout(
                        c,
                        {
                            anchor_x: c.anchor_x,
                            anchor_y: c.anchor_y,
                            target_cone_id: null,
                            target_barricade_id: null,
                        },
                        true,
                    );
                }
                return;
            }

            if (state.calloutResizeId) {
                const c = deps.inspector.findEntity('callout', state.calloutResizeId);
                const orig = state.calloutResizeOrig;
                state.calloutResizeId = null;
                state.calloutResizeOrig = null;
                if (c && orig && (c.box_width !== orig.bw || c.box_height !== orig.bh)) {
                    await deps.api.updateCallout(c, { box_width: c.box_width, box_height: c.box_height }, true);
                }
                return;
            }

            if (state.isMoving) {
                state.isMoving = false;
                deps.renderer.clearOverlays();

                const coneUpdates = [];
                const coneBefores = [];
                const barricadeUpdates = [];
                const barricadeBefores = [];
                const calloutUpdates = [];

                state.moveOrigins.forEach((orig) => {
                    const target = deps.inspector.findEntity(orig.kind, orig.id);
                    if (!target) return;
                    if (orig.kind === 'cone' && (target.pos_x !== orig.pos_x || target.pos_y !== orig.pos_y)) {
                        coneBefores.push({ id: orig.id, pos_x: orig.pos_x, pos_y: orig.pos_y });
                        coneUpdates.push({ id: orig.id, pos_x: target.pos_x, pos_y: target.pos_y });
                    } else if (orig.kind === 'barricade' && (target.pos_x !== orig.pos_x || target.pos_y !== orig.pos_y)) {
                        barricadeBefores.push({ id: orig.id, pos_x: orig.pos_x, pos_y: orig.pos_y });
                        barricadeUpdates.push({ id: orig.id, pos_x: target.pos_x, pos_y: target.pos_y });
                    } else if (orig.kind === 'callout' && (target.box_x !== orig.box_x || target.box_y !== orig.box_y)) {
                        calloutUpdates.push({ target, orig });
                    }
                });

                if (coneUpdates.length === 1) {
                    const c = deps.inspector.findEntity('cone', coneUpdates[0].id);
                    if (c) await deps.api.updateCone(c, coneUpdates[0], true, coneBefores[0]);
                } else if (coneUpdates.length > 1) {
                    await deps.api.bulkUpdateCones(coneUpdates, true, coneBefores);
                }

                if (barricadeUpdates.length === 1) {
                    const b = deps.inspector.findEntity('barricade', barricadeUpdates[0].id);
                    if (b) await deps.api.updateBarricade(b, barricadeUpdates[0], true, barricadeBefores[0]);
                } else if (barricadeUpdates.length > 1) {
                    await deps.api.bulkUpdateBarricades(barricadeUpdates, true, barricadeBefores);
                }

                for (const item of calloutUpdates) {
                    await deps.api.updateCallout(item.target, { box_x: item.target.box_x, box_y: item.target.box_y }, true, {
                        ...item.target,
                        box_x: item.orig.box_x,
                        box_y: item.orig.box_y,
                    });
                }
            }
        });

        canvasContainer.addEventListener(
            'wheel',
            (e) => {
                e.preventDefault();
                zoomAround(ctx, e.deltaY < 0 ? 1.15 : 0.85, e.clientX, e.clientY);
            },
            { passive: false },
        );

        // パレット D&D / クリック連携
        ctx.dom.paletteItems.forEach((item) => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData(
                    'application/json',
                    JSON.stringify({
                        kind: item.dataset.paletteKind,
                        barricade_type: item.dataset.barricadeType || 'type_a',
                    }),
                );
                e.dataTransfer.effectAllowed = 'copy';
            });
            item.addEventListener('click', () => {
                if (item.dataset.paletteKind === 'cone') setToolMode('place_cone');
                else if (item.dataset.paletteKind === 'callout') setToolMode('place_callout');
            });
        });

        canvasContainer.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        });

        canvasContainer.addEventListener('drop', async (e) => {
            e.preventDefault();
            const raw = e.dataTransfer.getData('application/json');
            if (!raw) return;
            let data;
            try {
                data = JSON.parse(raw);
            } catch {
                return;
            }
            const sc = getSvgCoords(ctx, e.clientX, e.clientY);
            const snapped = computeSnapPoint(sc.x, sc.y);
            deps.renderer.clearOverlays();

            if (data.kind === 'cone') {
                await deps.api.createCone({ pos_x: snapped.x, pos_y: snapped.y, color: state.selectedConeColor }, true);
            } else if (data.kind === 'barricade') {
                await deps.api.createBarricade(
                    {
                        barricade_type: data.barricade_type || 'type_a',
                        pos_x: snapped.x,
                        pos_y: snapped.y,
                        rotation: 0,
                    },
                    true,
                );
            } else if (data.kind === 'callout') {
                await deps.api.createCallout(
                    {
                        anchor_x: snapped.x,
                        anchor_y: snapped.y,
                        box_x: Math.round((snapped.x + 14) * 100) / 100,
                        box_y: Math.round((snapped.y - 34) * 100) / 100,
                        title: '注意事項',
                        body: 'ここに注意事項を入力',
                        color_theme: state.selectedCalloutColor,
                    },
                    true,
                );
            }
        });

        // キーボードショートカット
        window.addEventListener('keydown', (e) => {
            const tag = document.activeElement?.tagName?.toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

            if (e.code === 'Space' && !state.spacePressed) {
                e.preventDefault();
                state.spacePressed = true;
                canvasContainer.style.cursor = 'grab';
            } else if (e.key === 'v' || e.key === 'V') {
                setToolMode('select');
            } else if (e.key === 'h' || e.key === 'H') {
                setToolMode('pan');
            } else if (e.key === 'c' || e.key === 'C') {
                if (!e.ctrlKey && !e.metaKey) setToolMode('place_cone');
            } else if (e.key === 'b' || e.key === 'B') {
                setToolMode('place_cone_line');
            } else if (e.key === 'l' || e.key === 'L') {
                setToolMode('connect_bar');
            } else if (e.key === 'n' || e.key === 'N') {
                setToolMode('place_callout');
            } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' && !e.shiftKey) {
                e.preventDefault();
                deps.history.performUndo();
            } else if ((e.ctrlKey || e.metaKey) && (e.key.toLowerCase() === 'y' || (e.shiftKey && e.key.toLowerCase() === 'z'))) {
                e.preventDefault();
                deps.history.performRedo();
            } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'd') {
                e.preventDefault();
                duplicateSelected();
            } else if (e.key === 'Delete' || e.key === 'Backspace') {
                if (state.selectedItems.size > 0) {
                    e.preventDefault();
                    deps.api.deleteSelectedItems();
                }
            } else if (e.key === 'r' || e.key === 'R') {
                if (state.selectedItems.size === 1) {
                    const sel = [...state.selectedItems.values()][0];
                    if (sel.kind === 'barricade') {
                        const b = deps.inspector.findEntity('barricade', sel.id);
                        if (b) {
                            e.preventDefault();
                            const delta = e.shiftKey ? 15 : 45;
                            deps.api.updateBarricade(b, { rotation: ((parseInt(b.rotation, 10) || 0) + delta) % 360 }, true);
                        }
                    }
                }
            } else if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key) && state.selectedItems.size > 0) {
                e.preventDefault();
                const step = e.shiftKey ? 3.54 : 0.5;
                const dx = e.key === 'ArrowLeft' ? -step : e.key === 'ArrowRight' ? step : 0;
                const dy = e.key === 'ArrowUp' ? -step : e.key === 'ArrowDown' ? step : 0;

                const coneItems = [];
                const barricadeItems = [];
                state.selectedItems.forEach(({ kind, id }) => {
                    const target = deps.inspector.findEntity(kind, id);
                    if (!target) return;
                    if (kind === 'cone') {
                        target.pos_x = Math.round((parseFloat(target.pos_x) + dx) * 100) / 100;
                        target.pos_y = Math.round((parseFloat(target.pos_y) + dy) * 100) / 100;
                        deps.renderer.renderNode('cone', target);
                        deps.renderer.renderConnectedBarsForCone(id);
                        coneItems.push({ id, pos_x: target.pos_x, pos_y: target.pos_y });
                    } else if (kind === 'barricade') {
                        target.pos_x = Math.round((parseFloat(target.pos_x) + dx) * 100) / 100;
                        target.pos_y = Math.round((parseFloat(target.pos_y) + dy) * 100) / 100;
                        deps.renderer.renderNode('barricade', target);
                        barricadeItems.push({ id, pos_x: target.pos_x, pos_y: target.pos_y });
                    }
                });
                clearTimeout(state.nudgeTimer);
                state.nudgeTimer = setTimeout(() => {
                    if (coneItems.length > 0) deps.api.bulkUpdateCones(coneItems, false);
                    if (barricadeItems.length > 0) deps.api.bulkUpdateBarricades(barricadeItems, false);
                }, 350);
            } else if (e.key === 'Escape') {
                if (state.pendingLineStart || state.pendingBarFirstConeId) {
                    setToolMode('select');
                } else {
                    deps.inspector.clearSelection();
                }
            }
        });

        window.addEventListener('keyup', (e) => {
            if (e.code === 'Space') {
                state.spacePressed = false;
                canvasContainer.style.cursor = state.toolMode === 'pan' ? 'grab' : state.toolMode === 'select' ? 'default' : 'crosshair';
            }
        });

        // ツールバーボタン
        ctx.dom.modeButtons.forEach((btn) => {
            btn.addEventListener('click', () => setToolMode(btn.dataset.mode));
        });
        ctx.dom.btnUndo?.addEventListener('click', deps.history.performUndo);
        ctx.dom.btnRedo?.addEventListener('click', deps.history.performRedo);
        ctx.dom.btnDuplicate?.addEventListener('click', duplicateSelected);
        ctx.dom.btnDelete?.addEventListener('click', deps.api.deleteSelectedItems);
        ctx.dom.btnRotate?.addEventListener('click', () => {
            if (state.selectedItems.size !== 1) return;
            const sel = [...state.selectedItems.values()][0];
            if (sel.kind !== 'barricade') return;
            const b = deps.inspector.findEntity('barricade', sel.id);
            if (b) deps.api.updateBarricade(b, { rotation: ((parseInt(b.rotation, 10) || 0) + 45) % 360 }, true);
        });
        ctx.dom.btnAlignLeft?.addEventListener('click', () => alignSelectedCones('left'));
        ctx.dom.btnAlignTop?.addEventListener('click', () => alignSelectedCones('top'));
        ctx.dom.btnAlign2m?.addEventListener('click', () => alignSelectedCones('2m'));

        // 表示切替
        ctx.dom.btnToggleEmphasis?.addEventListener('click', () => {
            state.emphasisMode = !state.emphasisMode;
            ctx.dom.btnToggleEmphasis.textContent = state.emphasisMode ? '🔍 表示: 視認強調' : '🔍 表示: 実寸';
            ctx.dom.btnToggleEmphasis.className = state.emphasisMode
                ? 'px-2.5 py-1 bg-indigo-600 text-white border border-indigo-600 rounded-lg font-bold shadow-2xs transition cursor-pointer'
                : 'px-2.5 py-1 bg-white text-slate-700 border border-slate-300 rounded-lg font-bold shadow-2xs transition cursor-pointer';
            deps.inspector.syncExportPdfUrl();
            deps.renderer.renderAll();
        });

        ctx.dom.btnToggleBooths?.addEventListener('click', () => {
            state.showBooths = !state.showBooths;
            ctx.dom.btnToggleBooths.textContent = state.showBooths ? '🎪 ブース参考 ON' : '🎪 ブース参考 OFF';
            ctx.dom.btnToggleBooths.className = state.showBooths
                ? 'px-2.5 py-1 bg-amber-600 text-white border border-amber-600 rounded-lg font-bold shadow-2xs transition cursor-pointer'
                : 'px-2.5 py-1 bg-white text-slate-600 border border-slate-300 rounded-lg font-bold shadow-2xs transition cursor-pointer';
            deps.inspector.syncExportPdfUrl();
            deps.renderer.renderBoothReference();
        });

        ctx.dom.btnToggleSnap?.addEventListener('click', () => {
            state.snapEnabled = !state.snapEnabled;
            ctx.dom.btnToggleSnap.textContent = state.snapEnabled ? '🧲 吸着 ON' : '🧲 吸着 OFF';
            ctx.dom.btnToggleSnap.className = state.snapEnabled
                ? 'px-2.5 py-1 bg-emerald-600 text-white border border-emerald-600 rounded-lg font-bold shadow-2xs transition cursor-pointer'
                : 'px-2.5 py-1 bg-white text-slate-600 border border-slate-300 rounded-lg font-bold shadow-2xs transition cursor-pointer';
        });

        // ズーム
        ctx.dom.btnZoomIn?.addEventListener('click', () => {
            const r = canvasContainer.getBoundingClientRect();
            zoomAround(ctx, 1.25, r.left + r.width / 2, r.top + r.height / 2);
        });
        ctx.dom.btnZoomOut?.addEventListener('click', () => {
            const r = canvasContainer.getBoundingClientRect();
            zoomAround(ctx, 0.8, r.left + r.width / 2, r.top + r.height / 2);
        });
        ctx.dom.btnZoom100?.addEventListener('click', () => {
            const r = canvasContainer.getBoundingClientRect();
            state.scale = 1.0;
            state.x = (r.width - state.svgWidth) / 2;
            state.y = (r.height - state.svgHeight) / 2;
            updateTransform(ctx);
        });
        ctx.dom.btnZoomFit?.addEventListener('click', () => fitToScreen(ctx));

        // 左ペインタブ＆パレット設定
        ctx.dom.tabBtnPalette?.addEventListener('click', () => {
            ctx.dom.tabPanelPalette.classList.remove('hidden');
            ctx.dom.tabPanelCallouts.classList.add('hidden');
            ctx.dom.tabPanelCallouts.classList.remove('flex');
            ctx.dom.tabBtnPalette.className = 'py-1 rounded-lg text-xs font-bold bg-white text-indigo-700 shadow-2xs transition cursor-pointer';
            ctx.dom.tabBtnCallouts.className = 'py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer';
        });
        ctx.dom.tabBtnCallouts?.addEventListener('click', () => {
            ctx.dom.tabPanelPalette.classList.add('hidden');
            ctx.dom.tabPanelCallouts.classList.remove('hidden');
            ctx.dom.tabPanelCallouts.classList.add('flex');
            ctx.dom.tabBtnCallouts.className = 'py-1 rounded-lg text-xs font-bold bg-white text-indigo-700 shadow-2xs transition cursor-pointer';
            ctx.dom.tabBtnPalette.className = 'py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer';
        });

        ctx.dom.paletteConeColorBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                state.selectedConeColor = btn.dataset.coneColor;
                ctx.dom.paletteConeColorBtns.forEach((b) => {
                    const active = b.dataset.coneColor === state.selectedConeColor;
                    b.classList.toggle('ring-2', active);
                    b.classList.toggle('ring-indigo-600', active);
                    b.classList.toggle('ring-offset-1', active);
                });
            });
        });

        ctx.dom.btnPaletteConeLine?.addEventListener('click', () => setToolMode('place_cone_line'));
        ctx.dom.paletteBarStyle?.addEventListener('change', () => {
            state.selectedBarStyle = ctx.dom.paletteBarStyle.value;
        });
        ctx.dom.palettePitchMode?.addEventListener('change', () => {
            state.selectedPitchMode = ctx.dom.palettePitchMode.value;
        });
        ctx.dom.paletteCalloutColorBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                state.selectedCalloutColor = btn.dataset.calloutColor;
                ctx.dom.paletteCalloutColorBtns.forEach((b) => {
                    b.classList.toggle('ring-2', b.dataset.calloutColor === state.selectedCalloutColor);
                    b.classList.toggle('ring-indigo-600', b.dataset.calloutColor === state.selectedCalloutColor);
                });
            });
        });
    }

    return {
        setToolMode,
        handleNodeMouseDown,
        handleNodeDblClick,
        handleCalloutAnchorMouseDown,
        bindInteractionEvents,
    };
}
