<?php

namespace App\Services;

use App\Enums\BoothCategory;
use App\Enums\BoothType;
use App\Enums\FacilityType;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Timetable;
use App\Models\Vendor;
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
     * 通りマップビュー用の構造化データを生成
     *
     * @return array{
     *   street_pairs: array<int, array{a: ?LayoutItem, b: ?LayoutItem, facilities_near: array<int, LayoutItem>}>,
     *   parking_areas: array<int, array{area: mixed, booths: Collection, facilities: Collection}>
     * }
     */
    public function getStreetMapViewData(LayoutMap $map): array
    {
        $items = $map->items;
        $booths = $items->filter(fn (LayoutItem $item) => $item->isBooth());
        $facilities = $items->filter(fn (LayoutItem $item) => $item->isFacility());

        // A列・B列のブース（メイン通り）
        $boothsA = $booths->filter(fn (LayoutItem $b) => str_starts_with($b->code, 'A'))->keyBy('code');
        $boothsB = $booths->filter(fn (LayoutItem $b) => str_starts_with($b->code, 'B'))->keyBy('code');

        // 北から南へ
        $streetPairs = [];
        $maxIndex = 51;

        for ($i = $maxIndex; $i >= 1; $i--) {
            $codeA = 'A'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $bIndex = (int) ceil(($i / 51) * 30);
            $codeB = 'B'.str_pad((string) $bIndex, 2, '0', STR_PAD_LEFT);

            $prevBIndex = ($i < $maxIndex) ? (int) ceil((($i + 1) / 51) * 30) : -1;
            $boothB = ($bIndex !== $prevBIndex) ? $boothsB->get($codeB) : null;
            $boothA = $boothsA->get($codeA);

            // 近接設備（手洗い設備等）
            $nearFacilities = $facilities->filter(function (LayoutItem $f) use ($i) {
                if ($f->code === 'W-05' && $i === 50) {
                    return true;
                }
                if ($f->code === 'W-04' && $i === 46) {
                    return true;
                }
                if ($f->code === 'W-03' && $i === 33) {
                    return true;
                }
                if ($f->code === 'W-02' && $i === 17) {
                    return true;
                }
                if ($f->code === 'W-01' && $i === 1) {
                    return true;
                }

                return false;
            })->values()->all();

            $streetPairs[] = [
                'index' => $i,
                'a' => $boothA,
                'b' => $boothB,
                'facilities_near' => $nearFacilities,
            ];
        }

        // 駐車場・特設エリア
        $parkingAreas = [];
        foreach ($map->areas as $area) {
            if (str_starts_with($area->zone_code, 'AREA_') || $area->zone_code === 'KIDS_VILLAGE') {
                $areaItems = $area->items;
                $parkingAreas[] = [
                    'area' => $area,
                    'booths' => $areaItems->where('item_type', 'booth'),
                    'facilities' => $areaItems->where('item_type', 'facility'),
                ];
            }
        }

        return [
            'street_pairs' => $streetPairs,
            'parking_areas' => $parkingAreas,
        ];
    }

    /**
     * 配置アイテム一覧をページネーション取得
     *
     * @param  array{item_type?: string|null, area_id?: int|string|null, category?: string|null, search?: string|null}  $filters
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
     * 出店者一覧をページネーション取得
     *
     * @param  array{category?: string|null, search?: string|null}  $filters
     */
    public function paginateVendors(Timetable $timetable, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->vendorRepository->paginateForTimetable($timetable, $filters, $perPage);
    }

    /**
     * 全出展・出店者を取得（セレクトボックス用）
     */
    public function getVendorsForSelect(?Timetable $timetable = null): Collection
    {
        return $this->vendorRepository->getAll();
    }

    /**
     * タイムテーブルの全ブースを取得（セレクトボックス用）
     */
    public function getBoothsForSelect(Timetable $timetable): Collection
    {
        $map = $this->layoutMapRepository->getOrCreateForTimetable($timetable);

        return LayoutItem::with(['area', 'vendor'])
            ->where('layout_map_id', $map->id)
            ->where('item_type', 'booth')
            ->orderBy('display_order', 'asc')
            ->orderBy('code', 'asc')
            ->get();
    }

    /**
     * 出店者の登録（初期ブース割当にも対応）
     *
     * @param  array<string, mixed>  $data
     */
    public function createVendor(Timetable $timetable, array $data, ?int $initialBoothId = null, ?string $initialCategory = null, ?string $initialNotes = null): Vendor
    {
        $vendor = $this->vendorRepository->create($timetable, $data);

        if ($initialBoothId) {
            $booth = LayoutItem::find($initialBoothId);
            if ($booth && $booth->isBooth()) {
                $boothUpdate = ['vendor_id' => $vendor->id];
                if ($initialCategory) {
                    $boothUpdate['category'] = $initialCategory;
                }
                if ($initialNotes !== null) {
                    $boothUpdate['notes'] = $initialNotes;
                }
                $booth->update($boothUpdate);
            }
        }

        return $vendor->fresh(['items.area']);
    }

    /**
     * 出店者の更新（ブース更新・新規コマ割当・解除に対応）
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array{id: int, category?: string, notes?: string, unassign?: bool}>|null  $booths
     */
    public function updateVendor(Vendor $vendor, array $data, ?array $booths = null, ?int $addBoothId = null, ?string $addCategory = null, ?string $addNotes = null): Vendor
    {
        $vendor = $this->vendorRepository->update($vendor, $data);

        if (is_array($booths)) {
            foreach ($booths as $bData) {
                if (empty($bData['id'])) {
                    continue;
                }
                $booth = LayoutItem::where('id', $bData['id'])->where('vendor_id', $vendor->id)->first();
                if (! $booth) {
                    continue;
                }
                if (! empty($bData['unassign'])) {
                    $booth->update(['vendor_id' => null]);
                } else {
                    $update = [];
                    if (isset($bData['category'])) {
                        $update['category'] = $bData['category'];
                    }
                    if (isset($bData['notes'])) {
                        $update['notes'] = $bData['notes'];
                    }
                    if (! empty($update)) {
                        $booth->update($update);
                    }
                }
            }
        }

        if ($addBoothId) {
            $newBooth = LayoutItem::find($addBoothId);
            if ($newBooth && $newBooth->isBooth()) {
                $addUpdate = ['vendor_id' => $vendor->id];
                if ($addCategory) {
                    $addUpdate['category'] = $addCategory;
                }
                if ($addNotes !== null) {
                    $addUpdate['notes'] = $addNotes;
                }
                $newBooth->update($addUpdate);
            }
        }

        return $vendor->fresh(['items.area']);
    }

    /**
     * 出店者の削除
     */
    public function deleteVendor(Vendor $vendor): bool
    {
        LayoutItem::where('vendor_id', $vendor->id)->update(['vendor_id' => null]);

        return $this->vendorRepository->delete($vendor);
    }
}
