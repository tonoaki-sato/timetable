<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>保土ケ谷宿場まつり実行委員会 組織図 - 印刷</title>
    @vite(['resources/css/app.css', 'resources/js/organizations.js'])
</head>
<body class="bg-slate-100 text-slate-900 font-sans p-6 print:p-0 print:bg-white">
    <!-- 印刷操作バー (印刷時は非表示) -->
    <div class="max-w-6xl mx-auto mb-6 p-4 bg-white rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between print:hidden">
        <div class="flex items-center space-x-2">
            <span class="text-xl">🏛️</span>
            <span class="font-bold text-sm text-slate-800">保土ケ谷宿場まつり実行委員会 組織図 - 印刷プレビュー</span>
            <span class="text-xs text-slate-400">（推奨設定: A4 または A3 横向き）</span>
        </div>
        <div class="flex items-center space-x-2">
            <button type="button" id="btn-close-page" class="px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                閉じる
            </button>
            <button type="button" id="btn-print-page" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition flex items-center space-x-1 cursor-pointer">
                <span>🖨️</span>
                <span>このページを印刷</span>
            </button>
        </div>
    </div>

    <!-- 印刷用紙コンテナ -->
    <div class="max-w-6xl mx-auto bg-white p-8 rounded-2xl shadow-sm border border-slate-200 print:border-none print:shadow-none print:p-0 print:max-w-none">
        <!-- 表題 -->
        <div class="text-center pb-4 mb-6 border-b-2 border-slate-800">
            <h1 class="text-xl font-bold tracking-wider text-slate-900">保土ケ谷宿場まつり実行委員会 組織図</h1>
        </div>

        @if($treeUnits->isEmpty())
            <div class="py-16 text-center text-slate-400">
                <p class="font-bold">登録されている組織・係がありません</p>
            </div>
        @else
            <!-- 横つながり水平ツリー (第4階層以下＆末端グループ化対応) -->
            <div class="space-y-6">
                @foreach($treeUnits as $rootUnit)
                    <div class="pb-4 border-b border-slate-200 last:border-b-0">
                        @include('organizations.components.tree-node', ['unit' => $rootUnit, 'level' => 1, 'isPrint' => true])
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>
