<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>資材搬出入・中継・夜間保管計画書</title>
    <style>
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: 500;
            font-style: normal;
        }
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: 600;
            font-style: normal;
        }
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: 700;
            font-style: normal;
        }
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: 800;
            font-style: normal;
        }
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: 900;
            font-style: normal;
        }
        @page {
            size: A4 landscape;
            margin: 8mm 8mm;
        }
        *, body, table, th, td, h1, h2, h3, div, span, strong, b, p {
            font-family: 'JapaneseFont', sans-serif !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'JapaneseFont', sans-serif;
            color: #1e293b;
            font-size: 8.5px;
            line-height: 1.25;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .header-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
        }
        .filter-tag {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: bold;
            margin-left: 5px;
        }
        .header-meta {
            text-align: right;
            font-size: 8px;
            color: #475569;
            vertical-align: bottom;
        }
        .locations-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .locations-table td {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 3px 5px;
            vertical-align: top;
            font-size: 8px;
            width: 20%;
        }
        .loc-name {
            font-weight: bold;
            color: #0f172a;
        }
        .loc-time {
            color: #047857;
            font-size: 7.5px;
            margin-top: 1px;
        }
        .table-title {
            font-weight: bold;
            font-size: 9px;
            color: #334155;
            margin-bottom: 3px;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .main-table th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 3px 4px;
            text-align: left;
            font-weight: bold;
            color: #334155;
            font-size: 8px;
        }
        .main-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 4px;
            vertical-align: top;
            font-size: 8px;
        }
        .col-eq {
            width: 16%;
        }
        .col-type {
            width: 10%;
        }
        .col-phase {
            width: 12.3%;
        }
        .eq-name {
            font-weight: bold;
            color: #0f172a;
        }
        .eq-qty {
            color: #4338ca;
            font-weight: bold;
            font-size: 8px;
            margin-top: 1px;
        }
        .plan-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 2px;
            padding: 2px 3px;
            margin-bottom: 2px;
        }
        .plan-title {
            font-weight: bold;
            color: #1e293b;
        }
        .plan-meta {
            color: #64748b;
            font-size: 7.5px;
        }
        .witness-tag {
            color: #b91c1c;
            font-size: 7.5px;
            font-weight: bold;
        }
        .footer-table {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
            font-size: 8px;
            color: #64748b;
        }
        .sign-area {
            text-align: right;
            color: #334155;
        }
    </style>
</head>
<body>
    <!-- ヘッダー -->
    <table class="header-table">
        <tr>
            <td>
                <span class="header-title">資材搬出入・中継・夜間保管計画書</span>
                @if($laneFilter && ($curLane = $lanes->firstWhere('id', $laneFilter)))
                    <span class="filter-tag">【{{ $curLane->name }} 担当分】</span>
                @endif
                @if($typeFilter)
                    <span class="filter-tag">【区分: {{ $typeFilter }}】</span>
                @endif
            </td>
            <td class="header-meta">
                運行日程: {{ $festivalDates['day1'] }} 〜 {{ $festivalDates['day2'] }}<br>
                出力日時: {{ now()->format('Y/m/d H:i') }} / まつり運行管理システム
            </td>
        </tr>
    </table>

    <!-- 主要中継拠点・保管場所一覧 -->
    @if(!empty($locationsData))
    <div class="table-title">■ 主要中継拠点・保管場所 ＆ 利用可能時間</div>
    <table class="locations-table">
        <tr>
            @foreach(array_slice($locationsData, 0, 5) as $locItem)
            <td>
                <div class="loc-name">{{ $locItem['location']->name }}</div>
                <div class="loc-time">
                    @if($locItem['location']->is_all_day)
                        終日常時使用可
                    @elseif($locItem['location']->schedules->isNotEmpty())
                        @foreach($locItem['location']->schedules as $s)
                            {{ $s->start_at->format('n/j H:i') }}〜{{ $s->end_at->format($s->start_at->isSameDay($s->end_at) ? 'H:i' : 'n/j H:i') }}<br>
                        @endforeach
                    @else
                        利用枠未設定
                    @endif
                </div>
            </td>
            @endforeach
        </tr>
    </table>
    @endif

    <!-- 資材別 搬出入スケジュール -->
    <table class="main-table">
        <thead>
            <tr>
                <th class="col-eq">資材名・数量</th>
                <th class="col-type">区分 / 担当班</th>
                <th class="col-phase">事前・前日 (準備)</th>
                <th class="col-phase">1日目 朝 (設置)</th>
                <th class="col-phase">1日目 夜 (保管)</th>
                <th class="col-phase">2日目 朝 (設置)</th>
                <th class="col-phase">2日目 夜 (撤収)</th>
                <th class="col-phase">翌日 (回収)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($equipments as $eq)
            <tr>
                <td class="col-eq">
                    <div class="eq-name">{{ $eq->name }}</div>
                    <div class="eq-qty">{{ $eq->formatted_quantity }}</div>
                </td>
                <td class="col-type">
                    <div>{{ $eq->type_label }}</div>
                </td>
                <td class="col-phase">
                    @forelse($eq->plansForPhases(['other', 'pre_day']) as $p)
                    <div class="plan-box">
                        <div class="plan-title">{{ $p->title }}</div>
                        <div class="plan-meta">{{ $p->start_time ? substr($p->start_time, 0, 5) : '' }} ({{ $p->resolved_from_location }}→{{ $p->resolved_to_location }})</div>
                        @if($p->witness_required)
                        <div class="witness-tag">【立会要】: {{ $p->witnessStaff?->name ?: '要' }}</div>
                        @endif
                    </div>
                    @empty
                    <span style="color: #cbd5e1;">ー</span>
                    @endforelse
                </td>
                <td class="col-phase">
                    @forelse($eq->plansForPhases('day1_morning') as $p)
                    <div class="plan-box">
                        <div class="plan-title">{{ $p->title }}</div>
                        <div class="plan-meta">{{ $p->start_time ? substr($p->start_time, 0, 5) : '' }} ({{ $p->resolved_from_location }}→{{ $p->resolved_to_location }})</div>
                    </div>
                    @empty
                    <span style="color: #cbd5e1;">ー</span>
                    @endforelse
                </td>
                <td class="col-phase">
                    @forelse($eq->plansForPhases('day1_evening') as $p)
                    <div class="plan-box" style="background-color: #eef2ff;">
                        <div class="plan-title" style="color: #312e81;">{{ $p->to_location }}</div>
                        <div class="plan-meta">{{ $p->formatted_quantity !== '' ? $p->formatted_quantity : '全量保管' }} ({{ $p->start_time ? substr($p->start_time, 0, 5) : '' }})</div>
                    </div>
                    @empty
                    <span style="color: #cbd5e1;">ー</span>
                    @endforelse
                </td>
                <td class="col-phase">
                    @forelse($eq->plansForPhases('day2_morning') as $p)
                    <div class="plan-box">
                        <div class="plan-title">{{ $p->title }}</div>
                        <div class="plan-meta">{{ $p->start_time ? substr($p->start_time, 0, 5) : '' }}</div>
                    </div>
                    @empty
                    <span style="color: #cbd5e1;">ー</span>
                    @endforelse
                </td>
                <td class="col-phase">
                    @forelse($eq->plansForPhases('day2_evening') as $p)
                    <div class="plan-box" style="background-color: #fffbeb;">
                        <div class="plan-title" style="color: #78350f;">{{ $p->title }}</div>
                        <div class="plan-meta">→ {{ $p->to_location }} ({{ $p->start_time ? substr($p->start_time, 0, 5) : '' }})</div>
                    </div>
                    @empty
                    <span style="color: #cbd5e1;">ー</span>
                    @endforelse
                </td>
                <td class="col-phase">
                    @forelse($eq->plansForPhases('post_day') as $p)
                    <div class="plan-box">
                        <div class="plan-title">{{ $p->title }}</div>
                        <div class="plan-meta">{{ $p->start_time ? substr($p->start_time, 0, 5) : '' }}</div>
                    </div>
                    @empty
                    <span style="color: #cbd5e1;">ー</span>
                    @endforelse
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">
                    該当する資材または搬出入計画データはありません。
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 署名欄 -->
    <table class="footer-table">
        <tr>
            <td>
                ※駐車場は貸切時間帯以外は一般車両優先となります。貸切開始時刻を確認のうえ搬入してください。
            </td>
            <td class="sign-area">
                総括幹事 確認印: [ &emsp;&emsp;&emsp;&emsp; ] &emsp; 資材担当責任者: [ &emsp;&emsp;&emsp;&emsp; ]
            </td>
        </tr>
    </table>
</body>
</html>

