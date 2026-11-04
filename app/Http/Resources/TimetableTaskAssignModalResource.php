<?php

namespace App\Http\Resources;

use App\Models\TimetableTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin TimetableTask
 */
class TimetableTaskAssignModalResource extends JsonResource
{
    /**
     * @param  TimetableTask  $resource
     */
    public function __construct(mixed $resource, protected Collection $prioritizedStaff)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $task = $this->resource;
        $leadId = $task->laneRelation?->lead_staff_id;

        return [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'lane' => $task->lane,
                'start_time' => substr($task->start_time, 0, 5),
                'end_time' => substr($task->end_time, 0, 5),
                'location' => $task->location,
                'required_people_count' => $task->required_people_count,
                'required_people_label' => $task->required_people_label,
                'assigned_count' => $task->assigned_count,
                'is_satisfied' => $task->is_satisfied,
                'shortage_count' => $task->shortage_count,
            ],
            'assignments' => $task->assignments->map(function ($a) use ($leadId) {
                return [
                    'id' => $a->id,
                    'staff_id' => $a->staff_id,
                    'staff_name' => $a->staff?->name ?? '不明',
                    'staff_kana' => $a->staff?->kana ?? '',
                    'affiliation' => $a->staff?->affiliation ?? '',
                    'role_description' => $a->role_description ?: '一般担当',
                    'attendance_status' => $a->attendance_status,
                    'attendance_status_label' => $a->attendance_status_label,
                    'is_lane_lead' => $leadId !== null && (int) $a->staff_id === (int) $leadId,
                ];
            })->values(),
            'candidate_staff' => $this->prioritizedStaff->map(function ($s) use ($task) {
                return (new StaffCandidateResource($s, $task))->toArray(request());
            })->values(),
        ];
    }
}
