<?php

namespace App\Repositories\Eloquent;

use App\Models\Location;
use App\Models\Timetable;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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
}
