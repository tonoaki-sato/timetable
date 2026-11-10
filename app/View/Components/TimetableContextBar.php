<?php

namespace App\View\Components;

use App\Models\Timetable;
use App\Repositories\Contracts\TimetableRepositoryInterface;
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
        TimetableRepositoryInterface $timetableRepository,
        public ?Timetable $timetable = null,
        public string $active = 'matrix',
    ) {
        $this->timetablesList = $timetableRepository->getAll();
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
