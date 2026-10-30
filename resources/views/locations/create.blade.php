<x-layouts.app title="拠点の新規登録">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ヘッダーエリア -->
    <x-page-header
        title="拠点の新規登録"
        icon="📍"
        description="番所、駐車場、コミュニティスペース等の基本情報を登録します。">
        <x-slot:breadcrumbs>
            <a href="{{ route('locations.index') }}" class="hover:underline">拠点管理</a>
            <span>/</span>
            <span class="text-slate-800 font-bold">新規登録</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('locations.index') }}"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                <span>← 一覧に戻る</span>
            </a>
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

    <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-6">
        <form method="POST" action="{{ route('locations.store') }}" class="space-y-5">
            @csrf

            <!-- 拠点名 -->
            <!-- 拠点名 -->
            <div>
                <label for="name" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">
                    拠点名 <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       placeholder="例: 税務署前駐車場, 番所, コミュニティスペースA"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- 拠点タイプ -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="location_type_id" class="block text-[11px] sm:text-xs font-bold text-slate-700">
                        拠点タイプ <span class="text-rose-500">*</span>
                    </label>
                    <a href="{{ route('location-types.index') }}" target="_blank" class="text-[11px] font-bold text-indigo-600 hover:underline">
                        ＋ 新しいタイプを追加
                    </a>
                </div>
                <select name="location_type_id" id="location_type_id" required
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">選択してください</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" {{ old('location_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->label }} ({{ $type->name }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 終日利用可能フラグ -->
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex items-start space-x-3">
                <input type="checkbox" name="is_all_day" id="is_all_day" value="1" {{ old('is_all_day') ? 'checked' : '' }}
                       class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                <div>
                    <label for="is_all_day" class="text-xs font-bold text-slate-800 cursor-pointer">終日（24時間）常時利用可能な拠点</label>
                    <p class="text-[11px] text-slate-500 mt-0.5">時間制限なく利用できる場合にチェックしてください。チェックがない場合は詳細画面で年月日時分単位の時間帯スロットを設定します。</p>
                </div>
            </div>

            <!-- メモ欄（フリーテキスト） -->
            <div>
                <label for="notes" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">
                    メモ欄（フリーテキスト）
                </label>
                <textarea name="notes" id="notes" rows="4"
                          placeholder="例: &#10;・鍵の受渡方法・暗証番号&#10;・管理担当者の連絡先&#10;・利用時の注意事項・駐車制限等"
                          class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('notes') }}</textarea>
                <p class="text-[11px] text-slate-500 mt-1">連絡先や注意事項、鍵の保管場所など自由に記録できます。</p>
            </div>

            <!-- 表示順 & 有効フラグ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label for="display_order" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">表示順</label>
                    <input type="number" name="display_order" id="display_order" value="{{ old('display_order', 10) }}" min="0"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex items-center space-x-2 pt-5">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                    <label for="is_active" class="text-xs font-bold text-slate-700 cursor-pointer">有効（稼働中）</label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('locations.index') }}"
                    class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                    キャンセル
                </a>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    登録して利用時間帯を設定する
                </button>
            </div>
        </form>
    </div>

</div>
</x-layouts.app>
