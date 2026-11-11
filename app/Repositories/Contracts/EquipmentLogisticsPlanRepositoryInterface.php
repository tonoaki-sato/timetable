<?php

namespace App\Repositories\Contracts;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Collection;

interface EquipmentLogisticsPlanRepositoryInterface
{
    /**
     * 指定タイムテーブルにおける全計画ステップを取得
     *
     * @return Collection<int, EquipmentLogisticsPlan>
     */
    public function getForTimetable(Timetable $timetable): Collection;

    /**
     * 資材別フロー一覧用データ（タイムテーブル指定）を取得
     *
     * @param  array<int, int>|null  $equipmentIds
     * @return Collection<int, Equipment>
     */
    public function getEquipmentsFlowData(?string $type = null, ?Timetable $timetable = null, ?array $equipmentIds = null): Collection;

    /**
     * 新規計画ステップを作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): EquipmentLogisticsPlan;

    /**
     * 計画ステップを更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(EquipmentLogisticsPlan $plan, array $attributes): bool;

    /**
     * 計画ステップを削除
     */
    public function delete(EquipmentLogisticsPlan $plan): bool;

    /**
     * 特定資材かつ指定タイムテーブルの計画ステップを一括クリア
     */
    public function clearForEquipment(Equipment $equipment, Timetable $timetable): int;
}
