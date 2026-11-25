<?php

namespace App\Services;

use App\DTOs\ConeMap\ConeMapEditorDataDTO;
use App\DTOs\ConeMap\ConeMapSummaryDTO;
use App\Enums\BarricadeType;
use App\Enums\ConeBarStyle;
use App\Enums\ConeCalloutColor;
use App\Enums\ConeColor;
use App\Models\ConeMapBar;
use App\Models\ConeMapBarricade;
use App\Models\ConeMapCallout;
use App\Models\ConeMapCone;
use App\Models\Equipment;
use App\Models\SiteBaseMap;
use App\Models\Timetable;
use App\Repositories\Contracts\ConeMapRepositoryInterface;
use App\Repositories\Contracts\SiteMapRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConeMapService
{
    public function __construct(
        protected ConeMapRepositoryInterface $repository,
        protected SiteMapRepositoryInterface $siteMapRepository,
    ) {}

    /**
     * エディタ画面用の初期データを一括取得する
     */
    public function getEditorData(Timetable $timetable): ConeMapEditorDataDTO
    {
        $siteBaseMap = $this->siteMapRepository->resolveBaseMapForTimetable($timetable);
        $availableBaseMaps = $this->siteMapRepository->getActiveBaseMaps();

        if (! $availableBaseMaps->contains('id', $siteBaseMap->id)) {
            $availableBaseMaps = (new Collection([$siteBaseMap]))->merge($availableBaseMaps);
        }

        $cones = $this->repository->getCones($timetable, $siteBaseMap);
        $bars = $this->repository->getBars($timetable, $siteBaseMap);
        $barricades = $this->repository->getBarricades($timetable, $siteBaseMap);
        $callouts = $this->repository->getCallouts($timetable, $siteBaseMap);
        $boothPlacements = $this->siteMapRepository->getPlacements($timetable, $siteBaseMap);
        $summary = $this->buildSummary($cones, $bars, $barricades, $callouts);

        return new ConeMapEditorDataDTO(
            siteBaseMap: $siteBaseMap,
            availableBaseMaps: $availableBaseMaps,
            cones: $cones,
            bars: $bars,
            barricades: $barricades,
            callouts: $callouts,
            boothPlacements: $boothPlacements,
            summary: $summary,
        );
    }

    /**
     * API 返却用の全配置データおよびサマリーを取得する
     *
     * @return array{
     *     cones: Collection<int, ConeMapCone>,
     *     bars: Collection<int, ConeMapBar>,
     *     barricades: Collection<int, ConeMapBarricade>,
     *     callouts: Collection<int, ConeMapCallout>,
     *     summary: array<string, mixed>
     * }
     */
    public function getMapData(Timetable $timetable, SiteBaseMap $siteBaseMap): array
    {
        $cones = $this->repository->getCones($timetable, $siteBaseMap);
        $bars = $this->repository->getBars($timetable, $siteBaseMap);
        $barricades = $this->repository->getBarricades($timetable, $siteBaseMap);
        $callouts = $this->repository->getCallouts($timetable, $siteBaseMap);
        $summary = $this->buildSummary($cones, $bars, $barricades, $callouts);

        return [
            'cones' => $cones,
            'bars' => $bars,
            'barricades' => $barricades,
            'callouts' => $callouts,
            'summary' => $summary->toArray(),
        ];
    }

    /**
     * 配置データから集計サマリーおよび機材在庫比較を算出する
     *
     * @param  Collection<int, ConeMapCone>  $cones
     * @param  Collection<int, ConeMapBar>  $bars
     * @param  Collection<int, ConeMapBarricade>  $barricades
     * @param  Collection<int, ConeMapCallout>  $callouts
     */
    public function buildSummary(
        Collection $cones,
        Collection $bars,
        Collection $barricades,
        Collection $callouts,
    ): ConeMapSummaryDTO {
        $adjacency = [];
        foreach ($cones as $cone) {
            $adjacency[$cone->id] = [];
        }
        foreach ($bars as $bar) {
            if (isset($adjacency[$bar->start_cone_id], $adjacency[$bar->end_cone_id])) {
                $adjacency[$bar->start_cone_id][] = $bar->end_cone_id;
                $adjacency[$bar->end_cone_id][] = $bar->start_cone_id;
            }
        }

        $standaloneCount = 0;
        $linkedCount = 0;
        $linkedGroupsCount = 0;
        $visited = [];

        foreach ($cones as $cone) {
            $cid = $cone->id;
            if (isset($visited[$cid])) {
                continue;
            }

            if ($adjacency[$cid] === []) {
                $visited[$cid] = true;
                $standaloneCount++;

                continue;
            }

            $queue = [$cid];
            $visited[$cid] = true;
            $componentSize = 0;

            while ($queue !== []) {
                $curr = array_shift($queue);
                $componentSize++;
                foreach ($adjacency[$curr] as $next) {
                    if (! isset($visited[$next])) {
                        $visited[$next] = true;
                        $queue[] = $next;
                    }
                }
            }

            if ($componentSize >= 2) {
                $linkedGroupsCount++;
                $linkedCount += $componentSize;
            } else {
                $standaloneCount += $componentSize;
            }
        }

        $colorBreakdown = [];
        foreach (ConeColor::cases() as $case) {
            $colorBreakdown[$case->value] = 0;
        }
        foreach ($cones as $cone) {
            $key = (string) $cone->color;
            $colorBreakdown[$key] = ($colorBreakdown[$key] ?? 0) + 1;
        }

        $barStyleBreakdown = [];
        foreach (ConeBarStyle::cases() as $case) {
            $barStyleBreakdown[$case->value] = 0;
        }
        foreach ($bars as $bar) {
            $key = (string) $bar->bar_style;
            $barStyleBreakdown[$key] = ($barStyleBreakdown[$key] ?? 0) + 1;
        }

        $conesCount = $cones->count();
        $weightsCount = $conesCount; // ウェイトは常にカラーコーンと同数
        $barsCount = $bars->count();
        $typeACount = $barricades->where('barricade_type', BarricadeType::TypeA->value)->count();
        $resinPipeCount = $barricades->where('barricade_type', BarricadeType::ResinPipe->value)->count();

        $inventory = $this->buildInventoryComparison(
            conesCount: $conesCount,
            weightsCount: $weightsCount,
            barsCount: $barsCount,
            typeACount: $typeACount,
            resinPipeCount: $resinPipeCount,
        );

        return new ConeMapSummaryDTO(
            conesCount: $conesCount,
            standaloneConesCount: $standaloneCount,
            linkedConesCount: $linkedCount,
            linkedGroupsCount: $linkedGroupsCount,
            weightsCount: $weightsCount,
            barsCount: $barsCount,
            barricadesTypeACount: $typeACount,
            barricadesResinPipeCount: $resinPipeCount,
            calloutsCount: $callouts->count(),
            colorBreakdown: $colorBreakdown,
            barStyleBreakdown: $barStyleBreakdown,
            inventory: $inventory,
        );
    }

    /**
     * 運行日・白地図の現在のサマリーのみを再計算する
     */
    public function getSummaryFor(Timetable $timetable, SiteBaseMap $siteBaseMap): ConeMapSummaryDTO
    {
        return $this->buildSummary(
            $this->repository->getCones($timetable, $siteBaseMap),
            $this->repository->getBars($timetable, $siteBaseMap),
            $this->repository->getBarricades($timetable, $siteBaseMap),
            $this->repository->getCallouts($timetable, $siteBaseMap),
        );
    }

    /**
     * カラーコーンを単独配置する（ウェイト1個とセット）
     *
     * @param  array<string, mixed>  $data
     */
    public function createCone(Timetable $timetable, SiteBaseMap $siteBaseMap, array $data): ConeMapCone
    {
        $cone = new ConeMapCone([
            'timetable_id' => $timetable->id,
            'site_base_map_id' => $siteBaseMap->id,
            'pos_x' => round((float) $data['pos_x'], 2),
            'pos_y' => round((float) $data['pos_y'], 2),
            'color' => (string) ($data['color'] ?? ConeColor::Red->value),
            'label' => $data['label'] ?? null,
            'group_key' => $data['group_key'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->repository->saveCone($cone);
    }

    /**
     * カラーコーンを更新し、紐づく吹き出しアンカー座標を追従同期する
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCone(ConeMapCone $cone, array $data): ConeMapCone
    {
        return DB::transaction(function () use ($cone, $data): ConeMapCone {
            $posChanged = false;

            if (array_key_exists('pos_x', $data) && $data['pos_x'] !== null) {
                $cone->pos_x = round((float) $data['pos_x'], 2);
                $posChanged = true;
            }
            if (array_key_exists('pos_y', $data) && $data['pos_y'] !== null) {
                $cone->pos_y = round((float) $data['pos_y'], 2);
                $posChanged = true;
            }
            if (isset($data['color'])) {
                $cone->color = (string) $data['color'];
            }
            if (array_key_exists('label', $data)) {
                $cone->label = $data['label'] !== '' ? $data['label'] : null;
            }
            if (array_key_exists('notes', $data)) {
                $cone->notes = $data['notes'];
            }

            $saved = $this->repository->saveCone($cone);

            if ($posChanged) {
                $this->repository->syncCalloutAnchorsForCone($saved);
            }

            return $saved;
        });
    }

    /**
     * 複数のカラーコーンを一括更新し、紐づく吹き出しアンカーも同期する
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<int, ConeMapCone>
     */
    public function bulkUpdateCones(Timetable $timetable, SiteBaseMap $siteBaseMap, array $items): Collection
    {
        $ids = collect($items)->pluck('id')->filter()->map(static fn (mixed $id): int => (int) $id)->unique()->values()->all();
        $existingMap = $this->repository->findConesByIds($timetable, $siteBaseMap, $ids)->keyBy('id');

        DB::transaction(function () use ($items, $existingMap): void {
            foreach ($items as $itemData) {
                $id = (int) ($itemData['id'] ?? 0);
                /** @var ConeMapCone|null $cone */
                $cone = $existingMap->get($id);
                if (! $cone) {
                    continue;
                }
                $this->updateCone($cone, $itemData);
            }
        });

        return $this->repository->getCones($timetable, $siteBaseMap);
    }

    /**
     * カラーコーンを削除する（接続中のコーンバーも連動削除）
     *
     * @return array{deleted_bar_ids: array<int, int>}
     */
    public function deleteCone(ConeMapCone $cone): array
    {
        return DB::transaction(function () use ($cone): array {
            $connectedBars = $this->repository->getBarsForCone($cone);
            $deletedBarIds = $connectedBars->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();

            $this->repository->deleteBarsForCone($cone);
            $this->repository->deleteCone($cone);

            return [
                'deleted_bar_ids' => $deletedBarIds,
            ];
        });
    }

    /**
     * 既存の2本のカラーコーンをコーンバーで連結する（重複・閉路禁止によりバー本数＝コーン数−1を保証）
     *
     * @param  array<string, mixed>  $data
     */
    public function createBar(Timetable $timetable, SiteBaseMap $siteBaseMap, array $data): ConeMapBar
    {
        $startId = (int) ($data['start_cone_id'] ?? 0);
        $endId = (int) ($data['end_cone_id'] ?? 0);

        if ($startId <= 0 || $endId <= 0 || $startId === $endId) {
            throw new \DomainException('コーンバーは異なる2個のカラーコーン間にのみ連結できます。');
        }

        $startCone = $this->repository->findConeById($timetable, $siteBaseMap, $startId);
        $endCone = $this->repository->findConeById($timetable, $siteBaseMap, $endId);

        if (! $startCone || ! $endCone) {
            throw new \DomainException('指定されたカラーコーンがこの白地図上に存在しません。');
        }

        $existingBars = $this->repository->getBars($timetable, $siteBaseMap);

        foreach ($existingBars as $existing) {
            $pairMatches = ($existing->start_cone_id === $startId && $existing->end_cone_id === $endId)
                || ($existing->start_cone_id === $endId && $existing->end_cone_id === $startId);
            if ($pairMatches) {
                throw new \DomainException('この2個のカラーコーンは既にコーンバーで連結されています。');
            }
        }

        if ($this->areConesConnected($existingBars, $startId, $endId)) {
            throw new \DomainException('ループ連結はできません（連結バー本数はカラーコーン個数−1本までとなります）。');
        }

        $bar = new ConeMapBar([
            'timetable_id' => $timetable->id,
            'site_base_map_id' => $siteBaseMap->id,
            'start_cone_id' => $startId,
            'end_cone_id' => $endId,
            'bar_style' => (string) ($data['bar_style'] ?? ConeBarStyle::YellowBlack->value),
            'length_cm' => ConeMapBar::DEFAULT_LENGTH_CM,
            'diameter_cm' => ConeMapBar::DEFAULT_DIAMETER_CM,
            'group_key' => $data['group_key'] ?? ($startCone->group_key ?: $endCone->group_key),
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->repository->saveBar($bar);
    }

    /**
     * コーンバーの属性を更新する
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBar(ConeMapBar $bar, array $data): ConeMapBar
    {
        if (isset($data['bar_style'])) {
            $bar->bar_style = (string) $data['bar_style'];
        }
        if (array_key_exists('notes', $data)) {
            $bar->notes = $data['notes'];
        }

        return $this->repository->saveBar($bar);
    }

    /**
     * コーンバーを削除する
     */
    public function deleteBar(ConeMapBar $bar): bool
    {
        return $this->repository->deleteBar($bar);
    }

    /**
     * 始点〜終点間に複数カラーコーン＋連結コーンバーを一括生成する（バー本数＝生成コーン連結数−1）
     *
     * @param  array<string, mixed>  $data
     * @return array{cones: array<int, ConeMapCone>, bars: array<int, ConeMapBar>}
     */
    public function createConeLine(Timetable $timetable, SiteBaseMap $siteBaseMap, array $data): array
    {
        $scaleRatio = max(0.1, (float) $siteBaseMap->scale_ratio);
        $startX = (float) $data['start_x'];
        $startY = (float) $data['start_y'];
        $endX = (float) $data['end_x'];
        $endY = (float) $data['end_y'];
        $color = (string) ($data['color'] ?? ConeColor::Red->value);
        $barStyle = (string) ($data['bar_style'] ?? ConeBarStyle::YellowBlack->value);
        $pitchMode = (string) ($data['pitch_mode'] ?? 'even_max_2m');
        $labelPrefix = isset($data['label_prefix']) && trim((string) $data['label_prefix']) !== ''
            ? trim((string) $data['label_prefix'])
            : null;
        $startConeId = isset($data['start_cone_id']) && $data['start_cone_id'] !== null
            ? (int) $data['start_cone_id']
            : null;

        $existingStartCone = null;
        if ($startConeId !== null) {
            $existingStartCone = $this->repository->findConeById($timetable, $siteBaseMap, $startConeId);
            if (! $existingStartCone) {
                throw new \DomainException('延長元のカラーコーンが存在しません。');
            }
            $startX = (float) $existingStartCone->pos_x;
            $startY = (float) $existingStartCone->pos_y;
        }

        $dx = $endX - $startX;
        $dy = $endY - $startY;
        $distPt = hypot($dx, $dy);
        $distM = $distPt / $scaleRatio;
        $barLenPt = 2.0 * $scaleRatio;

        if ($distPt < 0.5) {
            $ux = 1.0;
            $uy = 0.0;
            $distM = 2.0;
            $dx = $barLenPt;
            $dy = 0.0;
        } else {
            $ux = $dx / $distPt;
            $uy = $dy / $distPt;
        }

        $points = [];
        if ($pitchMode === 'fixed_2m') {
            $spans = max(1, (int) round($distM / 2.0));
            $spans = min($spans, 40);
            for ($i = 0; $i <= $spans; $i++) {
                $points[] = [
                    'x' => round($startX + $ux * ($i * $barLenPt), 2),
                    'y' => round($startY + $uy * ($i * $barLenPt), 2),
                ];
            }
        } else {
            $spans = max(1, (int) ceil($distM / 2.0));
            $spans = min($spans, 40);
            for ($i = 0; $i <= $spans; $i++) {
                $t = $i / $spans;
                $points[] = [
                    'x' => round($startX + $dx * $t, 2),
                    'y' => round($startY + $dy * $t, 2),
                ];
            }
        }

        $groupKey = $existingStartCone?->group_key ?: (string) Str::uuid();

        return DB::transaction(function () use (
            $timetable,
            $siteBaseMap,
            $points,
            $color,
            $barStyle,
            $labelPrefix,
            $groupKey,
            $existingStartCone
        ): array {
            $chainCones = [];
            $createdCones = [];

            foreach ($points as $idx => $pt) {
                if ($idx === 0 && $existingStartCone !== null) {
                    if ($existingStartCone->group_key === null) {
                        $existingStartCone->group_key = $groupKey;
                        $this->repository->saveCone($existingStartCone);
                    }
                    $chainCones[] = $existingStartCone;

                    continue;
                }

                $cone = new ConeMapCone([
                    'timetable_id' => $timetable->id,
                    'site_base_map_id' => $siteBaseMap->id,
                    'pos_x' => $pt['x'],
                    'pos_y' => $pt['y'],
                    'color' => $color,
                    'label' => $labelPrefix !== null ? sprintf('%s%d', $labelPrefix, $idx + 1) : null,
                    'group_key' => $groupKey,
                ]);
                $savedCone = $this->repository->saveCone($cone);
                $chainCones[] = $savedCone;
                $createdCones[] = $savedCone;
            }

            $createdBars = [];
            $chainCount = count($chainCones);
            for ($i = 0; $i < $chainCount - 1; $i++) {
                $bar = new ConeMapBar([
                    'timetable_id' => $timetable->id,
                    'site_base_map_id' => $siteBaseMap->id,
                    'start_cone_id' => $chainCones[$i]->id,
                    'end_cone_id' => $chainCones[$i + 1]->id,
                    'bar_style' => $barStyle,
                    'length_cm' => ConeMapBar::DEFAULT_LENGTH_CM,
                    'diameter_cm' => ConeMapBar::DEFAULT_DIAMETER_CM,
                    'group_key' => $groupKey,
                ]);
                $createdBars[] = $this->repository->saveBar($bar);
            }

            return [
                'cones' => $createdCones,
                'bars' => $createdBars,
            ];
        });
    }

    /**
     * バリケード（A型 / 樹脂製単管）を配置する
     *
     * @param  array<string, mixed>  $data
     */
    public function createBarricade(Timetable $timetable, SiteBaseMap $siteBaseMap, array $data): ConeMapBarricade
    {
        $rotation = (int) ($data['rotation'] ?? 0);
        $normalizedRot = (($rotation % 360) + 360) % 360;

        $barricade = new ConeMapBarricade([
            'timetable_id' => $timetable->id,
            'site_base_map_id' => $siteBaseMap->id,
            'barricade_type' => (string) ($data['barricade_type'] ?? BarricadeType::TypeA->value),
            'pos_x' => round((float) $data['pos_x'], 2),
            'pos_y' => round((float) $data['pos_y'], 2),
            'rotation' => $normalizedRot,
            'label' => $data['label'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->repository->saveBarricade($barricade);
    }

    /**
     * バリケードを更新し、紐づく吹き出しアンカー座標を追従同期する
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBarricade(ConeMapBarricade $barricade, array $data): ConeMapBarricade
    {
        return DB::transaction(function () use ($barricade, $data): ConeMapBarricade {
            $posChanged = false;

            if (isset($data['barricade_type'])) {
                $barricade->barricade_type = (string) $data['barricade_type'];
            }
            if (array_key_exists('pos_x', $data) && $data['pos_x'] !== null) {
                $barricade->pos_x = round((float) $data['pos_x'], 2);
                $posChanged = true;
            }
            if (array_key_exists('pos_y', $data) && $data['pos_y'] !== null) {
                $barricade->pos_y = round((float) $data['pos_y'], 2);
                $posChanged = true;
            }
            if (array_key_exists('rotation', $data) && $data['rotation'] !== null) {
                $rot = (int) $data['rotation'];
                $barricade->rotation = (($rot % 360) + 360) % 360;
            }
            if (array_key_exists('label', $data)) {
                $barricade->label = $data['label'] !== '' ? $data['label'] : null;
            }
            if (array_key_exists('notes', $data)) {
                $barricade->notes = $data['notes'];
            }

            $saved = $this->repository->saveBarricade($barricade);

            if ($posChanged) {
                $this->repository->syncCalloutAnchorsForBarricade($saved);
            }

            return $saved;
        });
    }

    /**
     * 複数のバリケードを一括更新する
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<int, ConeMapBarricade>
     */
    public function bulkUpdateBarricades(Timetable $timetable, SiteBaseMap $siteBaseMap, array $items): Collection
    {
        $ids = collect($items)->pluck('id')->filter()->map(static fn (mixed $id): int => (int) $id)->unique()->values()->all();
        $existingMap = $this->repository->findBarricadesByIds($timetable, $siteBaseMap, $ids)->keyBy('id');

        DB::transaction(function () use ($items, $existingMap): void {
            foreach ($items as $itemData) {
                $id = (int) ($itemData['id'] ?? 0);
                /** @var ConeMapBarricade|null $barricade */
                $barricade = $existingMap->get($id);
                if (! $barricade) {
                    continue;
                }
                $this->updateBarricade($barricade, $itemData);
            }
        });

        return $this->repository->getBarricades($timetable, $siteBaseMap);
    }

    /**
     * バリケードを削除する
     */
    public function deleteBarricade(ConeMapBarricade $barricade): bool
    {
        return $this->repository->deleteBarricade($barricade);
    }

    /**
     * 吹き出し（注意事項）を作成する
     *
     * @param  array<string, mixed>  $data
     */
    public function createCallout(Timetable $timetable, SiteBaseMap $siteBaseMap, array $data): ConeMapCallout
    {
        $targetConeId = ! empty($data['target_cone_id']) ? (int) $data['target_cone_id'] : null;
        $targetBarricadeId = ! empty($data['target_barricade_id']) ? (int) $data['target_barricade_id'] : null;

        $anchorX = isset($data['anchor_x']) ? (float) $data['anchor_x'] : 0.0;
        $anchorY = isset($data['anchor_y']) ? (float) $data['anchor_y'] : 0.0;

        if ($targetConeId !== null) {
            $cone = $this->repository->findConeById($timetable, $siteBaseMap, $targetConeId);
            if (! $cone) {
                throw new \DomainException('紐づけ対象のカラーコーンが存在しません。');
            }
            if (! isset($data['anchor_x'])) {
                $anchorX = (float) $cone->pos_x;
            }
            if (! isset($data['anchor_y'])) {
                $anchorY = (float) $cone->pos_y;
            }
            $targetBarricadeId = null;
        } elseif ($targetBarricadeId !== null) {
            $barricade = $this->repository->findBarricadeById($timetable, $siteBaseMap, $targetBarricadeId);
            if (! $barricade) {
                throw new \DomainException('紐づけ対象のバリケードが存在しません。');
            }
            if (! isset($data['anchor_x'])) {
                $anchorX = (float) $barricade->pos_x;
            }
            if (! isset($data['anchor_y'])) {
                $anchorY = (float) $barricade->pos_y;
            }
        }

        $callout = new ConeMapCallout([
            'timetable_id' => $timetable->id,
            'site_base_map_id' => $siteBaseMap->id,
            'target_cone_id' => $targetConeId,
            'target_barricade_id' => $targetBarricadeId,
            'anchor_x' => round($anchorX, 2),
            'anchor_y' => round($anchorY, 2),
            'box_x' => round((float) ($data['box_x'] ?? ($anchorX + 14.0)), 2),
            'box_y' => round((float) ($data['box_y'] ?? ($anchorY - 34.0)), 2),
            'box_width' => round((float) ($data['box_width'] ?? 76.0), 2),
            'box_height' => round((float) ($data['box_height'] ?? 28.0), 2),
            'title' => isset($data['title']) && trim((string) $data['title']) !== '' ? trim((string) $data['title']) : null,
            'body' => (string) $data['body'],
            'color_theme' => (string) ($data['color_theme'] ?? ConeCalloutColor::Amber->value),
            'font_size' => isset($data['font_size']) && $data['font_size'] !== '' ? (float) $data['font_size'] : null,
        ]);

        return $this->repository->saveCallout($callout);
    }

    /**
     * 吹き出し（注意事項）を更新する
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCallout(ConeMapCallout $callout, array $data): ConeMapCallout
    {
        if (array_key_exists('target_cone_id', $data)) {
            if ($data['target_cone_id'] !== null && $data['target_cone_id'] !== '') {
                $cone = $this->repository->findConeById($callout->timetable, $callout->siteBaseMap, (int) $data['target_cone_id']);
                if (! $cone) {
                    throw new \DomainException('紐づけ対象のカラーコーンが存在しません。');
                }
                $callout->target_cone_id = $cone->id;
                $callout->target_barricade_id = null;
            } else {
                $callout->target_cone_id = null;
            }
        }
        if (array_key_exists('target_barricade_id', $data)) {
            if ($data['target_barricade_id'] !== null && $data['target_barricade_id'] !== '') {
                $barricade = $this->repository->findBarricadeById($callout->timetable, $callout->siteBaseMap, (int) $data['target_barricade_id']);
                if (! $barricade) {
                    throw new \DomainException('紐づけ対象のバリケードが存在しません。');
                }
                $callout->target_barricade_id = $barricade->id;
                $callout->target_cone_id = null;
            } else {
                $callout->target_barricade_id = null;
            }
        }
        if (array_key_exists('anchor_x', $data) && $data['anchor_x'] !== null) {
            $callout->anchor_x = round((float) $data['anchor_x'], 2);
        }
        if (array_key_exists('anchor_y', $data) && $data['anchor_y'] !== null) {
            $callout->anchor_y = round((float) $data['anchor_y'], 2);
        }
        if (array_key_exists('box_x', $data) && $data['box_x'] !== null) {
            $callout->box_x = round((float) $data['box_x'], 2);
        }
        if (array_key_exists('box_y', $data) && $data['box_y'] !== null) {
            $callout->box_y = round((float) $data['box_y'], 2);
        }
        if (array_key_exists('box_width', $data) && $data['box_width'] !== null) {
            $callout->box_width = round((float) $data['box_width'], 2);
        }
        if (array_key_exists('box_height', $data) && $data['box_height'] !== null) {
            $callout->box_height = round((float) $data['box_height'], 2);
        }
        if (array_key_exists('title', $data)) {
            $callout->title = $data['title'] !== null && trim((string) $data['title']) !== '' ? trim((string) $data['title']) : null;
        }
        if (array_key_exists('body', $data) && $data['body'] !== null) {
            $callout->body = (string) $data['body'];
        }
        if (isset($data['color_theme'])) {
            $callout->color_theme = (string) $data['color_theme'];
        }
        if (array_key_exists('font_size', $data)) {
            $callout->font_size = $data['font_size'] !== null && $data['font_size'] !== '' ? (float) $data['font_size'] : null;
        }

        return $this->repository->saveCallout($callout);
    }

    /**
     * 吹き出しを削除する
     */
    public function deleteCallout(ConeMapCallout $callout): bool
    {
        return $this->repository->deleteCallout($callout);
    }

    /**
     * 白地図上のカラーコーン・バー・バリケード・吹き出しをすべてクリアする
     */
    public function clearAll(Timetable $timetable, SiteBaseMap $siteBaseMap): void
    {
        DB::transaction(function () use ($timetable, $siteBaseMap): void {
            $this->repository->clearAll($timetable, $siteBaseMap);
        });
    }

    /**
     * 2個のコーンが既に同じ連結成分上にあるかを BFS で判定する（閉路防止）
     *
     * @param  Collection<int, ConeMapBar>  $bars
     */
    protected function areConesConnected(Collection $bars, int $startId, int $targetId): bool
    {
        $adj = [];
        foreach ($bars as $bar) {
            $adj[$bar->start_cone_id][] = $bar->end_cone_id;
            $adj[$bar->end_cone_id][] = $bar->start_cone_id;
        }

        if (! isset($adj[$startId])) {
            return false;
        }

        $visited = [$startId => true];
        $queue = [$startId];

        while ($queue !== []) {
            $curr = array_shift($queue);
            if ($curr === $targetId) {
                return true;
            }
            foreach ($adj[$curr] ?? [] as $next) {
                if (! isset($visited[$next])) {
                    $visited[$next] = true;
                    $queue[] = $next;
                }
            }
        }

        return false;
    }

    /**
     * 機材管理テーブル（equipments）の保有数と配置数量を比較する
     *
     * @return array<string, array{
     *     key: string,
     *     label: string,
     *     registered: bool,
     *     equipment_name: string|null,
     *     stock_quantity: int|null,
     *     is_approximate: bool,
     *     unit: string,
     *     used_quantity: int,
     *     remaining_quantity: int|null,
     *     is_over_stock: bool
     * }>
     */
    protected function buildInventoryComparison(
        int $conesCount,
        int $weightsCount,
        int $barsCount,
        int $typeACount,
        int $resinPipeCount,
    ): array {
        $equipments = $this->repository->getInventoryEquipments();

        $matchEquipment = static function (callable $predicate) use ($equipments): array {
            $matched = $equipments->filter($predicate);
            if ($matched->isEmpty()) {
                return [
                    'registered' => false,
                    'equipment_name' => null,
                    'stock_quantity' => null,
                    'is_approximate' => false,
                ];
            }

            /** @var Equipment $first */
            $first = $matched->first();
            $totalStock = (int) $matched->sum('quantity');
            $isApprox = $matched->contains(static fn (Equipment $e): bool => (bool) $e->is_approximate_quantity);
            $names = $matched->pluck('name')->unique()->implode(' / ');

            return [
                'registered' => true,
                'equipment_name' => $names !== '' ? $names : $first->name,
                'stock_quantity' => $totalStock,
                'is_approximate' => $isApprox,
            ];
        };

        $specs = [
            'cone' => [
                'label' => 'カラーコーン',
                'unit' => '個',
                'used' => $conesCount,
                'predicate' => static fn (Equipment $e): bool => str_contains((string) $e->name, 'カラーコーン')
                    && ! str_contains((string) $e->name, 'ウェイト')
                    && ! str_contains((string) $e->name, 'バー'),
            ],
            'weight' => [
                'label' => 'コーンウェイト',
                'unit' => '個',
                'used' => $weightsCount,
                'predicate' => static fn (Equipment $e): bool => str_contains((string) $e->name, 'ウェイト')
                    || str_contains((string) $e->name, 'コーン重り'),
            ],
            'bar' => [
                'label' => 'コーンバー',
                'unit' => '本',
                'used' => $barsCount,
                'predicate' => static fn (Equipment $e): bool => str_contains((string) $e->name, 'コーンバー'),
            ],
            'barricade_type_a' => [
                'label' => 'A型バリケード',
                'unit' => '台',
                'used' => $typeACount,
                'predicate' => static fn (Equipment $e): bool => str_contains((string) $e->name, 'A型バリケード')
                    || str_contains((string) $e->name, 'Ａ型バリケード'),
            ],
            'barricade_resin_pipe' => [
                'label' => '樹脂製単管バリケード',
                'unit' => '台',
                'used' => $resinPipeCount,
                'predicate' => static fn (Equipment $e): bool => str_contains((string) $e->name, '樹脂製単管バリケード')
                    || str_contains((string) $e->name, '単管バリケード'),
            ],
        ];

        $result = [];
        foreach ($specs as $key => $spec) {
            $stockInfo = $matchEquipment($spec['predicate']);
            $stockQty = $stockInfo['stock_quantity'];
            $used = $spec['used'];
            $remaining = $stockQty !== null ? $stockQty - $used : null;

            $result[$key] = [
                'key' => $key,
                'label' => $spec['label'],
                'registered' => $stockInfo['registered'],
                'equipment_name' => $stockInfo['equipment_name'],
                'stock_quantity' => $stockQty,
                'is_approximate' => $stockInfo['is_approximate'],
                'unit' => $spec['unit'],
                'used_quantity' => $used,
                'remaining_quantity' => $remaining,
                'is_over_stock' => $stockQty !== null && $used > $stockQty,
            ];
        }

        return $result;
    }
}
