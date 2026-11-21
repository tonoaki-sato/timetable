<?php

namespace App\Services;

use App\DTOs\LocationListItemData;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\LocationType;
use App\Models\Timetable;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\LocationScheduleRepositoryInterface;
use App\Repositories\Contracts\LocationTypeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class LocationService
{
    public function __construct(
        protected LocationRepositoryInterface $locationRepository,
        protected LocationTypeRepositoryInterface $locationTypeRepository,
        protected LocationScheduleRepositoryInterface $locationScheduleRepository,
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

            return new LocationListItemData(
                location: $location,
                currentEvaluation: $evaluation,
                dailyStatus: $dailyStatus,
                todaySchedules: $targetSchedules,
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

        if (! isset($validated['display_order']) || $validated['display_order'] === null) {
            $validated['display_order'] = $this->locationRepository->getMaxDisplayOrder() + 10;
        }

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
     * 拠点詳細画面の表示用データを取得
     *
     * @return array<string, mixed>
     */
    public function getShowViewData(Timetable|Location $timetableOrLocation, Location|Carbon|null $locationOrNow = null, ?Carbon $now = null): array
    {
        if ($timetableOrLocation instanceof Timetable && $locationOrNow instanceof Location) {
            $timetable = $timetableOrLocation;
            $location = $locationOrNow;
        } else {
            $timetable = null;
            /** @var Location $location */
            $location = $timetableOrLocation;
            $now = $locationOrNow instanceof Carbon ? $locationOrNow : $now;
        }

        $now = $now ?? Carbon::now('Asia/Tokyo');
        $locationWithDetails = $this->locationRepository->findWithDetails($location->id) ?? $location;
        $evaluation = $this->availabilityService->evaluateLocationStatus($locationWithDetails, $now);

        return [
            'timetable' => $timetable,
            'location' => $locationWithDetails,
            'evaluation' => $evaluation,
        ];
    }

    /**
     * 拠点編集画面の表示用データを取得
     *
     * @return array<string, mixed>
     */
    public function getEditViewData(Timetable|Location $timetableOrLocation, ?Location $location = null): array
    {
        $targetLocation = $location ?? ($timetableOrLocation instanceof Location ? $timetableOrLocation : null);
        $timetable = $timetableOrLocation instanceof Timetable ? $timetableOrLocation : null;

        $locationWithDetails = $targetLocation
            ? ($this->locationRepository->findWithDetails($targetLocation->id) ?? $targetLocation)
            : null;
        $types = $this->locationTypeRepository->getActiveOrdered();

        return [
            'timetable' => $timetable,
            'location' => $locationWithDetails,
            'types' => $types,
        ];
    }

    /**
     * 拠点の更新処理（およびスケジュール同期）
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateLocation(Location $location, array $validated): Location
    {
        $schedules = $validated['schedules'] ?? [];
        unset($validated['schedules']);

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
     * 拠点スケジュール枠の新規登録
     *
     * @param  array<string, mixed>  $validated
     */
    public function createSchedule(Location $location, array $validated): LocationSchedule
    {
        $validated['location_id'] = $location->id;

        return $this->locationScheduleRepository->create($validated);
    }

    /**
     * 拠点スケジュール枠の更新
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateSchedule(LocationSchedule $schedule, array $validated): LocationSchedule
    {
        $this->locationScheduleRepository->update($schedule, $validated);

        return $schedule;
    }

    /**
     * 拠点スケジュール枠のステータス更新
     */
    public function updateScheduleStatus(LocationSchedule $schedule, string $availabilityStatus): LocationSchedule
    {
        $this->locationScheduleRepository->update($schedule, [
            'availability_status' => $availabilityStatus,
        ]);

        return $schedule;
    }

    /**
     * 拠点スケジュール枠の削除
     */
    public function deleteSchedule(LocationSchedule $schedule): bool
    {
        return $this->locationScheduleRepository->delete($schedule);
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
