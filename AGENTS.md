<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- `resources/js/` や `resources/css/` を新規作成または変更した際、あるいは `vite.config.js` の `input` を更新した際は、必ず `npm run build` を実行して `public/build/manifest.json` を更新すること。
- UIにフロントエンドの変更が反映されない場合や "Unable to locate file in Vite manifest" エラーが発生した場合は、`npm run build` を再実行するかユーザーに `npm run dev` または `composer run dev` の実行を依頼する。

## View Cache Clearing

- 実装終了時（特に Blade テンプレートや View Component の新規作成・変更後、テスト実行後、および作業完了前の最終確認時）は、必ず `php artisan view:clear` を実行してコンパイル済みビューのキャッシュをクリアし、最新の描画が画面に確実に反映されるようにすること。
- **Webサーバー（Apache）との所有権不一致による touch() エラー防止**: CLI実行ユーザー（開発者・CI）とWebサーバープロセス（`apache` 等）の実行ユーザーが異なる環境において、CLIで生成されたコンパイル済みファイルに対してWebサーバーが `touch()` できずに `touch(): Utime failed: Operation not permitted` エラーが発生するのを防ぐため、ビューキャッシュクリアと併せて `rm -f storage/framework/views/*.php` や `chmod -R 777 storage bootstrap/cache` を確認・徹底すること。

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database Migrations

- テーブルスキーマを変更する際は、新規の差分マイグレーション（addColumn, dropColumn 等）を作成せず、既存のマイグレーションファイル（テーブル作成マイグレーション等）を直接修正して対応する。
- **マイグレーション・シーダー実行前の差分検証と同期**: `php artisan migrate:fresh --seed` などのデータベース再構築コマンドを実行する際は、直前に現時点のデータベース登録内容（UI経由で作成・変更・削除されたデータや最新のマスター状態）とシーダー（`database/seeders/*`）の内容を比較・検証すること。差分や更新の必要性がある場合は、あらかじめシーダーファイルを最新の状態に更新した上でマイグレーションを実行し、最新データの消失を防止する。

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.
- CSP検証や画面レンダリングテストでは、空モデルだけでなく子レコード（タスク、アサイン等）を登録した状態で検証し、ループ（`@foreach` / `@forelse`）や条件分岐（`@if`）内部のHTMLブロックが実際にレンダリングされた状態でインラインスタイル・イベントハンドラの不在を検証する。
- **PDFレンダリングテスト**: PDFを生成・検証するFeature/Unitテストでは、レスポンスのContent-Typeやバイナリヘッダー（`%PDF-`）だけでなく、必要に応じてDomPDFインスタンスのページ余白（`$dompdf->getCss()->get_page_styles()['base']->margin_top > 0`）が0にリセットされず維持されていることを検証し、余白喪失の再発を防止する。
- **画面レンダリングテストでのスクリプトおよびモーダルDOM検証の徹底**: 画面レンダリングの Feature テストでは、タイトルやテキスト等の表示確認にとどまらず、画面専用 JavaScript（`@vite` で解決されるスクリプトアセット、例: `vendors.js` / `vendors-`）およびモーダル用DOM要素（モーダルIDや新規登録ボタン等）が HTML 内に正しくレンダリングされていることを検証し、スクリプト読み込み欠落による UI 機能不全の再発を防止すること。

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

=== application architecture & view component rules ===

# Application Architecture & Layering Rules

本アプリケーションでは以下の7層レイヤードアーキテクチャを厳格に遵守すること。

1. **Presentation Layer (View / Presenter)**:
   - BladeテンプレートによるSSR描画。UI表示の加工は `app/Presenters/` や View Component に委譲し、Blade内に複雑なロジックを直接記述しない。
   - フロントエンドは Vanilla JS (ES Modules) + Vite + Print CSS。厳格な CSP（インラインスクリプト/スタイルの完全禁止）を遵守する。
   - 動的な幅・位置の計算（プログレスバー等）においてインラインスタイル属性（`style="..."` や動的生成文字列）をBlade内に出力しない。`data-*` 属性に値を保持させ、JavaScript (CSSOM: `element.style.width = ...`) 経由で初期化・更新する。
   - **厳格な CSP（インラインスクリプトの完全禁止）と Vite 外部モジュール化の徹底**:
     - Blade テンプレート内に直接 `<script>`（または `<script type="module">`）ブロックを記述してはならない。本アプリケーションでは CSP ミドルウェアにより `script-src 'self' 'nonce-{ViteNonce}'` が強制されており、直接記述されたインライン `<script>` はブラウザ側でセキュリティ違反として実行が遮断される。
     - JavaScript は必ず `resources/js/{feature}.js` に外部ファイルとして作成し、`vite.config.js` の `input` に登録した上で、Blade 側からは `@vite(['resources/js/{feature}.js'])` 経由で読み込むこと（Laravel Vite プラグインが自動的に CSP ノンス属性を付与するため安全に実行される）。
     - 動的な初期値やURL、件数などは HTML 側の `data-*` 属性（`dataset`）に保持させ、JavaScript 側では `element.dataset.*` 経由で取得すること（Blade 式を JS 内に展開しない）。
     - **Vite スクリプトの読み込み方式（スロット内末尾への直接配置）**: 本アプリケーションの親レイアウト（`resources/views/components/layouts/app.blade.php` 等）は各画面のビューをメインスロット `{{ $slot }}` として展開する設計となっており、親レイアウト側に `@stack('scripts')` の受け皿は設けていない。個別の Blade テンプレートで `@push('scripts')` を使用するとスクリプトタグが出力されず破棄されるため、画面専用のスクリプトは他の全画面と同様に、`<x-layouts.app>` のスロット内末尾に直接 `@vite(['resources/js/{feature}.js'])` と記述して読み込ませること。
   - **一覧画面のレイアウト・パーツデザイン標準規約**:
     一覧画面を新規作成または改修する際は、**共通 UI コンポーネント（`resources/views/components/ui/*`）および取引先管理（`resources/views/vendors/index.blade.php`）・屋台配置一覧（`resources/views/site_layouts/booths.blade.php`）**の構成とパーツデザインを基本標準として統一すること。
     - **ページヘッダー**: 画面のタイトル行は「タイトル（アイコン付き見出し）」「説明文」「リンク・アクションボタン」が横並びの一行に収まるレイアウトとする。タイトルと説明文を左側にインライン配置し、説明文は一行に収まる簡潔な短文とし、右側にアクションボタングループ（新規作成ボタン、画面遷移ボタン等）を右寄せ配置して画面上部の縦領域を圧迫しないようにする。
     - **ヘッダーアクションボタンの配色規約**: 画面タイトル行（`<x-slot:actions>`）に配置する操作ボタン（新規追加、画面遷移、一覧表示等）は、通常時（非ホバー時）は未選択・セカンダリカラー（白背景・微細グレー枠線・ダークテキスト: `bg-white border border-slate-300 text-slate-700`）とし、カーソルホバー（マウス搭乗）時にプライマリカラー（インディゴ背景・インディゴ枠線・白文字: `hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition`）へと滑らかに切り替えること。
     - **内部識別コード（ゾーンコード等）の露出防止**: 配置計画や一覧・サマリーカードなどの表示において、データベース管理上の内部識別コード（例: `ZONE_A`, `AREA_KITAGAWA`, `KIDS_VILLAGE` などのゾーンコード）は画面上に露出させず、ユーザーにとって直感的で分かりやすい正式な名称（例: `メイン通りA列（東側歩道）`, `北川駐車場内`, `キッズ村`）と件数情報のみをシンプルに表示すること。
     - **KPI / サマリーカード**: グリッド配置（2〜4列）、白背景（`bg-white`）、微細ボーダー（`border border-slate-200 rounded-xl p-4 shadow-xs`）、アイコンバッジ、カウントバッジ。
     - **表示切替フィルターバー**: `bg-slate-50 border border-slate-200 rounded-xl p-3 shadow-2xs`、インライン全選択・全解除ボタングループ、`✔`/`✕` チップトグル、件数インジケータ。
     - **データテーブル**: `bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden`、ヘッダー背景 `bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600`、行hover効果（`hover:bg-slate-50/50`）、識別コードバッジ（Monoフォント）、状態・種別バッジ。各行の「名前」は通常時は黒色（`font-bold text-slate-800`）、ホバー時に下線・インディゴ色（`hover:text-indigo-600 hover:underline transition cursor-pointer`）となるクリック可能なリンク/ボタンとし、クリック時に閲覧専用の詳細モーダルを表示する。
     - **行アクションボタン**: 操作ボタンは他のページ同様に「アイコン＋テキスト」で表示する。編集ボタン（`inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer`、`<span>✏️</span><span>編集</span>`、クリックで編集モーダルを開く）、削除ボタン（`inline-flex items-center space-x-1 px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition cursor-pointer`、`<span>🗑️</span><span>削除</span>`、確認ダイアログ付き）。
     - **詳細モーダル（閲覧専用）**: 各行の名前クリック時に開き、基本情報・関連レコード・特記事項の確認に専念させる。モーダル内には編集・削除リンクやボタンを配置せず、明確な「閉じる」ボタンのみを設けて誤操作を防止する。
     - **入力フォームモーダル（新規追加・編集）**: 画面遷移（別ページ遷移）を行わず、ページ上部アクションからの「新規登録」および行アクションからの「編集」ともにモーダルフォームを表示し、一覧画面内で追加・更新操作を完結させる。Backdrop（`bg-slate-900/40 backdrop-blur-xs`）、白カード（`rounded-2xl shadow-xl max-w-lg`）、キャンセル/保存ボタンの配色統一。
   - **グローバルナビゲーション・ドロップダウンメニューの表示規約**:
     - 項目数が多いドロップダウンメニュー（6項目以上等）は、1列の縦長配置を避け、業務・役割の論理的グループに基づいた2カラム（メガメニュー形式、例: `w-[620px] lg:w-[650px]`）に整理して縦幅を約300px以内に抑制し、ノートPC等の画面下端での見切れを防止すること。
     - 全てのドロップダウンメニュー（`.dropdown-menu`）には、小型ウィンドウや上下狭小画面での見切れ安全対策として、必ず `max-h-[calc(100vh-4.5rem)] overflow-y-auto` を付与し、常に全項目へスクロールしてアクセス可能にすること。
   - **PDFテンプレート (DomPDF) のCSS・テーブル規約**:
     - 全称セレクタ `*` をリセットスタイル等で使用しないこと（DomPDF内部で `@page` にマッチしてページ余白が `0` にリセットされ、`position: fixed` の固定ヘッダーを用紙外へ押し出すため）。リセットは `table, th, td, h1, h2, h3, div, span, strong, b, p` など具体的なタグ名を列挙して指定する。
     - テーブル列幅に `<colgroup>` / `<col>` を使用しないこと（DomPDF未サポートのため無視され均等幅にフォールバックする）。列幅は `<style>` ブロック内で `th`（または `td`）のクラスセレクタ（例: `.table th.col-name { width: ...; }`）に対して指定する。
   - **SVGベクター図面・キャンバスUIの描画品質・ズーム最適化規約**:
     - **GPUビットマップ拡大ボケの防止（ネイティブ再ラスタライズの強制）**: SVG図面やベクターキャンバスを拡大縮小するUIでは、ビューポートラッパーに `will-change: transform` や CSS `transform: scale()` を使用してはならない（ブラウザが初期サイズのテクスチャをキャッシュし、拡大時にビットマップ引き伸ばしによるボケ・にじみが発生するため）。拡大表示時は `<svg viewBox="...">` 自体の `width` / `height` 属性およびスタイルに拡大後の実ピクセル寸法（`baseWidth * scale` 等）を直接指定し、常にブラウザのネイティブ解像度でベクター再ラスタライズを実行させること。
     - **デバイスピクセル（DPR）整合**: パン座標 `(x, y)` や拡大サイズは `window.devicePixelRatio` を考慮して物理ピクセル単位（`Math.round(val * dpr) / dpr`）で整数スナップさせ、水平・垂直線のサブピクセル分散（アンチエイリアスボケ）を防止すること。
     - **ベクター精度とテキストヒンティング無効化**: `<svg>` ルートおよび図形・文字要素には `shape-rendering="geometricPrecision"` および `text-rendering="geometricPrecision"` を付与し、極小文字や極細線のグリッド吸着歪みを防止すること。
     - **CADテキストの白ハロー（視認性保護）**: 図面上の文字（ブース番号・建物名等）が背後のハッチング線や図形境界と干渉してにじまないよう、テキスト要素には `paint-order: stroke fill; stroke: #ffffff; stroke-width: ...; stroke-linejoin: round;` による白フチ（ハロー）処理を適用すること。
     - **DOMインプレース更新（イベント整合性の保護）**: SVG図面上の要素（ノード）を再描画・更新する際は、親 `<g>` 要素そのものを破棄（`remove()` / `innerHTML = ''`）せず、`<g>` 要素を保持したまま内部の子要素のみを再構築するか属性を更新し、バインド済みのイベントリスナー（ドラッグ、ダブルクリック等）を破壊しないこと。

2. **HTTP / Delivery Layer (Controller / FormRequest)**:
   - **Thin Controller の徹底**: コントローラーはリクエストの受付、バリデーション済みデータの受け渡し、Service/Actionの呼び出し、レスポンス返却のみを行い、業務ロジックを直接記述しない。
   - バリデーションおよび認可（authorize）は必ず `app/Http/Requests/` の FormRequest を使用する。

3. **Action Layer (`app/Actions/`)**:
   - スタッフアサインやCSVインポート/エクスポートなど、単一の具体的なユースケース（単一責任）をカプセル化する。

4. **Domain & Application Service Layer (`app/Services/`, `app/DTOs/`, `app/Enums/`, `app/Policies/`)**:
   - 業務ルール、横断的計算、競合検知、制約検証は Service に実装する。
   - 複数レイヤー間のデータ受け渡しは連想配列ではなく DTO（`app/DTOs/`）を定義して型安全に行う。
   - 状態・種別判定は Enum（`app/Enums/`）を使用する。
   - リソースごとの認可判定は Policy（`app/Policies/`）に委譲する。

5. **Repository Layer (`app/Repositories/`)**:
   - Eloquent クエリは直接 Controller に書かず、Repository パターンを用いる。
   - `app/Repositories/Contracts/` にインターフェースを定義し、`app/Repositories/Eloquent/` で実装する（DIPの遵守）。Service や Action からは Contract 経由で呼び出す。

6. **Model & Persistence Layer (`app/Models/`)**:
   - Eloquent Model はリレーション定義、スコープ、属性キャストに専念する。

7. **View Components (`app/View/Components/`, `resources/views/components/`)**:
   - **クラスベース (`app/View/Components/`)**: ダッシュボードの各カードなど、DI（Service注入）や表示判定ロジック（ViewModel的役割）が必要なUIパーツに使用する。
   - **匿名コンポーネント (`resources/views/components/`)**: ヘッダー、レイアウト、バッジなど、表示構造・デザインの共通化に使用する。

</laravel-boost-guidelines>
