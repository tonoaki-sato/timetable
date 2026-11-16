/**
 * 白地図配置エディタ (Site Layout Editor) - Phase 1
 * 商店街平面図 (A3 1/400) ベクターCADビューアー & パン・ズームエンジン
 */

document.addEventListener('DOMContentLoaded', () => {
    const appEl = document.getElementById('site-layout-editor-app');
    if (!appEl) return;

    const canvasContainer = document.getElementById('site-map-canvas-container');
    const viewport = document.getElementById('site-map-viewport');
    const svgContainer = document.getElementById('site-map-svg-container');
    const loadingEl = document.getElementById('canvas-loading');
    const zoomText = document.getElementById('zoom-level-text');

    const btnZoomIn = document.getElementById('btn-zoom-in');
    const btnZoomOut = document.getElementById('btn-zoom-out');
    const btnZoom100 = document.getElementById('btn-zoom-100');
    const btnZoomFit = document.getElementById('btn-zoom-fit');

    const svgUrl = appEl.dataset.svgUrl;
    const viewboxStr = appEl.dataset.viewbox || '0 0 1191 842';

    // ビューポート状態
    const state = {
        scale: 1.0,
        x: 0,
        y: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        svgWidth: 1191,
        svgHeight: 842,
    };

    // viewBox からデフォルトサイズをパース
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

        const padding = 32;
        const availWidth = containerRect.width - padding * 2;
        const availHeight = containerRect.height - padding * 2;

        const scaleX = availWidth / state.svgWidth;
        const scaleY = availHeight / state.svgHeight;
        const newScale = Math.min(scaleX, scaleY, 2.0);

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
        // 右クリックや子要素の個別操作でなければパン開始
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

    // ウィンドウリサイズ時の再計算（Fit維持などはせず位置保持）
    window.addEventListener('resize', () => {
        // 必要に応じて調整
    });

    // 白地図のロード開始
    loadSvgMap();
});
