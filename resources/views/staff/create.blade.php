<x-layouts.app title="新規スタッフ登録">
<div class="max-w-2xl mx-auto px-4 py-8 space-y-6">

    <!-- ヘッダー -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">➕ 新規スタッフ登録</h2>
            <p class="text-xs text-slate-500 mt-1">現場運行を補助するボランティア・スタッフを登録します。</p>
        </div>
        <a href="{{ route('staff.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-800 transition">
            ← 一覧に戻る
        </a>
    </div>

    <!-- バリデーションエラーサマリー -->
    @if ($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl space-y-1">
        <div class="font-bold flex items-center space-x-1">
            <span>⚠️</span>
            <span>入力内容を確認してください</span>
        </div>
        @foreach ($errors->all() as $error)
        <div>・{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <!-- 入力フォーム -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
        <form action="{{ route('staff.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- 氏名 -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                    氏名 <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    placeholder="例: 山田 太郎"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- フリガナ -->
            <div>
                <label for="kana" class="block text-xs font-bold text-slate-700 mb-1">
                    フリガナ
                </label>
                <input type="text" id="kana" name="kana" value="{{ old('kana') }}"
                    placeholder="例: ヤマダ タロウ"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- 所属団体・地域会等 -->
            <div>
                <label for="affiliation" class="block text-xs font-bold text-slate-700 mb-1">
                    所属団体・地域会等
                </label>
                <input type="text" id="affiliation" name="affiliation" value="{{ old('affiliation') }}"
                    placeholder="例: 中央商店街振興会 / 青葉大学ボランティア部 / 一般公募"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- 稼働状態 -->
            <div class="pt-2">
                <label class="flex items-center space-x-2.5 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                    <span class="text-xs font-bold text-slate-700">稼働可能スタッフとして登録する</span>
                </label>
                <p class="text-[11px] text-slate-400 mt-1 pl-6.5">チェックを外すとタスクのアサイン候補に表示されなくなります。</p>
            </div>

            <!-- ボタンエリア -->
            <div class="pt-4 border-t border-slate-200 flex items-center justify-end space-x-3">
                <a href="{{ route('staff.index') }}"
                    class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-medium transition">
                    キャンセル
                </a>
                <button type="submit"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition cursor-pointer">
                    スタッフを登録する
                </button>
            </div>

        </form>
    </div>

</div>
</x-layouts.app>
