<?php

namespace App\Repositories\Contracts;

use App\Models\EquipmentLogisticsPattern;
use Illuminate\Database\Eloquent\Collection;

interface EquipmentLogisticsPatternRepositoryInterface
{
    /**
     * パターン一覧をステップ・リレーション付きで取得
     *
     * @return Collection<int, EquipmentLogisticsPattern>
     */
    public function getAllWithSteps(): Collection;

    /**
     * IDでパターンをステップ付きで検索
     */
    public function findWithSteps(int $id): ?EquipmentLogisticsPattern;

    /**
     * ステップ情報を含めて新規パターンをトランザクション保存
     *
     * @param  array{name: string, target_type: string, description?: ?string, display_order?: ?int}  $attributes
     * @param  array<int, array<string, mixed>>  $stepsData
     */
    public function createWithSteps(array $attributes, array $stepsData, ?int $userId = null): EquipmentLogisticsPattern;

    /**
     * パターンおよびステップ構成をトランザクション更新
     *
     * @param  array{name: string, target_type: string, description?: ?string, display_order?: ?int}  $attributes
     * @param  array<int, array<string, mixed>>  $stepsData
     */
    public function updateWithSteps(EquipmentLogisticsPattern $pattern, array $attributes, array $stepsData): bool;

    /**
     * パターンを削除
     */
    public function delete(EquipmentLogisticsPattern $pattern): bool;

    /**
     * 最大の表示順を取得
     */
    public function getMaxDisplayOrder(): int;
}
