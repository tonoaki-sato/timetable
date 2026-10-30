<?php

namespace App\DTOs;

use App\Enums\ParkingConstraintStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

readonly class ParkingMonitoringContext
{
    /**
     * @param  Collection<int, ParkingLocationStatus>  $locations
     */
    public function __construct(
        public Carbon $now,
        public Collection $locations,
        public int $violationCount,
        public int $warningCount,
        public int $inUseCount,
    ) {}

    /**
     * 重大な違反が存在するか
     */
    public function hasViolations(): bool
    {
        return $this->violationCount > 0;
    }

    /**
     * 警告・要注意が存在するか
     */
    public function hasWarnings(): bool
    {
        return $this->warningCount > 0;
    }

    /**
     * 全体の要約バッジテキスト
     */
    public function summaryBadgeText(): string
    {
        if ($this->hasViolations()) {
            return "🚨 違反あり ({$this->violationCount}件)";
        }

        if ($this->hasWarnings()) {
            return "⚠️ 要注意 ({$this->warningCount}件)";
        }

        return '✓ 違反なし';
    }

    /**
     * 全体の要約バッジCSSクラス
     */
    public function summaryBadgeClasses(): string
    {
        if ($this->hasViolations()) {
            return 'bg-red-100 text-red-700 border border-red-300 font-bold';
        }

        if ($this->hasWarnings()) {
            return 'bg-amber-100 text-amber-800 border border-amber-300 font-bold';
        }

        return 'bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold';
    }

    /**
     * 全体最悪ステータス
     */
    public function overallStatus(): ParkingConstraintStatus
    {
        if ($this->hasViolations()) {
            return ParkingConstraintStatus::Violation;
        }

        if ($this->hasWarnings()) {
            return ParkingConstraintStatus::Warning;
        }

        if ($this->inUseCount > 0) {
            return ParkingConstraintStatus::InUse;
        }

        return ParkingConstraintStatus::Standby;
    }
}

