<?php

namespace App\DTOs\TimetableTask;

readonly class CreateCommonTimetableTaskDTO
{
    /**
     * @param  list<int>  $laneIds
     */
    public function __construct(
        public int $timetableId,
        public array $laneIds,
        public string $title,
        public string $startTime,
        public string $endTime,
        public int $requiredPeopleCount,
        public bool $isAtLeast,
        public bool $isCritical,
        public string $status,
        public ?string $location,
        public ?string $content,
        public bool $autoAssignLead,
    ) {}

    /**
     * タスク作成用の共通属性配列へ変換する
     *
     * @return array{
     *     timetable_id: int,
     *     title: string,
     *     start_time: string,
     *     end_time: string,
     *     required_people_count: int,
     *     is_at_least: bool,
     *     is_critical: bool,
     *     status: string,
     *     location: ?string,
     *     content: ?string
     * }
     */
    public function toTaskAttributes(): array
    {
        return [
            'timetable_id' => $this->timetableId,
            'title' => $this->title,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'required_people_count' => $this->requiredPeopleCount,
            'is_at_least' => $this->isAtLeast,
            'is_critical' => $this->isCritical,
            'status' => $this->status,
            'location' => $this->location,
            'content' => $this->content,
        ];
    }
}

