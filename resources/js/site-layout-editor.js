/**
 * 白地図配置エディタ (Site Layout Editor) - Phase 2 (精密CTM座標同期版)
 * 商店街平面図 (A3 1/400) ベクターCADビューアー & ドラッグ＆ドロップ配置エンジン
 */

document.addEventListener('DOMContentLoaded', () => {
    const appEl = document.getElementById('site-layout-editor-app');
    if (!appEl) return;

    const canvasContainer = document.getElementById('site-map-canvas-container');
    const viewport = document.getElementById('site-map-viewport');
    const svgContainer = document.getElementById('site-map-svg-container');
    const loadingEl = document.getElementById('canvas-loading');
    const zoomText = document.getElementById('zoom-level-text');
    const countBadge = document.getElementById('placement-count-badge');

    const btnZoomIn = document.getElementById('btn-zoom-in');
    const btnZoomOut = document.getElementById('btn-zoom-out');
    const btnZoom100 = document.getElementById('btn-zoom-100');
    const btnZoomFit = document.getElementById('btn-zoom-fit');
    const btnRotateSelected = document.getElementById('btn-rotate-selected');
    const btnDeleteSelected = document.getElementById('btn-delete-selected');

    const svgUrl = appEl.dataset.svgUrl;
    const viewboxStr = appEl.dataset.viewbox || '0 0 842 1191';
    const storeUrl = appEl.dataset.storeUrl;
    const csrfToken = appEl.dataset.csrfToken;

    // 初期データのパース
    let placements = [];
    const initialPlacementsEl = document.getElementById('initial-placements-data');
    if (initialPlacementsEl && initialPlacementsEl.dataset.json) {
        try {
            placements = JSON.parse(initialPlacementsEl.dataset.json);
        } catch (e) {
            console.error('Failed to parse initial placements:', e);
        }
    }

    let iconDefinitions = {};
    const iconDefsEl = document.getElementById('icon-definitions-data');
    if (iconDefsEl && iconDefsEl.dataset.json) {
        try {
            const list = JSON.parse(iconDefsEl.dataset.json);
            list.forEach(def => {
                iconDefinitions[def.icon_key] = def;
            });
        } catch (e) {
            console.error('Failed to parse icon definitions:', e);
        }
    }

    let layoutItems = [];
    const layoutItemsEl = document.getElementById('layout-items-data');
    if (layoutItemsEl && layoutItemsEl.dataset.json) {
        try {
            layoutItems = JSON.parse(layoutItemsEl.dataset.json);
        } catch (e) {
            console.error('Failed to parse layout items:', e);
        }
    }

    // モーダル要素の取得 (Phase 4)
    const modalEl = document.getElementById('site-map-placement-modal');
    const modalForm = document.getElementById('placement-modal-form');
    const modalTitle = document.getElementById('modal-title');
    const modalSubtitle = document.getElementById('modal-subtitle');
    const modalIconImg = document.getElementById('modal-icon-img');
    const modalPlacementId = document.getElementById('modal-placement-id');
    const modalDisplayText = document.getElementById('modal-display-text');
    const modalColorOptions = document.querySelectorAll('input[name="modal_color"]');
    const modalColorBadge = document.getElementById('modal-color-status-badge');
    const modalRotation = document.getElementById('modal-rotation');
    const modalRotationBadge = document.getElementById('modal-rotation-status-badge');
    const modalLayoutItemId = document.getElementById('modal-layout-item-id');
    const modalItemBadge = document.getElementById('modal-item-status-badge');
    const modalVendorPreview = document.getElementById('modal-vendor-preview');
    const modalNotes = document.getElementById('modal-notes');
    const btnModalClose = document.getElementById('btn-modal-close');
    const btnModalCancel = document.getElementById('btn-modal-cancel');
    const btnModalDelete = document.getElementById('btn-modal-delete');
    const btnModalSave = document.getElementById('btn-modal-save');

    // ビューポート & 編集状態
    const state = {
        scale: 1.0,
        x: 0,
        y: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        panDist: 0,
        svgWidth: 842,
        svgHeight: 1191,
        placements: placements,
        selectedPlacementId: null,
        isMovingNode: false,
        movingNodeId: null,
        moveStartSvgX: 0,
        moveStartSvgY: 0,
        origPlacementX: 0,
        origPlacementY: 0,
        // フリーエリアリサイズ用状態 (Phase 5)
        isResizing: false,
        resizeHandle: null, // 'tl', 'tr', 'br', 'bl'
        resizePlacementId: null,
        resizeStartSvgX: 0,
        resizeStartSvgY: 0,
        origWidth: 0,
        origHeight: 0,
        origX: 0,
        origY: 0,
    };

    /**
     * 座標がメイン通り（南北の車道・歩道領域）沿いにあるかを幾何判定 (Phase 5)
     */
    function isAlongStreet(x, y, w, h) {
        const centerX = x + w / 2;
        // 商店街のメイン道路（車道・両側歩道を含む縦断ゾーン）: X 230〜330
        return centerX >= 230 && centerX <= 330;
    }

    // viewBox からデフォルトサイズをパース (842 x 1191)
    const vbParts = viewboxStr.split(/\s+/).map(Number);
    if (vbParts.length === 4 && vbParts[2] > 0 && vbParts[3] > 0) {
        state.svgWidth = vbParts[2];
        state.svgHeight = vbParts[3];
    }

    /**
     * トランスフォームの適用 & UI更新
     */
    function updateTransform() {
        viewport.style.transform = `translate(${state.x}px, ${state.y}px) scale(${state.scale})`;
        if (zoomText) {
            zoomText.textContent = `${Math.round(state.scale * 100)}%`;
        }
    }

    /**
     * 全体表示 (Fit to Screen)
     */
    function fitToScreen() {
        const containerRect = canvasContainer.getBoundingClientRect();
        if (containerRect.width === 0 || containerRect.height === 0) return;

        const padding = 24;
        const availWidth = containerRect.width - padding * 2;
        const availHeight = containerRect.height - padding * 2;

        const scaleX = availWidth / state.svgWidth;
        const scaleY = availHeight / state.svgHeight;
        const newScale = Math.min(scaleX, scaleY, 1.8);

        state.scale = Math.max(0.2, Math.min(newScale, 5.0));

        // 中央配置
        const renderedW = state.svgWidth * state.scale;
        const renderedH = state.svgHeight * state.scale;
        state.x = (containerRect.width - renderedW) / 2;
        state.y = (containerRect.height - renderedH) / 2;

        updateTransform();
    }

    /**
     * 指定中心点に向かってズーム
     */
    function zoomAround(deltaScale, clientX, clientY) {
        const rect = canvasContainer.getBoundingClientRect();
        const cursorX = clientX - rect.left;
        const cursorY = clientY - rect.top;

        const oldScale = state.scale;
        const newScale = Math.max(0.2, Math.min(oldScale * deltaScale, 6.0));

        // カーソル下の図面上の絶対位置を維持
        const svgPointX = (cursorX - state.x) / oldScale;
        const svgPointY = (cursorY - state.y) / oldScale;

        state.scale = newScale;
        state.x = cursorX - svgPointX * newScale;
        state.y = cursorY - svgPointY * newScale;

        updateTransform();
    }

    /**
     * スクリーン座標 (clientX, clientY) を SVG 内部の正確な図面座標 (X, Y) に変換
     * ブラウザ標準の getScreenCTM().inverse() を使用して、ズーム倍率やパン位置、
     * レターボックスの有無に関係なく完全ピンポイントで逆変換する。
     */
    function getSvgCoordinates(clientX, clientY) {
        const svg = svgContainer.querySelector('svg');
        if (!svg) {
            const rect = canvasContainer.getBoundingClientRect();
            return {
                x: (clientX - rect.left - state.x) / state.scale,
                y: (clientY - rect.top - state.y) / state.scale,
            };
        }

        const pt = svg.createSVGPoint();
        pt.x = clientX;
        pt.y = clientY;

        const ctm = svg.getScreenCTM();
        if (!ctm) {
            const rect = canvasContainer.getBoundingClientRect();
            return {
                x: (clientX - rect.left - state.x) / state.scale,
                y: (clientY - rect.top - state.y) / state.scale,
            };
        }

        const svgPt = pt.matrixTransform(ctm.inverse());
        return { x: svgPt.x, y: svgPt.y };
    }

    /**
     * 配置レイヤーの取得または生成
     */
    function getPlacementsLayer() {
        const svg = svgContainer.querySelector('svg');
        if (!svg) return null;

        let layer = svg.querySelector('#site-map-placements-layer');
        if (!layer) {
            layer = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            layer.setAttribute('id', 'site-map-placements-layer');
            svg.appendChild(layer);
        }
        return layer;
    }

    /**
     * 単一の配置ノードをSVGに描画
     */
    function renderPlacementNode(placement) {
        const layer = getPlacementsLayer();
        if (!layer) return;

        // 既存ノードがあれば削除
        const existing = layer.querySelector(`[data-placement-id="${placement.id}"]`);
        if (existing) {
            existing.remove();
        }

        const iconDef = iconDefinitions[placement.shape_type];
        const iconPath = iconDef ? `/${iconDef.icon_asset_path}` : '/maps/icons/icon_general_tent.png';

        const w = parseFloat(placement.width) || 78.0;
        const h = parseFloat(placement.height) || 78.0;
        const x = parseFloat(placement.pos_x) || 0;
        const y = parseFloat(placement.pos_y) || 0;
        const rot = parseInt(placement.rotation, 10) || 0;
        const isSelected = state.selectedPlacementId === placement.id;

        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', `site-map-node cursor-pointer select-none transition-opacity ${isSelected ? 'selected-node' : ''}`);
        g.setAttribute('data-placement-id', placement.id);
        g.setAttribute('data-shape-type', placement.shape_type);

        if (rot !== 0) {
            const cx = x + w / 2;
            const cy = y + h / 2;
            g.setAttribute('transform', `rotate(${rot} ${cx} ${cy})`);
        }

        // カラーテーマに応じた枠色・文字色 (添付PDFの赤枠・緑枠・黒枠に準拠)
        const colorMap = {
            red: { stroke: '#dc2626', fill: 'rgba(254, 242, 242, 0.6)', text: '#dc2626' },
            green: { stroke: '#16a34a', fill: 'rgba(240, 253, 244, 0.6)', text: '#16a34a' },
            blue: { stroke: '#2563eb', fill: 'rgba(239, 246, 255, 0.6)', text: '#1d4ed8' },
            black: { stroke: '#0f172a', fill: 'rgba(248, 250, 252, 0.6)', text: '#0f172a' },
            orange: { stroke: '#ea580c', fill: 'rgba(255, 247, 237, 0.6)', text: '#c2410c' },
            pink: { stroke: '#db2777', fill: 'rgba(253, 242, 248, 0.6)', text: '#be185d' },
            purple: { stroke: '#7e22ce', fill: 'rgba(250, 245, 255, 0.6)', text: '#6b21a8' },
        };
        const theme = colorMap[placement.color_theme] || colorMap.red;

        // 1. ブース外枠 (添付PDFの赤枠/緑枠スタイル)
        const rectEl = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rectEl.setAttribute('x', x);
        rectEl.setAttribute('y', y);
        rectEl.setAttribute('width', w);
        rectEl.setAttribute('height', h);
        rectEl.setAttribute('fill', theme.fill);
        rectEl.setAttribute('stroke', theme.stroke);
        rectEl.setAttribute('stroke-width', '0.75');
        rectEl.setAttribute('rx', '0.5');
        g.appendChild(rectEl);

        // 2. アイコン画像 (透過PNG)
        const img = document.createElementNS('http://www.w3.org/2000/svg', 'image');
        img.setAttributeNS('http://www.w3.org/1999/xlink', 'href', iconPath);
        img.setAttribute('href', iconPath);
        img.setAttribute('x', x);
        img.setAttribute('y', y);
        img.setAttribute('width', w);
        img.setAttribute('height', h);
        img.setAttribute('preserveAspectRatio', 'none');
        img.setAttribute('opacity', '0.85');
        g.appendChild(img);

        // 3. 中央テキスト表示 (PDF原本のA01/B08と同等のサイズ比率)
        const textStr = placement.display_text || '';
        if (textStr) {
            const textEl = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            textEl.setAttribute('x', x + w / 2);
            textEl.setAttribute('y', y + h / 2);
            textEl.setAttribute('text-anchor', 'middle');
            textEl.setAttribute('dominant-baseline', 'central');
            textEl.setAttribute('font-family', 'sans-serif');

            // ブースの大きさに合わせたフォントサイズ (最小4px、標準4.5〜5.5px)
            const calcFontSize = Math.max(3.8, Math.min(5.2, h * 0.45));
            textEl.setAttribute('font-size', `${calcFontSize.toFixed(1)}px`);
            textEl.setAttribute('font-weight', 'bold');
            textEl.setAttribute('fill', theme.text);
            textEl.textContent = textStr;
            g.appendChild(textEl);
        }

        // 4. 選択状態のハイライト枠 & 四隅の白丸ハンドル描画
        if (isSelected) {
            // 外枠ハイライト (青色の破線枠)
            const selRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            selRect.setAttribute('x', x - 0.75);
            selRect.setAttribute('y', y - 0.75);
            selRect.setAttribute('width', w + 1.5);
            selRect.setAttribute('height', h + 1.5);
            selRect.setAttribute('fill', 'rgba(37, 99, 235, 0.08)');
            selRect.setAttribute('stroke', '#2563eb');
            selRect.setAttribute('stroke-width', '0.85');
            selRect.setAttribute('stroke-dasharray', '2 1');
            selRect.setAttribute('pointer-events', 'none');
            g.appendChild(selRect);

            // 縦横リサイズ可能判定 (フリーエリアのみ可変、テント類は固定規格値で型崩れ防止)
            const isResizable = (iconDef && iconDef.is_resizable) || placement.shape_type === 'free_area';

            // 四隅の白丸ハンドル
            const corners = [
                { type: 'tl', cx: x, cy: y, cursor: 'nwse-resize' },          // 左上
                { type: 'tr', cx: x + w, cy: y, cursor: 'nesw-resize' },      // 右上
                { type: 'br', cx: x + w, cy: y + h, cursor: 'nwse-resize' },  // 右下
                { type: 'bl', cx: x, cy: y + h, cursor: 'nesw-resize' },      // 左下
            ];

            corners.forEach(corner => {
                const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circle.setAttribute('cx', corner.cx);
                circle.setAttribute('cy', corner.cy);
                circle.setAttribute('r', isResizable ? '1.8' : '1.3');
                circle.setAttribute('fill', '#ffffff');
                circle.setAttribute('stroke', '#2563eb');
                circle.setAttribute('stroke-width', isResizable ? '0.8' : '0.6');

                if (isResizable) {
                    circle.setAttribute('class', 'resize-handle');
                    circle.style.cursor = corner.cursor;
                    circle.addEventListener('mousedown', (e) => {
                        if (e.button !== 0) return;
                        e.stopPropagation();

                        state.isResizing = true;
                        state.resizeHandle = corner.type;
                        state.resizePlacementId = placement.id;

                        const svgCoords = getSvgCoordinates(e.clientX, e.clientY);
                        state.resizeStartSvgX = svgCoords.x;
                        state.resizeStartSvgY = svgCoords.y;
                        state.origWidth = parseFloat(placement.width) || 78.0;
                        state.origHeight = parseFloat(placement.height) || 78.0;
                        state.origX = parseFloat(placement.pos_x) || 0;
                        state.origY = parseFloat(placement.pos_y) || 0;
                    });
                } else {
                    circle.setAttribute('pointer-events', 'none');
                }

                g.appendChild(circle);
            });
        }

        // 5. アイコンのクリック選択 & ドラッグ移動開始イベント
        g.addEventListener('mousedown', (e) => {
            if (e.button !== 0) return;
            e.stopPropagation();

            selectPlacement(placement.id);

            state.isMovingNode = true;
            state.movingNodeId = placement.id;

            const svgCoords = getSvgCoordinates(e.clientX, e.clientY);
            state.moveStartSvgX = svgCoords.x;
            state.moveStartSvgY = svgCoords.y;
            state.origPlacementX = parseFloat(placement.pos_x) || 0;
            state.origPlacementY = parseFloat(placement.pos_y) || 0;
        });

        // 6. ダブルクリックで設定モーダル表示 (Phase 4)
        g.addEventListener('dblclick', (e) => {
            e.stopPropagation();
            openPlacementModal(placement.id);
        });

        layer.appendChild(g);
    }

    /**
     * 指定IDの配置アイテムを選択状態にする
     */
    function selectPlacement(id) {
        if (state.selectedPlacementId === id) return;

        const prevId = state.selectedPlacementId;
        state.selectedPlacementId = id;

        const target = state.placements.find(p => p.id === id);

        if (btnDeleteSelected) {
            btnDeleteSelected.disabled = false;
        }

        if (btnRotateSelected) {
            const iconDef = target ? iconDefinitions[target.shape_type] : null;
            const canRotate = target && (iconDef ? iconDef.allow_rotation : true) && !target.is_rotation_locked;
            btnRotateSelected.disabled = !canRotate;
            if (target && target.is_rotation_locked) {
                btnRotateSelected.title = '通り沿い配置のため回転不可（平行固定）';
            } else if (iconDef && !iconDef.allow_rotation) {
                btnRotateSelected.title = '現場設備のため回転不可（直立固定）';
            } else {
                btnRotateSelected.title = '時計回りに45°回転 (Rキー)';
            }
        }

        if (prevId) {
            const prevTarget = state.placements.find(p => p.id === prevId);
            if (prevTarget) renderPlacementNode(prevTarget);
        }

        if (target) renderPlacementNode(target);
    }

    /**
     * 選択を解除する
     */
    function deselectPlacement() {
        if (!state.selectedPlacementId) return;

        const prevId = state.selectedPlacementId;
        state.selectedPlacementId = null;

        if (btnDeleteSelected) {
            btnDeleteSelected.disabled = true;
        }

        if (btnRotateSelected) {
            btnRotateSelected.disabled = true;
            btnRotateSelected.title = '時計回りに45°回転 (Rキー)';
        }

        if (prevId) {
            const prevTarget = state.placements.find(p => p.id === prevId);
            if (prevTarget) renderPlacementNode(prevTarget);
        }
    }

    /**
     * 選択された配置アイテムを時計回りに45°回転する (Phase 5)
     */
    async function rotateSelectedPlacement(step = 45) {
        if (!state.selectedPlacementId) return;

        const target = state.placements.find(p => p.id === state.selectedPlacementId);
        if (!target) return;

        if (target.is_rotation_locked) {
            alert('通り沿いに配置されているため、道路平行に固定されています（回転不可）。');
            return;
        }

        const iconDef = iconDefinitions[target.shape_type];
        if (iconDef && !iconDef.allow_rotation) {
            alert('この設備アイコンは直立固定のため回転できません。');
            return;
        }

        const currentRot = parseInt(target.rotation, 10) || 0;
        target.rotation = (currentRot + step) % 360;

        renderPlacementNode(target);

        // API保存
        await updatePlacementGeometry(target);
    }

    /**
     * アイコンの幾何情報（座標・寸法・角度・ロック）をAPIにPUT保存する (Phase 5)
     */
    async function updatePlacementGeometry(placement) {
        const url = `${storeUrl}/${placement.id}`;
        const payload = {
            pos_x: placement.pos_x,
            pos_y: placement.pos_y,
            width: placement.width,
            height: placement.height,
            rotation: placement.rotation,
            is_rotation_locked: placement.is_rotation_locked,
        };

        try {
            const res = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!res.ok) {
                const errData = await res.json().catch(() => ({}));
                throw new Error(errData.message || '配置ジオメトリの更新に失敗しました');
            }
        } catch (err) {
            console.error('Error updating placement geometry:', err);
            alert(`ジオメトリの保存に失敗しました: ${err.message}`);
        }
    }

    /**
     * 位置移動の保存用エイリアス
     */
    async function updatePlacementPosition(placement) {
        await updatePlacementGeometry(placement);
    }

    /**
     * 選択された配置アイテムを削除する
     */
    async function deleteSelectedPlacement() {
        if (!state.selectedPlacementId) return;

        const target = state.placements.find(p => p.id === state.selectedPlacementId);
        if (!target) return;

        const itemName = target.display_text || 'このアイテム';
        if (!confirm(`「${itemName}」を配置図から削除（配置解除）しますか？`)) {
            return;
        }

        const url = `${storeUrl}/${target.id}`;
        try {
            const res = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!res.ok) {
                const errData = await res.json().catch(() => ({}));
                throw new Error(errData.message || '配置の削除に失敗しました');
            }

            // state から削除
            state.placements = state.placements.filter(p => p.id !== target.id);

            // DOM から削除
            const layer = getPlacementsLayer();
            if (layer) {
                const node = layer.querySelector(`[data-placement-id="${target.id}"]`);
                if (node) node.remove();
            }

            deselectPlacement();

            if (countBadge) {
                countBadge.textContent = `配置数: ${state.placements.length}件`;
            }
        } catch (err) {
            console.error('Error deleting placement:', err);
            alert(`削除に失敗しました: ${err.message}`);
        }
    }

    /**
     * オブジェクト設定モーダルを開く (Phase 4)
     */
    function openPlacementModal(id) {
        if (!modalEl) return;

        const placement = state.placements.find(p => p.id === id);
        if (!placement) return;

        selectPlacement(id);

        const iconDef = iconDefinitions[placement.shape_type];
        const iconName = iconDef ? iconDef.name : 'オブジェクト';
        const iconPath = iconDef ? `/${iconDef.icon_asset_path}` : '/maps/icons/icon_general_tent.png';

        if (modalTitle) modalTitle.textContent = `${iconName} 設定`;
        if (modalIconImg) {
            modalIconImg.src = iconPath;
            modalIconImg.alt = iconName;
        }

        const scaleRatio = parseFloat(appEl.dataset.scaleRatio) || 7.0866;
        const wMeters = (parseFloat(placement.width) / scaleRatio).toFixed(1);
        const hMeters = (parseFloat(placement.height) / scaleRatio).toFixed(1);
        if (modalSubtitle) {
            modalSubtitle.textContent = `寸法: 間口 ${wMeters}m × 奥行 ${hMeters}m (1/400縮尺)`;
        }

        modalPlacementId.value = placement.id;
        modalDisplayText.value = placement.display_text || '';
        modalNotes.value = placement.notes || '';

        // カラーテーマ制御
        const hasColorVariants = iconDef ? iconDef.has_color_variants : true;
        const currentColor = placement.color_theme || 'red';

        modalColorOptions.forEach(opt => {
            const parent = opt.closest('.color-option');
            if (!hasColorVariants) {
                opt.disabled = true;
                if (parent) parent.classList.add('opacity-40', 'cursor-not-allowed');
            } else if (placement.shape_type === 'toilet') {
                if (opt.value === 'blue' || opt.value === 'black') {
                    opt.disabled = false;
                    if (parent) parent.classList.remove('opacity-40', 'cursor-not-allowed');
                } else {
                    opt.disabled = true;
                    if (parent) parent.classList.add('opacity-40', 'cursor-not-allowed');
                }
            } else {
                opt.disabled = false;
                if (parent) parent.classList.remove('opacity-40', 'cursor-not-allowed');
            }

            opt.checked = (opt.value === currentColor);
            if (parent) {
                if (opt.checked) {
                    parent.classList.add('ring-2', 'ring-indigo-600', 'bg-indigo-50/50');
                } else {
                    parent.classList.remove('ring-2', 'ring-indigo-600', 'bg-indigo-50/50');
                }
            }
        });

        if (modalColorBadge) {
            if (!hasColorVariants) {
                modalColorBadge.textContent = 'バリエーション無し（固定色）';
            } else if (placement.shape_type === 'toilet') {
                modalColorBadge.textContent = '2色（青／黒）選択可能';
            } else {
                modalColorBadge.textContent = '5色パレットから選択可能';
            }
        }

        // 回転制御
        const allowRotation = iconDef ? iconDef.allow_rotation : true;
        if (modalRotation) {
            if (!allowRotation || placement.is_rotation_locked) {
                modalRotation.disabled = true;
                if (modalRotationBadge) {
                    modalRotationBadge.textContent = placement.is_rotation_locked
                        ? '通り沿い配置のため回転不可（平行固定）'
                        : '回転不可（直立固定）';
                }
                modalRotation.value = "0";
            } else {
                modalRotation.disabled = false;
                if (modalRotationBadge) {
                    modalRotationBadge.textContent = '45°刻みで選択可能';
                }
                const rot = parseInt(placement.rotation, 10) || 0;
                const snappedRot = (Math.round(rot / 45) * 45) % 360;
                modalRotation.value = String(snappedRot);
            }
        }

        // 出店ブース連動制御
        const isFacility = iconDef && iconDef.category === 'facility';
        if (modalLayoutItemId) {
            if (isFacility) {
                modalLayoutItemId.disabled = true;
                modalLayoutItemId.value = '';
                if (modalItemBadge) modalItemBadge.textContent = '現場インフラ設備（ブース台帳紐づけ対象外）';
                if (modalVendorPreview) modalVendorPreview.classList.add('hidden');
            } else {
                modalLayoutItemId.disabled = false;
                if (modalItemBadge) modalItemBadge.textContent = 'ブース台帳と連動可能';
                modalLayoutItemId.value = placement.layout_item_id || '';
                updateVendorPreview();
            }
        }

        modalEl.classList.remove('hidden');
        if (modalDisplayText) {
            setTimeout(() => modalDisplayText.focus(), 50);
        }
    }

    /**
     * オブジェクト設定モーダルを閉じる
     */
    function closePlacementModal() {
        if (!modalEl) return;
        modalEl.classList.add('hidden');
    }

    /**
     * 出店者詳細プレビューの更新
     */
    function updateVendorPreview() {
        if (!modalLayoutItemId || !modalVendorPreview) return;
        const selectedOpt = modalLayoutItemId.options[modalLayoutItemId.selectedIndex];
        if (!selectedOpt || !selectedOpt.value) {
            modalVendorPreview.classList.add('hidden');
            return;
        }

        const code = selectedOpt.dataset.code || '';
        const name = selectedOpt.dataset.name || '';
        const vendor = selectedOpt.dataset.vendor || '';
        const fire = selectedOpt.dataset.fire || '';

        modalVendorPreview.innerHTML = `
            <div class="flex items-center justify-between font-bold text-xs">
                <span>コマ: ${code} - ${name}</span>
                <span class="${fire ? 'text-red-600 font-bold' : 'text-slate-500'}">${fire || '火気不使用'}</span>
            </div>
            <div class="text-xs text-slate-600 mt-0.5">出店者: ${vendor}</div>
        `;
        modalVendorPreview.classList.remove('hidden');
    }

    /**
     * モーダル保存処理
     */
    async function handleModalSave(e) {
        e.preventDefault();
        const placementId = parseInt(modalPlacementId.value, 10);
        const placement = state.placements.find(p => p.id === placementId);
        if (!placement) return;

        let selectedColor = 'red';
        modalColorOptions.forEach(opt => {
            if (opt.checked) selectedColor = opt.value;
        });

        const newDisplayText = modalDisplayText.value.trim();
        const newRotation = parseInt(modalRotation.value, 10) || 0;
        const newLayoutItemId = modalLayoutItemId.value ? parseInt(modalLayoutItemId.value, 10) : null;
        const newNotes = modalNotes.value.trim();

        const payload = {
            display_text: newDisplayText,
            color_theme: selectedColor,
            rotation: newRotation,
            layout_item_id: newLayoutItemId,
            notes: newNotes,
        };

        const url = `${storeUrl}/${placement.id}`;
        try {
            const res = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!res.ok) {
                const errData = await res.json().catch(() => ({}));
                throw new Error(errData.message || '設定の保存に失敗しました');
            }

            const resData = await res.json();
            if (resData.success && resData.placement) {
                placement.display_text = resData.placement.display_text;
                placement.color_theme = resData.placement.color_theme;
                placement.rotation = resData.placement.rotation;
                placement.layout_item_id = resData.placement.layout_item_id;
                placement.notes = resData.placement.notes;

                renderPlacementNode(placement);
                closePlacementModal();
            }
        } catch (err) {
            console.error('Error saving placement settings:', err);
            alert(`保存に失敗しました: ${err.message}`);
        }
    }

    /**
     * 全配置ノードの再描画
     */
    function renderAllPlacements() {
        const layer = getPlacementsLayer();
        if (!layer) return;
        layer.innerHTML = '';
        state.placements.forEach(p => {
            renderPlacementNode(p);
        });

        if (countBadge) {
            countBadge.textContent = `配置数: ${state.placements.length}件`;
        }
    }

    /**
     * SVG 白地図データを非同期ロードしてインジェクション
     */
    async function loadSvgMap() {
        try {
            const res = await fetch(svgUrl);
            if (!res.ok) throw new Error(`SVG load failed: ${res.statusText}`);
            const svgContent = await res.text();

            svgContainer.innerHTML = svgContent;

            const svgElement = svgContainer.querySelector('svg');
            if (svgElement) {
                // SVGタグのネイティブviewBox属性から正規サイズを読み取る
                const nativeViewBox = svgElement.getAttribute('viewBox');
                if (nativeViewBox) {
                    const parts = nativeViewBox.split(/\s+/).map(Number);
                    if (parts.length === 4 && parts[2] > 0 && parts[3] > 0) {
                        state.svgWidth = parts[2];
                        state.svgHeight = parts[3];
                    }
                }

                svgElement.setAttribute('width', `${state.svgWidth}px`);
                svgElement.setAttribute('height', `${state.svgHeight}px`);
                svgElement.style.display = 'block';
                svgElement.style.maxWidth = 'none';
                svgElement.id = 'site-map-base-svg';
            }

            if (loadingEl) {
                loadingEl.style.opacity = '0';
                setTimeout(() => {
                    loadingEl.style.display = 'none';
                }, 300);
            }

            // 初期配置の描画
            renderAllPlacements();

            // 初期配置: 画面全体にフィット
            fitToScreen();
        } catch (err) {
            console.error('Error loading SVG base map:', err);
            if (loadingEl) {
                loadingEl.innerHTML = `
                    <div class="text-center text-red-600 p-4">
                        <span class="text-2xl">⚠️</span>
                        <p class="font-bold mt-2">白地図データの読み込みに失敗しました</p>
                        <p class="text-xs text-slate-500 mt-1">${err.message}</p>
                    </div>
                `;
            }
        }
    }

    // --- イベントリスナー設定 ---

    // ドラッグによるパン移動およびノード移動
    canvasContainer.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        state.isDragging = true;
        state.dragStartX = e.clientX - state.x;
        state.dragStartY = e.clientY - state.y;
        state.panDist = 0;
    });

    window.addEventListener('mousemove', (e) => {
        // フリーエリアのリサイズドラッグ中 (Phase 5)
        if (state.isResizing) {
            const svgCoords = getSvgCoordinates(e.clientX, e.clientY);
            const dx = svgCoords.x - state.resizeStartSvgX;
            const dy = svgCoords.y - state.resizeStartSvgY;

            const target = state.placements.find(p => p.id === state.resizePlacementId);
            if (target) {
                const minSize = 7.0866; // 最小 1.0m (約7.09px)
                let newX = state.origX;
                let newY = state.origY;
                let newW = state.origWidth;
                let newH = state.origHeight;

                switch (state.resizeHandle) {
                    case 'br':
                        newW = Math.max(minSize, state.origWidth + dx);
                        newH = Math.max(minSize, state.origHeight + dy);
                        break;
                    case 'tr':
                        newW = Math.max(minSize, state.origWidth + dx);
                        if (state.origHeight - dy >= minSize) {
                            newH = state.origHeight - dy;
                            newY = state.origY + dy;
                        } else {
                            newH = minSize;
                            newY = state.origY + (state.origHeight - minSize);
                        }
                        break;
                    case 'bl':
                        if (state.origWidth - dx >= minSize) {
                            newW = state.origWidth - dx;
                            newX = state.origX + dx;
                        } else {
                            newW = minSize;
                            newX = state.origX + (state.origWidth - minSize);
                        }
                        newH = Math.max(minSize, state.origHeight + dy);
                        break;
                    case 'tl':
                        if (state.origWidth - dx >= minSize) {
                            newW = state.origWidth - dx;
                            newX = state.origX + dx;
                        } else {
                            newW = minSize;
                            newX = state.origX + (state.origWidth - minSize);
                        }
                        if (state.origHeight - dy >= minSize) {
                            newH = state.origHeight - dy;
                            newY = state.origY + dy;
                        } else {
                            newH = minSize;
                            newY = state.origY + (state.origHeight - minSize);
                        }
                        break;
                }

                target.pos_x = Math.round(newX * 100) / 100;
                target.pos_y = Math.round(newY * 100) / 100;
                target.width = Math.round(newW * 100) / 100;
                target.height = Math.round(newH * 100) / 100;
                renderPlacementNode(target);
            }
            return;
        }

        // 配置アイコンのドラッグ移動中
        if (state.isMovingNode) {
            const svgCoords = getSvgCoordinates(e.clientX, e.clientY);
            const dx = svgCoords.x - state.moveStartSvgX;
            const dy = svgCoords.y - state.moveStartSvgY;

            const target = state.placements.find(p => p.id === state.movingNodeId);
            if (target) {
                target.pos_x = Math.round((state.origPlacementX + dx) * 100) / 100;
                target.pos_y = Math.round((state.origPlacementY + dy) * 100) / 100;
                renderPlacementNode(target);
            }
            return;
        }

        // キャンバス全体のパン移動
        if (!state.isDragging) return;
        const newX = e.clientX - state.dragStartX;
        const newY = e.clientY - state.dragStartY;
        state.panDist += Math.abs(newX - state.x) + Math.abs(newY - state.y);
        state.x = newX;
        state.y = newY;
        updateTransform();
    });

    window.addEventListener('mouseup', (e) => {
        // フリーエリアリサイズの完了 (Phase 5)
        if (state.isResizing) {
            const target = state.placements.find(p => p.id === state.resizePlacementId);
            state.isResizing = false;
            state.resizeHandle = null;
            state.resizePlacementId = null;

            if (target) {
                const hasChanged = (target.width !== state.origWidth) || (target.height !== state.origHeight)
                    || (target.pos_x !== state.origX) || (target.pos_y !== state.origY);
                if (hasChanged) {
                    updatePlacementGeometry(target);
                }
            }
            return;
        }

        // 配置アイコン移動の完了 (Phase 5: 通り沿い自動平行整列 & ロック判定)
        if (state.isMovingNode) {
            const target = state.placements.find(p => p.id === state.movingNodeId);
            state.isMovingNode = false;
            state.movingNodeId = null;

            if (target) {
                const inStreet = isAlongStreet(target.pos_x, target.pos_y, target.width, target.height);
                if (inStreet) {
                    target.is_rotation_locked = true;
                    target.rotation = 0; // 通りに沿って平行直立に固定
                } else {
                    target.is_rotation_locked = false;
                }

                renderPlacementNode(target);

                if (btnRotateSelected && state.selectedPlacementId === target.id) {
                    const iconDef = iconDefinitions[target.shape_type];
                    const canRotate = (iconDef ? iconDef.allow_rotation : true) && !target.is_rotation_locked;
                    btnRotateSelected.disabled = !canRotate;
                    if (target.is_rotation_locked) {
                        btnRotateSelected.title = '通り沿い配置のため回転不可（平行固定）';
                    } else if (iconDef && !iconDef.allow_rotation) {
                        btnRotateSelected.title = '現場設備のため回転不可（直立固定）';
                    } else {
                        btnRotateSelected.title = '時計回りに45°回転 (Rキー)';
                    }
                }

                updatePlacementGeometry(target);
            }
            return;
        }

        // キャンバスパン移動の終了 & 背景クリック判定（選択解除）
        if (state.isDragging) {
            state.isDragging = false;
            if (state.panDist < 6) {
                const isClickOnNode = e.target.closest('.site-map-node');
                if (!isClickOnNode) {
                    deselectPlacement();
                }
            }
        }
    });

    // 45°スナップ回転ボタン (Phase 5)
    if (btnRotateSelected) {
        btnRotateSelected.addEventListener('click', () => {
            rotateSelectedPlacement(45);
        });
    }

    // 選択アイテムの削除ボタン
    if (btnDeleteSelected) {
        btnDeleteSelected.addEventListener('click', () => {
            deleteSelectedPlacement();
        });
    }

    // キーボードショートカット (Delete/Backspace で削除、Rで45°回転、Esc でモーダル閉じる/選択解除)
    window.addEventListener('keydown', (e) => {
        const tag = document.activeElement?.tagName?.toLowerCase();
        const isInput = tag === 'input' || tag === 'textarea' || tag === 'select';

        if (e.key === 'Delete' || e.key === 'Backspace') {
            if (isInput) return;
            if (state.selectedPlacementId) {
                e.preventDefault();
                deleteSelectedPlacement();
            }
        } else if (e.key === 'r' || e.key === 'R') {
            if (isInput) return;
            if (state.selectedPlacementId) {
                e.preventDefault();
                rotateSelectedPlacement(45);
            }
        } else if (e.key === 'Escape') {
            if (modalEl && !modalEl.classList.contains('hidden')) {
                closePlacementModal();
            } else {
                deselectPlacement();
            }
        }
    });

    // モーダル関連イベントバインド (Phase 4)
    if (modalColorOptions) {
        modalColorOptions.forEach(opt => {
            opt.addEventListener('change', () => {
                modalColorOptions.forEach(o => {
                    const p = o.closest('.color-option');
                    if (p) {
                        if (o.checked) {
                            p.classList.add('ring-2', 'ring-indigo-600', 'bg-indigo-50/50');
                        } else {
                            p.classList.remove('ring-2', 'ring-indigo-600', 'bg-indigo-50/50');
                        }
                    }
                });
            });
        });
    }

    if (modalLayoutItemId) {
        modalLayoutItemId.addEventListener('change', () => {
            updateVendorPreview();
            const opt = modalLayoutItemId.options[modalLayoutItemId.selectedIndex];
            if (opt && opt.value && opt.dataset.code) {
                if (!modalDisplayText.value || modalDisplayText.value === 'ブース' || modalDisplayText.value === 'テキスト') {
                    modalDisplayText.value = opt.dataset.code;
                }
            }
        });
    }

    if (modalForm) {
        modalForm.addEventListener('submit', handleModalSave);
    }

    if (btnModalClose) {
        btnModalClose.addEventListener('click', closePlacementModal);
    }

    if (btnModalCancel) {
        btnModalCancel.addEventListener('click', closePlacementModal);
    }

    if (btnModalDelete) {
        btnModalDelete.addEventListener('click', async () => {
            await deleteSelectedPlacement();
            closePlacementModal();
        });
    }

    if (modalEl) {
        modalEl.addEventListener('click', (e) => {
            if (e.target === modalEl) {
                closePlacementModal();
            }
        });
    }

    // マウスホイールによるズーム
    canvasContainer.addEventListener('wheel', (e) => {
        e.preventDefault();
        const delta = e.deltaY < 0 ? 1.15 : 0.85;
        zoomAround(delta, e.clientX, e.clientY);
    }, { passive: false });

    // ツールバーボタン
    if (btnZoomIn) {
        btnZoomIn.addEventListener('click', () => {
            const rect = canvasContainer.getBoundingClientRect();
            zoomAround(1.25, rect.left + rect.width / 2, rect.top + rect.height / 2);
        });
    }

    if (btnZoomOut) {
        btnZoomOut.addEventListener('click', () => {
            const rect = canvasContainer.getBoundingClientRect();
            zoomAround(0.8, rect.left + rect.width / 2, rect.top + rect.height / 2);
        });
    }

    if (btnZoom100) {
        btnZoom100.addEventListener('click', () => {
            const rect = canvasContainer.getBoundingClientRect();
            state.scale = 1.0;
            state.x = (rect.width - state.svgWidth) / 2;
            state.y = (rect.height - state.svgHeight) / 2;
            updateTransform();
        });
    }

    if (btnZoomFit) {
        btnZoomFit.addEventListener('click', () => {
            fitToScreen();
        });
    }

    // --- パレットからのドラッグ＆ドロップ (DnD) 実装 ---

    // 1. パレットアイテムのドラッグ開始
    const paletteItems = document.querySelectorAll('.palette-item');
    paletteItems.forEach(item => {
        item.addEventListener('dragstart', (e) => {
            const dragData = {
                shape_type: item.dataset.shapeType,
                default_width: parseFloat(item.dataset.defaultWidth) || 78.0,
                default_height: parseFloat(item.dataset.defaultHeight) || 78.0,
                name: item.dataset.name || 'ブース',
            };
            e.dataTransfer.setData('application/json', JSON.stringify(dragData));
            e.dataTransfer.effectAllowed = 'copy';
        });
    });

    // 2. キャンバス上でのドラッグオーバー許可
    canvasContainer.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });

    // 3. ドロップ時の精密 CTM 逆変換座標計算 & API保存
    canvasContainer.addEventListener('drop', async (e) => {
        e.preventDefault();
        const rawData = e.dataTransfer.getData('application/json');
        if (!rawData) return;

        let dragData;
        try {
            dragData = JSON.parse(rawData);
        } catch (err) {
            console.error('Failed to parse dropped data:', err);
            return;
        }

        const targetW = dragData.default_width;
        const targetH = dragData.default_height;

        // ブラウザ標準の getScreenCTM().inverse() を用いたピンポイント絶対座標取得
        const svgCoords = getSvgCoordinates(e.clientX, e.clientY);

        // ドロップ位置がオブジェクトの中心に来るようにオフセット
        const svgX = svgCoords.x - targetW / 2;
        const svgY = svgCoords.y - targetH / 2;

        // 非同期保存リクエスト (Phase 5: 通り沿いドロップ判定)
        const inStreet = isAlongStreet(svgX, svgY, targetW, targetH);
        const payload = {
            shape_type: dragData.shape_type,
            pos_x: Math.round(svgX * 100) / 100,
            pos_y: Math.round(svgY * 100) / 100,
            width: targetW,
            height: targetH,
            display_text: dragData.name,
            color_theme: 'red',
            rotation: 0,
            is_rotation_locked: inStreet,
        };

        try {
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!res.ok) {
                const errorData = await res.json().catch(() => ({}));
                throw new Error(errorData.message || '配置の保存に失敗しました');
            }

            const resData = await res.json();
            if (resData.success && resData.placement) {
                state.placements.push(resData.placement);
                renderPlacementNode(resData.placement);
                selectPlacement(resData.placement.id);
                if (countBadge) {
                    countBadge.textContent = `配置数: ${state.placements.length}件`;
                }
            }
        } catch (err) {
            console.error('Error storing placement:', err);
            alert(`配置の保存に失敗しました: ${err.message}`);
        }
    });

    // 白地図のロード開始
    loadSvgMap();
});
