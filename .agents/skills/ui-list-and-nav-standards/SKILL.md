---
name: ui-list-and-nav-standards
description: >-
  UI layout, Tailwind component styling, modal behavior, and global navigation
  dropdown standards for list/index pages in this Laravel application. Use when
  creating or modifying index/list Blade views, CRUD/detail modals, header
  action buttons, filter bars, data tables, or navigation dropdown menus.
---

# 一覧画面・ナビゲーション UI 標準規約

一覧画面を新規作成または改修する際は、**共通 UI コンポーネント（`resources/views/components/ui/*`）および取引先管理（`resources/views/vendors/index.blade.php`）・屋台配置一覧（`resources/views/site_layouts/booths.blade.php`）** の構成とパーツデザインを基本標準として統一すること。

## 一覧画面のレイアウト・パーツデザイン標準

- **ページヘッダー**: 画面のタイトル行は「タイトル（アイコン付き見出し）」「説明文」「リンク・アクションボタン」が横並びの一行に収まるレイアウトとする。タイトルと説明文を左側にインライン配置し、説明文は一行に収まる簡潔な短文とし、右側にアクションボタングループ（新規作成ボタン、画面遷移ボタン等）を右寄せ配置して画面上部の縦領域を圧迫しないようにする。
- **ヘッダーアクションボタンの配色規約**: 画面タイトル行（`<x-slot:actions>`）に配置する操作ボタン（新規追加、画面遷移、一覧表示等）は、通常時（非ホバー時）は未選択・セカンダリカラー（白背景・微細グレー枠線・ダークテキスト: `bg-white border border-slate-300 text-slate-700`）とし、カーソルホバー時にプライマリカラー（インディゴ背景・インディゴ枠線・白文字: `hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition`）へと滑らかに切り替えること。
- **内部識別コード（ゾーンコード等）の露出防止**: 配置計画や一覧・サマリーカードなどの表示において、データベース管理上の内部識別コード（例: `ZONE_A`, `AREA_KITAGAWA`, `KIDS_VILLAGE` 等）は画面上に露出させず、ユーザーにとって直感的で分かりやすい正式な名称（例: `メイン通りA列（東側歩道）`, `北川駐車場内`, `キッズ村`）と件数情報のみをシンプルに表示すること。
- **KPI / サマリーカード**: グリッド配置（2〜4列）、白背景（`bg-white`）、微細ボーダー（`border border-slate-200 rounded-xl p-4 shadow-xs`）、アイコンバッジ、カウントバッジ。
- **表示切替フィルターバー**: `bg-slate-50 border border-slate-200 rounded-xl p-3 shadow-2xs`、インライン全選択・全解除ボタングループ、`✔`/`✕` チップトグル、件数インジケータ。
- **データテーブル**: `bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden`、ヘッダー背景 `bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600`、行 hover 効果（`hover:bg-slate-50/50`）、識別コードバッジ（Mono フォント）、状態・種別バッジ。各行の「名前」は通常時は黒色（`font-bold text-slate-800`）、ホバー時に下線・インディゴ色（`hover:text-indigo-600 hover:underline transition cursor-pointer`）となるクリック可能なリンク/ボタンとし、クリック時に閲覧専用の詳細モーダルを表示する。
- **行アクションボタン**: 「アイコン＋テキスト」で表示する。
  - 編集ボタン: `inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer`、`<span>✏️</span><span>編集</span>`（クリックで編集モーダルを開く）
  - 削除ボタン: `inline-flex items-center space-x-1 px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition cursor-pointer`、`<span>🗑️</span><span>削除</span>`（確認ダイアログ付き）
- **詳細モーダル（閲覧専用）**: 各行の名前クリック時に開き、基本情報・関連レコード・特記事項の確認に専念させる。モーダル内には編集・削除リンクやボタンを配置せず、明確な「閉じる」ボタンのみを設けて誤操作を防止する。
- **入力フォームモーダル（新規追加・編集）**: 別ページ遷移を行わず、ページ上部アクションからの「新規登録」および行アクションからの「編集」ともにモーダルフォームを表示し、一覧画面内で追加・更新操作を完結させる。Backdrop（`bg-slate-900/40 backdrop-blur-xs`）、白カード（`rounded-2xl shadow-xl max-w-lg`）、キャンセル/保存ボタンの配色を統一する。

## グローバルナビゲーション・ドロップダウンメニューの表示規約

- 項目数が多いドロップダウンメニュー（6項目以上等）は、1列の縦長配置を避け、業務・役割の論理的グループに基づいた2カラム（メガメニュー形式、例: `w-[620px] lg:w-[650px]`）に整理して縦幅を約 300px 以内に抑制し、ノート PC 等の画面下端での見切れを防止すること。
- 全てのドロップダウンメニュー（`.dropdown-menu`）には、小型ウィンドウや上下狭小画面での見切れ安全対策として、必ず `max-h-[calc(100vh-4.5rem)] overflow-y-auto` を付与し、常に全項目へスクロールしてアクセス可能にすること。
