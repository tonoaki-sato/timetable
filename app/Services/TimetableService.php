<?php

namespace App\Services;

use App\Enums\TimetableStatus;
use App\Models\Timetable;
use App\Models\User;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use Carbon\Carbon;
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
     * 全運行計画を取得
     *
     * @return Collection<int, Timetable>
     */
    public function getAllTimetables(): Collection
    {
        return $this->timetableRepository->getAll();
    }

    /**
     * セレクター用の選択可能な運行計画一覧を取得
     *
     * @return Collection<int, Timetable>
     */
    public function getSelectableTimetables(): Collection
    {
        return $this->timetableRepository->getSelectableTimetables();
    }

    /**
     * デフォルト運行日（準備中・運行中のうち現在日時に最も近い運行日）を取得
     */
    public function findFirstActiveOrEarliest(?Carbon $now = null): ?Timetable
    {
        return $this->timetableRepository->findFirstActiveOrEarliest($now);
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
            'statuses' => TimetableStatus::options(),
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
     * 複製元タイムテーブルを関連件数付きで取得
     */
    public function findForClone(int $id): ?Timetable
    {
        return $this->timetableRepository->findForClone($id);
    }

    /**
     * 新規タイムテーブルの作成
     *
     * @param  array<string, mixed>  $data
     */
    public function createTimetable(array $data, ?User $creator): Timetable
    {
        unset($data['create_default_locations']);

        $data['created_by'] = $creator?->id;

        $timetable = $this->timetableRepository->create($data);

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
