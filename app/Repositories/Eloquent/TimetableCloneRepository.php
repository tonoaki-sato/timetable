<?php

namespace App\Repositories\Eloquent;

use App\Models\ConeMapBar;
use App\Models\ConeMapBarricade;
use App\Models\ConeMapCallout;
use App\Models\ConeMapCone;
use App\Models\EquipmentLogisticsPlan;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\SiteMapPlacement;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Repositories\Contracts\TimetableCloneRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Override;

class TimetableCloneRepository implements TimetableCloneRepositoryInterface
{
    #[Override]
    public function getLanesWithFixedStaff(int $timetableId): Collection
    {
        return TimetableLane::with('fixedStaff')
            ->where('timetable_id', $timetableId)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function createTimetableLane(array $attributes, array $fixedStaffIds = []): TimetableLane
    {
        $lane = TimetableLane::create($attributes);

        if ($fixedStaffIds !== []) {
            $lane->fixedStaff()->sync($fixedStaffIds);
        }

        return $lane;
    }

    #[Override]
    public function getTasksWithAssignments(int $timetableId): Collection
    {
        return TimetableTask::with('assignments')
            ->where('timetable_id', $timetableId)
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function createTimetableTask(array $attributes): TimetableTask
    {
        return TimetableTask::create($attributes);
    }

    #[Override]
    public function insertTaskAssignments(array $rows): void
    {
        $this->chunkInsert(TaskStaffAssignment::class, $rows);
    }

    #[Override]
    public function getLogisticsPlans(int $timetableId): Collection
    {
        return EquipmentLogisticsPlan::where('timetable_id', $timetableId)
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function insertLogisticsPlans(array $rows): void
    {
        $this->chunkInsert(EquipmentLogisticsPlan::class, $rows);
    }

    #[Override]
    public function getLayoutMapsWithItems(int $timetableId): Collection
    {
        return LayoutMap::with(['items'])
            ->where('timetable_id', $timetableId)
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function createLayoutMap(array $attributes): LayoutMap
    {
        return LayoutMap::create($attributes);
    }

    #[Override]
    public function createLayoutItem(array $attributes): LayoutItem
    {
        return LayoutItem::create($attributes);
    }

    #[Override]
    public function updateTimetableBaseMap(Timetable $timetable, int $siteBaseMapId): void
    {
        $timetable->update([
            'site_base_map_id' => $siteBaseMapId,
        ]);
    }

    #[Override]
    public function getSiteMapPlacements(int $timetableId): Collection
    {
        return SiteMapPlacement::query()
            ->where('timetable_id', $timetableId)
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function insertSiteMapPlacements(array $rows): void
    {
        $this->chunkInsert(SiteMapPlacement::class, $rows);
    }

    #[Override]
    public function getConeMapCones(int $timetableId): Collection
    {
        return ConeMapCone::query()
            ->where('timetable_id', $timetableId)
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function createConeMapCone(array $attributes): ConeMapCone
    {
        return ConeMapCone::create($attributes);
    }

    #[Override]
    public function getConeMapBars(int $timetableId): Collection
    {
        return ConeMapBar::query()
            ->where('timetable_id', $timetableId)
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function insertConeMapBars(array $rows): void
    {
        $this->chunkInsert(ConeMapBar::class, $rows);
    }

    #[Override]
    public function getConeMapBarricades(int $timetableId): Collection
    {
        return ConeMapBarricade::query()
            ->where('timetable_id', $timetableId)
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function createConeMapBarricade(array $attributes): ConeMapBarricade
    {
        return ConeMapBarricade::create($attributes);
    }

    #[Override]
    public function getConeMapCallouts(int $timetableId): Collection
    {
        return ConeMapCallout::query()
            ->where('timetable_id', $timetableId)
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function insertConeMapCallouts(array $rows): void
    {
        $this->chunkInsert(ConeMapCallout::class, $rows);
    }

    /**
     * チャンク単位でレコードを一括挿入
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function chunkInsert(string $modelClass, array $rows, int $chunkSize = 500): void
    {
        if ($rows === []) {
            return;
        }

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $modelClass::insert($chunk);
        }
    }
}
