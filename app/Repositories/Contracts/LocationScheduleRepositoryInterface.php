<?php

namespace App\Repositories\Contracts;

use App\Models\Location;
use App\Models\LocationSchedule;
use Illuminate\Database\Eloquent\Collection;

interface LocationScheduleRepositoryInterface
{
    /**
     * 指定拠点のスケジュール一覧を開始日時昇順で取得
     *
     * @return Collection<int, LocationSchedule>
     */
    public function getForLocationOrdered(Location $location): Collection;

    /**
     * 新規拠点スケジュール枠を作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): LocationSchedule;

    /**
     * 拠点スケジュール枠を更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(LocationSchedule $schedule, array $attributes): bool;

    /**
     * 拠点スケジュール枠を削除
     */
    public function delete(LocationSchedule $schedule): bool;
}
