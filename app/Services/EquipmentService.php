<?php

namespace App\Services;

use App\DTOs\EquipmentFilterDTO;
use App\Models\Equipment;
use App\Models\Timetable;
use App\Repositories\Contracts\EquipmentRepositoryInterface;

class EquipmentService
{
    public function __construct(
        protected EquipmentRepositoryInterface $equipmentRepository,
        protected TimetableService $timetableService,
    ) {}

    /**
     * 資機材一覧画面の表示用データを取得
     *
     * @return array<string, mixed>
     */
    public function getIndexViewData(EquipmentFilterDTO $filter, ?Timetable $timetable = null, int $perPage = 20): array
    {
        $equipments = $this->equipmentRepository->getPaginated($filter, $timetable, $perPage);
        $counts = $this->equipmentRepository->getCountsByType();

        return [
            'equipments' => $equipments,
            'typeFilter' => $filter->type,
            'checkoutRuleFilter' => $filter->checkoutRule,
            'keyword' => $filter->keyword,
            'totalCount' => $counts['total'],
            'committeeCount' => $counts['committee'],
            'rentalCount' => $counts['rental'],
            'personalCount' => $counts['personal'],
        ];
    }

    /**
     * 資機材の表示順を一括更新
     *
     * @param  array<int, int>  $equipmentIds
     */
    public function reorderEquipments(array $equipmentIds): void
    {
        $this->equipmentRepository->reorder($equipmentIds);
    }

    /**
     * 資機材の新規登録（表示順の自動採番を含む）
     *
     * @param  array<string, mixed>  $data
     */
    public function createEquipment(array $data, ?int $userId = null): Equipment
    {
        if ($userId !== null && empty($data['created_by'])) {
            $data['created_by'] = $userId;
        }

        if (! isset($data['display_order']) || $data['display_order'] === null) {
            $data['display_order'] = $this->equipmentRepository->getMaxDisplayOrder() + 10;
        }

        return $this->equipmentRepository->create($data);
    }

    /**
     * 詳細画面用に資機材を取得
     */
    public function findWithDetails(int $id, ?Timetable $timetable = null): ?Equipment
    {
        return $this->equipmentRepository->findWithDetails($id, $timetable);
    }

    /**
     * 資機材情報の更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateEquipment(Equipment $equipment, array $data): bool
    {
        return $this->equipmentRepository->update($equipment, $data);
    }

    /**
     * 資機材の削除
     */
    public function deleteEquipment(Equipment $equipment): bool
    {
        return $this->equipmentRepository->delete($equipment);
    }

    /**
     * 対象タイムテーブルの解決
     */
    public function resolveTimetable(?int $timetableId = null, ?Timetable $timetable = null): Timetable
    {
        return $this->timetableService->resolveFallbackTimetable($timetableId, $timetable);
    }
}
