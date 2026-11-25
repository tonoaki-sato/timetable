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
    }

    /**
     * 指定したタイムテーブルに対する現在のタブに応じた遷移先URLを取得
     */
    public function targetRouteFor(Timetable $tt): string
    {
        return match ($this->active) {
            'lanes' => route('timetables.lanes.index', $tt),
            'tasks' => route('timetables.tasks.index', $tt),
            'lane-timeline' => route('timetables.lane-timeline', $tt),
            'assignments' => route('timetables.assignments', $tt),
            'timeline' => route('timetables.staff-timeline', $tt),
            'logistics' => route('timetables.logistics.index', $tt),
            'logistics-timeline' => route('timetables.logistics-timeline', $tt),
            'layouts', 'layouts-booths' => route('timetables.layouts.index', $tt),
            'site-maps' => route('timetables.site-maps.editor', $tt),
            'cone-maps' => route('timetables.cone-maps.editor', $tt),
            'show' => route('timetables.show', $tt),
            default => route('timetable.index', ['timetable_id' => $tt->id]),
        };
    }

    public function render(): View
    {
        return view('components.timetable-context-bar');
    }
}
