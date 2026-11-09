<?php

namespace App\Repositories\Eloquent;

use App\Models\Location;
use App\Models\Timetable;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LocationRepository implements LocationRepositoryInterface
{
    /**
     * 指定タイムテーブルに紐づく拠点一覧をスケジュール付きで取得
     *
     * @return Collection<int, Location>
     */
    public function getForTimetable(Timetable $timetable): Collection
    {
        return $timetable->locations()
            ->with(['type', 'schedules' => function ($query): void {
                $query->orderBy('start_at', 'asc');
            }])
            ->ordered()
            ->get();
    }

    /**
     * 詳細画面用の拠点を取得（スケジュール・関連計画付き）
     */
    public function findWithDetails(Timetable $timetable, int $locationId): ?Location
    {
        /** @var Location|null $location */
        $location = $timetable->locations()
            ->with([
                'type',
                'schedules' => function ($query): void {
                    $query->orderBy('start_at', 'asc');
                },
                'plansTo.equipment',
                'plansFrom.equipment',
            ])
            ->where('id', $locationId)
            ->first();

        return $location;
    }

    /**
     * 新規拠点を作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(Timetable $timetable, array $attributes): Location
    {
        return $timetable->locations()->create($attributes);
    }

    /**
     * 拠点を更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Location $location, array $attributes): bool
    {
        return $location->update($attributes);
    }

    /**
     * 拠点を削除
     */
    public function delete(Location $location): bool
    {
        return (bool) $location->delete();
    }

    /**
     * 指定タイムテーブルの拠点表示順を一括更新
     *
     * @param  array<int, int>  $locationIds
     */
    public function reorder(Timetable $timetable, array $locationIds): void
    {
        DB::transaction(function () use ($timetable, $locationIds): void {
            $order = 10;
            foreach ($locationIds as $locationId) {
                $timetable->locations()->where('id', $locationId)->update([
                    'display_order' => $order,
                ]);
                $order += 10;
            }
        });
    }

    /**
     * 指定タイムテーブルにおける最大の表示順を取得
     */
    public function getMaxDisplayOrder(Timetable $timetable): int
    {
        return (int) ($timetable->locations()->max('display_order') ?? 0);
    }

    /**
     * 拠点の利用可能時間帯スケジュールを一括同期（作成・更新・削除）
     *
     * @param  array<int, array<string, mixed>>  $schedulesData
     */
    public function syncSchedules(Location $location, array $schedulesData): void
    {
        DB::transaction(function () use ($location, $schedulesData): void {
            $updatedIds = [];

            foreach ($schedulesData as $item) {
                if (! empty($item['id'])) {
                    $schedule = $location->schedules()->where('id', $item['id'])->first();
                    if ($schedule) {
                        $schedule->update([
                            'start_at' => $item['start_at'],
                            'end_at' => $item['end_at'],
                            'availability_status' => $item['availability_status'],
                            'title' => $item['title'] ?? null,
                            'note' => $item['note'] ?? null,
                        ]);
                        $updatedIds[] = $schedule->id;
                    }
                } else {
                    $created = $location->schedules()->create([
                        'start_at' => $item['start_at'],
                        'end_at' => $item['end_at'],
                        'availability_status' => $item['availability_status'],
                        'title' => $item['title'] ?? null,
                        'note' => $item['note'] ?? null,
                    ]);
                    $updatedIds[] = $created->id;
                }
            }

            $location->schedules()->whereNotIn('id', $updatedIds)->delete();
        });
    }
}
