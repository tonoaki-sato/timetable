<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資材搬出入・中継・夜間保管 計画書 - まつり運行本部</title>
    @vite(['resources/css/app.css'])
    <script src="{{ asset('js/print-helper.js') }}" defer></script>
</head>
<body class="bg-slate-100 text-slate-800 antialiased p-4 sm:p-6 print:p-0">

    <!-- 印刷コントロールバー（画面上のみ表示） -->
    <div class="no-print max-w-7xl mx-auto mb-6 bg-slate-900 text-white rounded-2xl p-4 shadow-lg space-y-3">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center space-x-2">
                <span class="text-xl">🖨️</span>
                <div>
                    <div class="font-bold text-sm">
                        資材搬出入計画書 印刷プレビュー
                        @if(isset($timetable))
                            <span class="text-xs font-normal text-indigo-300 ml-2">【{{ $timetable->name }} ({{ $timetable->target_date?->format('Y/m/d') }})】</span>
                        @endif
                    </div>
                    <div class="text-[11px] text-slate-400">A4横向き印刷に最適化されています。必要に応じて担当班や区分で絞り込んで現場配布用シートを出力してください。</div>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <a href="{{ $backUrl }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition">
                    ← 画面に戻る
                </a>
                <a href="{{ $downloadUrl }}" class="px-4 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-md transition flex items-center space-x-1">
                    <span>📄</span>
                    <span>PDFダウンロード (A4横)</span>
                </a>
                <button type="button" data-action="print" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-700 hover:bg-slate-600 text-slate-200 transition flex items-center space-x-1 cursor-pointer">
                    <span>🖨️</span>
                    <span>ブラウザ印刷</span>
                </button>
            </div>
        </div>

        <!-- 印刷絞り込みフィルタ（画面のみ） -->
        <form method="GET" action="{{ $formAction }}" class="flex items-center space-x-3 pt-2 border-t border-slate-800 text-xs flex-wrap gap-y-2">
            <input type="hidden" name="preview" value="1">
            <span class="text-slate-400 font-bold">小分け印刷フィルタ:</span>

            <select name="lane_id" data-auto-submit class="bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1 text-slate-200">
                <option value="">全班対象</option>
                @foreach($lanes as $lane)
                    <option value="{{ $lane->id }}" {{ $laneFilter == $lane->id ? 'selected' : '' }}>
                        {{ $lane->icon }} {{ $lane->name }}
                    </option>
                @endforeach
            </select>

            <select name="type" data-auto-submit class="bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1 text-slate-200">
                <option value="">全区分</option>
                <option value="committee" {{ $typeFilter === 'committee' ? 'selected' : '' }}>自前機材のみ</option>
                <option value="rental" {{ $typeFilter === 'rental' ? 'selected' : '' }}>レンタル機材のみ</option>
                <option value="personal" {{ $typeFilter === 'personal' ? 'selected' : '' }}>私物のみ</option>
            </select>

            @if($laneFilter || $typeFilter)
                <a href="{{ $resetUrl }}" class="text-indigo-400 hover:underline">
                    フィルタ解除
                </a>
            @endif
        </form>
    </div>

    <!-- 印刷用メインシート (A4横) -->
    <div class="max-w-7xl mx-auto bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm print:border-none print:shadow-none print:p-0 space-y-6">

        <!-- 帳票タイトルヘッダー -->
        <div class="border-b-2 border-slate-800 pb-3 flex items-end justify-between">
            <div>
                <div class="text-[11px] font-bold tracking-widest text-slate-500 uppercase flex items-center space-x-2">
                    <span>宿場まつり 運行本部 公式帳票</span>
                    @if($laneFilter && ($curLane = $lanes->firstWhere('id', $laneFilter)))
                        <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold">【{{ $curLane->name }} 担当分】</span>
                    @endif
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">資材・機材 搬出入・中継・夜間保管計画書</h1>
            </div>

            <div class="text-right text-xs text-slate-600 font-mono space-y-0.5">
                <div>運行日程: {{ $festivalDates['day1'] }} 〜 {{ $festivalDates['day2'] }}</div>
                <div class="text-[10px] text-slate-400">作成日時: {{ now()->translatedFormat('Y年n月j日 H:i') }} / まつり運行管理システム</div>
            </div>
        </div>

        <!-- 拠点・貸切期間一覧（サマリー） -->
        <div class="space-y-2">
            <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center space-x-1">
                <span>📍</span>
                <span>主要中継拠点・保管場所 & 利用可能時間一覧</span>
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs">
                @forelse($locationsData as $locItem)
                    <div class="p-2 rounded-lg border border-slate-200 bg-slate-50">
                        <div class="font-bold text-slate-900 truncate">{{ $locItem['location']->name }}</div>
                        <div class="text-[10px] font-mono mt-0.5 {{ $locItem['location']->is_all_day ? 'text-emerald-700 font-bold' : 'text-amber-800' }}">
                            @if($locItem['location']->is_all_day)
                                終日常時使用可
                            @elseif($locItem['location']->schedules->isNotEmpty())
                                @foreach($locItem['location']->schedules as $s)
                                    <div>{{ $s->start_at->format('n/j H:i') }}〜{{ $s->end_at->format($s->start_at->isSameDay($s->end_at) ? 'H:i' : 'n/j H:i') }}</div>
                                @endforeach
                            @else
                                利用枠未設定
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full p-3 rounded-lg border border-slate-200 bg-slate-50 text-center text-slate-500">
                        登録されている拠点はありません。
                    </div>
                @endforelse
            </div>
        </div>

        <!-- 全資材の搬出入・移動計画一覧表 -->
        <div class="space-y-2">
            <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center space-x-1">
                <span>📦</span>
                <span>資材別 搬出入・中継・夜間保管スケジュール</span>
            </h2>

            <table class="min-w-full text-left text-[11px] border border-slate-300 divide-y divide-slate-300 table-print">
                <thead class="bg-slate-100 text-slate-800 font-bold">
                    <tr>
                        <th class="p-2 border-r border-slate-300 w-44">資材名・数量</th>
                        <th class="p-2 border-r border-slate-300 w-28">区分 / 担当班</th>
                        <th class="p-2 border-r border-slate-300">事前・前日 (準備・中継移動)</th>
                        <th class="p-2 border-r border-slate-300">1日目 朝 (設置)</th>
                        <th class="p-2 border-r border-slate-300">1日目 夜 (夜間分散保管)</th>
                        <th class="p-2 border-r border-slate-300">2日目 朝 (設置)</th>
                        <th class="p-2 border-r border-slate-300">2日目 夜 (撤収・格納・集約)</th>
                        <th class="p-2">翌日以降 (回収・片付け)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($equipments as $eq)
                        <tr class="align-top">
                            <!-- 資材名 -->
                            <td class="p-2 border-r border-slate-300 font-bold text-slate-900">
                                <div>{{ $eq->name }}</div>
                                <div class="font-mono text-indigo-700 font-black mt-0.5">
                                    {{ $eq->formatted_quantity }}
                                </div>
                            </td>

                            <!-- 区分 -->
                            <td class="p-2 border-r border-slate-300 text-slate-600">
                                <div class="font-bold">{{ $eq->type_label }}</div>
                            </td>

                            <!-- 事前・前日 -->
                            <td class="p-2 border-r border-slate-300 space-y-1">
                                @forelse($eq->plansForPhases(['other', 'pre_day']) as $p)
                                    <div class="bg-slate-50 p-1.5 rounded border border-slate-200 leading-tight">
                                        <div class="font-bold text-slate-800">{{ $p->title }}</div>
                                        <div class="text-[10px] text-slate-600 font-mono">
                                            {{ $p->start_time ? substr($p->start_time, 0, 5) : '' }}
                                            ({{ $p->resolved_from_location }}➔{{ $p->resolved_to_location }})
                                        </div>
                                        @if($p->formatted_quantity !== '')
                                            <div class="text-[9px] text-indigo-700 font-bold">{{ $p->formatted_quantity }}</div>
                                        @endif
                                        @if($p->witness_required)
                                            <div class="text-[9px] text-rose-700 font-bold">👁️立会: {{ $p->witnessStaff?->name ?: '要' }}</div>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-slate-300">-</span>
                                @endforelse
                            </td>

                            <!-- 1日目 朝 -->
                            <td class="p-2 border-r border-slate-300 space-y-1">
                                @forelse($eq->plansForPhases('day1_morning') as $p)
                                    <div class="bg-slate-50 p-1.5 rounded border border-slate-200 leading-tight">
                                        <div class="font-bold text-slate-800">{{ $p->title }}</div>
                                        <div class="text-[10px] text-slate-600 font-mono">
                                            {{ $p->start_time ? substr($p->start_time, 0, 5) : '' }}
                                            ({{ $p->resolved_from_location }}➔{{ $p->resolved_to_location }})
                                        </div>
                                        @if($p->formatted_quantity !== '')
                                            <div class="text-[9px] text-indigo-700 font-bold">{{ $p->formatted_quantity }}</div>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-slate-300">-</span>
                                @endforelse
                            </td>

                            <!-- 1日目 夜 (夜間分散保管) -->
                            <td class="p-2 border-r border-slate-300 space-y-1 bg-indigo-50/20">
                                @forelse($eq->plansForPhases('day1_evening') as $p)
                                    <div class="bg-indigo-50/80 p-1.5 rounded border border-indigo-200 leading-tight">
                                        <div class="font-bold text-indigo-950">{{ $p->to_location }}</div>
                                        <div class="text-[10px] text-indigo-800 font-mono font-bold">
                                            {{ $p->formatted_quantity !== '' ? $p->formatted_quantity : '全量保管' }}
                                            ({{ $p->start_time ? substr($p->start_time, 0, 5) : '' }})
                                        </div>
                                        @if($p->notes)
                                            <div class="text-[9px] text-slate-500 truncate">{{ $p->notes }}</div>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-slate-300">-</span>
                                @endforelse
                            </td>

                            <!-- 2日目 朝 -->
                            <td class="p-2 border-r border-slate-300 space-y-1">
                                @forelse($eq->plansForPhases('day2_morning') as $p)
                                    <div class="bg-slate-50 p-1.5 rounded border border-slate-200 leading-tight">
                                        <div class="font-bold text-slate-800">{{ $p->title }}</div>
                                        <div class="text-[10px] text-slate-600 font-mono">
                                            {{ $p->start_time ? substr($p->start_time, 0, 5) : '' }}
                                        </div>
                                        @if($p->formatted_quantity !== '')
                                            <div class="text-[9px] text-indigo-700 font-bold">{{ $p->formatted_quantity }}</div>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-slate-300">-</span>
                                @endforelse
                            </td>

                            <!-- 2日目 夜 -->
                            <td class="p-2 border-r border-slate-300 space-y-1 bg-amber-50/20">
                                @forelse($eq->plansForPhases('day2_evening') as $p)
                                    <div class="bg-amber-50 p-1.5 rounded border border-amber-200 leading-tight">
                                        <div class="font-bold text-amber-950">{{ $p->title }}</div>
                                        <div class="text-[10px] text-amber-800 font-mono">
                                            ➔ {{ $p->to_location }} ({{ $p->start_time ? substr($p->start_time, 0, 5) : '' }})
                                        </div>
                                    </div>
                                @empty
                                    <span class="text-slate-300">-</span>
                                @endforelse
                            </td>

                            <!-- 翌日 -->
                            <td class="p-2 space-y-1">
                                @forelse($eq->plansForPhases('post_day') as $p)
                                    <div class="bg-slate-50 p-1.5 rounded border border-slate-200 leading-tight">
                                        <div class="font-bold text-slate-800">{{ $p->title }}</div>
                                        <div class="text-[10px] text-slate-600 font-mono">
                                            {{ $p->start_time ? substr($p->start_time, 0, 5) : '' }}
                                        </div>
                                        @if($p->witness_required)
                                            <div class="text-[9px] text-rose-700 font-bold">👁️立会: {{ $p->witnessStaff?->name ?: '要' }}</div>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-slate-300">-</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-slate-500">
                                該当する資材または搬出入計画データはありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 署名欄・現場注意事項 -->
        <div class="pt-4 border-t border-slate-200 text-xs text-slate-500 flex items-center justify-between">
            <div>
                ※駐車場は貸切時間帯以外は一般車両優先となります。貸切開始時刻を確認のうえ搬入してください。
            </div>
            <div class="flex items-center space-x-6 text-slate-700">
                <div>総括幹事 確認印: [ &emsp;&emsp;&emsp;&emsp;&emsp;&emsp; ]</div>
                <div>資材担当責任者: [ &emsp;&emsp;&emsp;&emsp;&emsp;&emsp; ]</div>
            </div>
        </div>

    </div>

</body>
</html>
