<?php

namespace App\Repositories\Contracts;

use App\Models\Timetable;
use Carbon\Carbon;
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
     * デフォルト運行日（準備中・運行中のうち現在日時に最も近い運行日）を取得
     */
    public function findFirstActiveOrEarliest(?Carbon $now = null): ?Timetable;

    /**
     * 運行情報セレクタ用の準備中・運行中タイムテーブル一覧を取得（時系列順）
     *
     * @return Collection<int, Timetable>
     */
    public function getSelectableTimetables(): Collection;

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
     * 運行準備用の先頭（日付昇順）の進行中・準備中タイムテーブルを1件取得
     */
    public function findFirstActiveOrDraftWithWorkflowDetails(): ?Timetable;

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

    /**
     * 複製元選択用の全タイムテーブル一覧を取得（日付降順・関連件数付き）
     *
     * @return Collection<int, Timetable>
     */
    public function getAllForCloneSelection(): Collection;

    /**
     * 複製元タイムテーブルを関連件数付きで取得
     */
    public function findForClone(int $id): ?Timetable;
}
