<x-layouts.app title="新規スタッフ登録">
<div class="max-w-2xl mx-auto px-4 py-6 space-y-6">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        title="新規スタッフ登録"
        icon="➕"
        description="現場運行を補助するボランティア・スタッフを登録します。">
        <x-slot:breadcrumbs>
            <a href="{{ route('staff.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1">
                <span>👷</span>
                <span>スタッフ一覧</span>
            </a>
            <span>/</span>
            <span class="text-slate-700 font-bold">新規登録</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('staff.index') }}" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>←</span>
                <span>スタッフ一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

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

            <!-- LINEグループ表示名（呼名） -->
            <div>
                <label for="line_display_name" class="block text-xs font-bold text-slate-700 mb-1">
                    LINEグループ表示名（呼名） <span class="text-slate-400 font-normal">（任意）</span>
                </label>
                <input type="text" id="line_display_name" name="line_display_name" value="{{ old('line_display_name') }}"
                    placeholder="例: やまちゃん / 〇〇町内会・鈴木"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-[11px] text-slate-400 mt-1">※現場LINEグループでのメンションや指示出し用の呼名です。後から本人がLINE連携することも可能です。</p>
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
