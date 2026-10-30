<x-layouts.app title="拠点タイプマスタ管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ヘッダーエリア -->
    <x-page-header
        title="拠点タイプマスタ管理"
        icon="🏷️"
        description="「コミュニティスペース」「駐車場」など、拠点の種別を追加・編集・整理できます。">
        <x-slot:breadcrumbs>
            <a href="{{ route('locations.index') }}" class="hover:underline">拠点管理</a>
            <span>/</span>
            <span class="text-slate-800 font-bold">タイプマスタ管理</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('locations.index') }}"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                <span>← 拠点一覧に戻る</span>
            </a>
            <button type="button"
                    data-action="open-type-modal"
                    data-mode="create"
                    data-store-url="{{ route('location-types.store') }}"
                    class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                <span>➕</span>
                <span>新しいタイプを追加</span>
            </button>
        </x-slot:actions>
    </x-page-header>

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- タイプ一覧テーブル -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs divide-y divide-slate-100">
                <thead class="bg-slate-50/75 border-b border-slate-200 text-slate-500 font-bold text-[11px]">
                    <tr>
                        <th class="py-2.5 px-4">表示名 (label)</th>
                        <th class="py-2.5 px-4">内部処理用文字列 (name / slug)</th>
                        <th class="py-2.5 px-4">説明</th>
                        <th class="py-2.5 px-4 text-center">所属拠点数</th>
                        <th class="py-2.5 px-4 text-center">表示順</th>
                        <th class="py-2.5 px-4 text-center">状態</th>
                        <th class="py-2.5 px-4 text-right">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($types as $type)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-2.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                    {{ $type->label }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 font-mono text-slate-600 whitespace-nowrap">
                                <code>{{ $type->name }}</code>
                            </td>
                            <td class="py-2.5 px-4 text-slate-600 max-w-xs truncate">
                                {{ $type->description ?: '—' }}
                            </td>
                            <td class="py-2.5 px-4 text-center font-bold text-slate-700 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[11px]">
                                    {{ $type->locations_count }} 件
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-center text-slate-500 whitespace-nowrap">
                                {{ $type->display_order }}
                            </td>
                            <td class="py-2.5 px-4 text-center whitespace-nowrap">
                                @if($type->is_active)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">有効</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-400">無効</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-4 text-right space-x-1 whitespace-nowrap">
                                <button type="button"
                                        data-action="open-type-modal"
                                        data-mode="edit"
                                        data-update-url="{{ route('location-types.update', $type) }}"
                                        data-type="{{ json_encode($type) }}"
                                        class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[11px] font-bold transition cursor-pointer">
                                    編集
                                </button>
                                @if($type->locations_count === 0)
                                    <form method="POST" action="{{ route('location-types.destroy', $type) }}"
                                          data-confirm="拠点タイプ「{{ $type->label }}」を削除しますか？" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded text-[11px] font-medium transition cursor-pointer">
                                            削除
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-slate-300 cursor-not-allowed" title="拠点が紐付いているため削除できません">
                                        削除不可
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- タイプ追加・編集モーダル (既存標準モーダルデザイン・CSP準拠) -->
<div id="type-modal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-slate-200 text-left space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="type-modal-title" class="text-sm sm:text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>🏷️</span>
                <span>拠点タイプの追加</span>
            </h3>
            <button type="button" data-action="close-type-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">✕</button>
        </div>

        <form id="type-modal-form" method="POST" action="{{ route('location-types.store') }}" class="space-y-4">
            @csrf
            <div id="type-form-method"></div>

            <div>
                <label for="type_label" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">表示名 (label) <span class="text-rose-500">*</span></label>
                <input type="text" name="label" id="type_label" required placeholder="例: 駐車場, コミュニティスペース, 広場"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label for="type_name" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">
                    内部処理用文字列 (name / slug) <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="name" id="type_name" required placeholder="例: parking, community_space, plaza"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                <p class="text-[11px] text-slate-400 mt-0.5">※半角英数字とハイフン・アンダースコア（重複不可）</p>
            </div>

            <div>
                <label for="type_description" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">説明（任意）</label>
                <textarea name="description" id="type_description" rows="2" placeholder="このタイプの利用用途などを記載"
                          class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="type_display_order" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">表示順</label>
                    <input type="number" name="display_order" id="type_display_order" value="10" min="0"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex items-center space-x-2 pt-5">
                    <input type="checkbox" name="is_active" id="type_is_active" value="1" checked
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                    <label for="type_is_active" class="text-xs font-bold text-slate-700 cursor-pointer">有効</label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" data-action="close-type-modal"
                        class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                    キャンセル
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    保存する
                </button>
            </div>
        </form>
    </div>
</div>

@vite(['resources/js/locations.js'])
</x-layouts.app>
