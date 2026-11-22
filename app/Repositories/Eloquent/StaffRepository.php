<?php

namespace App\Repositories\Eloquent;

use App\Enums\StaffAttribute;
use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Support\TableQueryOptions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
        ?string $staffAttribute = null,
    ): LengthAwarePaginator {
        $query = Staff::query();

        if ($search) {
            $query->search($search);
        }

        if ($status !== null) {
            $query->where('is_active', $status);
        }

        $this->applyAttributeFilter($query, $staffAttribute);

        $resolved = TableQueryOptions::resolveSort(
            $sort,
            $direction,
            ['id', 'name', 'kana', 'affiliation', 'staff_attribute', 'is_active'],
            'name',
            'asc',
        );

        return $query->orderBy($resolved['sort'], $resolved['direction'])
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    #[Override]
    public function getFiltered(?string $search = null, ?bool $status = null, ?string $staffAttribute = null): Collection
    {
        $query = Staff::query();

        if ($search) {
            $query->search($search);
        }

        if ($status !== null) {
            $query->where('is_active', $status);
        }

        $this->applyAttributeFilter($query, $staffAttribute);

        return $query->orderBy('kana', 'asc')->orderBy('name', 'asc')->get();
    }

    /**
     * スタッフ属性の絞り込み条件をクエリへ適用
     */
    private function applyAttributeFilter(Builder $query, ?string $staffAttribute): void
    {
        if ($staffAttribute === StaffAttribute::FILTER_EXECUTIVE_GROUP) {
            $query->whereIn('staff_attribute', StaffAttribute::executiveGroupValues());

            return;
        }

        if ($staffAttribute !== null && in_array($staffAttribute, StaffAttribute::values(), true)) {
            $query->where('staff_attribute', $staffAttribute);
        }
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
                $q->whereHas('fixedTimetableLanes')
                    ->orWhereHas('ledTimetableLanes');
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
    public function loadShiftRelationsAndGetLedTimetableLanes(Staff $staff): Collection
    {
        $staff->load([
            'assignments' => function ($query): void {
                $query->whereHas('task.timetable', function ($tq): void {
                    $tq->where('status', '!=', 'completed');
                });
            },
            'assignments.task.timetable',
            'assignments.task.timetableLane.lane',
            'assignments.task.timetableLane.leadStaff',
            'assignments.task.timetableLane.timetable',
            'assignments.task.assignments.staff',
            'checkIns',
        ]);

        return $staff->ledTimetableLanes()
            ->whereHas('timetable', function ($query): void {
                $query->where('status', '!=', 'completed');
            })
            ->ordered()
            ->with([
                'lane',
                'timetable',
                'fixedStaff',
                'tasks.assignments.staff',
                'tasks.timetableLane.lane',
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
            ->get(['id', 'name', 'kana', 'staff_attribute']);
    }
}
