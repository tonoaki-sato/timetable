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
            <!-- 横つながり水平ツリー -->
            <div class="space-y-6">
                @foreach($treeUnits as $rootUnit)
                    <div class="flex items-start space-x-6 pb-4 border-b border-slate-200 last:border-b-0">
                        <!-- ルート組織カード (左端) -->
                        <div class="shrink-0 w-44 p-3 rounded-lg border-2 border-slate-800 bg-emerald-50 print:bg-slate-100">
                            <div class="font-bold text-xs text-slate-900">{{ $rootUnit->name }}</div>
                            @if($rootUnit->staff->isNotEmpty())
                                <div class="text-[11px] text-slate-700 mt-1">{{ $rootUnit->staffNamesDisplay }}</div>
                            @endif
                            @if($rootUnit->short_description)
                                <div class="text-[10px] text-slate-500 mt-0.5">{{ $rootUnit->short_description }}</div>
                            @endif
                        </div>

                        <!-- 子組織群 (右へ横に展開) -->
                        @if($rootUnit->children->isNotEmpty())
                            <div class="relative pl-6 border-l-2 border-slate-400 flex flex-col space-y-3 flex-1">
                                @foreach($rootUnit->children as $childUnit)
                                    <div class="flex items-start space-x-6 relative">
                                        <!-- 水平接続線 -->
                                        <div class="absolute -left-6 top-3 w-6 h-0.5 bg-slate-400"></div>

                                        <!-- 子カード (第1階層) -->
                                        <div class="shrink-0 w-44 p-2 rounded-lg border border-slate-700 bg-white">
                                            <div class="font-bold text-xs text-slate-900">{{ $childUnit->name }}</div>
                                            @if($childUnit->staff->isNotEmpty())
                                                <div class="text-[11px] text-slate-700 mt-0.5">{{ $childUnit->staffNamesDisplay }}</div>
                                            @endif
                                            @if($childUnit->short_description)
                                                <div class="text-[10px] text-slate-500 mt-0.5">{{ $childUnit->short_description }}</div>
                                            @endif
                                        </div>

                                        <!-- 孫組織群 (さらに右へ横に展開) -->
                                        @if($childUnit->children->isNotEmpty())
                                            <div class="relative pl-6 border-l-2 border-slate-300 flex flex-col space-y-2 flex-1">
                                                @foreach($childUnit->children as $grandChild)
                                                    <div class="flex items-center space-x-2 relative">
                                                        <!-- 水平接続線 -->
                                                        <div class="absolute -left-6 top-2.5 w-6 h-0.5 bg-slate-300"></div>

                                                        <!-- 孫カード (第2階層) -->
                                                        <div class="p-1.5 rounded-md border border-slate-400 bg-slate-50 min-w-32 max-w-52">
                                                            <div class="font-bold text-[11px] text-slate-800">{{ $grandChild->name }}</div>
                                                            @if($grandChild->staff->isNotEmpty())
                                                                <div class="text-[10px] text-slate-600">{{ $grandChild->staffNamesDisplay }}</div>
                                                            @endif
                                                            @if($grandChild->short_description)
                                                                <div class="text-[9px] text-slate-500">{{ $grandChild->short_description }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>
