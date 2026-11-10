<?php

namespace App\Repositories\Contracts;

use App\Models\Timetable;
use Illuminate\Database\Eloquent\Collection;

interface TimetableRepositoryInterface
{
    /**
     * IDによるタイムテーブル取得
     */
    public function findById(int $id): ?Timetable;

    /**
     * IDによるタイムテーブル取得（存在しない場合は例外）
     */
    public function findOrFail(int $id): Timetable;

    /**
     * アクティブな運行日（なければ日付順で最初の運行日）を取得
     */
    public function findFirstActiveOrEarliest(): ?Timetable;

    /**
     * 全タイムテーブル一覧の取得（日付順）
     *
     * @return Collection<int, Timetable>
     */
    public function getAll(): Collection;

    /**
     * ナビゲーション表示用の未完了タイムテーブル一覧を取得
     *
     * @return Collection<int, Timetable>
     */
    public function getForNavigation(): Collection;

    /**
     * ステータス絞り込み付き一覧取得
     *
     * @return Collection<int, Timetable>
     */
    public function getFiltered(?string $status = null): Collection;

    /**
     * 指定年月のタイムテーブル一覧を取得（カレンダー用）
     *
     * @return Collection<int, Timetable>
     */
    public function getForMonth(int $year, int $month, ?string $search = null): Collection;

    /**
     * 指定日付のタイムテーブルをアクティブ優先で取得
     */
    public function findForDatePrioritizingActive(string $dateStr): ?Timetable;

    /**
     * レーン・班長情報付きで全タイムテーブルを取得（日付順）
     *
     * @return Collection<int, Timetable>
     */
    public function getAllWithLanesAndLeadStaff(): Collection;

    /**
     * 搬出入計画ステップ数カウント付きで全タイムテーブルを取得（日付順）
     *
     * @return Collection<int, Timetable>
     */
    public function getAllWithLogisticsPlansCount(): Collection;

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
     * タイムテーブルの総登録数を取得
     */
    public function getTotalCount(): int;

    /**
     * ワークフロー詳細付きで単一タイムテーブルを取得
     */
    public function findWithWorkflowDetails(int $id): ?Timetable;

    /**
     * 人員配置一覧表（印刷・プレビュー）用のリレーションをロード
     */
    public function loadForAssignmentsPrint(Timetable $timetable): Timetable;

    /**
     * 人員アサイン専用ワークスペース用のリレーションをロード
     */
    public function loadForAssignmentWorkspace(Timetable $timetable): Timetable;

    /**
     * 作業タスク管理画面用のリレーションをロード
     */
    public function loadForTasksIndex(Timetable $timetable): Timetable;
}
