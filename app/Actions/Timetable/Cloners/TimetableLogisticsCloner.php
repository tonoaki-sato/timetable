<?php

namespace App\Actions\Timetable\Cloners;

use App\Models\Timetable;
use App\Repositories\Contracts\TimetableCloneRepositoryInterface;

class TimetableLogisticsCloner
{
    public function __construct(
        protected TimetableCloneRepositoryInterface $cloneRepository,
    ) {}

    /**
     * 資材搬出入計画を複製（日付シフト・ステータス初期化）し、作成件数を返却
     */
    public function cloneLogisticsPlans(
        Timetable $source,
        Timetable $newTimetable,
        int $dayOffset
    ): int {
        $sourcePlans = $this->cloneRepository->getLogisticsPlans($source->id);

        $planRows = [];
        $now = now()->format('Y-m-d H:i:s');

        foreach ($sourcePlans as $sourcePlan) {
            $planRows[] = [
                'timetable_id' => $newTimetable->id,
                'equipment_id' => $sourcePlan->equipment_id,
                'target_date' => $sourcePlan->target_date?->copy()->addDays($dayOffset)->format('Y-m-d'),
                'day_phase' => $sourcePlan->day_phase,
                'action_type' => $sourcePlan->action_type,
                'title' => $sourcePlan->title,
                'quantity' => $sourcePlan->quantity,
                'quantity_note' => $sourcePlan->quantity_note,
                'start_time' => $sourcePlan->start_time,
                'end_time' => $sourcePlan->end_time,
                'from_location' => $sourcePlan->from_location,
                'to_location' => $sourcePlan->to_location,
                'from_location_id' => $sourcePlan->from_location_id,
                'to_location_id' => $sourcePlan->to_location_id,
                'operator_type' => $sourcePlan->operator_type,
                'witness_required' => (bool) $sourcePlan->witness_required,
                'witness_staff_id' => $sourcePlan->witness_staff_id,
                'status' => 'planned',
                'notes' => $sourcePlan->notes,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->cloneRepository->insertLogisticsPlans($planRows);

        return count($planRows);
    }
}
