<?php

namespace App\Repositories\Eloquent;

use App\Models\Timetable;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TimetableRepository implements TimetableRepositoryInterface
{
    /**
     * 全タイムテーブル一覧の取得（タスク数カウント付き・日付順）
     *
     * @return Collection<int, Timetable>
     */
    public function getAll(): Collection
    {
        return Timetable::withCount('tasks')
            ->orderBy('target_date', 'asc')
            ->get();
    }

    /**
     * ステータス絞り込み付き一覧取得
     *
     * @return Collection<int, Timetable>
     */
    public function getFiltered(?string $status = null): Collection
    {
        $query = Timetable::withCount('tasks')->orderBy('target_date', 'asc');

        if ($status) {
            $query->status($status);
        }

        return $query->get();
    }

    /**
     * IDによる取得（タスクおよびスタッフアサイン付き）
     */
    public function findWithDetails(int $id): ?Timetable
    {
        return Timetable::with([
            'creator',
            'tasks' => function ($q) {
                $q->orderBy('start_time', 'asc')
                    ->with(['assignments.staff']);
            },
        ])->find($id);
    }

    /**
     * 開催日による取得
     */
    public function findByDate(string $date): ?Timetable
    {
        return Timetable::where('target_date', $date)->first();
    }

    /**
     * 新規作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Timetable
    {
        return Timetable::create($attributes);
    }

    /**
     * 更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Timetable $timetable, array $attributes): bool
    {
        return $timetable->update($attributes);
    }

    /**
     * 削除
     */
    public function delete(Timetable $timetable): bool
    {
        return (bool) $timetable->delete();
    }

    /**
     * 運行準備用の進行中・準備中タイムテーブル一覧を取得（日付昇順）
     *
     * @return Collection<int, Timetable>
     */
    public function getActiveAndDraftWithWorkflowDetails(): Collection
    {
        return Timetable::with(['lanes.fixedStaff', 'lanes.leadStaff', 'tasks.assignments'])
            ->where('status', '!=', 'completed')
            ->orderBy('target_date', 'asc')
            ->get();
    }

    /**
     * 運行準備用の直近完了済みタイムテーブルを取得（日付降順・最大件数指定）
     *
     * @return Collection<int, Timetable>
     */
    public function getRecentCompletedWithWorkflowDetails(int $limit = 2): Collection
    {
        return Timetable::with(['lanes.fixedStaff', 'lanes.leadStaff', 'tasks.assignments'])
            ->where('status', 'completed')
            ->orderBy('target_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * タイムテーブルの総登録数を取得
     */
    public function getTotalCount(): int
    {
        return Timetable::count();
    }

    /**
     * ワークフロー詳細付きで単一タイムテーブルを取得
     */
    public function findWithWorkflowDetails(int $id): ?Timetable
    {
        return Timetable::with(['lanes.fixedStaff', 'lanes.leadStaff', 'tasks.assignments'])
            ->find($id);
    }
}
