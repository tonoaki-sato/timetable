<?php

namespace App\Services;

use App\DTOs\EquipmentFilterDTO;
use App\Models\Equipment;
use App\Repositories\Contracts\EquipmentRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;

class EquipmentService
{
    public function __construct(
        protected EquipmentRepositoryInterface $equipmentRepository,
        protected LocationRepositoryInterface $locationRepository,
    ) {}

    /**
     * 資機材一覧画面の表示用データを取得
     *
     * @return array<string, mixed>
     */
    public function getIndexViewData(EquipmentFilterDTO $filter, int $perPage = 20): array
    {
        $equipments = $this->equipmentRepository->getPaginated($filter, $perPage);
        $counts = $this->equipmentRepository->getCountsByType();
        $locations = $this->locationRepository->getActiveWithTypeOrdered();

        return [
            'equipments' => $equipments,
            'locations' => $locations,
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
}
