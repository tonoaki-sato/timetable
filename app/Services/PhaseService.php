<?php

namespace App\Services;

use App\DTOs\PhaseContext;
use App\Enums\AnnualPhase;
use App\Models\AnnualSchedule;
use App\Models\Timetable;
use Carbon\Carbon;

class PhaseService
{
    /**
     * 現在（または指定日時）のフェーズ状況を一括取得
     */
    public function getCurrentContext(?Carbon $now = null): PhaseContext
    {
        // 現在時刻の取得
        $now = $now ?? Carbon::now('Asia/Tokyo');

        // 日付と年を取得
        $todayStr = $now->toDateString();
        $targetYear = $now->year;

        // 実行フェーズ判定
        $context = $this->getTodaysContext($now);
        if ($context !== null) {
            return $context;
        }

        // 平時（年間準備フェーズ）
        $phase = AnnualPhase::fromMonth($now->month);
        $displayName = $phase->label();

        // 該当年または直近の未完了年間タスクを取得
        $nextTask = AnnualSchedule::where('target_year', $targetYear)
            ->where('start_date', '>=', $todayStr)
            ->where('status', '!=', 'completed')
            ->orderBy('start_date', 'asc')
            ->first();

        // 直近タスクに基づくサブステータス構築
        if ($nextTask !== null && $nextTask->start_date !== null) {
            $dateStr = $nextTask->start_date->format('n/j');
            $daysDiff = (int) $now->startOfDay()->diffInDays($nextTask->start_date->startOfDay(), false);

            if ($daysDiff === 0) {
                $subStatus = "本日: {$nextTask->title}";
            } elseif ($daysDiff > 0) {
                $subStatus = "直近: {$dateStr} {$nextTask->title} (あと{$daysDiff}日)";
            } else {
                $subStatus = "直近: {$dateStr} {$nextTask->title}";
            }
        } else {
            $subStatus = '直近の年間行事予定なし';
        }

        // 当フェーズ内の未完了タスク数を集計
        $phaseMonths = $phase->months();
        $pendingCount = AnnualSchedule::where('target_year', $targetYear)
            ->where(function ($q) use ($phaseMonths) {
                foreach ($phaseMonths as $m) {
                    $q->orWhereMonth('start_date', $m);
                }
            })
            ->where('status', '!=', 'completed')
            ->count();

        return new PhaseContext(
            phase: $phase,
            displayName: $displayName,
            subStatus: $subStatus,
            isMainEventDay: false,
            activeTimetable: null,
            nextTask: $nextTask,
            pendingTaskCount: $pendingCount,
            actionUrl: route('annual-schedules.index', ['year' => $targetYear])
        );
    }

    /**
     * 実行フェーズかどうか判定
     */
    private function getTodaysContext(Carbon $now): ?PhaseContext
    {
        // 日付と年を取得
        $todayStr = $now->toDateString();

        // 1. 本日開催の運行タイムテーブル（本番日判定）
        $timetable = Timetable::whereDate('target_date', $todayStr)->first();

        if ($timetable !== null) {
            $currentTime = $now->format('H:i');
            $sessionLabel = match (true) {
                $currentTime < '07:00' => '早朝・実施判断セッション',
                $currentTime < '10:00' => '事前準備セッション進行中',
                $currentTime < '11:00' => '最優先本設置セッション',
                $currentTime < '16:30' => '本番運行セッション進行中',
                $currentTime < '17:30' => '規制解除前 撤去セッション',
                default => '夜間・完全撤収セッション',
            };

            return new PhaseContext(
                phase: AnnualPhase::Execution,
                displayName: $timetable->name,
                subStatus: $sessionLabel,
                isMainEventDay: true,
                activeTimetable: $timetable,
                nextTask: null,
                pendingTaskCount: 0,
                actionUrl: route('timetable.index')
            );
        }

        return null;
    }
}
