<?php

namespace App\Services;

use App\Models\Timetable;
use App\Models\User;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TimetableService
{
    public function __construct(
        protected TimetableRepositoryInterface $timetableRepository
    ) {}

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
        $data['created_by'] = $creator?->id;

        $timetable = $this->timetableRepository->create($data);
        $timetable->createDefaultLanes();

        return $timetable;
    }

    /**
     * タイムテーブルの更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTimetable(Timetable $timetable, array $data): bool
    {
        return $this->timetableRepository->update($timetable, $data);
    }

    /**
     * タイムテーブルの削除
     */
    public function deleteTimetable(Timetable $timetable): bool
    {
        return $this->timetableRepository->delete($timetable);
    }
}
