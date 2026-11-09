<?php

namespace App\Repositories\Eloquent;

use App\Models\EquipmentLogisticsPattern;
use App\Repositories\Contracts\EquipmentLogisticsPatternRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Override;

class EquipmentLogisticsPatternRepository implements EquipmentLogisticsPatternRepositoryInterface
{
    #[Override]
    public function getAllWithSteps(): Collection
    {
        return EquipmentLogisticsPattern::with(['steps.fromLocationModel', 'steps.toLocationModel', 'creator'])
            ->ordered()
            ->get();
    }

    #[Override]
    public function findWithSteps(int $id): ?EquipmentLogisticsPattern
    {
        return EquipmentLogisticsPattern::with(['steps.fromLocationModel', 'steps.toLocationModel'])->find($id);
    }

    #[Override]
    public function createWithSteps(array $attributes, array $stepsData, ?int $userId = null): EquipmentLogisticsPattern
    {
        return DB::transaction(function () use ($attributes, $stepsData, $userId) {
            $pattern = EquipmentLogisticsPattern::create([
                'name' => $attributes['name'],
                'target_type' => $attributes['target_type'],
                'description' => $attributes['description'] ?? null,
                'display_order' => $attributes['display_order'] ?? ($this->getMaxDisplayOrder() + 10),
                'created_by' => $userId,
            ]);

            foreach ($stepsData as $index => $step) {
                $pattern->steps()->create([
                    'day_phase' => $step['day_phase'],
                    'action_type' => $step['action_type'],
                    'title' => $step['title'],
                    'from_location_id' => $step['from_location_id'] ?? null,
                    'to_location_id' => $step['to_location_id'] ?? null,
                    'operator_type' => $step['operator_type'] ?? 'committee',
                    'witness_required' => (bool) ($step['witness_required'] ?? false),
                    'notes' => $step['notes'] ?? null,
                    'display_order' => $step['display_order'] ?? (($index + 1) * 10),
                ]);
            }

            return $pattern->load('steps');
        });
    }

    #[Override]
    public function updateWithSteps(EquipmentLogisticsPattern $pattern, array $attributes, array $stepsData): bool
    {
        return DB::transaction(function () use ($pattern, $attributes, $stepsData) {
            $pattern->update([
                'name' => $attributes['name'],
                'target_type' => $attributes['target_type'],
                'description' => $attributes['description'] ?? null,
                'display_order' => $attributes['display_order'] ?? $pattern->display_order,
            ]);

            $pattern->steps()->delete();

            foreach ($stepsData as $index => $step) {
                $pattern->steps()->create([
                    'day_phase' => $step['day_phase'],
                    'action_type' => $step['action_type'],
                    'title' => $step['title'],
                    'from_location_id' => $step['from_location_id'] ?? null,
                    'to_location_id' => $step['to_location_id'] ?? null,
                    'operator_type' => $step['operator_type'] ?? 'committee',
                    'witness_required' => (bool) ($step['witness_required'] ?? false),
                    'notes' => $step['notes'] ?? null,
                    'display_order' => $step['display_order'] ?? (($index + 1) * 10),
                ]);
            }

            return true;
        });
    }

    #[Override]
    public function delete(EquipmentLogisticsPattern $pattern): bool
    {
        return $pattern->delete();
    }

    #[Override]
    public function getMaxDisplayOrder(): int
    {
        return (int) EquipmentLogisticsPattern::max('display_order');
    }
}

