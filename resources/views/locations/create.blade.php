<x-layouts.app :title="'新規拠点の追加 - ' . $timetable->name">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        title="新規拠点の追加"
        icon="➕"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）に新しい拠点（番所・駐車場・倉庫等）を追加します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>運行情報一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>📅</span>
                <span>{{ $timetable->name }}</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.locations.index', $timetable) }}" class="hover:text-indigo-600 transition truncate">
                📍 拠点一覧
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">➕ 新規追加</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.locations.index', $timetable) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>拠点一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="locations" />

    <!-- 入力エラーメッセージ -->
    @if($errors->any())
    <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl space-y-1">
        <div class="font-bold flex items-center space-x-1">
            <span>⚠️</span>
            <span>入力内容を確認してください</span>
        </div>
        @foreach($errors->all() as $err)
        <div>・{{ $err }}</div>
        @endforeach
    </div>
    @endif

    @if($types->isEmpty())
    <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 text-xs rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="space-y-1">
            <div class="font-bold flex items-center space-x-1.5">
                <span>⚠️</span>
                <span>拠点タイプ（種別マスタ）がまだ登録されていません</span>
            </div>
            <p class="text-[11px] text-amber-800">
                拠点を登録するには、先に「タイプマスタ管理」画面から拠点タイプ（駐車場・保管倉庫・コミュニティスペース等）を追加してください。
            </p>
        </div>
        <a href="{{ route('location-types.index') }}"
            class="inline-flex items-center space-x-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-xs transition shrink-0">
            <span>🏷️</span>
            <span>タイプマスタ管理へ移動</span>
        </a>
    </div>
    @endif

    <!-- 作成フォームカード -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                <span>📍</span>
                <span>拠点の基本情報設定</span>
            </h2>
            <span class="text-xs text-slate-500 font-mono">
                日付: {{ $timetable->target_date?->format('Y/m/d') }}
            </span>
        </div>

        <form action="{{ route('timetables.locations.store', $timetable) }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- 2段組みグリッド -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                <!-- 左カラム: 拠点名 & タイプ & 終日設定 -->
                <div class="space-y-4">
                    <!-- 拠点名 -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                            拠点名 <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            placeholder="例: 税務署前駐車場, 番所, コミュニティスペースA"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- 拠点タイプ -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="location_type_id" class="block text-xs font-bold text-slate-700">
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

                    <!-- 表示順 & 有効フラグ -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="display_order" class="block text-xs font-bold text-slate-700 mb-1">表示順</label>
                            <input type="number" name="display_order" id="display_order" value="{{ old('display_order', 10) }}" min="0"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="flex items-center space-x-2 pt-6">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <label for="is_active" class="text-xs font-bold text-slate-700 cursor-pointer">有効（稼働中）</label>
                        </div>
                    </div>
                </div>

                <!-- 右カラム: 備考・案内メモ -->
                <div class="space-y-4">
                    <div>
                        <label for="notes" class="block text-xs font-bold text-slate-700 mb-1">
                            メモ欄（注意事項・連絡先・鍵管理）
                        </label>
                        <textarea name="notes" id="notes" rows="8"
                            placeholder="例: &#10;・鍵の受渡方法・暗証番号&#10;・管理担当者の連絡先&#10;・利用時の注意事項・駐車制限等"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('notes') }}</textarea>
                        <p class="text-[11px] text-slate-500 mt-1">連絡先や注意事項、鍵の保管場所など自由に記録できます。</p>
                    </div>
                </div>

            </div>

            <!-- アクションボタン -->
            <div class="flex items-center justify-end space-x-2 pt-4 border-t border-slate-100">
                <a href="{{ route('timetables.locations.index', $timetable) }}"
                    class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer">
                    キャンセル
                </a>
                <button type="submit" @disabled($types->isEmpty())
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer">
                    登録して利用時間帯を設定する
                </button>
            </div>
        </form>
    </div>

</div>
</x-layouts.app>
