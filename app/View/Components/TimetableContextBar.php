<?php

namespace App\View\Components;

use App\Models\Timetable;
use App\Services\TimetableService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class TimetableContextBar extends Component
{
    /**
     * @var Collection<int, Timetable>
     */
    public Collection $timetablesList;

    public function __construct(
        TimetableService $timetableService,
        public ?Timetable $timetable = null,
        public string $active = 'matrix',
    ) {
        $this->timetablesList = $timetableService->getSelectableTimetables();

        if ($this->timetable !== null) {
            $countMap = [
                'lanes' => 'lanes_count',
                'tasks' => 'tasks_count',
                'locations' => 'locations_count',
                'logisticsPlans' => 'logistics_plans_count',
            ];

            $missingCounts = [];
            $attributes = $this->timetable->getAttributes();

            foreach ($countMap as $relation => $countAttribute) {
                if (array_key_exists($countAttribute, $attributes)) {
                    continue;
                }

                if ($this->timetable->relationLoaded($relation)) {
                    $this->timetable->setAttribute($countAttribute, $this->timetable->getRelation($relation)->count());
                } else {
                    $missingCounts[] = $relation;
                }
            }

            if ($missingCounts !== []) {
                $this->timetable->loadCount($missingCounts);
            }
        }
    }

    /**
     * 指定したタイムテーブルに対する現在のタブに応じた遷移先URLを取得
     */
    public function targetRouteFor(Timetable $tt): string
    {
        return match ($this->active) {
            'lanes' => route('timetables.lanes.index', $tt),
            'tasks' => route('timetables.tasks.index', $tt),
            'assignments' => route('timetables.assignments', $tt),
            'timeline' => route('timetables.staff-timeline', $tt),
            'locations' => route('timetables.locations.index', $tt),
            'equipments' => route('timetables.equipments.index', $tt),
            'logistics' => route('timetables.logistics.index', $tt),
            'logistics-timeline' => route('timetables.logistics-timeline', $tt),
            'layouts', 'layouts-booths', 'layouts-facilities', 'goza-stores' => route('timetables.layouts.index', $tt),
            'site-maps' => route('timetables.site-maps.editor', $tt),
            'show' => route('timetables.show', $tt),
            'print' => route('timetables.print-assignments', $tt),
            default => route('timetable.index', ['timetable_id' => $tt->id]),
        };
    }

    public function render(): View
    {
        return view('components.timetable-context-bar');
    }
}
