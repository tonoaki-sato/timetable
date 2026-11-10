<?php

namespace App\Repositories\Eloquent;

use App\Models\Lane;
use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
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
    public function paginateFiltered(?string $search = null, ?bool $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Staff::query();

        if ($search) {
            $query->search($search);
        }

        if ($status !== null) {
            $query->where('is_active', $status);
        }

        return $query->orderBy('name', 'asc')
            ->paginate($perPage)
            ->withQueryString();
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
                $q->where('staff_type', 'fixed')
                    ->orWhereIn('id', Lane::whereNotNull('lead_staff_id')->pluck('lead_staff_id'));
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
    public function updateStaffTypeByIds(array $ids, string $staffType): int
    {
        if (empty($ids)) {
            return 0;
        }

        return Staff::whereIn('id', $ids)->update(['staff_type' => $staffType]);
    }

    #[Override]
    public function delete(Staff $staff): bool
    {
        return (bool) $staff->delete();
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
    public function findFirstByNameAndKana(string $name, string $kana): ?Staff
    {
        return Staff::where('name', $name)
            ->where('kana', $kana)
            ->first();
    }

    #[Override]
    public function chunkAllOrderedById(int $chunkSize, callable $callback): bool
    {
        return Staff::orderBy('id')->chunk($chunkSize, $callback);
    }

    #[Override]
    public function loadShowRelations(Staff $staff): Staff
    {
        return $staff->load(['assignments.task.timetable']);
    }

    #[Override]
    public function loadShiftRelationsAndGetLedLanes(Staff $staff): Collection
    {
        $staff->load([
            'assignments.task.timetable',
            'assignments.task.laneRelation.leadStaff',
        ]);

        return $staff->ledLanes()
            ->with([
                'timetable',
                'fixedStaff',
                'tasks.assignments.staff',
                'laneRequests.task',
            ])
            ->get();
    }
}
