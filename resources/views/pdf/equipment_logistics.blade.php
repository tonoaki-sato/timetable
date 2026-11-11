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
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        body, table, th, td, h1, h2, h3, div, span, strong, b, p {
            font-family: 'JapaneseFont', sans-serif !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'JapaneseFont', sans-serif;
            color: #0f172a;
            font-size: 9pt;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            border-bottom: 1.5pt solid #0f172a;
            padding-bottom: 3pt;
            margin-bottom: 8pt;
        }
        .header-title {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
        }
        .filter-tag {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 1pt 5pt;
            border-radius: 2.5pt;
            font-size: 7.5pt;
            font-weight: bold;
            margin-left: 4pt;
        }
        .header-meta {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 2pt;
        }
        .header-stats {
            text-align: right;
            font-size: 7.5pt;
            color: #334155;
            padding-top: 2pt;
        }
        .stat-badge {
            display: inline-block;
            background-color: #f1f5f9;
            border: 0.75pt solid #cbd5e1;
            padding: 2pt 6pt;
            border-radius: 2.5pt;
            font-weight: bold;
            white-space: nowrap;
        }
        .equipment-section {
            margin-bottom: 9pt;
        }
        .equipment-header-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #f8fafc;
            border: 0.75pt solid #cbd5e1;
            border-bottom: none;
            page-break-after: avoid;
        }
        .equipment-header-table td {
            padding: 4pt 6pt;
            vertical-align: middle;
        }
        .eq-name {
            font-weight: bold;
            font-size: 9.5pt;
            color: #0f172a;
        }
        .eq-badge {
            display: inline-block;
            background-color: #eef2ff;
            color: #3730a3;
            border: 0.75pt solid #c7d2fe;
            border-radius: 2pt;
            padding: 0.5pt 4pt;
            font-size: 7.5pt;
            font-weight: bold;
            margin-left: 4pt;
        }
        .eq-type-badge {
            display: inline-block;
            background-color: #ffffff;
            color: #334155;
            border: 0.75pt solid #cbd5e1;
            border-radius: 2pt;
            padding: 0.5pt 4pt;
            font-size: 7.5pt;
            font-weight: bold;
            margin-left: 3pt;
        }
        .eq-storage {
            font-size: 7.5pt;
            color: #64748b;
            margin-left: 5pt;
        }
        .eq-count {
            text-align: right;
            font-size: 7.5pt;
            color: #475569;
            white-space: nowrap;
            width: 20%;
        }
        .eq-empty-count {
            color: #b45309;
            font-weight: bold;
        }
        .steps-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8pt;
        }
        .steps-table thead {
            display: table-header-group;
        }
        .steps-table tr {
            page-break-inside: avoid;
        }
        .steps-table th {
            background-color: #f1f5f9;
            border: 0.75pt solid #cbd5e1;
            padding: 3.5pt 5pt;
            text-align: left;
            font-weight: bold;
            color: #1e293b;
            font-size: 7.5pt;
        }
        .steps-table td {
            border: 0.75pt solid #cbd5e1;
            padding: 3.5pt 5pt;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .col-order {
            width: 6%;
            text-align: center !important;
            color: #475569;
            font-weight: bold;
        }
        .col-phase {
            width: 17%;
        }
        .col-title {
            width: 28%;
        }
        .col-date {
            width: 11%;
            white-space: nowrap;
            color: #334155;
        }
        .col-time {
            width: 12%;
            white-space: nowrap;
            color: #334155;
        }
        .col-route {
            width: 26%;
            color: #1e293b;
        }
        .phase-badge {
            display: inline-block;
            background-color: #f1f5f9;
            color: #334155;
            border: 0.75pt solid #cbd5e1;
            border-radius: 2pt;
            padding: 0.5pt 4pt;
            font-size: 7pt;
            font-weight: bold;
        }
        .phase-pre_day {
            background-color: #fffbeb;
            color: #92400e;
            border-color: #fde68a;
        }
        .phase-day1_morning,
        .phase-day2_morning {
            background-color: #eef2ff;
            color: #3730a3;
            border-color: #c7d2fe;
        }
        .phase-day1_evening,
        .phase-day2_evening {
            background-color: #f5f3ff;
            color: #5b21b6;
            border-color: #ddd6fe;
        }
        .phase-post_day {
            background-color: #f0fdfa;
            color: #115e59;
            border-color: #99f6e4;
        }
        .witness-tag {
            color: #b91c1c;
            font-size: 7pt;
            font-weight: bold;
            margin-top: 1.5pt;
        }
        .step-title {
            font-weight: bold;
            color: #0f172a;
        }
        .step-sub {
            font-size: 7pt;
            color: #64748b;
            margin-top: 1pt;
            line-height: 1.25;
        }
        .route-from {
            color: #475569;
        }
        .route-arrow {
            color: #4338ca;
            font-weight: bold;
        }
        .route-to {
            font-weight: bold;
            color: #0f172a;
        }
        .empty-step-row {
            text-align: center;
            color: #64748b;
            padding: 8pt !important;
            font-size: 7.5pt;
        }
        .empty-row {
            text-align: center;
            padding: 20pt !important;
            color: #64748b;
        }
        .footer-table {
            width: 100%;
            border-top: 0.75pt solid #cbd5e1;
            padding-top: 4pt;
            margin-top: 6pt;
            font-size: 7.5pt;
            color: #64748b;
        }
        .footer-note {
            width: 56%;
            vertical-align: top;
        }
        .sign-area {
            width: 44%;
            text-align: right;
            color: #334155;
            vertical-align: top;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    @php
        $totalEquipments = $equipments->count();
        $totalSteps = $equipments->sum(fn ($eq) => $eq->plans->count());
    @endphp

    <!-- ヘッダー -->
    <table class="header-table">
        <tr>
            <td>
                <div class="header-title">
                    資材搬出入・中継・夜間保管計画書
                    @if($typeFilter)
                        <span class="filter-tag">【区分: {{ $typeFilter }}】</span>
                    @endif
                    @if(!empty($equipmentIds))
                        <span class="filter-tag">【表示資材指定: {{ $totalEquipments }}件】</span>
                    @endif
                </div>
                <div class="header-meta">
                    @if($timetable)
                        運行枠: <strong>{{ $timetable->name }}</strong> ｜
                        運行日: <strong>{{ $timetable->target_date?->format('Y年m月d日') ?? '未設定' }}</strong> ｜
                    @else
                        運行日程: <strong>{{ $festivalDates['day1'] }} 〜 {{ $festivalDates['day2'] }}</strong> ｜
                    @endif
                    出力日時: {{ now()->format('Y/m/d H:i') }}
                </div>
            </td>
        </tr>
        <tr>
            <td class="header-stats">
                <span class="stat-badge">
                    対象資材: <strong>{{ $totalEquipments }}件</strong> ／ 総ステップ: <strong>{{ $totalSteps }}件</strong>
                </span>
            </td>
        </tr>
    </table>

    <!-- 資材別 搬出入計画一覧（タスク一覧形式） -->
    @forelse($equipments as $eq)
    <div class="equipment-section">
        <table class="equipment-header-table">
            <tr>
                <td>
                    <span class="eq-name">{{ $eq->name }}</span>
                    <span class="eq-badge">{{ $eq->formatted_quantity }}</span>
                    <span class="eq-type-badge">{{ $eq->type_enum->shortLabel() }}</span>
                    @if($eq->storage_location)
                        <span class="eq-storage">保管: {{ $eq->storage_location }}</span>
                    @endif
                </td>
                <td class="eq-count">
                    @if($eq->plans->isNotEmpty())
                        ステップ: <strong>{{ $eq->plans->count() }}件</strong>
                    @else
                        <span class="eq-empty-count">ステップ未設定</span>
                    @endif
                </td>
            </tr>
        </table>

        <table class="steps-table">
            <thead>
                <tr>
                    <th class="col-order">順序</th>
                    <th class="col-phase">フェーズ</th>
                    <th class="col-title">ステップ表示名</th>
                    <th class="col-date">日付</th>
                    <th class="col-time">時間</th>
                    <th class="col-route">移動元 -> 移動先</th>
                </tr>
            </thead>
            <tbody>
                @forelse($eq->plans as $plan)
                <tr>
                    <td class="col-order">#{{ $loop->iteration }}</td>
                    <td class="col-phase">
                        <span class="phase-badge phase-{{ $plan->day_phase }}">{{ $plan->day_phase_enum->shortLabel() }}</span>
                        @if($plan->witness_required)
                            <div class="witness-tag">[立会要]{{ $plan->witnessStaff?->name ? ' ' . $plan->witnessStaff->name : '' }}</div>
                        @endif
                    </td>
                    <td class="col-title">
                        <div class="step-title">{{ $plan->title }}</div>
                        @if($plan->formatted_quantity !== '')
                            <div class="step-sub">数量: {{ $plan->formatted_quantity }}</div>
                        @endif
                        @if($plan->notes)
                            <div class="step-sub">{{ $plan->notes }}</div>
                        @endif
                    </td>
                    <td class="col-date">{{ $plan->target_date?->format('Y-m-d') ?: '-' }}</td>
                    <td class="col-time">{{ $plan->formatted_time_range ?: '-' }}</td>
                    <td class="col-route">
                        <span class="route-from">{{ $plan->resolved_from_location }}</span>
                        <span class="route-arrow">-&gt;</span>
                        <span class="route-to">{{ $plan->resolved_to_location }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-step-row">この資材に登録された搬出入ステップはありません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @empty
    <table class="steps-table">
        <tbody>
            <tr>
                <td class="empty-row">該当する資材または搬出入計画データはありません。</td>
            </tr>
        </tbody>
    </table>
    @endforelse

    <!-- 署名欄 -->
    <table class="footer-table">
        <tr>
            <td class="footer-note">
                注: 駐車場は貸切時間帯以外は一般車両優先となります。貸切開始時刻を確認のうえ搬入してください。
            </td>
            <td class="sign-area">
                総括幹事 確認印: [&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;] &nbsp; 資材担当責任者: [&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]
            </td>
        </tr>
    </table>
</body>
</html>
