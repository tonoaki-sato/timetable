<?php

namespace App\Repositories\Eloquent;

use App\Models\AnnualSchedule;
use App\Repositories\Contracts\AnnualScheduleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AnnualScheduleRepository implements AnnualScheduleRepositoryInterface
{
    /**
     * 条件付き年間スケジュール一覧の取得
     */
    public function paginateFiltered(
        int $year,
        ?string $category = null,
        ?string $status = null,
        ?string $search = null,
        ?int $staffId = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = AnnualSchedule::with(['creator', 'assignee']);

        if ($year) {
            $query->where('target_year', $year);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        if ($search) {
            $query->search($search);
        }

        return $query->orderBy('start_date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * 指定月のスケジュール一覧の取得（単日および期間重複）
     *
     * @return Collection<int, AnnualSchedule>
     */
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

        $query = AnnualSchedule::with(['creator', 'assignee'])
            ->year($year)
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->where(function ($sub) use ($startOfMonth, $endOfMonth) {
                    $sub->whereNull('end_date')
                        ->whereBetween('start_date', [$startOfMonth, $endOfMonth]);
                })->orWhere(function ($sub) use ($startOfMonth, $endOfMonth) {
                    $sub->whereNotNull('end_date')
                        ->where('start_date', '<=', $endOfMonth)
                        ->where('end_date', '>=', $startOfMonth);
                });
            });

        if ($category) {
            $query->where('category', $category);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        if ($search) {
            $query->search($search);
        }

        return $query->orderBy('start_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * 登録済み対象年度一覧の取得
     *
     * @return array<int, int>
     */
    public function getAvailableYears(): array
    {
        $availableYears = AnnualSchedule::query()
            ->select('target_year')
            ->distinct()
            ->orderBy('target_year', 'desc')
            ->pluck('target_year')
            ->map(fn ($val) => (int) $val)
            ->all();

        if (empty($availableYears)) {
            $availableYears = [2026];
        } elseif (! in_array(2026, $availableYears, true)) {
            $availableYears[] = 2026;
            rsort($availableYears);
        }

        return $availableYears;
    }

    /**
     * 指定年度のステータス別集計の取得
     *
     * @return array{total: int, planned: int, in_progress: int, completed: int}
     */
    public function getYearStatusSummary(int $year): array
    {
        return [
            'total' => AnnualSchedule::where('target_year', $year)->count(),
            'planned' => AnnualSchedule::where('target_year', $year)->where('status', 'planned')->count(),
            'in_progress' => AnnualSchedule::where('target_year', $year)->where('status', 'in_progress')->count(),
            'completed' => AnnualSchedule::where('target_year', $year)->where('status', 'completed')->count(),
        ];
    }

    /**
     * 新規スケジュールの永続化
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): AnnualSchedule
    {
        return AnnualSchedule::create($attributes);
    }

    /**
     * スケジュールの更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(AnnualSchedule $schedule, array $attributes): bool
    {
        return $schedule->update($attributes);
    }

    /**
     * スケジュールの削除
     */
    public function delete(AnnualSchedule $schedule): bool
    {
        return (bool) $schedule->delete();
    }
}
