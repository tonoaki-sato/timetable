<?php

namespace App\Http\Resources;

use App\Models\Staff;
use App\Models\TimetableTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Staff
 */
class StaffCandidateResource extends JsonResource
{
    /**
     * @param  Staff  $resource
     */
    public function __construct(mixed $resource, protected ?TimetableTask $task = null)
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
        $isAlreadyAssigned = $this->task
            ? $this->task->assignments->contains('staff_id', $this->id)
            : false;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'kana' => $this->kana ?? '',
            'affiliation' => $this->affiliation ?? '',
            'staff_type' => $this->staff_type ?? 'fulfillment',
            'staff_type_label' => $this->staff_type_label,
            'priority_tier' => $this->priority_tier ?? 2,
            'is_conflicted' => $this->conflict_task !== null,
            'conflicted_task_title' => $this->conflict_task?->title ?? '',
            'conflicted_label' => $this->conflict_task ? "重複中: {$this->conflict_task->title}" : '',
            'is_lane_lead' => (bool) ($this->is_lane_lead ?? false),
            'is_fixed_in_lane' => (bool) ($this->is_fixed_in_lane ?? false),
            'is_surplus_fixed' => (bool) ($this->is_surplus_fixed ?? false),
            'surplus_type' => $this->surplus_type ?? null,
            'is_same_lane' => (bool) ($this->is_same_lane ?? false),
            'is_checked_in' => (bool) ($this->is_checked_in ?? false),
            'minutes_today' => $this->today_assigned_minutes ?? 0,
            'is_already_assigned' => $isAlreadyAssigned,
        ];
    }
}
