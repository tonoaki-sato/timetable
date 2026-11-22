<!-- 共通: セルフ登録受付設定モーダル (全画面から起動可能) -->
@if(isset($selfRegistrationSetting))
<div id="registrationSettingModal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="registrationSettingModal-title">
    <div data-close-target="#registrationSettingModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity cursor-pointer"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-xl transition-all sm:w-full sm:max-w-md border border-slate-200 z-10 text-slate-800">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 id="registrationSettingModal-title" class="text-sm font-bold text-slate-800 flex items-center space-x-1.5">
                    <span>⚙️</span>
                    <span>スタッフセルフ登録 受付設定</span>
                </h3>
                <button type="button" data-close-target="#registrationSettingModal" aria-label="閉じる" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">✕</button>
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
                    <div class="flex items-center justify-between mb-1">
                        <label for="opened_at" class="block text-xs font-bold text-slate-700">
                            受付開始日時（日本時間・未指定時は即時開始）
                        </label>
                        <button type="button" data-clear-target="opened_at" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-medium cursor-pointer">
                            ✕ クリア
                        </button>
                    </div>
                    <input type="datetime-local" id="opened_at" name="opened_at"
                        value="{{ old('opened_at', $selfRegistrationSetting->opened_at?->timezone('Asia/Tokyo')->format('Y-m-d\TH:i')) }}"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- 受付終了日時 -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="closed_at" class="block text-xs font-bold text-slate-700">
                            受付終了日時（日本時間・未指定時は無期限）
                        </label>
                        <button type="button" data-clear-target="closed_at" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-medium cursor-pointer">
                            ✕ クリア
                        </button>
                    </div>
                    <input type="datetime-local" id="closed_at" name="closed_at"
                        value="{{ old('closed_at', $selfRegistrationSetting->closed_at?->timezone('Asia/Tokyo')->format('Y-m-d\TH:i')) }}"
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
                    <button type="button" data-close-target="#registrationSettingModal"
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
