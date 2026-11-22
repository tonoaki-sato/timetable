<?php

namespace App\Services;

use App\Models\EquipmentLogisticsPattern;
use App\Models\EquipmentLogisticsPatternStep;
use App\Models\Location;
use App\Repositories\Contracts\EquipmentLogisticsPatternRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EquipmentLogisticsPatternService
{
    public function __construct(
        protected EquipmentLogisticsPatternRepositoryInterface $patternRepository,
        protected LocationRepositoryInterface $locationRepository,
    ) {}

    /**
     * パターン一覧画面用のデータを取得
     *
     * @return array{patterns: Collection<int, EquipmentLogisticsPattern>, locations: Collection<int, Location>}
     */
    public function getPatternIndexViewData(): array
    {
        return [
            'patterns' => $this->patternRepository->getAllWithSteps(),
            'locations' => $this->locationRepository->getUniqueNamesOrdered(),
        ];
    }

    /**
     * 搬出入パターンを新規作成
     *
     * @param  array{name: string, target_type: string, description?: ?string, display_order?: ?int}  $attributes
     * @param  array<int, array<string, mixed>>  $stepsData
     */
    public function createPatternWithSteps(array $attributes, array $stepsData, ?int $userId = null): EquipmentLogisticsPattern
    {
        return $this->patternRepository->createWithSteps($attributes, $stepsData, $userId);
    }

    /**
     * 搬出入パターンを更新
     *
     * @param  array{name: string, target_type: string, description?: ?string, display_order?: ?int}  $attributes
     * @param  array<int, array<string, mixed>>  $stepsData
     */
    public function updatePatternWithSteps(EquipmentLogisticsPattern $pattern, array $attributes, array $stepsData): EquipmentLogisticsPattern
    {
        $this->patternRepository->updateWithSteps($pattern, $attributes, $stepsData);

        return $pattern->load('steps');
    }

    /**
     * 搬出入パターンを削除
     */
    public function deletePattern(EquipmentLogisticsPattern $pattern): bool
    {
        return $this->patternRepository->delete($pattern);
    }

    /**
     * 搬出入パターンにステップを単体追加
     *
     * @param  array<string, mixed>  $data
     */
    public function addPatternStep(EquipmentLogisticsPattern $pattern, array $data): EquipmentLogisticsPatternStep
    {
        return $this->patternRepository->createStep($pattern, $data);
    }

    /**
     * 搬出入パターンのステップを単体更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePatternStep(EquipmentLogisticsPatternStep $step, array $data): EquipmentLogisticsPatternStep
    {
        $this->patternRepository->updateStep($step, $data);

        return $step->fresh() ?? $step;
    }

    /**
     * 搬出入パターンのステップを単体削除
     */
    public function deletePatternStep(EquipmentLogisticsPatternStep $step): bool
    {
        return $this->patternRepository->deleteStep($step);
    }

    /**
     * パターン内ステップの表示順を一括更新
     *
     * @param  array<int>  $stepIds
     */
    public function reorderPatternSteps(EquipmentLogisticsPattern $pattern, array $stepIds): void
    {
        $this->patternRepository->reorderSteps($pattern, $stepIds);
    }
}
