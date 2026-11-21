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
}
