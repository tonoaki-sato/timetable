<x-layouts.app title="現場スタッフ・ボランティア管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ヘッダー -->
    <x-page-header
        title="現場スタッフ・ボランティア管理"
        icon="👷"
        description="まつり運行を支える現場スタッフ・ボランティアの台帳管理および担当タスクの確認を行います。">
        <x-slot:actions>
            <!-- 名札一括印刷 -->
            <a href="{{ route('staff.print-cards') }}" target="_blank"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                <span>🖨️</span>
                <span>名札カード一括印刷</span>
            </a>

            <!-- CSVエクスポート -->
            <a href="{{ route('staff.export-csv') }}"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                <span>📥</span>
                <span>CSV出力</span>
            </a>

            <!-- CSVインポートボタン & 隠しフォーム -->
            @can('create', App\Models\Staff::class)
            <form action="{{ route('staff.import-csv') }}" method="POST" enctype="multipart/form-data" class="inline" id="csvImportForm">
                @csrf
                <input type="file" name="file" id="csvFileInput" accept=".csv,text/csv" class="hidden">
                <button type="button" id="csvImportBtn"
                    class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                    <span>📤</span>
                    <span>CSV取込</span>
                </button>
            </form>
            @endcan

            <!-- 登録案内文コピーボタン -->
            <div class="inline-flex items-center space-x-1.5">
                <button type="button" id="copyInviteBtn" data-join-url="{{ route('staff.join') }}"
                    class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    <span>📋</span>
                    <span>登録案内文コピー</span>
                </button>
                @if(isset($selfRegistrationStatus) && ! $selfRegistrationStatus['isOpen'])
                    <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-1 rounded-lg border border-rose-200">
                        ⚠️ 登録停止中
                    </span>
                @endif
            </div>

            @can('create', App\Models\Staff::class)
            <a href="{{ route('staff.create') }}"
                class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                <span>➕</span>
                <span>新規スタッフ登録</span>
            </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <!-- セルフ登録受付ステータス & コントロールカード -->
    @if(isset($selfRegistrationSetting))
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start sm:items-center space-x-3">
            <span class="text-2xl p-2 bg-slate-50 border border-slate-100 rounded-xl">🌐</span>
            <div>
                <div class="flex items-center space-x-2">
                    <h3 class="text-xs font-bold text-slate-800">Webセルフ登録機能</h3>
                    @if($selfRegistrationStatus['status'] === 'open')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                            {{ $selfRegistrationStatus['label'] }}
                        </span>
                    @elseif($selfRegistrationStatus['status'] === 'upcoming')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                            {{ $selfRegistrationStatus['label'] }}
                        </span>
                    @elseif($selfRegistrationStatus['status'] === 'closed')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                            {{ $selfRegistrationStatus['label'] }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">
                            {{ $selfRegistrationStatus['label'] }}
                        </span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    受付期間: 
                    @if($selfRegistrationSetting->opened_at || $selfRegistrationSetting->closed_at)
                        {{ $selfRegistrationSetting->opened_at?->format('Y/m/d H:i') ?? '制限なし' }} 〜 {{ $selfRegistrationSetting->closed_at?->format('Y/m/d H:i') ?? '制限なし' }}
                    @else
                        期間制限なし（常時受付）
                    @endif
                    @if($selfRegistrationSetting->notice_message)
                        <span class="text-slate-400 ml-2">※特記事項あり</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center space-x-2 shrink-0">
            @can('create', App\Models\Staff::class)
            <!-- 即時トグルボタン -->
            <form action="{{ route('feature-settings.toggle', 'staff_self_registration') }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                @if($selfRegistrationSetting->is_enabled)
                    <button type="submit" class="inline-flex items-center space-x-1 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer">
                        <span>⏹️</span>
                        <span>即時停止</span>
                    </button>
                @else
                    <button type="submit" class="inline-flex items-center space-x-1 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold transition cursor-pointer">
                        <span>▶️</span>
                        <span>即時再開</span>
                    </button>
                @endif
            </form>

            <!-- 受付日時設定ボタン -->
            <button type="button" id="openRegistrationSettingModalBtn"
                    class="inline-flex items-center space-x-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition cursor-pointer">
                <span>⚙️</span>
                <span>日時・メッセージ設定</span>
            </button>
            @endcan
        </div>
    </div>
    @endif

    <!-- 検索・絞り込みフォーム -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('staff.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- フリーワード -->
            <div>
                <label for="search" class="block text-[11px] font-bold text-slate-600 mb-1">フリーワード</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="氏名・フリガナ・所属"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- ステータス -->
            <div>
                <label for="status" class="block text-[11px] font-bold text-slate-600 mb-1">稼働状態</label>
                <select id="status" name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべての状態</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>稼働可能</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>停止中</option>
                </select>
            </div>

            <!-- ボタン -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition">
                    🔍 絞り込み
                </button>
                <a href="{{ route('staff.index') }}"
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium transition text-center">
                    リセット
                </a>
            </div>
        </form>
    </div>

    <!-- スタッフ一覧テーブル -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-4 py-3 font-bold">ID</th>
                        <th class="px-4 py-3 font-bold">氏名</th>
                        <th class="px-4 py-3 font-bold">フリガナ</th>
                        <th class="px-4 py-3 font-bold">スタッフ区分</th>
                        <th class="px-4 py-3 font-bold">所属団体・地域会等</th>
                        <th class="px-4 py-3 font-bold">稼働状態</th>
                        <th class="px-4 py-3 font-bold text-right">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($staffMembers as $staff)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="px-4 py-3 font-mono text-slate-400">#{{ $staff->id }}</td>
                        <td class="px-4 py-3 font-bold text-slate-900">
                            <a href="{{ route('staff.show', $staff) }}" class="hover:text-indigo-600 transition">
                                {{ $staff->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-500 font-mono text-[11px]">{{ $staff->kana ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($staff->isFixed())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                📌 固定スタッフ
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-sky-50 text-sky-700 border border-sky-200">
                                🔄 充足スタッフ
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $staff->affiliation ?? '未設定' }}</td>
                        <td class="px-4 py-3">
                            @if($staff->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                ● 稼働可能
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                停止中
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                            <!-- QR表示ボタン -->
                            <button type="button"
                                    class="open-qr-btn px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded text-[11px] font-bold transition cursor-pointer"
                                    data-staff-name="{{ $staff->name }}"
                                    data-staff-url="{{ $staff->my_shift_url }}">
                                📱 QR
                            </button>

                            <a href="{{ route('staff.show', $staff) }}"
                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-medium transition">
                                詳細
                            </a>

                            @can('update', $staff)
                            <a href="{{ route('staff.edit', $staff) }}"
                                class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[11px] font-bold transition">
                                編集
                            </a>
                            @endcan

                            @can('delete', $staff)
                            <form action="{{ route('staff.destroy', $staff) }}" method="POST" class="inline"
                                data-confirm="スタッフ「{{ $staff->name }}」を削除しますか？&#10;（関連するタスクのアサインも解除されます）">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded text-[11px] font-medium transition cursor-pointer">
                                    削除
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                            <span class="text-3xl block mb-2">👷</span>
                            登録されているスタッフは見つかりませんでした。
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($staffMembers->hasPages())
        <div class="p-4 border-t border-slate-200 bg-slate-50/50">
            {{ $staffMembers->links() }}
        </div>
        @endif
    </div>

    <!-- 個別QRコード表示モーダル -->
    <div id="qrModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div id="qrModalBackdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white p-6 text-center shadow-2xl transition-all sm:w-full sm:max-w-sm border border-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center space-x-1.5">
                        <span>📱</span>
                        <span>マイタイムテーブル用 QRコード</span>
                    </h3>
                    <button type="button" id="qrModalCloseBtn" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">✕</button>
                </div>

                <div class="mb-4">
                    <p class="text-xs text-slate-500 mb-1">対象スタッフ:</p>
                    <p id="qrModalStaffName" class="text-base font-bold text-slate-900"></p>
                </div>

                <!-- QRコード画像 -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 inline-block mb-4">
                    <img id="qrModalImage" src="" alt="QR Code" class="w-48 h-48 mx-auto">
                </div>

                <p class="text-[11px] text-slate-500 mb-3">
                    スタッフ本人のスマホカメラで読み取ると、即座にマイタイムテーブルが開きます。
                </p>

                <!-- URLコピー -->
                <div class="space-y-2">
                    <input type="text" id="qrModalStaffUrl" readonly class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-600 text-center">
                    <button type="button" id="qrModalCopyBtn"
                            class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer">
                        <span>📋</span>
                        <span>URLをコピー</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- セルフ登録受付設定モーダル -->
    @if(isset($selfRegistrationSetting))
    <div id="registrationSettingModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div id="registrationSettingModalBackdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-2xl transition-all sm:w-full sm:max-w-md border border-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center space-x-1.5">
                        <span>⚙️</span>
                        <span>スタッフセルフ登録 受付設定</span>
                    </h3>
                    <button type="button" id="registrationSettingModalCloseBtn" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">✕</button>
                </div>

                <form action="{{ route('feature-settings.update', 'staff_self_registration') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- 有効/無効 スイッチ -->
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <span class="block text-xs font-bold text-slate-800">機能の有効化</span>
                            <span class="block text-[11px] text-slate-500">オフにすると期間内でも即時停止されます</span>
                        </div>
                        <input type="hidden" name="is_enabled" value="0">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_enabled" value="1" class="sr-only peer"
                                   {{ old('is_enabled', $selfRegistrationSetting->is_enabled) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <!-- 受付開始日時 -->
                    <div>
                        <label for="opened_at" class="block text-xs font-bold text-slate-700 mb-1">
                            受付開始日時（未指定時は即時開始）
                        </label>
                        <input type="datetime-local" id="opened_at" name="opened_at"
                               value="{{ old('opened_at', $selfRegistrationSetting->opened_at?->format('Y-m-d\TH:i')) }}"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- 受付終了日時 -->
                    <div>
                        <label for="closed_at" class="block text-xs font-bold text-slate-700 mb-1">
                            受付終了日時（未指定時は無期限）
                        </label>
                        <input type="datetime-local" id="closed_at" name="closed_at"
                               value="{{ old('closed_at', $selfRegistrationSetting->closed_at?->format('Y-m-d\TH:i')) }}"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- 停止時・期間外メッセージ -->
                    <div>
                        <label for="notice_message" class="block text-xs font-bold text-slate-700 mb-1">
                            特記事項・案内メッセージ（任意）
                        </label>
                        <textarea id="notice_message" name="notice_message" rows="3"
                                  placeholder="受付停止中や期間外に表示したい案内文があれば入力してください（未入力の場合は自動生成メッセージを表示）。"
                                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('notice_message', $selfRegistrationSetting->notice_message ?? '') }}</textarea>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
                        <button type="button" id="registrationSettingModalCancelBtn"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer">
                            キャンセル
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                            設定を保存
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>

<!-- 外部JS（CSP準拠） -->
<script src="{{ asset('js/staff-management.js') }}" defer></script>
</x-layouts.app>

