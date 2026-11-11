<x-layouts.app :title="$staff->name . ' - スタッフ詳細'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$staff->name"
        icon="👷"
        description="スタッフ台帳の基本情報、マイタイムテーブル確認URL・QRコードおよび名札PDF発行を管理します。">
        <x-slot:breadcrumbs>
            <a href="{{ route('staff.index') }}" class="hover:text-indigo-600 transition shrink-0">スタッフ管理</a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate max-w-[160px]">{{ $staff->name }}</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            @if($staff->is_active)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                ● 稼働可能
            </span>
            @else
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200 shrink-0">
                停止中
            </span>
            @endif

            @if($staff->is_reissued)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 shrink-0">
                ⚠️ 再発行 {{ $staff->reissue_count }}回目
            </span>
            @endif
        </x-slot:badges>

        <x-slot:actions>
            <a href="{{ route('staff.print-card', $staff) }}"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0"
                title="名札カードをPDFでダウンロード">
                <span>📄</span>
                <span>名札PDFダウンロード</span>
            </a>

            <a href="{{ route('staff.index') }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- セキュリティ再発行完了バナー -->
    @if(session('reissued_success'))
    <div class="p-4 bg-amber-50 border-2 border-amber-300 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
        <div class="flex items-start space-x-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <h4 class="text-sm font-bold text-amber-900">名札トークンのセキュリティ再発行が完了しました</h4>
                <p class="text-xs text-amber-800 mt-0.5">
                    以前の古いQRコード・URLはすべて無効化（404）されました。新しく発行された名札カードを印刷してスタッフにお渡しください。
                </p>
            </div>
        </div>
        <a href="{{ route('staff.print-card', $staff) }}"
           class="inline-flex items-center justify-center px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-sm transition whitespace-nowrap space-x-1.5 cursor-pointer">
            <span>📄</span>
            <span>新名札カードPDFをダウンロード</span>
            <span>↓</span>
        </a>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

        <!-- 左カラム (2/3): 基本情報 & 本人専用マイタイムテーブル確認URL -->
        <div class="lg:col-span-2 space-y-4">

            <!-- 1. 基本情報カード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
                <div class="pb-3 border-b border-slate-100">
                    <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                        <span>👷</span>
                        <span>基本情報</span>
                    </h2>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">スタッフID</span>
                        <span class="font-bold font-mono text-slate-800 block mt-0.5">
                            #{{ $staff->id }}
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">氏名</span>
                        <span class="font-bold text-slate-900 text-sm block mt-0.5">
                            {{ $staff->name }}
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">フリガナ</span>
                        <span class="font-bold text-slate-800 block mt-0.5">
                            {{ $staff->kana ?: '未設定' }}
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">稼働状態</span>
                        <div class="mt-0.5">
                            @if($staff->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                ● 稼働可能
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                停止中
                            </span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">最終アクセス日時</span>
                        <span class="font-bold font-mono text-slate-800 block mt-0.5">
                            {{ $staff->last_accessed_at?->format('Y/m/d H:i') ?? '未アクセス' }}
                        </span>
                    </div>

                    <div class="col-span-2 sm:col-span-3 pt-3 border-t border-slate-100">
                        <span class="text-slate-400 block text-[11px]">名札・トークン再発行状況</span>
                        <div class="mt-1">
                            @if($staff->is_reissued)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                ⚠️ 再発行 {{ $staff->reissue_count }}回目（{{ $staff->token_reissued_at?->format('Y/m/d H:i') ?? '-' }}）
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                初回発行（未再発行）
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. 専用マイタイムテーブルURL & セキュリティ再発行カード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
                <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                            <span>📱</span>
                            <span>本人専用 マイタイムテーブル確認URL</span>
                        </h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            パスワード不要でスタッフ本人がスマホから直接確認できる個別URLです。
                        </p>
                    </div>

                    @can('update', $staff)
                    <form action="{{ route('staff.regenerate-token', $staff) }}" method="POST"
                          data-confirm="スタッフ「{{ $staff->name }}」の名札紛失・セキュリティ再発行を実行しますか？&#10;&#10;【注意】落とした名札の古いQRコードは即時に無効化（404）され、新しい名札が発行されます。">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 cursor-pointer whitespace-nowrap">
                            <span>⚠️</span>
                            <span>名札紛失・セキュリティ再発行</span>
                        </button>
                    </form>
                    @endcan
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <input type="text" id="staffShiftUrlInput" readonly value="{{ $shiftUrl }}"
                           class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-700">
                    <div class="flex items-center space-x-2 shrink-0">
                        <button type="button" id="copyShiftUrlBtn"
                                class="px-3 py-2 bg-white hover:bg-slate-50 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 cursor-pointer whitespace-nowrap">
                            <span>📋</span>
                            <span id="copyShiftUrlBtnText">URLコピー</span>
                        </button>
                        <a href="{{ $shiftUrl }}" target="_blank" rel="noopener noreferrer"
                           class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1 whitespace-nowrap">
                            <span>開く</span>
                            <span>↗</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <!-- 右カラム (1/3): QRコード & 名札PDF出力 -->
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
                <div class="pb-3 border-b border-slate-100">
                    <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                        <span>🔳</span>
                        <span>マイタイムテーブル QRコード</span>
                    </h2>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-center space-y-2">
                    <img src="{{ $qrDataUri }}" alt="{{ $staff->name }} マイタイムテーブルQRコード" class="w-48 h-48 mx-auto rounded-lg bg-white p-1.5 border border-slate-200">
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        スタッフ本人のスマートフォンで読み取ると、専用マイタイムテーブルページを直接開けます。
                    </p>
                </div>

                <div class="space-y-2">
                    <a href="{{ route('staff.print-card', $staff) }}"
                       class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center justify-center space-x-1.5">
                        <span>📄</span>
                        <span>名札PDFをダウンロード</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- 外部JS（CSP準拠） -->
<script src="{{ asset('js/staff-management.js') }}" defer></script>
</x-layouts.app>
