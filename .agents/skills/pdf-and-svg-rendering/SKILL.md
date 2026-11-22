---
name: pdf-and-svg-rendering
description: >-
  DomPDF template CSS/table constraints and SVG vector site-map canvas rendering
  quality, zoom, DPR alignment, text halo, and in-place DOM update rules. Use
  when creating or editing PDF Blade templates, PDF exporter services/tests, or
  SVG site-layout editor views and scripts.
---

# PDF (DomPDF) / SVG ベクター図面描画規約

## PDF テンプレート (DomPDF) の CSS・テーブル規約

- 全称セレクタ `*` をリセットスタイル等で使用しないこと（DomPDF 内部で `@page` にマッチしてページ余白が `0` にリセットされ、`position: fixed` の固定ヘッダーを用紙外へ押し出すため）。リセットは `table, th, td, h1, h2, h3, div, span, strong, b, p` など具体的なタグ名を列挙して指定する。
- テーブル列幅に `<colgroup>` / `<col>` を使用しないこと（DomPDF 未サポートのため無視され均等幅にフォールバックする）。列幅は `<style>` ブロック内で `th`（または `td`）のクラスセレクタ（例: `.table th.col-name { width: ...; }`）に対して指定する。
- PDF を生成・検証するテストでは、Content-Type や `%PDF-` ヘッダーに加え、必要に応じて DomPDF インスタンスのページ余白（`$dompdf->getCss()->get_page_styles()['base']->margin_top > 0`）が維持されていることを検証する。

## SVG ベクター図面・キャンバス UI の描画品質・ズーム最適化規約

- **GPU ビットマップ拡大ボケの防止（ネイティブ再ラスタライズの強制）**: SVG 図面やベクターキャンバスを拡大縮小する UI では、ビューポートラッパーに `will-change: transform` や CSS `transform: scale()` を使用してはならない（ブラウザが初期サイズのテクスチャをキャッシュし、拡大時にビットマップ引き伸ばしによるボケ・にじみが発生するため）。拡大表示時は `<svg viewBox="...">` 自体の `width` / `height` 属性およびスタイルに拡大後の実ピクセル寸法（`baseWidth * scale` 等）を直接指定し、常にブラウザのネイティブ解像度でベクター再ラスタライズを実行させること。
- **デバイスピクセル（DPR）整合**: パン座標 `(x, y)` や拡大サイズは `window.devicePixelRatio` を考慮して物理ピクセル単位（`Math.round(val * dpr) / dpr`）で整数スナップさせ、水平・垂直線のサブピクセル分散（アンチエイリアスボケ）を防止すること。
- **ベクター精度とテキストヒンティング無効化**: `<svg>` ルートおよび図形・文字要素には `shape-rendering="geometricPrecision"` および `text-rendering="geometricPrecision"` を付与し、極小文字や極細線のグリッド吸着歪みを防止すること。
- **CAD テキストの白ハロー（視認性保護）**: 図面上の文字（ブース番号・建物名等）が背後のハッチング線や図形境界と干渉してにじまないよう、テキスト要素には `paint-order: stroke fill; stroke: #ffffff; stroke-width: ...; stroke-linejoin: round;` による白フチ（ハロー）処理を適用すること。
- **DOM インプレース更新（イベント整合性の保護）**: SVG 図面上の要素（ノード）を再描画・更新する際は、親 `<g>` 要素そのものを破棄（`remove()` / `innerHTML = ''`）せず、`<g>` 要素を保持したまま内部の子要素のみを再構築するか属性を更新し、バインド済みのイベントリスナー（ドラッグ、ダブルクリック等）を破壊しないこと。
