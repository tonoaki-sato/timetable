<?php

namespace App\Repositories\Contracts;

use App\DTOs\EquipmentFilterDTO;
use App\Models\Equipment;
use App\Models\Timetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface EquipmentRepositoryInterface
{
    /**
     * フィルター条件に基づいてページネーション付きで資機材一覧を取得
     */
    public function getPaginated(EquipmentFilterDTO $filter, ?Timetable $timetable = null, int $perPage = 20): LengthAwarePaginator;

    /**
     * 所有区分ごとの統計件数を取得
     *
     * @return array{total: int, committee: int, rental: int, personal: int}
     */
    public function getCountsByType(): array;

    /**
     * 資機材の総登録数を取得
     */
    public function getTotalCount(): int;

    /**
     * IDで資機材を検索（リレーション付き）
     */
    public function findWithDetails(int $id, ?Timetable $timetable = null): ?Equipment;

    /**
     * 指定運行日の搬出入計画リレーションを資機材へロード
     */
    public function loadPlansForTimetable(Equipment $equipment, Timetable $timetable): Equipment;

    /**
     * 全資機材一覧を取得（表示順）
     *
     * @return Collection<int, Equipment>
     */
    public function getAllOrdered(): Collection;

    /**
     * 新規資機材を作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Equipment;

    /**
     * 資機材を更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Equipment $equipment, array $attributes): bool;

    /**
     * 資機材を削除
     */
    public function delete(Equipment $equipment): bool;

    /**
     * 備品の表示順を一括更新
     *
     * @param  array<int, int>  $equipmentIds
     */
    public function reorder(array $equipmentIds): void;

    /**
     * 最大の表示順を取得
     */
    public function getMaxDisplayOrder(): int;
}
