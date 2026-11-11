<?php

namespace App\Repositories\Contracts;

use App\Models\Lane;
use App\Models\Staff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface StaffRepositoryInterface
{
    /**
     * IDによるスタッフ取得
     */
    public function findById(int $id): ?Staff;

    /**
     * IDによるスタッフ取得（存在しない場合は例外）
     */
    public function findOrFail(int $id): Staff;

    /**
     * 複数IDによるスタッフ一括取得
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, Staff>
     */
    public function findByIds(array $ids): Collection;

    /**
     * スタッフ一覧の取得（検索・絞り込み・ページネーション）
     */
    public function paginateFiltered(?string $search = null, ?bool $status = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * 稼働可能な全スタッフの取得（アサイン選択用・フリガナ順）
     *
     * @return Collection<int, Staff>
     */
    public function getActiveStaffList(): Collection;

    /**
     * 稼働可能なスタッフを所属・氏名順で取得
     *
     * @return Collection<int, Staff>
     */
    public function getActiveOrderedByAffiliation(): Collection;

    /**
     * 稼働可能なスタッフを指定IDリスト（省略時は全員）で取得（フリガナ順）
     *
     * @param  array<int, int>|null  $ids
     * @return Collection<int, Staff>
     */
    public function getActiveByIds(?array $ids = null): Collection;

    /**
     * 有効なスタッフ総数を取得
     */
    public function getActiveCount(): int;

    /**
     * 有効な班長・専属（固定）スタッフ数を取得
     */
    public function getActiveLeaderCount(): int;

    /**
     * スタッフ新規登録
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Staff;

    /**
     * スタッフ情報更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Staff $staff, array $attributes): bool;

    /**
     * 指定ID群のスタッフ種別を一括更新
     *
     * @param  array<int, int>  $ids
     */
    public function updateStaffTypeByIds(array $ids, string $staffType): int;

    /**
     * スタッフ削除
     */
    public function delete(Staff $staff): bool;

    /**
     * アクセストークンによるスタッフ取得
     */
    public function findByAccessToken(string $token): ?Staff;

    /**
     * 氏名・フリガナによる有効スタッフ検索（照合用）
     *
     * @return Collection<int, Staff>
     */
    public function findByNameAndKana(string $name, string $kana): Collection;

    /**
     * 氏名・フリガナによるスタッフ1件取得（アクティブ状態問わず・CSVインポート用）
     */
    public function findFirstByNameAndKana(string $name, string $kana): ?Staff;

    /**
     * 全スタッフをID順でチャンク処理（CSVエクスポート用）
     */
    public function chunkAllOrderedById(int $chunkSize, callable $callback): bool;

    /**
     * マイタイムテーブル画面用にアサイン関連をロードし、班長を務めるレーン一覧を取得
     *
     * @return Collection<int, Lane>
     */
    public function loadShiftRelationsAndGetLedLanes(Staff $staff): Collection;
}
