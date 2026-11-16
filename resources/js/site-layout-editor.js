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

    // ビューポート状態
    const state = {
        scale: 1.0,
        x: 0,
        y: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        svgWidth: 842,
        svgHeight: 1191,
        placements: placements,
    };

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

        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'site-map-node cursor-pointer select-none transition-opacity');
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

        layer.appendChild(g);
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

    // ドラッグによるパン移動
    canvasContainer.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        state.isDragging = true;
        state.dragStartX = e.clientX - state.x;
        state.dragStartY = e.clientY - state.y;
    });

    window.addEventListener('mousemove', (e) => {
        if (!state.isDragging) return;
        state.x = e.clientX - state.dragStartX;
        state.y = e.clientY - state.dragStartY;
        updateTransform();
    });

    window.addEventListener('mouseup', () => {
        state.isDragging = false;
    });

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

        // 非同期保存リクエスト
        const payload = {
            shape_type: dragData.shape_type,
            pos_x: Math.round(svgX * 100) / 100,
            pos_y: Math.round(svgY * 100) / 100,
            width: targetW,
            height: targetH,
            display_text: dragData.name,
            color_theme: 'red',
            rotation: 0,
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
