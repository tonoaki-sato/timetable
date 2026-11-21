<?php

namespace App\Services;

use App\DTOs\LocationListItemData;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\LocationType;
use App\Models\Timetable;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\LocationTypeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class LocationService
{
    public function __construct(
        protected LocationRepositoryInterface $locationRepository,
        protected LocationTypeRepositoryInterface $locationTypeRepository,
        protected LocationAvailabilityService $availabilityService,
    ) {}

    /**
     * 拠点一覧表示用データを取得
     *
     * @return array{
     *     timetable: ?Timetable,
     *     locationsWithStatus: \Illuminate\Support\Collection<int, LocationListItemData>,
     *     now: Carbon
     * }
     */
    public function getIndexViewData(Timetable|Carbon|null $timetable = null, ?Carbon $now = null): array
    {
        if ($timetable instanceof Carbon) {
            $now = $timetable;
            $timetable = null;
        }

        $now = $now ?? Carbon::now('Asia/Tokyo');
        $targetDate = $timetable?->target_date?->format('Y-m-d') ?? $now->format('Y-m-d');

        $locations = $this->locationRepository->getAllOrdered();

        $locationsWithStatus = $locations->map(function (Location $location) use ($now, $targetDate): LocationListItemData {
            $evaluation = $this->availabilityService->evaluateLocationStatus($location, $now);
            $dailyStatus = $this->availabilityService->evaluateDailyAvailability($location, $targetDate);
            $targetSchedules = $location->schedules->filter(function (LocationSchedule $s) use ($targetDate): bool {
                return $s->start_at->format('Y-m-d') === $targetDate || $s->end_at->format('Y-m-d') === $targetDate;
            });

            // 現在日時を含む未来の利用枠（end_at >= $now、開始日時昇順）
            $upcomingSchedules = $location->schedules
                ->filter(fn (LocationSchedule $s): bool => $s->end_at !== null && $s->end_at->greaterThanOrEqualTo($now))
                ->values();

            // 現在日時を含まない過去の利用枠（end_at < $now、開始日時降順）
            $pastSchedules = $location->schedules
                ->filter(fn (LocationSchedule $s): bool => $s->end_at !== null && $s->end_at->lessThan($now))
                ->sortByDesc('start_at')
                ->values();

            return new LocationListItemData(
                location: $location,
                currentEvaluation: $evaluation,
                dailyStatus: $dailyStatus,
                todaySchedules: $targetSchedules,
                upcomingSchedules: $upcomingSchedules,
                pastSchedules: $pastSchedules,
            );
        });

        return [
            'timetable' => $timetable,
            'locationsWithStatus' => $locationsWithStatus,
            'now' => $now,
        ];
    }

    /**
     * 有効な拠点タイプ一覧を取得
     *
     * @return Collection<int, LocationType>
     */
    public function getActiveLocationTypes(): Collection
    {
        return $this->locationTypeRepository->getActiveOrdered();
    }

    /**
     * 拠点の新規登録（および利用可能時間帯スケジュールの初期同期）
     *
     * @param  array<string, mixed>  $validated
     */
    public function createLocation(array|Timetable $timetableOrValidated, array|int|null $validatedOrUserId = null, ?int $userId = null): Location
    {
        if ($timetableOrValidated instanceof Timetable) {
            $validated = is_array($validatedOrUserId) ? $validatedOrUserId : [];
        } else {
            $validated = $timetableOrValidated;
            $userId = is_int($validatedOrUserId) ? $validatedOrUserId : $userId;
        }

        $validated['created_by'] = $userId;
        $validated['display_order'] = $this->locationRepository->getMaxDisplayOrder() + 10;

        $schedules = $validated['schedules'] ?? [];
        unset($validated['schedules']);

        $location = $this->locationRepository->create($validated);

        if (! empty($schedules) && ! ($validated['is_all_day'] ?? false)) {
            $this->locationRepository->syncSchedules($location, $schedules);
        }

        return $location;
    }

    /**
     * 拠点の表示順を一括更新
     *
     * @param  array<int, int>|Timetable  $timetableOrLocationIds
     * @param  array<int, int>|null  $locationIds
     */
    public function reorderLocations(array|Timetable $timetableOrLocationIds, ?array $locationIds = null): void
    {
        $cleanIds = is_array($timetableOrLocationIds) ? $timetableOrLocationIds : ($locationIds ?? []);
        $this->locationRepository->reorder($cleanIds);
    }

    /**
     * 拠点の更新処理（およびスケジュール同期）
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateLocation(Location $location, array $validated): Location
    {
        $schedules = $validated['schedules'] ?? [];
        unset($validated['schedules'], $validated['display_order']);

        $this->locationRepository->update($location, $validated);

        if (! ($validated['is_all_day'] ?? false)) {
            $this->locationRepository->syncSchedules($location, $schedules);
        }

        return $location;
    }

    /**
     * 拠点の削除
     */
    public function deleteLocation(Location $location): bool
    {
        return $this->locationRepository->delete($location);
    }

    /**
     * 拠点タイプ一覧を拠点件数カウント付きで取得
     *
     * @return Collection<int, LocationType>
     */
    public function getAllLocationTypesWithCounts(): Collection
    {
        return $this->locationTypeRepository->getAllWithLocationsCountOrdered();
    }

    /**
     * 拠点タイプの新規登録
     *
     * @param  array<string, mixed>  $validated
     */
    public function createLocationType(array $validated): LocationType
    {
        return $this->locationTypeRepository->create($validated);
    }

    /**
     * 拠点タイプの更新
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateLocationType(LocationType $locationType, array $validated): LocationType
    {
        $this->locationTypeRepository->update($locationType, $validated);

        return $locationType;
    }

    /**
     * 指定拠点タイプに紐づく拠点が存在するか判定
     */
    public function hasLocationsForType(LocationType $locationType): bool
    {
        return $this->locationTypeRepository->hasLocations($locationType);
    }

    /**
     * 拠点タイプの削除
     */
    public function deleteLocationType(LocationType $locationType): bool
    {
        return $this->locationTypeRepository->delete($locationType);
    }
}
