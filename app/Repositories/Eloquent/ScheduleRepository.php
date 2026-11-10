<?php

namespace App\Repositories\Eloquent;

use App\Models\Schedule;
use App\Models\Timetable;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Override;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    #[Override]
    public function paginateFiltered(
        ?string $category = null,
        ?string $status = null,
        ?string $search = null,
        ?int $staffId = null,
        ?string $tab = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Schedule::with(['assignee', 'creator', 'timetable']);

        if ($tab === 'upcoming') {
            $query->upcoming();
        } elseif ($tab === 'past') {
            $query->past();
        } else {
            $query->orderBy('start_date', 'asc')->orderBy('start_time', 'asc');
        }

        if ($category) {
            $query->category($category);
        }

        if ($status) {
            $query->status($status);
        }

        if ($staffId) {
            $query->assignee($staffId);
        }

        if ($search) {
            $query->search($search);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    #[Override]
    public function getForMonth(
        int $year,
        int $month,
        ?string $category = null,
        ?string $status = null,
        ?int $staffId = null,
        ?string $search = null
    ): Collection {
        $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
        $endOfMonth = date('Y-m-t', strtotime($startOfMonth));

        $query = Schedule::with(['assignee', 'creator'])
            ->where(function ($q) use ($startOfMonth, $endOfMonth): void {
                $q->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($sub) use ($startOfMonth, $endOfMonth): void {
                        $sub->whereNotNull('end_date')
                            ->where('start_date', '<=', $endOfMonth)
                            ->where('end_date', '>=', $startOfMonth);
                    });
            });

        if ($category) {
            $query->category($category);
        }

        if ($status) {
            $query->status($status);
        }

        if ($staffId) {
            $query->assignee($staffId);
        }

        if ($search) {
            $query->search($search);
        }

        return $query->orderBy('start_date')->orderBy('start_time')->get();
    }

    #[Override]
    public function getUpcomingMeetings(int $limit = 3): Collection
    {
        return Schedule::with(['assignee'])
            ->meetings()
            ->upcoming()
            ->limit($limit)
            ->get();
    }

    #[Override]
    public function getUpcomingPermits(int $limit = 3): Collection
    {
        return Schedule::with(['assignee'])
            ->whereIn('category', ['permit', 'briefing'])
            ->upcoming()
            ->limit($limit)
            ->get();
    }

    #[Override]
    public function findNextUpcomingTask(string $todayStr): ?Schedule
    {
        return Schedule::where('start_date', '>=', $todayStr)
            ->where('status', '!=', 'completed')
            ->orderBy('start_date', 'asc')
            ->first();
    }

    #[Override]
    public function countPendingInMonthsAndYear(array $months, int $year): int
    {
        if (empty($months)) {
            return 0;
        }

        return Schedule::where(function ($query) use ($months): void {
            foreach ($months as $m) {
                $query->orWhereMonth('start_date', $m);
            }
        })
            ->whereYear('start_date', $year)
            ->where('status', '!=', 'completed')
            ->count();
    }

    #[Override]
    public function getStatusSummary(): array
    {
        return [
            'total' => Schedule::count(),
            'planned' => Schedule::where('status', 'planned')->count(),
            'in_progress' => Schedule::where('status', 'in_progress')->count(),
            'completed' => Schedule::where('status', 'completed')->count(),
        ];
    }

    #[Override]
    public function create(array $attributes): Schedule
    {
        return Schedule::create($attributes);
    }

    #[Override]
    public function update(Schedule $schedule, array $attributes): bool
    {
        return $schedule->update($attributes);
    }

    #[Override]
    public function delete(Schedule $schedule): bool
    {
        return (bool) $schedule->delete();
    }

    #[Override]
    public function syncForTimetable(Timetable $timetable): Schedule
    {
        $statusMap = [
            'draft' => 'planned',
            'active' => 'in_progress',
            'completed' => 'completed',
        ];

        $targetDate = $timetable->target_date instanceof \DateTimeInterface
            ? $timetable->target_date->format('Y-m-d')
            : (is_string($timetable->target_date) ? substr($timetable->target_date, 0, 10) : null);

        return Schedule::updateOrCreate(
            ['timetable_id' => $timetable->id],
            [
                'title' => $timetable->name,
                'category' => 'operation',
                'start_date' => $targetDate,
                'end_date' => $targetDate,
                'start_time' => $timetable->start_time,
                'end_time' => $timetable->end_time,
                'location' => '会場一帯',
                'status' => $statusMap[$timetable->status] ?? 'planned',
                'description' => $timetable->description,
                'created_by' => $timetable->created_by,
            ]
        );
    }

    #[Override]
    public function deleteForTimetable(int $timetableId): bool
    {
        return (bool) Schedule::where('timetable_id', $timetableId)->delete();
    }
}
