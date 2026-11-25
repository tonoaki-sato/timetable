import { BARRICADE_SPECS, CALLOUT_COLORS, CONE_COLORS } from './constants.js';
import { selectionKey } from './context.js';
import { focusOnSvgPoint } from './viewport.js';

/**
 * 選択管理・右インスペクタ・サマリー＆機材在庫比較・注意事項一覧同期
 */
export function createConeMapInspector(ctx, deps) {
    const { scaleRatio } = ctx.config;

    function findEntity(kind, id) {
        if (kind === 'cone') return ctx.state.cones.find((c) => c.id === id) || null;
        if (kind === 'bar') return ctx.state.bars.find((b) => b.id === id) || null;
        if (kind === 'barricade') return ctx.state.barricades.find((b) => b.id === id) || null;
        if (kind === 'callout') return ctx.state.callouts.find((c) => c.id === id) || null;
        return null;
    }

    function refreshNodes(itemsMap) {
        itemsMap.forEach(({ kind, id }) => {
            const entity = findEntity(kind, id);
            if (entity) deps.renderer.renderNode(kind, entity);
        });
    }

    function setSingleSelection(kind, id) {
        const prev = new Map(ctx.state.selectedItems);
        ctx.state.selectedItems.clear();
        if (kind && id !== null) {
            ctx.state.selectedItems.set(selectionKey(kind, id), { kind, id });
        }
        refreshNodes(prev);
        refreshNodes(ctx.state.selectedItems);
        updateSummaryAndInspector();
    }

    function toggleSelection(kind, id) {
        const key = selectionKey(kind, id);
        if (ctx.state.selectedItems.has(key)) {
            ctx.state.selectedItems.delete(key);
        } else {
            ctx.state.selectedItems.set(key, { kind, id });
        }
        const entity = findEntity(kind, id);
        if (entity) deps.renderer.renderNode(kind, entity);
        updateSummaryAndInspector();
    }

    function clearSelection() {
        if (ctx.state.selectedItems.size === 0) return;
        const prev = new Map(ctx.state.selectedItems);
        ctx.state.selectedItems.clear();
        refreshNodes(prev);
        updateSummaryAndInspector();
    }

    function syncExportPdfUrl() {
        const { btnExportPdf } = ctx.dom;
        if (!btnExportPdf || !btnExportPdf.dataset.basePdfUrl) return;
        const emp = ctx.state.emphasisMode ? '1' : '0';
        const booths = ctx.state.showBooths ? '1' : '0';
        btnExportPdf.href = `${btnExportPdf.dataset.basePdfUrl}?emphasis=${emp}&show_booths=${booths}`;
    }

    function renderSummaryCards() {
        const sum = ctx.state.summary || {};
        const {
            sumConesCount,
            sumConesBreakdown,
            sumWeightsCount,
            sumBarsCount,
            sumBarricadesTypeA,
            sumBarricadesResinPipe,
            sumCalloutsCount,
            inventoryList,
            colorBreakdown,
            leftCalloutCount,
        } = ctx.dom;

        const conesCount = sum.cones_count ?? ctx.state.cones.length;
        const standalone = sum.standalone_cones_count ?? conesCount;
        const linked = sum.linked_cones_count ?? 0;
        const weightsCount = sum.weights_count ?? conesCount;
        const barsCount = sum.bars_count ?? ctx.state.bars.length;
        const typeACount = sum.barricades_type_a_count ?? ctx.state.barricades.filter((b) => b.barricade_type === 'type_a').length;
        const resinCount = sum.barricades_resin_pipe_count ?? ctx.state.barricades.filter((b) => b.barricade_type === 'resin_pipe').length;
        const calloutsCount = sum.callouts_count ?? ctx.state.callouts.length;

        if (sumConesCount) sumConesCount.textContent = `${conesCount}個`;
        if (sumConesBreakdown) sumConesBreakdown.textContent = `単独 ${standalone}個 / 連結 ${linked}個`;
        if (sumWeightsCount) sumWeightsCount.textContent = `${weightsCount}個`;
        if (sumBarsCount) sumBarsCount.textContent = `${barsCount}本`;
        if (sumBarricadesTypeA) sumBarricadesTypeA.textContent = `${typeACount}台`;
        if (sumBarricadesResinPipe) sumBarricadesResinPipe.textContent = `${resinCount}台`;
        if (sumCalloutsCount) sumCalloutsCount.textContent = `${calloutsCount}件`;
        if (leftCalloutCount) leftCalloutCount.textContent = String(calloutsCount);

        if (inventoryList) {
            inventoryList.innerHTML = '';
            const inv = sum.inventory || {};
            Object.values(inv).forEach((row) => {
                const div = document.createElement('div');
                div.className = row.is_over_stock
                    ? 'flex items-center justify-between px-2 py-1 rounded bg-red-100 border border-red-300 text-red-900 font-bold'
                    : 'flex items-center justify-between px-2 py-1 rounded bg-white border border-sky-100 text-slate-700';

                const left = document.createElement('span');
                left.className = 'truncate pr-1';
                left.textContent = row.label;

                const right = document.createElement('span');
                right.className = 'font-mono shrink-0';
                if (!row.registered) {
                    right.textContent = `${row.used_quantity}${row.unit} (在庫未登録)`;
                    right.className += ' text-slate-400';
                } else {
                    const approx = row.is_approximate ? '約' : '';
                    const remText = row.is_over_stock
                        ? `⚠️ ${Math.abs(row.remaining_quantity)}${row.unit}超過`
                        : `残${row.remaining_quantity}${row.unit}`;
                    right.textContent = `${row.used_quantity}/${approx}${row.stock_quantity}${row.unit} (${remText})`;
                }

                div.appendChild(left);
                div.appendChild(right);
                inventoryList.appendChild(div);
            });
        }

        if (colorBreakdown) {
            colorBreakdown.innerHTML = '';
            const cb = sum.color_breakdown || {};
            Object.entries(CONE_COLORS).forEach(([key, meta]) => {
                const cnt = cb[key] ?? 0;
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between px-2 py-1 bg-slate-50 rounded border border-slate-100';
                const left = document.createElement('span');
                left.className = 'text-slate-600';
                left.textContent = meta.label;
                const right = document.createElement('span');
                right.className = 'font-mono font-bold text-slate-900';
                right.textContent = `${cnt}個`;
                item.appendChild(left);
                item.appendChild(right);
                colorBreakdown.appendChild(item);
            });
        }
    }

    function syncCalloutsList() {
        const { leftCalloutsList } = ctx.dom;
        if (!leftCalloutsList) return;
        leftCalloutsList.innerHTML = '';

        if (ctx.state.callouts.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'py-6 text-center text-[11px] text-slate-400';
            empty.textContent = '注意事項の吹き出しはまだありません。';
            leftCalloutsList.appendChild(empty);
            return;
        }

        ctx.state.callouts.forEach((callout) => {
            const theme = CALLOUT_COLORS[callout.color_theme] || CALLOUT_COLORS.amber;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full text-left p-2 rounded-lg border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/40 bg-slate-50 transition cursor-pointer space-y-0.5';

            const head = document.createElement('div');
            head.className = 'flex items-center justify-between gap-1';

            const badge = document.createElement('span');
            badge.className = 'text-[9px] font-bold px-1.5 py-0.5 rounded bg-white border border-slate-200 text-slate-700';
            badge.textContent = theme.label;

            const titleSpan = document.createElement('span');
            titleSpan.className = 'text-[11px] font-bold text-slate-800 truncate flex-1';
            titleSpan.textContent = callout.title || '注意事項';

            head.appendChild(titleSpan);
            head.appendChild(badge);

            const bodyP = document.createElement('div');
            bodyP.className = 'text-[10px] text-slate-600 line-clamp-2';
            bodyP.textContent = callout.body || '';

            btn.appendChild(head);
            btn.appendChild(bodyP);
            btn.addEventListener('click', () => {
                setSingleSelection('callout', callout.id);
                const cx = (parseFloat(callout.box_x) || 0) + (parseFloat(callout.box_width) || 76) / 2;
                const cy = (parseFloat(callout.box_y) || 0) + (parseFloat(callout.box_height) || 28) / 2;
                focusOnSvgPoint(ctx, cx, cy, 2.5);
            });
            leftCalloutsList.appendChild(btn);
        });
    }

    function updateSummaryAndInspector() {
        renderSummaryCards();

        const {
            btnDuplicate,
            btnRotate,
            btnDelete,
            btnAlignLeft,
            btnAlignTop,
            btnAlign2m,
            btnUndo,
            btnRedo,
            inspBadge,
            inspEmpty,
            inspConeForm,
            inspBarForm,
            inspBarricadeForm,
            inspCalloutForm,
            inspMultiPanel,
            inspMultiCount,
            inspMultiColors,
        } = ctx.dom;

        const sel = [...ctx.state.selectedItems.values()];
        const selCount = sel.length;
        const selectedCones = sel.filter((s) => s.kind === 'cone');

        if (btnUndo) btnUndo.disabled = ctx.state.undoStack.length === 0;
        if (btnRedo) btnRedo.disabled = ctx.state.redoStack.length === 0;
        if (btnDelete) btnDelete.disabled = selCount === 0;
        if (btnDuplicate) btnDuplicate.disabled = !(selCount === 1 && (sel[0].kind === 'cone' || sel[0].kind === 'barricade'));
        if (btnRotate) btnRotate.disabled = !(selCount === 1 && sel[0].kind === 'barricade');
        if (btnAlignLeft) btnAlignLeft.disabled = selectedCones.length < 2;
        if (btnAlignTop) btnAlignTop.disabled = selectedCones.length < 2;
        if (btnAlign2m) btnAlign2m.disabled = selectedCones.length < 2;

        [inspEmpty, inspConeForm, inspBarForm, inspBarricadeForm, inspCalloutForm, inspMultiPanel].forEach((el) => {
            if (el) el.classList.add('hidden');
        });

        if (selCount === 0) {
            if (inspBadge) inspBadge.textContent = '未選択';
            if (inspEmpty) inspEmpty.classList.remove('hidden');
            return;
        }

        if (selCount > 1) {
            if (inspBadge) inspBadge.textContent = `${selCount}件選択`;
            if (inspMultiPanel) inspMultiPanel.classList.remove('hidden');
            if (inspMultiCount) inspMultiCount.textContent = `${selCount} 件を選択中`;
            if (inspMultiColors) inspMultiColors.classList.toggle('hidden', selectedCones.length === 0);
            return;
        }

        const { kind, id } = sel[0];
        const entity = findEntity(kind, id);
        if (!entity) return;

        if (kind === 'cone') {
            if (inspBadge) inspBadge.textContent = entity.label || 'カラーコーン';
            if (inspConeForm) inspConeForm.classList.remove('hidden');
            ctx.dom.inspConeColorBtns.forEach((btn) => {
                const active = btn.dataset.inspConeColor === entity.color;
                btn.classList.toggle('ring-2', active);
                btn.classList.toggle('ring-indigo-600', active);
                btn.classList.toggle('ring-offset-1', active);
            });
            if (ctx.dom.inspConeLabel) ctx.dom.inspConeLabel.value = entity.label || '';
            if (ctx.dom.inspConeNotes) ctx.dom.inspConeNotes.value = entity.notes || '';
            const connectedCount = ctx.state.bars.filter((b) => b.start_cone_id === entity.id || b.end_cone_id === entity.id).length;
            if (ctx.dom.inspConeConnectedBars) ctx.dom.inspConeConnectedBars.textContent = `${connectedCount}本`;
        } else if (kind === 'bar') {
            if (inspBadge) inspBadge.textContent = 'コーンバー';
            if (inspBarForm) inspBarForm.classList.remove('hidden');
            if (ctx.dom.inspBarStyle) ctx.dom.inspBarStyle.value = entity.bar_style || 'yellow_black';
            if (ctx.dom.inspBarNotes) ctx.dom.inspBarNotes.value = entity.notes || '';

            const c1 = findEntity('cone', entity.start_cone_id);
            const c2 = findEntity('cone', entity.end_cone_id);
            const distM = c1 && c2 ? Math.hypot(c2.pos_x - c1.pos_x, c2.pos_y - c1.pos_y) / scaleRatio : 0;
            const over = distM > 2.05;
            if (ctx.dom.inspBarSpanM) ctx.dom.inspBarSpanM.textContent = `${distM.toFixed(2)}m`;
            if (ctx.dom.inspBarSpanWarning) ctx.dom.inspBarSpanWarning.classList.toggle('hidden', !over);
            if (ctx.dom.btnBarFit2m) ctx.dom.btnBarFit2m.classList.toggle('hidden', !over);
        } else if (kind === 'barricade') {
            const spec = BARRICADE_SPECS[entity.barricade_type] || BARRICADE_SPECS.type_a;
            if (inspBadge) inspBadge.textContent = entity.label || spec.label;
            if (inspBarricadeForm) inspBarricadeForm.classList.remove('hidden');
            if (ctx.dom.inspBarricadeTitle) {
                ctx.dom.inspBarricadeTitle.textContent = entity.barricade_type === 'resin_pipe' ? '🟩 樹脂製単管バリケード' : '🟧 A型バリケード';
            }
            if (ctx.dom.inspBarricadeSpec) ctx.dom.inspBarricadeSpec.textContent = spec.specText;
            if (ctx.dom.inspBarricadeType) ctx.dom.inspBarricadeType.value = entity.barricade_type;
            if (ctx.dom.inspBarricadeRotation) ctx.dom.inspBarricadeRotation.value = String(entity.rotation ?? 0);
            if (ctx.dom.inspBarricadeLabel) ctx.dom.inspBarricadeLabel.value = entity.label || '';
            if (ctx.dom.inspBarricadeNotes) ctx.dom.inspBarricadeNotes.value = entity.notes || '';
        } else if (kind === 'callout') {
            if (inspBadge) inspBadge.textContent = entity.title || '吹き出し';
            if (inspCalloutForm) inspCalloutForm.classList.remove('hidden');
            if (ctx.dom.inspCalloutTitle) ctx.dom.inspCalloutTitle.value = entity.title || '';
            if (ctx.dom.inspCalloutBody) ctx.dom.inspCalloutBody.value = entity.body || '';
            ctx.dom.inspCalloutColorBtns.forEach((btn) => {
                const active = btn.dataset.inspCalloutColor === entity.color_theme;
                btn.classList.toggle('ring-2', active);
                btn.classList.toggle('ring-indigo-600', active);
            });
            if (ctx.dom.inspCalloutFontSize) ctx.dom.inspCalloutFontSize.value = entity.font_size || '';
            if (ctx.dom.inspCalloutWidth) ctx.dom.inspCalloutWidth.value = Math.round(parseFloat(entity.box_width) || 76);
            if (ctx.dom.inspCalloutHeight) ctx.dom.inspCalloutHeight.value = Math.round(parseFloat(entity.box_height) || 28);
            const hasTarget = Boolean(entity.target_cone_id || entity.target_barricade_id);
            if (ctx.dom.inspCalloutTargetWrap) ctx.dom.inspCalloutTargetWrap.classList.toggle('hidden', !hasTarget);
            if (ctx.dom.inspCalloutTargetLabel) {
                ctx.dom.inspCalloutTargetLabel.textContent = entity.target_cone_id ? 'カラーコーンへ追従中' : 'バリケードへ追従中';
            }
        }
    }

    function openInlineCalloutEditor(callout) {
        const { inlineCalloutEditor, svgContainer, canvasContainer } = ctx.dom;
        if (!inlineCalloutEditor) return;
        const svg = svgContainer.querySelector('svg');
        if (!svg) return;

        const pt = svg.createSVGPoint();
        pt.x = (parseFloat(callout.box_x) || 0) + (parseFloat(callout.box_width) || 76) / 2;
        pt.y = (parseFloat(callout.box_y) || 0) + (parseFloat(callout.box_height) || 28) / 2;
        const screenPt = pt.matrixTransform(svg.getScreenCTM());
        const rect = canvasContainer.getBoundingClientRect();

        inlineCalloutEditor.value = callout.body || '';
        inlineCalloutEditor.style.width = '180px';
        inlineCalloutEditor.style.left = `${Math.max(8, screenPt.x - rect.left - 90)}px`;
        inlineCalloutEditor.style.top = `${Math.max(8, screenPt.y - rect.top - 30)}px`;
        inlineCalloutEditor.classList.remove('hidden');
        inlineCalloutEditor.focus();
        inlineCalloutEditor.select();

        const commit = async () => {
            inlineCalloutEditor.classList.add('hidden');
            inlineCalloutEditor.onblur = null;
            inlineCalloutEditor.onkeydown = null;
            const nextBody = inlineCalloutEditor.value.trim();
            if (nextBody && nextBody !== (callout.body || '')) {
                await deps.api.updateCallout(callout, { body: nextBody }, true);
            }
        };

        inlineCalloutEditor.onblur = commit;
        inlineCalloutEditor.onkeydown = (ev) => {
            if (ev.key === 'Enter' && (ev.ctrlKey || ev.metaKey)) {
                ev.preventDefault();
                commit();
            } else if (ev.key === 'Escape') {
                inlineCalloutEditor.classList.add('hidden');
                inlineCalloutEditor.onblur = null;
            }
        };
    }

    function showHoverTooltip(e, kind, entity) {
        const { hoverTooltip } = ctx.dom;
        if (!hoverTooltip || ctx.state.isMoving || ctx.state.calloutAnchorDragId || ctx.state.calloutResizeId) return;

        let text = '';
        if (kind === 'cone') {
            const cMeta = CONE_COLORS[entity.color] || CONE_COLORS.red;
            text = `${entity.label ? entity.label + ' | ' : ''}${cMeta.label}（＋ウェイト1個） [H70cm / 38.8×38.8cm]`;
        } else if (kind === 'bar') {
            const c1 = findEntity('cone', entity.start_cone_id);
            const c2 = findEntity('cone', entity.end_cone_id);
            const distM = c1 && c2 ? (Math.hypot(c2.pos_x - c1.pos_x, c2.pos_y - c1.pos_y) / scaleRatio).toFixed(2) : '2.00';
            text = `コーンバー [L200cm / φ3.4cm | 実測スパン ${distM}m]`;
        } else if (kind === 'barricade') {
            const spec = BARRICADE_SPECS[entity.barricade_type] || BARRICADE_SPECS.type_a;
            text = `${entity.label ? entity.label + ' | ' : ''}${spec.label} [${spec.specText} / ${entity.rotation ?? 0}°]`;
        } else if (kind === 'callout') {
            text = `${entity.title ? entity.title + ': ' : ''}${entity.body || ''}`;
        }

        hoverTooltip.textContent = text;
        hoverTooltip.classList.remove('hidden');
        moveHoverTooltip(e);
    }

    function moveHoverTooltip(e) {
        const { hoverTooltip, canvasContainer } = ctx.dom;
        if (!hoverTooltip || hoverTooltip.classList.contains('hidden')) return;
        const rect = canvasContainer.getBoundingClientRect();
        hoverTooltip.style.left = `${e.clientX - rect.left + 12}px`;
        hoverTooltip.style.top = `${e.clientY - rect.top + 12}px`;
    }

    function hideHoverTooltip() {
        if (ctx.dom.hoverTooltip) ctx.dom.hoverTooltip.classList.add('hidden');
    }

    function bindInspectorEvents() {
        const single = () => (ctx.state.selectedItems.size === 1 ? [...ctx.state.selectedItems.values()][0] : null);

        // コーン編集
        ctx.dom.inspConeColorBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const s = single();
                if (!s || s.kind !== 'cone') return;
                const cone = findEntity('cone', s.id);
                if (cone) deps.api.updateCone(cone, { color: btn.dataset.inspConeColor }, true);
            });
        });
        ctx.dom.inspConeLabel?.addEventListener('change', () => {
            const s = single();
            if (!s || s.kind !== 'cone') return;
            const cone = findEntity('cone', s.id);
            if (cone) deps.api.updateCone(cone, { label: ctx.dom.inspConeLabel.value.trim() }, true);
        });
        ctx.dom.inspConeNotes?.addEventListener('change', () => {
            const s = single();
            if (!s || s.kind !== 'cone') return;
            const cone = findEntity('cone', s.id);
            if (cone) deps.api.updateCone(cone, { notes: ctx.dom.inspConeNotes.value }, true);
        });
        ctx.dom.btnConeAddCallout?.addEventListener('click', () => {
            const s = single();
            if (!s || s.kind !== 'cone') return;
            const cone = findEntity('cone', s.id);
            if (!cone) return;
            deps.api.createCallout(
                {
                    target_cone_id: cone.id,
                    anchor_x: parseFloat(cone.pos_x),
                    anchor_y: parseFloat(cone.pos_y),
                    box_x: parseFloat(cone.pos_x) + 14,
                    box_y: parseFloat(cone.pos_y) - 34,
                    title: cone.label ? `${cone.label} 注意事項` : '設置注意',
                    body: '通行・接触に注意',
                    color_theme: ctx.state.selectedCalloutColor,
                },
                true,
            );
        });

        // バー編集
        ctx.dom.inspBarStyle?.addEventListener('change', () => {
            const s = single();
            if (!s || s.kind !== 'bar') return;
            const bar = findEntity('bar', s.id);
            if (bar) deps.api.updateBar(bar, { bar_style: ctx.dom.inspBarStyle.value }, true);
        });
        ctx.dom.inspBarNotes?.addEventListener('change', () => {
            const s = single();
            if (!s || s.kind !== 'bar') return;
            const bar = findEntity('bar', s.id);
            if (bar) deps.api.updateBar(bar, { notes: ctx.dom.inspBarNotes.value }, true);
        });
        ctx.dom.btnBarFit2m?.addEventListener('click', () => {
            const s = single();
            if (!s || s.kind !== 'bar') return;
            const bar = findEntity('bar', s.id);
            if (!bar) return;
            const c1 = findEntity('cone', bar.start_cone_id);
            const c2 = findEntity('cone', bar.end_cone_id);
            if (!c1 || !c2) return;
            const dx = c2.pos_x - c1.pos_x;
            const dy = c2.pos_y - c1.pos_y;
            const dist = Math.hypot(dx, dy);
            if (dist < 0.01) return;
            const targetDist = 2.0 * scaleRatio;
            const nextX = Math.round((parseFloat(c1.pos_x) + (dx / dist) * targetDist) * 100) / 100;
            const nextY = Math.round((parseFloat(c1.pos_y) + (dy / dist) * targetDist) * 100) / 100;
            deps.api.updateCone(c2, { pos_x: nextX, pos_y: nextY }, true);
        });

        // バリケード編集
        ctx.dom.inspBarricadeType?.addEventListener('change', () => {
            const s = single();
            if (!s || s.kind !== 'barricade') return;
            const b = findEntity('barricade', s.id);
            if (b) deps.api.updateBarricade(b, { barricade_type: ctx.dom.inspBarricadeType.value }, true);
        });
        ctx.dom.inspBarricadeRotation?.addEventListener('change', () => {
            const s = single();
            if (!s || s.kind !== 'barricade') return;
            const b = findEntity('barricade', s.id);
            const rot = (((parseInt(ctx.dom.inspBarricadeRotation.value, 10) || 0) % 360) + 360) % 360;
            if (b) deps.api.updateBarricade(b, { rotation: rot }, true);
        });
        ctx.dom.btnBarricadeRot15?.addEventListener('click', () => {
            const s = single();
            if (!s || s.kind !== 'barricade') return;
            const b = findEntity('barricade', s.id);
            if (b) deps.api.updateBarricade(b, { rotation: ((parseInt(b.rotation, 10) || 0) + 15) % 360 }, true);
        });
        ctx.dom.btnBarricadeRot45?.addEventListener('click', () => {
            const s = single();
            if (!s || s.kind !== 'barricade') return;
            const b = findEntity('barricade', s.id);
            if (b) deps.api.updateBarricade(b, { rotation: ((parseInt(b.rotation, 10) || 0) + 45) % 360 }, true);
        });
        ctx.dom.inspBarricadeLabel?.addEventListener('change', () => {
            const s = single();
            if (!s || s.kind !== 'barricade') return;
            const b = findEntity('barricade', s.id);
            if (b) deps.api.updateBarricade(b, { label: ctx.dom.inspBarricadeLabel.value.trim() }, true);
        });
        ctx.dom.inspBarricadeNotes?.addEventListener('change', () => {
            const s = single();
            if (!s || s.kind !== 'barricade') return;
            const b = findEntity('barricade', s.id);
            if (b) deps.api.updateBarricade(b, { notes: ctx.dom.inspBarricadeNotes.value }, true);
        });
        ctx.dom.btnBarricadeAddCallout?.addEventListener('click', () => {
            const s = single();
            if (!s || s.kind !== 'barricade') return;
            const b = findEntity('barricade', s.id);
            if (!b) return;
            const spec = BARRICADE_SPECS[b.barricade_type] || BARRICADE_SPECS.type_a;
            deps.api.createCallout(
                {
                    target_barricade_id: b.id,
                    anchor_x: parseFloat(b.pos_x),
                    anchor_y: parseFloat(b.pos_y),
                    box_x: parseFloat(b.pos_x) + 14,
                    box_y: parseFloat(b.pos_y) - 34,
                    title: b.label ? `${b.label} (${spec.label})` : `${spec.label} 注意`,
                    body: '通行止め・設置注意',
                    color_theme: ctx.state.selectedCalloutColor,
                },
                true,
            );
        });

        // 吹き出し編集
        ctx.dom.inspCalloutTitle?.addEventListener('change', () => {
            const s = single();
            if (!s || s.kind !== 'callout') return;
            const c = findEntity('callout', s.id);
            if (c) deps.api.updateCallout(c, { title: ctx.dom.inspCalloutTitle.value.trim() }, true);
        });
        ctx.dom.inspCalloutBody?.addEventListener('change', () => {
            const s = single();
            if (!s || s.kind !== 'callout') return;
            const c = findEntity('callout', s.id);
            const val = ctx.dom.inspCalloutBody.value.trim();
            if (c && val) deps.api.updateCallout(c, { body: val }, true);
        });
        ctx.dom.inspCalloutColorBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const s = single();
                if (!s || s.kind !== 'callout') return;
                const c = findEntity('callout', s.id);
                if (c) deps.api.updateCallout(c, { color_theme: btn.dataset.inspCalloutColor }, true);
            });
        });
        ctx.dom.inspCalloutFontSize?.addEventListener('change', () => {
            const s = single();
            if (!s || s.kind !== 'callout') return;
            const c = findEntity('callout', s.id);
            if (c) {
                const val = ctx.dom.inspCalloutFontSize.value ? parseFloat(ctx.dom.inspCalloutFontSize.value) : null;
                deps.api.updateCallout(c, { font_size: val }, true);
            }
        });
        ctx.dom.inspCalloutWidth?.addEventListener('change', () => {
            const s = single();
            if (!s || s.kind !== 'callout') return;
            const c = findEntity('callout', s.id);
            if (c) deps.api.updateCallout(c, { box_width: Math.max(20, parseFloat(ctx.dom.inspCalloutWidth.value) || 76) }, true);
        });
        ctx.dom.inspCalloutHeight?.addEventListener('change', () => {
            const s = single();
            if (!s || s.kind !== 'callout') return;
            const c = findEntity('callout', s.id);
            if (c) deps.api.updateCallout(c, { box_height: Math.max(12, parseFloat(ctx.dom.inspCalloutHeight.value) || 28) }, true);
        });
        ctx.dom.btnCalloutUnlink?.addEventListener('click', () => {
            const s = single();
            if (!s || s.kind !== 'callout') return;
            const c = findEntity('callout', s.id);
            if (c) deps.api.updateCallout(c, { target_cone_id: null, target_barricade_id: null }, true);
        });

        // 複数選択一括コーン色変更
        ctx.dom.bulkConeColorBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const color = btn.dataset.bulkConeColor;
                const cones = [...ctx.state.selectedItems.values()]
                    .filter((s) => s.kind === 'cone')
                    .map((s) => findEntity('cone', s.id))
                    .filter(Boolean);
                if (cones.length === 0) return;
                const before = cones.map((c) => ({ id: c.id, color: c.color }));
                const items = cones.map((c) => ({ id: c.id, color }));
                deps.api.bulkUpdateCones(items, true, before);
            });
        });

        syncExportPdfUrl();
    }

    return {
        findEntity,
        setSingleSelection,
        toggleSelection,
        clearSelection,
        syncExportPdfUrl,
        syncCalloutsList,
        updateSummaryAndInspector,
        openInlineCalloutEditor,
        showHoverTooltip,
        moveHoverTooltip,
        hideHoverTooltip,
        bindInspectorEvents,
    };
}
