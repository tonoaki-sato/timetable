<?php

namespace App\Services;

use App\DTOs\LayoutItemFilterDTO;
use App\Enums\BoothCategory;
use App\Enums\BoothType;
use App\Enums\FacilityType;
use App\Models\LayoutArea;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Timetable;
use App\Repositories\Contracts\LayoutAreaRepositoryInterface;
use App\Repositories\Contracts\LayoutMapRepositoryInterface;
use App\Repositories\Contracts\VendorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SiteLayoutService
{
    public function __construct(
        protected LayoutMapRepositoryInterface $layoutMapRepository,
        protected LayoutAreaRepositoryInterface $layoutAreaRepository,
        protected VendorRepositoryInterface $vendorRepository,
    ) {}

    /**
     * タイムテーブルに紐づく現場配置図面を取得（なければ作成）
     */
    public function getLayoutMap(Timetable $timetable): LayoutMap
    {
        return $this->layoutMapRepository->getOrCreateForTimetable($timetable);
    }

    /**
     * 指定配置図面で利用対象となる会場エリア一覧を取得
     *
     * @return Collection<int, LayoutArea>
     */
    public function getAreasForMap(LayoutMap $map, bool $withScopedItems = false): Collection
    {
        return $this->layoutAreaRepository->getForLayoutMap($map, $withScopedItems);
    }

    /**
     * 指定配置図面における全体およびエリア別の配置アイテム件数を取得
     *
     * @return array{total_items: int, area_totals: array<int, int>}
     */
    public function getAreaItemCounts(LayoutMap $map): array
    {
        $aggregates = $this->layoutMapRepository->getSummaryAggregates($map->id);

        return [
            'total_items' => $aggregates['total_items'],
            'area_totals' => $aggregates['area_totals'],
        ];
    }

    /**
     * 配置計画サマリー集計データを生成
     *
     * @param  Collection<int, LayoutArea>  $areas
     * @return array{
     *   total_booths: int,
     *   total_facilities: int,
     *   total_items: int,
     *   categories: array<string, array{key: string, label: string, icon: string, count: int, badge: string}>,
     *   facilities: array<string, array{key: string, label: string, icon: string, count: int, badge: string}>,
     *   area_booth_types: array<int, array{area_id: int, area_name: string, is_active: bool, total_count: int, types: array<int, array{value: string, label: string, count: int}>}>
     * }
     */
    public function getSummary(LayoutMap $map, Collection $areas): array
    {
        $aggregates = $this->layoutMapRepository->getSummaryAggregates($map->id);

        $totalBooths = $aggregates['total_booths'];
        $totalFacilities = $aggregates['total_facilities'];
        $totalItems = $aggregates['total_items'];

        // 1. 出展区分別集計 (BoothCategory)
        $categoryCounts = [];
        foreach (BoothCategory::cases() as $case) {
            $categoryCounts[$case->value] = [
                'key' => $case->value,
                'label' => $case->label(),
                'icon' => $case->icon(),
                'count' => $aggregates['booth_categories'][$case->value] ?? 0,
                'badge' => $case->badgeClasses(),
            ];
        }

        // 2. 設備区分別集計 (FacilityType)
        $facilityCounts = [];
        foreach (FacilityType::cases() as $case) {
            $facilityCounts[$case->value] = [
                'key' => $case->value,
                'label' => $case->label(),
                'icon' => $case->icon(),
                'count' => $aggregates['facility_categories'][$case->value] ?? 0,
                'badge' => $case->badgeClasses(),
            ];
        }

        // 3. 所属エリア別のブース形態別集計
        $areaBoothTypes = [];
        foreach ($areas as $area) {
            $types = [];
            $areaTypeCounts = $aggregates['area_booth_types'][$area->id] ?? [];
            foreach (BoothType::cases() as $type) {
                $cnt = $areaTypeCounts[$type->value] ?? 0;
                if ($cnt > 0) {
                    $types[] = [
                        'value' => $type->value,
                        'label' => $type->label(),
                        'count' => $cnt,
                    ];
                }
            }
            $areaBoothTypes[] = [
                'area_id' => $area->id,
                'area_name' => $area->name,
                'is_active' => (bool) $area->is_active,
                'total_count' => $aggregates['area_totals'][$area->id] ?? 0,
                'types' => $types,
            ];
        }

        return [
            'total_booths' => $totalBooths,
            'total_facilities' => $totalFacilities,
            'total_items' => $totalItems,
            'categories' => $categoryCounts,
            'facilities' => $facilityCounts,
            'area_booth_types' => $areaBoothTypes,
        ];
    }

    /**
     * 配置アイテム一覧をページネーション取得
     *
     * @param  LayoutItemFilterDTO|array{item_type?: string|null, area_id?: int|string|null, category?: string|null, search?: string|null, sort?: string|null, direction?: string|null}  $filters
     */
    public function paginateItems(LayoutMap $map, LayoutItemFilterDTO|array $filters = [], ?int $perPage = null): LengthAwarePaginator
    {
        $filterArray = $filters instanceof LayoutItemFilterDTO ? $filters->toFilterArray() : $filters;
        $resolvedPerPage = $perPage ?? ($filters instanceof LayoutItemFilterDTO ? $filters->perPage : 50);

        return $this->layoutMapRepository->paginateItems($map, $filterArray, $resolvedPerPage);
    }

    /**
     * 配置アイテムを新規登録
     *
     * @param  array<string, mixed>  $data
     */
    public function storeItem(LayoutMap $map, array $data): LayoutItem
    {
        return $this->layoutMapRepository->storeItem($map, $data);
    }

    /**
     * 配置アイテムの更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateItem(LayoutItem $item, array $data): LayoutItem
    {
        return $this->layoutMapRepository->updateItem($item, $data);
    }

    /**
     * 配置アイテムの削除
     */
    public function deleteItem(LayoutItem $item): void
    {
        $this->layoutMapRepository->deleteItem($item);
    }

    /**
     * 全出展・出店者を取得（セレクトボックス用）
     */
    public function getVendorsForSelect(?Timetable $timetable = null): Collection
    {
        return $this->vendorRepository->getAllForSelect();
    }
}
