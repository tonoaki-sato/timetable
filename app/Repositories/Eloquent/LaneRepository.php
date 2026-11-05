<?php

namespace App\Repositories\Eloquent;

use App\Models\Lane;
use App\Models\Timetable;
use App\Repositories\Contracts\LaneRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LaneRepository implements LaneRepositoryInterface
{
    /**
     * 指定タイムテーブルに紐づく担当班一覧を関連リレーション付きで取得（表示順昇順）
     *
     * @return Collection<int, Lane>
     */
    public function getForTimetable(Timetable $timetable): Collection
    {
        return $timetable->lanes()
            ->with([
                'leadStaff',
                'fixedStaff',
                'tasks',
                'laneRequests.task',
                'laneRequests.requestedByStaff',
            ])
            ->ordered()
            ->get();
    }

    /**
     * 詳細画面用の担当班を取得（班長・固定スタッフ・タスク・要請・資材リレーション付き）
     */
    public function findWithDetails(Timetable $timetable, int $laneId): ?Lane
    {
        /** @var Lane|null $lane */
        $lane = $timetable->lanes()
            ->with([
                'leadStaff',
                'fixedStaff',
                'tasks' => function ($query): void {
                    $query->with(['assignments'])->orderBy('start_time', 'asc');
                },
                'laneRequests' => function ($query): void {
                    $query->with(['requestedByStaff', 'task'])->orderByDesc('id');
                },
            ])
            ->where('id', $laneId)
            ->first();

        return $lane;
    }

    /**
     * 新規担当班を作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(Timetable $timetable, array $attributes): Lane
    {
        return $timetable->lanes()->create($attributes);
    }

    /**
     * 担当班を更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Lane $lane, array $attributes): bool
    {
        return $lane->update($attributes);
    }

    /**
     * 担当班を削除
     */
    public function delete(Lane $lane): bool
    {
        return (bool) $lane->delete();
    }

    /**
     * 担当班の並び順を一括更新
     *
     * @param  array<int, int>  $laneIds
     */
    public function reorder(Timetable $timetable, array $laneIds): void
    {
        DB::transaction(function () use ($timetable, $laneIds): void {
            $order = 10;
            foreach ($laneIds as $laneId) {
                $timetable->lanes()->where('id', $laneId)->update([
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
        return (int) ($timetable->lanes()->max('display_order') ?? 0);
    }
}
