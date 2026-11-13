<x-layouts.app :title="'🏛️ 組織管理'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">
        <!-- ページヘッダー -->
        <x-page-header
            title="組織管理"
            icon="🏛️"
            description="祭り実行委員会の組織体制・部門・係・担当スタッフの管理"
        >
            <x-slot:breadcrumbs>
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>🏮</span>
                    <span>本部</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-500">🗂️ マスター管理</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">🏛️ 組織管理</span>
            </x-slot:breadcrumbs>

            <x-slot:actions>
                <a href="{{ route('organizations.print') }}" target="_blank" class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0 cursor-pointer">
                    <span>🖨️</span>
                    <span>PDF印刷</span>
                    <span class="text-[10px] text-slate-400">↗</span>
                </a>
                <button type="button" id="btn-open-create-modal" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0 cursor-pointer">
                    <span>➕</span>
                    <span>新規組織追加</span>
                </button>
            </x-slot:actions>
        </x-page-header>

        <!-- タブナビゲーション -->
        <div class="flex items-center space-x-1 border-b border-slate-200">
            <button type="button" class="tab-button px-4 py-2 text-xs font-bold border-b-2 transition cursor-pointer border-indigo-600 text-indigo-600" data-tab-target="tab-list">
                📋 組織一覧
            </button>
            <button type="button" class="tab-button px-4 py-2 text-xs font-bold border-b-2 transition cursor-pointer border-transparent text-slate-500 hover:text-slate-800" data-tab-target="tab-tree">
                🗺️ 組織図ビュー
            </button>
        </div>

        <!-- 成功/通知メッセージ -->
        @if(session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-medium flex items-center justify-between">
                <span>✅ {{ session('success') }}</span>
            </div>
        @endif
        <div id="reorder-toast" class="hidden fixed bottom-5 right-5 z-50 p-3 bg-slate-900 text-white rounded-xl shadow-2xl text-xs font-medium flex items-center space-x-2 transition-all">
            <span>✅</span>
            <span id="reorder-toast-message">並び順を更新しました</span>
        </div>

        <!-- 1. 組織一覧テーブル (デフォルト表示) -->
        <div id="tab-list" class="tab-content space-y-3">
            <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold">
                                <th class="w-12 py-3 px-3 text-center">並替</th>
                                <th class="py-3 px-3">名前</th>
                                <th class="py-3 px-3">説明</th>
                                <th class="py-3 px-3">担当</th>
                                <th class="w-28 py-3 px-3 text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody id="organizations-table-body" class="divide-y divide-slate-100">
                            @forelse($units as $unit)
                                <tr class="hover:bg-slate-50/70 transition group" data-unit-id="{{ $unit->id }}">
                                    <!-- ドラッグハンドル -->
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="drag-handle inline-flex items-center justify-center p-1 text-slate-300 hover:text-slate-600 rounded-md cursor-grab active:cursor-grabbing text-base" title="ドラッグして並び替え">
                                            ⠿
                                        </span>
                                    </td>

                                    <!-- 名前 (クリックで詳細モーダル起動、階層順に字下げ) -->
                                    <td class="py-2.5 px-3 text-slate-900">
                                        <div class="flex items-center">
                                            @if(($unit->depth ?? 0) > 0)
                                                <span class="text-slate-400 font-mono select-none mr-1.5" aria-hidden="true">
                                                    {{ str_repeat('　', (int) $unit->depth - 1) }}└─
                                                </span>
                                            @endif
                                            <button type="button" class="text-indigo-600 hover:text-indigo-800 hover:underline transition font-bold text-left cursor-pointer" data-open-detail-modal="{{ $unit->id }}">
                                                {{ $unit->name }}
                                            </button>
                                        </div>
                                    </td>

                                    <!-- 説明 -->
                                    <td class="py-2.5 px-3 text-slate-600 max-w-xs truncate" title="{{ $unit->short_description }}">
                                        {{ $unit->short_description ?: '-' }}
                                    </td>

                                    <!-- 担当 (役割を除いた名前のみ) -->
                                    <td class="py-2.5 px-3 text-slate-700">
                                        @if($unit->staff->isNotEmpty())
                                            <span class="text-xs">{{ $unit->staffNamesDisplay }}</span>
                                        @else
                                            <span class="text-slate-400 italic text-[11px]">-</span>
                                        @endif
                                    </td>

                                    <!-- 操作 (編集・削除) -->
                                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center space-x-1">
                                            <button type="button" class="p-1.5 text-xs text-indigo-600 hover:bg-indigo-50 rounded-lg transition cursor-pointer" data-open-edit-modal="{{ $unit->id }}" title="編集">
                                                <span>✏️</span>
                                                <span class="hidden sm:inline">編集</span>
                                            </button>
                                            <button type="button" class="p-1.5 text-xs text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer" data-delete-unit="{{ $unit->id }}" data-unit-name="{{ $unit->name }}" title="削除">
                                                <span>🗑️</span>
                                                <span class="hidden sm:inline">削除</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <span class="text-3xl">🏛️</span>
                                            <p class="font-bold text-slate-600">登録されている組織・係がありません</p>
                                            <p class="text-xs text-slate-400">右上の「新規組織追加」ボタンから登録してください。</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. 組織図ビュー (親から子へ横につながる水平ツリー) -->
        <div id="tab-tree" class="tab-content hidden space-y-4">
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-5 overflow-x-auto min-h-[500px]">
                <div class="text-center mb-6">
                    <h2 class="text-lg font-bold text-slate-900 tracking-wide">保土ケ谷宿場まつり実行委員会 組織図</h2>
                </div>

                @if($treeUnits->isEmpty())
                    <div class="py-12 text-center text-slate-400">
                        <span class="text-3xl block mb-2">🏛️</span>
                        <p class="font-bold text-slate-600">組織図データがありません</p>
                        <p class="text-xs text-slate-400 mt-1">「新規組織追加」から組織や係を登録すると、ここに水平ツリーが表示されます。</p>
                    </div>
                @else
                    <!-- 水平ツリーコンテナ (親から子へ横につながるレイアウト: 第4階層以下＆末端グループ化対応) -->
                    <div class="horizontal-tree-container flex flex-col space-y-8 min-w-[800px]">
                        @foreach($treeUnits as $rootUnit)
                            @include('organizations.components.tree-node', ['unit' => $rootUnit, 'level' => 1, 'isPrint' => false])
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 削除確認フォーム（隠し） -->
    <form id="delete-organization-form" method="POST" action="" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <!-- モーダルインクルード -->
    @include('organizations.components.form-modal')
    @include('organizations.components.detail-modal')

    @vite(['resources/js/organizations.js'])
</x-layouts.app>

