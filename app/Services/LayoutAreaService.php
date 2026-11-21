<?php

namespace App\Services;

use App\Models\LayoutArea;
use App\Models\Location;
use App\Repositories\Contracts\LayoutAreaRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LayoutAreaService
{
    public function __construct(
        protected LayoutAreaRepositoryInterface $layoutAreaRepository,
        protected LocationRepositoryInterface $locationRepository,
    ) {}

    /**
     * 会場エリアマスター一覧画面用データを取得
     *
     * @return array{
     *     layoutAreas: Collection<int, LayoutArea>,
     *     locations: Collection<int, Location>
     * }
     */
    public function getIndexViewData(): array
    {
        $layoutAreas = $this->layoutAreaRepository->getAllWithCountsAndLocation();
        $locations = $this->locationRepository->getActiveWithTypeOrdered();

        // 既存エリアに非稼働（無効）拠点が紐付いている場合も編集時に選択肢が欠落しないよう補完する
        $usedLocations = $layoutAreas
            ->pluck('location')
            ->filter(static fn (mixed $loc): bool => $loc instanceof Location)
            ->unique('id');

        foreach ($usedLocations as $usedLocation) {
            if (! $locations->contains('id', $usedLocation->id)) {
                $locations->push($usedLocation);
            }
        }

        return [
            'layoutAreas' => $layoutAreas,
            'locations' => $locations,
        ];
    }

    /**
     * 会場エリアマスターを新規登録
     *
     * @param  array<string, mixed>  $validated
     */
    public function createLayoutArea(array $validated): LayoutArea
    {
        $payload = [
            'name' => (string) $validated['name'],
            'location_id' => $validated['location_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'display_order' => $this->layoutAreaRepository->getMaxDisplayOrder() + 10,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];

        return $this->layoutAreaRepository->create($payload);
    }

    /**
     * 会場エリアマスターを更新
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateLayoutArea(LayoutArea $layoutArea, array $validated): LayoutArea
    {
        $payload = [
            'name' => (string) $validated['name'],
            'location_id' => array_key_exists('location_id', $validated) ? $validated['location_id'] : $layoutArea->location_id,
            'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $layoutArea->notes,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : (bool) $layoutArea->is_active,
        ];

        return $this->layoutAreaRepository->update($layoutArea, $payload);
    }

    /**
     * 会場エリアマスターを削除（使用中の場合はブロック）
     */
    public function deleteLayoutArea(LayoutArea $layoutArea): void
    {
        if ($this->layoutAreaRepository->countItems($layoutArea) > 0) {
            throw new \DomainException("会場エリア「{$layoutArea->name}」は配置データで使用中のため削除できません。不要な場合は無効化してください。");
        }

        $this->layoutAreaRepository->delete($layoutArea);
    }

    /**
     * 会場エリアの表示順を一括更新
     *
     * @param  array<int, int>  $layoutAreaIds
     */
    public function reorderLayoutAreas(array $layoutAreaIds): void
    {
        $this->layoutAreaRepository->reorder($layoutAreaIds);
    }
}
