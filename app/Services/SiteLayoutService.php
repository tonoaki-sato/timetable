<?php

namespace App\Services;

use App\Enums\BoothCategory;
use App\Enums\FacilityType;
use App\Models\LayoutBooth;
use App\Models\LayoutFacility;
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
        protected VendorRepositoryInterface $vendorRepository
    ) {}

    /**
     * タイムテーブルの配置図面を取得（詳細リレーション込み）
     */
    public function getLayoutDetails(Timetable $timetable): LayoutMap
    {
        $map = $this->layoutMapRepository->getOrCreateForTimetable($timetable);

        return $this->layoutMapRepository->findWithDetails($map->id);
    }

    /**
     * 配置計画の主要サマリー指標を集計
     *
     * @return array{
     *   total_booths: int,
     *   assigned_booths: int,
     *   unassigned_booths: int,
     *   categories: array<string, array{label: string, count: int, badge: string}>,
     *   facilities: array<string, array{label: string, icon: string, count: int}>,
     *   areas_summary: array<int, array{name: string, booths_count: int, facilities_count: int}>
     * }
     */
    public function getSummary(LayoutMap $map): array
    {
        $booths = $map->booths;
        $facilities = $map->facilities;

        $totalBooths = $booths->count();
        $assignedBooths = $booths->filter(fn (LayoutBooth $b) => ! empty($b->name) || $b->vendor_id !== null)->count();
        $unassignedBooths = $totalBooths - $assignedBooths;

        // カテゴリ別集計
        $categoryCounts = [];
        foreach (BoothCategory::cases() as $case) {
            $count = $booths->filter(fn (LayoutBooth $b) => $b->category === $case)->count();
            $categoryCounts[$case->value] = [
                'label' => $case->label(),
                'count' => $count,
                'badge' => $case->badgeClasses(),
            ];
        }

        // 設備種別集計
        $facilityCounts = [];
        foreach (FacilityType::cases() as $case) {
            $count = $facilities->filter(fn (LayoutFacility $f) => $f->facility_type === $case)->count();
            $facilityCounts[$case->value] = [
                'label' => $case->label(),
                'icon' => $case->icon(),
                'count' => $count,
            ];
        }

        // エリア別集計
        $areasSummary = [];
        foreach ($map->areas as $area) {
            $areasSummary[] = [
                'id' => $area->id,
                'name' => $area->name,
                'zone_code' => $area->zone_code,
                'booths_count' => $area->booths->count(),
                'facilities_count' => $area->facilities->count(),
            ];
        }

        return [
            'total_booths' => $totalBooths,
            'assigned_booths' => $assignedBooths,
            'unassigned_booths' => $unassignedBooths,
            'categories' => $categoryCounts,
            'facilities' => $facilityCounts,
            'areas_summary' => $areasSummary,
        ];
    }

    /**
     * 通りマップビュー用の構造化データを生成
     *
     * @return array{
     *   street_pairs: array<int, array{a: ?LayoutBooth, b: ?LayoutBooth, facilities_near: array<int, LayoutFacility>}>,
     *   parking_areas: array<int, array{area: mixed, booths: Collection, facilities: Collection}>
     * }
     */
    public function getStreetMapViewData(LayoutMap $map): array
    {
        $booths = $map->booths;
        $facilities = $map->facilities;

        // A列・B列のブース（メイン通り）
        $boothsA = $booths->filter(fn (LayoutBooth $b) => str_starts_with($b->booth_code, 'A'))->keyBy('booth_code');
        $boothsB = $booths->filter(fn (LayoutBooth $b) => str_starts_with($b->booth_code, 'B'))->keyBy('booth_code');

        // 北から南へ（番号の大きい順: 北側 A51〜A01, B30〜B01）
        // 通り沿いのペアを作成（A列51コマ、B列30コマを並行配置）
        $streetPairs = [];
        $maxIndex = 51; // A列は51コマ

        for ($i = $maxIndex; $i >= 1; $i--) {
            $codeA = 'A'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            // B列は30コマなので、按分または同等位置にマッピング (30 -> 1)
            $bIndex = (int) ceil(($i / 51) * 30);
            $codeB = 'B'.str_pad((string) $bIndex, 2, '0', STR_PAD_LEFT);

            // この行でB列が初出の場合のみB列に表示（重複回避）
            $prevBIndex = ($i < $maxIndex) ? (int) ceil((($i + 1) / 51) * 30) : -1;
            $boothB = ($bIndex !== $prevBIndex) ? $boothsB->get($codeB) : null;
            $boothA = $boothsA->get($codeA);

            // 近接設備（手洗い設備等）
            $nearFacilities = $facilities->filter(function (LayoutFacility $f) use ($i) {
                // 給水設備⑤（丸山浴槽前: i=50付近）
                if ($f->facility_code === 'W-05' && $i === 50) {
                    return true;
                }
                // 給水設備④（ガスト前: i=46付近）
                if ($f->facility_code === 'W-04' && $i === 46) {
                    return true;
                }
                // 救護所（作田邸付近: i=35付近）
                if ($f->facility_code === 'FA-01' && $i === 38) {
                    return true;
                }
                // 本陣・待機所（おもちゃのうえの向かい: i=42付近）
                if (in_array($f->facility_code, ['HQ-01', 'STBY-01'], true) && $i === 42) {
                    return true;
                }
                // 給水設備③（フローレンスパレス前: i=35付近）
                if ($f->facility_code === 'W-03' && $i === 35) {
                    return true;
                }
                // 給水設備②（ヴィラソレイユ付近: i=3付近）
                if ($f->facility_code === 'W-02' && $i === 3) {
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
        foreach ($map->areas->filter(fn ($a) => ! in_array($a->zone_code, ['ZONE_A', 'ZONE_B'])) as $area) {
            $parkingAreas[] = [
                'area' => $area,
                'booths' => $booths->filter(fn ($b) => $b->layout_area_id === $area->id),
                'facilities' => $facilities->filter(fn ($f) => $f->layout_area_id === $area->id),
            ];
        }

        return [
            'street_pairs' => $streetPairs,
            'parking_areas' => $parkingAreas,
        ];
    }

    /**
     * コマ割り一覧をページネーション取得
     */
    public function paginateBooths(LayoutMap $map, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->layoutMapRepository->paginateBooths($map, $filters, $perPage);
    }

    /**
     * コマ割り情報の更新
     */
    public function updateBooth(LayoutBooth $booth, array $data): LayoutBooth
    {
        return $this->layoutMapRepository->updateBooth($booth, $data);
    }

    /**
     * 現場設備一覧を取得
     */
    public function getFacilities(LayoutMap $map, array $filters = []): Collection
    {
        return $this->layoutMapRepository->getFacilities($map, $filters);
    }

    /**
     * 現場設備の更新
     */
    public function updateFacility(LayoutFacility $facility, array $data): LayoutFacility
    {
        return $this->layoutMapRepository->updateFacility($facility, $data);
    }

    /**
     * 現場設備の新規登録
     *
     * @param  array<string, mixed>  $data
     */
    public function storeFacility(LayoutMap $map, array $data): LayoutFacility
    {
        return $this->layoutMapRepository->storeFacility($map, $data);
    }

    /**
     * 現場設備の削除
     */
    public function deleteFacility(LayoutFacility $facility): void
    {
        $this->layoutMapRepository->deleteFacility($facility);
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

        return LayoutBooth::with(['area', 'vendor'])
            ->where('layout_map_id', $map->id)
            ->orderBy('display_order', 'asc')
            ->orderBy('booth_code', 'asc')
            ->get();
    }

    /**
     * 出店者の登録（初期ブース割当にも対応）
     *
     * @param  array<string, mixed>  $data
     */
    public function createVendor(Timetable $timetable, array $data, ?int $initialBoothId = null, ?string $initialCategory = null, ?string $initialProductItems = null): Vendor
    {
        $vendor = $this->vendorRepository->create($timetable, $data);

        if ($initialBoothId) {
            $booth = LayoutBooth::find($initialBoothId);
            if ($booth) {
                $boothUpdate = ['vendor_id' => $vendor->id];
                if ($initialCategory) {
                    $boothUpdate['category'] = $initialCategory;
                }
                if ($initialProductItems !== null) {
                    $boothUpdate['product_items'] = $initialProductItems;
                }
                $booth->update($boothUpdate);
            }
        }

        return $vendor->fresh(['booths.area']);
    }

    /**
     * 出店者の更新（ブース更新・新規コマ割当・解除に対応）
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array{id: int, category?: string, product_items?: string, unassign?: bool}>|null  $booths
     */
    public function updateVendor(Vendor $vendor, array $data, ?array $booths = null, ?int $addBoothId = null, ?string $addCategory = null, ?string $addProductItems = null): Vendor
    {
        $vendor = $this->vendorRepository->update($vendor, $data);

        // 既存ブースの更新 (category, product_items, 解除)
        if (is_array($booths)) {
            foreach ($booths as $bData) {
                if (empty($bData['id'])) {
                    continue;
                }
                $booth = LayoutBooth::where('id', $bData['id'])->where('vendor_id', $vendor->id)->first();
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
                    if (isset($bData['product_items'])) {
                        $update['product_items'] = $bData['product_items'];
                    }
                    if (! empty($update)) {
                        $booth->update($update);
                    }
                }
            }
        }

        // 新規ブースの追加割当
        if ($addBoothId) {
            $newBooth = LayoutBooth::find($addBoothId);
            if ($newBooth) {
                $addUpdate = ['vendor_id' => $vendor->id];
                if ($addCategory) {
                    $addUpdate['category'] = $addCategory;
                }
                if ($addProductItems !== null) {
                    $addUpdate['product_items'] = $addProductItems;
                }
                $newBooth->update($addUpdate);
            }
        }

        return $vendor->fresh(['booths.area']);
    }

    /**
     * 出店者の削除
     */
    public function deleteVendor(Vendor $vendor): bool
    {
        LayoutBooth::where('vendor_id', $vendor->id)->update(['vendor_id' => null]);

        return $this->vendorRepository->delete($vendor);
    }

    /**
     * 後方互換用エイリアス
     */
    public function paginateGozaStores(Timetable $timetable, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->paginateVendors($timetable, $filters, $perPage);
    }

    public function getGozaStoresForSelect(Timetable $timetable): Collection
    {
        return $this->getVendorsForSelect($timetable);
    }

    public function createGozaStore(Timetable $timetable, array $data): Vendor
    {
        return $this->createVendor($timetable, $data);
    }

    public function updateGozaStore(Vendor $store, array $data): Vendor
    {
        return $this->updateVendor($store, $data);
    }

    public function deleteGozaStore(Vendor $store): bool
    {
        return $this->deleteVendor($store);
    }
}
