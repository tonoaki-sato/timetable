<?php

namespace App\Services;

use App\DTOs\TrafficRegulationContext;
use App\Enums\TrafficRegulationStatus;
use App\Models\Timetable;
use Carbon\Carbon;

class TrafficRegulationService
{
    public const string TIME_PRE_SETUP = '06:00:00';

    public const string TIME_REGULATION_START = '10:00:00';

    public const string TIME_MAIN_START = '11:00:00';

    public const string TIME_EARLY_CLEARING = '16:30:00';

    public const string TIME_MAIN_END = '17:00:00';

    public const string TIME_REGULATION_END = '18:00:00';

    /**
     * 現在（または指定日時）の道路交通規制状況を一括取得
     */
    public function getCurrentContext(?Carbon $now = null): TrafficRegulationContext
    {
        $now = $now ?? Carbon::now('Asia/Tokyo');
        $todayStr = $now->toDateString();
        $actionUrl = route('timetable.index');

        // 1. 本日開催の運行タイムテーブル（本番日判定）
        $timetable = Timetable::whereDate('target_date', $todayStr)->first();

        if ($timetable === null) {
            $status = TrafficRegulationStatus::Inactive;

            return new TrafficRegulationContext(
                status: $status,
                badgeText: $status->badgeLabel(),
                headline: $status->headline(),
                subStatus: '一般車両通行中',
                isRoadClosed: false,
                isAlertActive: false,
                actionUrl: $actionUrl
            );
        }

        // 2. 本番日の時間帯判定
        $currentTime = $now->format('H:i:s');

        // 06:00以前（早朝待機）
        if ($currentTime < self::TIME_PRE_SETUP) {
            $status = TrafficRegulationStatus::PreRegulation;

            return new TrafficRegulationContext(
                status: $status,
                badgeText: '⚠️ 通行止め前（早朝待機）',
                headline: '早朝待機中',
                subStatus: '06:00より機材仮設置開始 / 10:00より車両通行止め',
                isRoadClosed: false,
                isAlertActive: false,
                actionUrl: $actionUrl
            );
        }

        // 06:00〜09:59（規制前・仮設営中 / 一般車通行中）
        if ($currentTime < self::TIME_REGULATION_START) {
            $status = TrafficRegulationStatus::PreRegulation;
            $target = $now->copy()->setTime(10, 0, 0);
            $diffMinutes = (int) $now->diffInMinutes($target, false);
            $remaining = $diffMinutes > 0 ? "あと{$diffMinutes}分" : '直前';

            return new TrafficRegulationContext(
                status: $status,
                badgeText: $status->badgeLabel(),
                headline: $status->headline(),
                subStatus: "10:00より車両通行止め ({$remaining}) / 路上本設置準備",
                isRoadClosed: false,
                isAlertActive: false,
                actionUrl: $actionUrl
            );
        }

        // 10:00〜10:59（車両通行止め・路上本設置集中）
        if ($currentTime < self::TIME_MAIN_START) {
            $status = TrafficRegulationStatus::RoadSetup;
            $target = $now->copy()->setTime(11, 0, 0);
            $diffMinutes = (int) $now->diffInMinutes($target, false);
            $remaining = $diffMinutes > 0 ? "あと{$diffMinutes}分" : '直前';

            return new TrafficRegulationContext(
                status: $status,
                badgeText: $status->badgeLabel(),
                headline: $status->headline(),
                subStatus: "ござ市テント・機材展開中 (11:00本番まで{$remaining})",
                isRoadClosed: true,
                isAlertActive: true,
                actionUrl: $actionUrl
            );
        }

        // 11:00〜16:59（車両通行止め・まつり本番運行）
        if ($currentTime < self::TIME_MAIN_END) {
            $status = TrafficRegulationStatus::MainEvent;
            $target = $now->copy()->setTime(18, 0, 0);
            $diffHours = (int) $now->diffInHours($target);
            $diffMins = (int) ($now->diffInMinutes($target) % 60);
            $remaining = $diffHours > 0 ? "{$diffHours}時間{$diffMins}分" : "{$diffMins}分";

            $clearingNote = $currentTime >= self::TIME_EARLY_CLEARING ? '一部先行仮撤収中 / ' : '';

            return new TrafficRegulationContext(
                status: $status,
                badgeText: $status->badgeLabel(),
                headline: $status->headline(),
                subStatus: "10:00〜18:00通行止め ({$clearingNote}解除まであと{$remaining})",
                isRoadClosed: true,
                isAlertActive: false,
                actionUrl: $actionUrl
            );
        }

        // 17:00〜17:59（車両通行止め・撤収ピーク / 18:00解除必須）
        if ($currentTime < self::TIME_REGULATION_END) {
            $status = TrafficRegulationStatus::PeakTeardown;
            $target = $now->copy()->setTime(18, 0, 0);
            $diffMinutes = (int) $now->diffInMinutes($target, false);
            $remaining = $diffMinutes > 0 ? "あと{$diffMinutes}分" : '直前';

            return new TrafficRegulationContext(
                status: $status,
                badgeText: $status->badgeLabel(),
                headline: $status->headline(),
                subStatus: "車道資材・ゴミ箱完全回収中 (18:00解除まで{$remaining})",
                isRoadClosed: true,
                isAlertActive: true,
                actionUrl: $actionUrl
            );
        }

        // 18:00以降（通行止め解除済・残作業中）
        $status = TrafficRegulationStatus::PostRegulation;

        return new TrafficRegulationContext(
            status: $status,
            badgeText: $status->badgeLabel(),
            headline: $status->headline(),
            subStatus: '18:00に通行止め解除済 (一般車両通行中・足元注意 / 番所格納中)',
            isRoadClosed: false,
            isAlertActive: false,
            actionUrl: $actionUrl
        );
    }
}
