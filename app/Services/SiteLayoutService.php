<?php

namespace App\Services;

use App\Enums\BoothCategory;
use App\Enums\BoothType;
use App\Enums\FacilityType;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Timetable;
use App\Repositories\Contracts\LayoutMapRepositoryInterface;
use App\Repositories\Contracts\VendorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SiteLayoutService
{
    public function __construct(
        protected LayoutMapRepositoryInterface $layoutMapRepository,
        protected VendorRepositoryInterface $vendorRepository,
    ) {}

    /**
     * タイムテーブルに紐づく現場配置図面詳細を取得
     */
    public function getLayoutDetails(Timetable $timetable): LayoutMap
    {
        $map = $this->layoutMapRepository->getOrCreateForTimetable($timetable);

        return $this->layoutMapRepository->findWithDetails($map->id) ?? $map;
    }

    /**
     * 配置計画サマリー集計データを生成
     *
     * @return array{
     *   total_booths: int,
     *   total_facilities: int,
     *   total_items: int,
     *   categories: array<string, array{label: string, icon: string, count: int, badge: string}>,
     *   facilities: array<string, array{label: string, icon: string, count: int, badge: string}>,
     *   area_booth_types: array<int, array{area_id: int, area_name: string, total_count: int, types: array<int, array{value: string, label: string, count: int}>}>
     * }
     */
    public function getSummary(LayoutMap $map): array
    {
        $items = $map->items;
        $booths = $items->filter(fn (LayoutItem $item) => $item->isBooth());
        $facilities = $items->filter(fn (LayoutItem $item) => $item->isFacility());

        $totalBooths = $booths->count();
        $totalFacilities = $facilities->count();
        $totalItems = $items->count();

        // 1. 出展区分別集計 (BoothCategory)
        $categoryCounts = [];
        foreach (BoothCategory::cases() as $case) {
            $count = $booths->filter(fn (LayoutItem $b) => $b->category === $case->value)->count();
            $categoryCounts[$case->value] = [
                'key' => $case->value,
                'label' => $case->label(),
                'icon' => $case->icon(),
                'count' => $count,
                'badge' => $case->badgeClasses(),
            ];
        }

        // 2. 設備区分別集計 (FacilityType)
        $facilityCounts = [];
        foreach (FacilityType::cases() as $case) {
            $count = $facilities->filter(fn (LayoutItem $f) => $f->category === $case->value)->count();
            $facilityCounts[$case->value] = [
                'key' => $case->value,
                'label' => $case->label(),
                'icon' => $case->icon(),
                'count' => $count,
                'badge' => $case->badgeClasses(),
            ];
        }

        // 3. 所属エリア別のブース形態別集計
        $areaBoothTypes = [];
        foreach ($map->areas as $area) {
            $areaItems = $items->filter(fn (LayoutItem $i) => $i->layout_area_id === $area->id);
            $types = [];
            foreach (BoothType::cases() as $type) {
                $cnt = $areaItems->filter(fn (LayoutItem $i) => $i->booth_type === $type->value)->count();
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
                'total_count' => $areaItems->count(),
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
     * @param  array{item_type?: string|null, area_id?: int|string|null, category?: string|null, search?: string|null, sort?: string|null, direction?: string|null}  $filters
     */
    public function paginateItems(LayoutMap $map, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return $this->layoutMapRepository->paginateItems($map, $filters, $perPage);
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
        return $this->vendorRepository->getAll();
    }
}
