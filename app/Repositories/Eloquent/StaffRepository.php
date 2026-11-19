<?php

namespace App\Repositories\Eloquent;

use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Support\TableQueryOptions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Override;

class StaffRepository implements StaffRepositoryInterface
{
    #[Override]
    public function findById(int $id): ?Staff
    {
        return Staff::find($id);
    }

    #[Override]
    public function findOrFail(int $id): Staff
    {
        return Staff::findOrFail($id);
    }

    #[Override]
    public function findByIds(array $ids): Collection
    {
        if (empty($ids)) {
            return new Collection;
        }

        return Staff::whereIn('id', $ids)->get();
    }

    #[Override]
    public function paginateFiltered(
        ?string $search = null,
        ?bool $status = null,
        int $perPage = 15,
        ?string $sort = null,
        ?string $direction = null,
    ): LengthAwarePaginator {
        $query = Staff::query();

        if ($search) {
            $query->search($search);
        }

        if ($status !== null) {
            $query->where('is_active', $status);
        }

        $resolved = TableQueryOptions::resolveSort(
            $sort,
            $direction,
            ['id', 'name', 'kana', 'affiliation', 'is_active'],
            'name',
            'asc',
        );

        return $query->orderBy($resolved['sort'], $resolved['direction'])
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    #[Override]
    public function getFiltered(?string $search = null, ?bool $status = null): Collection
    {
        $query = Staff::query();

        if ($search) {
            $query->search($search);
        }

        if ($status !== null) {
            $query->where('is_active', $status);
        }

        return $query->orderBy('kana', 'asc')->orderBy('name', 'asc')->get();
    }

    #[Override]
    public function getActiveStaffList(): Collection
    {
        return Staff::active()->orderBy('kana', 'asc')->orderBy('name', 'asc')->get();
    }

    #[Override]
    public function getActiveOrderedByAffiliation(): Collection
    {
        return Staff::active()->orderBy('affiliation', 'asc')->orderBy('name', 'asc')->get();
    }

    #[Override]
    public function getActiveByIds(?array $ids = null): Collection
    {
        $query = Staff::query()->where('is_active', true);

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return $query->orderBy('kana', 'asc')->get();
    }

    #[Override]
    public function getActiveCount(): int
    {
        return Staff::where('is_active', true)->count();
    }

    #[Override]
    public function getActiveLeaderCount(): int
    {
        return Staff::where('is_active', true)
            ->where(function ($q): void {
                $q->whereHas('fixedLanes')
                    ->orWhereHas('ledLanes');
            })
            ->distinct()
            ->count('id');
    }

    #[Override]
    public function create(array $attributes): Staff
    {
        return Staff::create($attributes);
    }

    #[Override]
    public function update(Staff $staff, array $attributes): bool
    {
        return $staff->update($attributes);
    }

    #[Override]
    public function delete(Staff $staff): bool
    {
        return (bool) $staff->delete();
    }

    #[Override]
    public function searchQuick(string $keyword, int $limit = 5): Collection
    {
        return Staff::query()
            ->search($keyword)
            ->orderByDesc('is_active')
            ->orderBy('kana', 'asc')
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get();
    }

    #[Override]
    public function findByAccessToken(string $token): ?Staff
    {
        return Staff::where('access_token', $token)
            ->where('is_active', true)
            ->first();
    }

    #[Override]
    public function findByNameAndKana(string $name, string $kana): Collection
    {
        return Staff::where('name', $name)
            ->where('kana', $kana)
            ->where('is_active', true)
            ->get();
    }

    #[Override]
    public function chunkAllOrderedById(int $chunkSize, callable $callback): bool
    {
        return Staff::with(['checkIns.timetable'])->orderBy('id')->chunk($chunkSize, $callback);
    }

    #[Override]
    public function loadShiftRelationsAndGetLedLanes(Staff $staff): Collection
    {
        $staff->load([
            'assignments' => function ($query): void {
                $query->whereHas('task.timetable', function ($tq): void {
                    $tq->where('status', '!=', 'completed');
                });
            },
            'assignments.task.timetable',
            'assignments.task.laneRelation.leadStaff',
            'assignments.task.laneRelation.timetable',
            'assignments.task.assignments.staff',
            'checkIns',
        ]);

        return $staff->ledLanes()
            ->whereHas('timetable', function ($query): void {
                $query->where('status', '!=', 'completed');
            })
            ->ordered()
            ->with([
                'timetable',
                'fixedStaff',
                'tasks.assignments.staff',
                'tasks.laneRelation',
                'laneRequests.task',
            ])
            ->get();
    }

    #[Override]
    public function getActiveStaffMinimal(): Collection
    {
        return Staff::active()
            ->orderBy('kana')
            ->orderBy('name')
            ->get(['id', 'name', 'kana']);
    }
}
