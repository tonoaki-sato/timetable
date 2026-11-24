<?php

namespace App\DTOs\Timeline;

use App\Models\TimetableLane;

readonly class LaneTimelineRowDTO
{
    /**
     * @param  list<GanttBarDTO>  $routineBars
     * @param  list<GanttBarDTO>  $spotBars
     */
    public function __construct(
        public TimetableLane $timetableLane,
        public int $routineCount,
        public int $spotCount,
        public int $shortageTaskCount,
        public int $routineTracksCount,
        public int $spotTracksCount,
        public array $routineBars,
        public array $spotBars,
        public string $suggestedStartTime,
    ) {}
}
