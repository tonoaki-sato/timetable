<?php

namespace App\Presenters;

use App\Models\Timetable;
use Illuminate\Support\Collection;

class NavigationPresenter
{
    /**
     * @param  Collection<int, Timetable>  $navTimetables
     */
    public function __construct(
        protected Collection $navTimetables,
        protected ?Timetable $activeTimetable = null,
    ) {}

    /**
     * 「当日運行」ドロップダウンがアクティブか判定
     */
    public function isDayActive(): bool
    {
        return request()->routeIs('dashboard*')
            || request()->routeIs('timetable.index')
            || request()->routeIs('timetables.staff-timeline')
            || request()->routeIs('lane-requests.*');
    }

    /**
     * 「準備・計画」ドロップダウンがアクティブか判定
     */
    public function isPlanActive(): bool
    {
        return (
            request()->routeIs('timetables.*')
            && ! request()->routeIs('timetables.print*')
            && ! request()->routeIs('timetables.staff-timeline')
            && ! request()->routeIs('timetables.logistics.print')
        )
            || request()->routeIs('timetable-tasks.*')
            || request()->routeIs('lanes.*')
            || request()->routeIs('calendar.*')
            || request()->routeIs('schedules.*')
            || (request()->routeIs('logistics.*') && ! request()->routeIs('logistics.print'))
            || request()->routeIs('timetables.logistics.index')
            || request()->routeIs('preparation-navigator.*');
    }

    /**
     * 「マスター管理」ドロップダウンがアクティブか判定
     */
    public function isMasterActive(): bool
    {
        return (
            request()->routeIs('staff.*')
            && ! request()->routeIs('staff.print*')
            && ! request()->routeIs('staff.export*')
            && ! request()->routeIs('staff.import*')
        )
            || (request()->routeIs('organizations.*') && ! request()->routeIs('organizations.print*'))
            || request()->routeIs('equipments.*')
            || request()->routeIs('timetables.equipments.*')
            || request()->routeIs('locations.*')
            || request()->routeIs('location-types.*')
            || request()->routeIs('equipment-patterns.*');
    }

    /**
     * 「帳票・印刷」ドロップダウンがアクティブか判定
     */
    public function isPrintActive(): bool
    {
        return request()->routeIs('staff.print*')
            || request()->routeIs('staff.export*')
            || request()->routeIs('staff.import*')
            || request()->routeIs('logistics.print')
            || request()->routeIs('timetables.logistics.print')
            || request()->routeIs('timetables.print*')
            || request()->routeIs('organizations.print*');
    }

    /**
     * 「システム・ガイド」ドロップダウンがアクティブか判定
     */
    public function isGuideActive(): bool
    {
        return request()->routeIs('manual.*')
            || request()->routeIs('presentation.*')
            || request()->routeIs('staff.join*')
            || request()->routeIs('feature-settings.*')
            || request()->routeIs('users.*');
    }

    /**
     * 印刷・当日配置対象のデフォルト運行計画を取得
     */
    public function targetPrintTimetable(): ?Timetable
    {
        return $this->activeTimetable
            ?? $this->navTimetables->firstWhere('status', 'active')
            ?? $this->navTimetables->first();
    }

    /**
     * 搬出入計画メニューがアクティブか判定
     */
    public function isLogisticsPlansActive(): bool
    {
        return (request()->routeIs('logistics.*') || request()->routeIs('timetables.logistics.*'))
            && ! request()->routeIs('*print*');
    }

    /**
     * 指定の運行計画が現在進行表で表示中のものか判定
     */
    public function isCurrentTimetable(Timetable $timetable): bool
    {
        if (! request()->routeIs('timetable.index')) {
            return false;
        }

        if (request('timetable_id') == $timetable->id) {
            return true;
        }

        return ! request('timetable_id') && $this->targetPrintTimetable()?->id == $timetable->id;
    }
}
