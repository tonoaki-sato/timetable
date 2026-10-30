<?php

namespace App\Repositories\Contracts;

use App\Models\Timetable;
use Illuminate\Database\Eloquent\Collection;

interface TimetableRepositoryInterface
{
    /**
     * 全タイムテーブル一覧の取得（タスク数カウント付き・日付順）
     *
     * @return Collection<int, Timetable>
     */
    public function getAll(): Collection;

    /**
     * ステータス絞り込み付き一覧取得
     *
     * @return Collection<int, Timetable>
     */
    public function getFiltered(?string $status = null): Collection;

    /**
     * IDによる取得（タスクおよびスタッフアサイン付き）
     */
    public function findWithDetails(int $id): ?Timetable;

    /**
     * 開催日による取得
     */
    public function findByDate(string $date): ?Timetable;

    /**
     * 新規作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Timetable;

    /**
     * 更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Timetable $timetable, array $attributes): bool;

    /**
     * 削除
     */
    public function delete(Timetable $timetable): bool;

    /**
     * 運行準備用の進行中・準備中タイムテーブル一覧を取得（日付昇順）
     *
     * @return Collection<int, Timetable>
     */
    public function getActiveAndDraftWithWorkflowDetails(): Collection;

    /**
     * 運行準備用の直近完了済みタイムテーブルを取得（日付降順・最大件数指定）
     *
     * @return Collection<int, Timetable>
     */
    public function getRecentCompletedWithWorkflowDetails(int $limit = 2): Collection;

    /**
     * タイムテーブルの総登録数を取得
     */
    public function getTotalCount(): int;

    /**
     * ワークフロー詳細付きで単一タイムテーブルを取得
     */
    public function findWithWorkflowDetails(int $id): ?Timetable;
}
