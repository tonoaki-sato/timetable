<?php

namespace App\Services;

use App\Models\Lane;
use App\Presenters\LanePresenter;
use App\Repositories\Contracts\LaneRepositoryInterface;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class LaneService
{
    public function __construct(
        protected LaneRepositoryInterface $laneRepository,
    ) {}

    /**
     * 担当班マスター一覧画面用データを取得
     *
     * @return array{
     *     lanes: Collection<int, Lane>,
     *     colorOptions: array<string, string>,
     *     colorPaletteMap: array<string, array{btn: string, dot: string, badge: string}>,
     *     emojiPresets: array<int, string>
     * }
     */
    public function getIndexViewData(): array
    {
        return [
            'lanes' => $this->laneRepository->getAllWithCountsAndTimetables(),
            'colorOptions' => LanePresenter::COLOR_OPTIONS,
            'colorPaletteMap' => LanePresenter::COLOR_PALETTE_MAP,
            'emojiPresets' => LanePresenter::EMOJI_PRESETS,
        ];
    }

    /**
     * 担当班マスターを新規登録
     *
     * @param  array<string, mixed>  $validated
     */
    public function createLane(array $validated): Lane
    {
        $payload = [
            'name' => (string) $validated['name'],
            'color' => (string) ($validated['color'] ?? 'slate'),
            'icon' => $validated['icon'] ?? null,
            'description' => $validated['description'] ?? null,
            'display_order' => $this->laneRepository->getMaxDisplayOrder() + 10,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];

        return $this->laneRepository->create($payload);
    }

    /**
     * 担当班マスターを更新
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateLane(Lane $lane, array $validated): Lane
    {
        $payload = [
            'name' => (string) $validated['name'],
            'color' => (string) $validated['color'],
            'icon' => array_key_exists('icon', $validated) ? $validated['icon'] : $lane->icon,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $lane->description,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : (bool) $lane->is_active,
        ];

        return $this->laneRepository->update($lane, $payload);
    }

    /**
     * 担当班マスターを削除（運行計画で使用中の場合はブロック）
     */
    public function deleteLane(Lane $lane): void
    {
        if ($this->laneRepository->countTimetableLanes($lane) > 0) {
            throw new DomainException("担当班「{$lane->name}」は運行計画で使用中のため削除できません。不要な場合は無効化してください。");
        }

        $this->laneRepository->delete($lane);
    }

    /**
     * 担当班マスターの表示順を一括更新
     *
     * @param  array<int, int>  $laneIds
     */
    public function reorderLanes(array $laneIds): void
    {
        $this->laneRepository->reorder($laneIds);
    }
}
