<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * クエリビルダー経由の書き込み・削除時にも NavigationCache を自動無効化する拡張 Eloquent Builder
 *
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
class NavigationCacheInvalidatingBuilder extends Builder
{
    /**
     * 基底 Query\Builder へ透過転送される書き込み系メソッド名（小文字）
     *
     * @var array<int, string>
     */
    private const array MUTATING_PASSTHRU_METHODS = [
        'insert',
        'insertgetid',
        'insertorignore',
        'insertorignorereturning',
        'insertusing',
        'insertorignoreusing',
        'updateorinsert',
        'truncate',
    ];

    /**
     * Update records in the database.
     *
     * @param  array<string, mixed>  $values
     */
    #[Override]
    public function update(array $values): int
    {
        return $this->runWithCacheInvalidation(fn (): int => parent::update($values));
    }

    /**
     * Insert new records or update the existing ones.
     *
     * @param  array<int|string, mixed>  $values
     * @param  array<int, string>|string  $uniqueBy
     * @param  array<int, string>|null  $update
     */
    #[Override]
    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        return $this->runWithCacheInvalidation(fn (): int => parent::upsert($values, $uniqueBy, $update));
    }

    /**
     * Delete records from the database.
     */
    #[Override]
    public function delete(): mixed
    {
        return $this->runWithCacheInvalidation(fn (): mixed => parent::delete());
    }

    /**
     * Run the default delete function on the builder.
     */
    #[Override]
    public function forceDelete(): mixed
    {
        return $this->runWithCacheInvalidation(fn (): mixed => parent::forceDelete());
    }

    /**
     * Update the column's update timestamp.
     *
     * @param  array<int, string>|string|null  $column
     */
    #[Override]
    public function touch($column = null): int|false
    {
        return $this->runWithCacheInvalidation(fn (): int|false => parent::touch($column));
    }

    /**
     * Increment a column's value by a given amount.
     *
     * @param  array<string, mixed>  $extra
     */
    #[Override]
    public function increment($column, $amount = 1, array $extra = []): int
    {
        return $this->runWithCacheInvalidation(fn (): int => parent::increment($column, $amount, $extra));
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * @param  array<string, mixed>  $extra
     */
    #[Override]
    public function decrement($column, $amount = 1, array $extra = []): int
    {
        return $this->runWithCacheInvalidation(fn (): int => parent::decrement($column, $amount, $extra));
    }

    /**
     * Increment each given column's value by the given amounts.
     *
     * @param  array<string, float|int|numeric-string>  $columns
     * @param  array<string, mixed>  $extra
     */
    #[Override]
    public function incrementEach(array $columns, array $extra = []): int
    {
        return $this->runWithCacheInvalidation(fn (): int => parent::incrementEach($columns, $extra));
    }

    /**
     * Decrement each given column's value by the given amounts.
     *
     * @param  array<string, float|int|numeric-string>  $columns
     * @param  array<string, mixed>  $extra
     */
    #[Override]
    public function decrementEach(array $columns, array $extra = []): int
    {
        return $this->runWithCacheInvalidation(fn (): int => parent::decrementEach($columns, $extra));
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array<int, mixed>  $parameters
     */
    #[Override]
    public function __call($method, $parameters): mixed
    {
        $result = parent::__call($method, $parameters);

        if (in_array(strtolower((string) $method), self::MUTATING_PASSTHRU_METHODS, true)) {
            $this->invalidateNavigationCache();
        }

        return $result;
    }

    /**
     * 更新系クエリ実行後に NavigationCache を無効化する共通ラッパー
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $operation
     * @return TReturn
     */
    protected function runWithCacheInvalidation(callable $operation): mixed
    {
        $result = $operation();
        $this->invalidateNavigationCache();

        return $result;
    }

    /**
     * 対象モデルに対応するキャッシュを無効化
     */
    protected function invalidateNavigationCache(): void
    {
        if ($this->model !== null && app()->bound(NavigationCache::class)) {
            app(NavigationCache::class)->forgetForModel($this->model);
        }
    }
}
