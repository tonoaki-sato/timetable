<x-layouts.app :title="'拠点の編集 - ' . $location->name">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="'拠点の編集: ' . $location->name"
        icon="✏️"
        :description="'「' . $timetable->name . '」における拠点「' . $location->name . '」の基本設定・種別・終日設定・メモを更新します。'">
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
            <a href="{{ route('timetables.locations.show', [$timetable, $location]) }}" class="hover:text-indigo-600 transition truncate">
                📍 {{ $location->name }}
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">✏️ 編集</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.locations.show', [$timetable, $location]) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>👁️</span>
                <span>詳細を見る</span>
            </a>
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

    <!-- 編集フォームカード -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                <span>📍</span>
                <span>拠点の基本情報編集</span>
            </h2>
            <span class="text-xs text-slate-500 font-mono">
                日付: {{ $timetable->target_date?->format('Y/m/d') }}
            </span>
        </div>

        <form action="{{ route('timetables.locations.update', [$timetable, $location]) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- 2段組みグリッド -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                <!-- 左カラム: 拠点名 & タイプ & 終日設定 & 有効フラグ -->
                <div class="space-y-4">
                    <!-- 拠点名 -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1">
                            拠点名 <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $location->name) }}" required
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- 拠点タイプ -->
                    <div>
                        <label for="location_type_id" class="block text-xs font-bold text-slate-700 mb-1">
                            拠点タイプ <span class="text-rose-500">*</span>
                        </label>
                        <select name="location_type_id" id="location_type_id" required
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach($types as $type)
                                <option value="{{ $type->id }}" {{ old('location_type_id', $location->location_type_id) == $type->id ? 'selected' : '' }}>
                                    {{ $type->label }} ({{ $type->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 終日利用可能フラグ -->
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex items-start space-x-3">
                        <input type="checkbox" name="is_all_day" id="is_all_day" value="1" {{ old('is_all_day', $location->is_all_day) ? 'checked' : '' }}
                            class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        <div>
                            <label for="is_all_day" class="text-xs font-bold text-slate-800 cursor-pointer">終日（24時間）常時利用可能な拠点</label>
                            <p class="text-[11px] text-slate-500 mt-0.5">時間制限なく常時利用可能な場合にチェックしてください。チェックを外すと下部で年月日時分単位の時間帯スロットを設定できます。</p>
                        </div>
                    </div>

                    <!-- 有効フラグ -->
                    <div class="flex items-center space-x-2 pt-2">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $location->is_active) ? 'checked' : '' }}
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        <label for="is_active" class="text-xs font-bold text-slate-700 cursor-pointer">有効（稼働中）</label>
                    </div>
                </div>

                <!-- 右カラム: 備考・案内メモ -->
                <div class="space-y-4">
                    <div>
                        <label for="notes" class="block text-xs font-bold text-slate-700 mb-1">
                            メモ欄（注意事項・連絡先・鍵管理）
                        </label>
                        <textarea name="notes" id="notes" rows="8"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('notes', $location->notes) }}</textarea>
                        <p class="text-[11px] text-slate-500 mt-1">連絡先や注意事項、鍵の保管場所など自由に記録できます。</p>
                    </div>
                </div>

            </div>

            <!-- 利用可能時間帯スロット設定セクション -->
            <div id="schedules-form-container" class="space-y-3 pt-6 border-t border-slate-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                            <span>⏱️</span>
                            <span>利用可能時間帯スロット設定</span>
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            終日常時利用でない場合、この運行日における利用枠（年月日時分）を設定します。
                        </p>
                    </div>
                    <button type="button" id="add-schedule-row-btn"
                        data-target-date="{{ $timetable->target_date?->format('Y-m-d') }}"
                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 self-start sm:self-auto cursor-pointer">
                        <span>➕</span>
                        <span>時間帯枠を追加</span>
                    </button>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl bg-slate-50/50">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                        <thead class="bg-slate-100/75 text-slate-700 font-bold">
                            <tr>
                                <th scope="col" class="py-2.5 px-3 whitespace-nowrap">日時範囲 (年月日時分) <span class="text-rose-500">*</span></th>
                                <th scope="col" class="py-2.5 px-3 whitespace-nowrap">利用ステータス <span class="text-rose-500">*</span></th>
                                <th scope="col" class="py-2.5 px-3 whitespace-nowrap">枠名 / タイトル</th>
                                <th scope="col" class="py-2.5 px-3 whitespace-nowrap">メモ / 特記事項</th>
                                <th scope="col" class="py-2.5 px-3 whitespace-nowrap text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody id="schedules-form-body" class="divide-y divide-slate-100 bg-white">
                            @php
                                $schedulesData = old('schedules');
                                if ($schedulesData === null) {
                                    $schedulesData = $location->schedules->map(function ($s) {
                                        return [
                                            'id' => $s->id,
                                            'start_at' => $s->start_at->format('Y-m-d\TH:i'),
                                            'end_at' => $s->end_at->format('Y-m-d\TH:i'),
                                            'availability_status' => $s->availability_status,
                                            'title' => $s->title,
                                            'note' => $s->note,
                                        ];
                                    })->toArray();
                                }
                            @endphp
                            @foreach($schedulesData as $index => $sch)
                                <tr data-schedule-row="true" class="border-b border-slate-100 hover:bg-slate-50/60 transition">
                                    <td class="py-2.5 px-3">
                                        @if(!empty($sch['id']))
                                            <input type="hidden" name="schedules[{{ $index }}][id]" value="{{ $sch['id'] }}">
                                        @endif
                                        <div class="flex items-center space-x-1 font-mono">
                                            <input type="datetime-local" name="schedules[{{ $index }}][start_at]" value="{{ $sch['start_at'] ?? '' }}" required
                                                class="px-2 py-1 bg-white border border-slate-300 rounded text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono">
                                            <span class="text-slate-400">〜</span>
                                            <input type="datetime-local" name="schedules[{{ $index }}][end_at]" value="{{ $sch['end_at'] ?? '' }}" required
                                                class="px-2 py-1 bg-white border border-slate-300 rounded text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono">
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <select name="schedules[{{ $index }}][availability_status]" required
                                            class="px-2 py-1 bg-white border border-slate-300 rounded text-xs font-bold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                            @foreach(\App\Models\LocationSchedule::AVAILABILITY_STATUSES as $k => $l)
                                                <option value="{{ $k }}" {{ ($sch['availability_status'] ?? 'available') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <input type="text" name="schedules[{{ $index }}][title]" value="{{ $sch['title'] ?? '' }}" placeholder="例: 午前設営枠"
                                            class="w-full px-2 py-1 bg-white border border-slate-300 rounded text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <input type="text" name="schedules[{{ $index }}][note]" value="{{ $sch['note'] ?? '' }}" placeholder="例: 道路使用許可申請中"
                                            class="w-full px-2 py-1 bg-white border border-slate-300 rounded text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="py-2.5 px-3 text-right">
                                        <button type="button" data-action="remove-schedule-row"
                                            class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-600 border border-slate-200 hover:border-rose-200 rounded text-xs font-bold transition cursor-pointer">
                                            ✕ 削除
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div id="schedules-empty-message" class="p-6 text-center text-slate-400 text-xs {{ !empty($schedulesData) ? 'hidden' : '' }}">
                        利用可能時間帯枠がありません。「➕ 時間帯枠を追加」から登録してください。
                    </div>
                </div>
            </div>

            <!-- アクションボタン -->
            <div class="flex items-center justify-end space-x-2 pt-4 border-t border-slate-100">
                <a href="{{ route('timetables.locations.index', $timetable) }}"
                    class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer">
                    キャンセル
                </a>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer">
                    更新する
                </button>
            </div>
        </form>
    </div>

</div>

@vite(['resources/js/locations.js'])
</x-layouts.app>
