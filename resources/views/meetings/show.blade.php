<x-layouts.app :title="$meeting->title . ' - 会議詳細'">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$meeting->title"
        icon="🤝"
        :description="$meeting->formatted_datetime . ($meeting->location ? ' ｜ 場所: ' . $meeting->location : '')">
        <x-slot:breadcrumbs>
            <a href="{{ route('meetings.index', ['tab' => $meeting->is_upcoming ? 'upcoming' : 'past']) }}" class="hover:text-indigo-600 transition flex items-center space-x-1">
                <span>🤝</span>
                <span>会議・打ち合わせ一覧</span>
            </a>
            <span>/</span>
            <span class="text-slate-700 font-bold">{{ $meeting->title }}</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            @if($meeting->is_upcoming)
                @if($meeting->meeting_date?->isToday())
                <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    今日開催
                </span>
                @elseif($meeting->meeting_date?->diffInDays(now()) <= 3)
                <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    まもなく開催
                </span>
                @else
                <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    開催予定
                </span>
                @endif
            @else
                <span class="px-2.5 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                    終了した会議
                </span>
            @endif

            <span class="text-xs text-slate-400 font-mono">
                ID: #{{ $meeting->id }}
            </span>
        </x-slot:badges>

        <x-slot:actions>
            @can('update', $meeting)
            <a href="{{ route('meetings.edit', $meeting) }}"
                class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>✏️</span>
                <span>編集する</span>
            </a>
            @endcan

            @can('delete', $meeting)
            <form action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="inline"
                data-confirm="会議「{{ $meeting->title }}」を削除しますか？&#10;この操作は取り消せません。">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs cursor-pointer">
                    <span>🗑️</span>
                    <span>削除</span>
                </button>
            </form>
            @endcan

            <a href="{{ route('meetings.index', ['tab' => $meeting->is_upcoming ? 'upcoming' : 'past']) }}"
                class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>←</span>
                <span>会議・打ち合わせ一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 1. メイン会議情報カード -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <!-- 基本情報グリッド -->
        <div class="p-6 sm:p-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- 日時 -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">📅 開催日時</span>
                    <div class="text-sm font-bold text-slate-900 font-mono">
                        {{ $meeting->formatted_datetime }}
                    </div>
                </div>

                <!-- 場所 -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">📍 開催場所</span>
                    <div class="text-sm font-bold text-slate-900">
                        {{ $meeting->location }}
                    </div>
                </div>

                <!-- 主催者 -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 flex flex-col justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-500 block mb-1">👤 主催者（スタッフ）</span>
                        @if($meeting->host)
                        <div class="text-sm font-bold text-slate-900">
                            {{ $meeting->host->name }}
                        </div>
                        @if($meeting->host->affiliation)
                        <span class="text-[11px] text-slate-500 block mt-0.5">{{ $meeting->host->affiliation }}</span>
                        @endif
                        @else
                        <div class="inline-flex items-center space-x-1.5 mt-0.5">
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">未設定（後から設定可）</span>
                        </div>
                        @can('update', $meeting)
                        <a href="{{ route('meetings.edit', $meeting) }}" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-bold block mt-1">
                            ✏️ 主催者を設定する
                        </a>
                        @endcan
                        @endif
                    </div>
                </div>
            </div>

            <!-- 議題・内容 -->
            <div>
                <h3 class="text-xs font-bold text-slate-700 mb-2">📝 議題・内容・持ち物</h3>
                <div class="p-5 bg-slate-50/70 border border-slate-200 rounded-xl text-sm text-slate-800 leading-relaxed min-h-[90px] whitespace-pre-wrap">
                    {{ $meeting->description ?: '議題・詳細内容の登録はありません。' }}
                </div>
            </div>

            <!-- LINE特記メモがある場合 -->
            @if($meeting->notice_note)
            <div>
                <h3 class="text-xs font-bold text-slate-700 mb-2">📌 連絡事項・出欠期日等</h3>
                <div class="p-4 bg-amber-50/60 border border-amber-200 rounded-xl text-xs sm:text-sm text-amber-900 leading-relaxed whitespace-pre-wrap">
                    {{ $meeting->notice_note }}
                </div>
            </div>
            @endif

            <!-- メタフッター -->
            <div class="pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-500">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 block">登録ユーザー</span>
                    <span class="font-medium text-slate-700">{{ $meeting->creator?->name ?? 'システム' }}</span>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-slate-400 block">登録日時</span>
                    <span class="font-mono text-slate-700">{{ $meeting->created_at?->format('Y/m/d H:i') ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. 全体LINE告知用テキストボックス（中核機能） -->
    <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 text-white rounded-2xl shadow-xl border border-slate-700 overflow-hidden">
        <!-- ボックスヘッダー & タブ -->
        <div class="p-6 border-b border-slate-700/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-2.5">
                <span class="text-2xl">💬</span>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-white tracking-tight flex items-center space-x-2">
                        <span>全体LINE告知テキスト</span>
                        <span class="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded-full font-mono">自動生成</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">LINEグループにそのまま貼り付けて送信できます</p>
                </div>
            </div>

            <!-- タブ切り替え（開催案内 / 前日リマインド） -->
            <div class="inline-flex p-1 bg-slate-800 rounded-xl border border-slate-700 shrink-0">
                <button type="button" data-meeting-tab="share" id="tabBtnShare"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition bg-emerald-600 text-white shadow-xs cursor-pointer">
                    📢 開催案内文
                </button>
                <button type="button" data-meeting-tab="remind" id="tabBtnRemind"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition text-slate-400 hover:text-white cursor-pointer">
                    ⏰ 前日リマインド文
                </button>
            </div>
        </div>

        <!-- テキスト表示エリア -->
        <div class="p-6 space-y-4">
            <template id="shareTextTemplate">{{ $meeting->line_share_text }}</template>
            <template id="remindTextTemplate">{{ $meeting->line_remind_text }}</template>

            <div class="bg-slate-950/80 p-5 rounded-xl border border-slate-800 font-mono text-xs sm:text-sm text-slate-100 whitespace-pre-wrap leading-relaxed select-all" id="lineTextBox">{{ $meeting->line_share_text }}</div>

            <!-- アクションボタン群 -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                <div class="text-xs text-slate-400 flex items-center space-x-1">
                    <span>💡</span>
                    <span>下のボタンでコピーし、LINEアプリに貼り付けてください</span>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- LINEアプリ直接起動リンク (スマホ用) -->
                    @php
                        $encodedShare = urlencode($meeting->line_share_text);
                    @endphp
                    <a href="https://line.me/R/msg/text/?{{ $encodedShare }}" target="_blank" rel="noopener noreferrer"
                        id="lineAppBtn"
                        class="px-4 py-2.5 bg-[#06C755] hover:bg-[#05b34c] text-white rounded-xl text-xs font-bold shadow-md transition flex items-center space-x-1.5">
                        <span class="text-base font-bold">L</span>
                        <span>LINEで送る</span>
                    </a>

                    <!-- クリップボードコピーボタン -->
                    <button type="button" data-action="copy-current-line"
                        id="copyLineBtn"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center space-x-1.5 cursor-pointer">
                        <span>📋</span>
                        <span id="copyBtnLabel">文面をコピーする</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@vite(['resources/js/meetings.js'])
</x-layouts.app>
