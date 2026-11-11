<x-layouts.app title="スタッフ情報編集">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <div class="max-w-4xl mx-auto space-y-3.5">
        <!-- ナビゲーション & ヘッダー -->
        <x-page-header
            title="スタッフ情報編集"
            icon="✏️"
            :description="'「' . $staff->name . '」の情報を更新します。'">
            <x-slot:breadcrumbs>
                <a href="{{ route('staff.index') }}" class="hover:underline shrink-0">スタッフ管理</a>
                <span class="shrink-0 text-slate-300">/</span>
                <a href="{{ route('staff.show', $staff) }}" class="hover:underline truncate max-w-[160px]">{{ $staff->name }}</a>
                <span class="shrink-0 text-slate-300">/</span>
                <span class="text-slate-800 font-bold shrink-0">編集</span>
            </x-slot:breadcrumbs>
        </x-page-header>

        <!-- バリデーションエラーサマリー -->
        @if ($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-lg space-y-1">
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
        <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 shadow-xs">
            <form action="{{ route('staff.update', $staff) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <!-- 氏名 -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                        氏名 <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name', $staff->name) }}" required
                        class="w-full px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- フリガナ -->
                <div>
                    <label for="kana" class="block text-xs font-bold text-slate-700 mb-1">
                        フリガナ
                    </label>
                    <input type="text" id="kana" name="kana" value="{{ old('kana', $staff->kana) }}"
                        class="w-full px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- 所属団体・地域会等 -->
                <div>
                    <label for="affiliation" class="block text-xs font-bold text-slate-700 mb-1">
                        所属団体・地域会等
                    </label>
                    <input type="text" id="affiliation" name="affiliation" value="{{ old('affiliation', $staff->affiliation) }}"
                        class="w-full px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- 稼働状態 -->
                <div class="pt-1">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $staff->is_active) ? 'checked' : '' }}
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs font-bold text-slate-700">稼働可能スタッフ</span>
                    </label>
                    <p class="text-[11px] text-slate-400 mt-0.5 pl-5">チェックを外すとタスクのアサイン候補に表示されなくなります。</p>
                </div>

                <!-- ボタンエリア -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('staff.show', $staff) }}"
                        class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0">
                        キャンセル
                    </a>
                    <button type="submit"
                        class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer whitespace-nowrap shrink-0">
                        変更内容を保存する
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
</x-layouts.app>
