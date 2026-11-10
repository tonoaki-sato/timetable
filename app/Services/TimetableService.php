<?php

namespace App\Services;

use App\Models\Timetable;
use App\Models\User;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TimetableService
{
    public function __construct(
        protected TimetableRepositoryInterface $timetableRepository,
        protected ScheduleRepositoryInterface $scheduleRepository
    ) {}

    /**
     * IDによるタイムテーブル取得
     */
    public function findById(int $id): ?Timetable
    {
        return $this->timetableRepository->findById($id);
    }

    /**
     * IDによるタイムテーブル取得（存在しない場合は例外）
     */
    public function findOrFail(int $id): Timetable
    {
        return $this->timetableRepository->findOrFail($id);
    }

    /**
     * アクティブ（または最も早い）タイムテーブルを取得
     */
    public function findFirstActiveOrEarliest(): ?Timetable
    {
        return $this->timetableRepository->findFirstActiveOrEarliest();
    }

    /**
     * 対象タイムテーブルの解決（指定モデル ＞ 指定ID ＞ アクティブ運行日 ＞ 最小日付運行日 ＞ デフォルト作成）
     */
    public function resolveFallbackTimetable(?int $timetableId = null, ?Timetable $timetable = null): Timetable
    {
        if ($timetable && $timetable->exists) {
            return $timetable;
        }

        if ($timetableId) {
            $found = $this->timetableRepository->findById($timetableId);
            if ($found) {
                return $found;
            }
        }

        return $this->timetableRepository->findFirstActiveOrEarliest()
            ?? $this->timetableRepository->create([
                'name' => '既定運行計画',
                'target_date' => now()->toDateString(),
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'status' => 'active',
            ]);
    }

    /**
     * タイムテーブル一覧画面用データの取得
     *
     * @return array{
     *     timetables: Collection<int, Timetable>,
     *     status: ?string,
     *     statuses: array<string, string>
     * }
     */
    public function getIndexViewData(?string $status): array
    {
        $timetables = $this->timetableRepository->getFiltered($status);

        return [
            'timetables' => $timetables,
            'status' => $status,
            'statuses' => Timetable::STATUSES,
        ];
    }

    /**
     * タイムテーブル詳細の取得
     */
    public function getTimetableDetails(int $id): ?Timetable
    {
        return $this->timetableRepository->findWithDetails($id);
    }

    /**
     * 新規タイムテーブルの作成
     *
     * @param  array<string, mixed>  $data
     */
    public function createTimetable(array $data, ?User $creator): Timetable
    {
        $createDefaultLanes = array_key_exists('create_default_lanes', $data)
            ? (bool) $data['create_default_lanes']
            : true;
        unset($data['create_default_lanes']);

        $createDefaultLocations = array_key_exists('create_default_locations', $data)
            ? (bool) $data['create_default_locations']
            : true;
        unset($data['create_default_locations']);

        $data['created_by'] = $creator?->id;

        $timetable = $this->timetableRepository->create($data);

        if ($createDefaultLanes) {
            $timetable->createDefaultLanes();
        }

        if ($createDefaultLocations) {
            $timetable->createDefaultLocations();
        }

        $this->scheduleRepository->syncForTimetable($timetable);

        return $timetable;
    }

    /**
     * タイムテーブルの更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTimetable(Timetable $timetable, array $data): bool
    {
        $updated = $this->timetableRepository->update($timetable, $data);
        if ($updated) {
            $this->scheduleRepository->syncForTimetable($timetable->fresh());
        }

        return $updated;
    }

    /**
     * タイムテーブルの削除
     */
    public function deleteTimetable(Timetable $timetable): bool
    {
        $this->scheduleRepository->deleteForTimetable($timetable->id);

        return $this->timetableRepository->delete($timetable);
    }
}
