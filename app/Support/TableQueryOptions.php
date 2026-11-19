<?php

namespace App\Support;

class TableQueryOptions
{
    /**
     * ソート列・方向をホワイトリスト検証して解決
     *
     * @param  array<int, string>  $allowedColumns
     * @return array{sort: string, direction: 'asc'|'desc'}
     */
    public static function resolveSort(
        ?string $sort,
        ?string $direction,
        array $allowedColumns,
        string $defaultSort,
        string $defaultDirection = 'asc',
    ): array {
        $resolvedSort = ($sort !== null && in_array($sort, $allowedColumns, true))
            ? $sort
            : $defaultSort;

        $normalizedDefault = strtolower($defaultDirection) === 'desc' ? 'desc' : 'asc';
        if ($direction === null || $direction === '') {
            $resolvedDirection = $normalizedDefault;
        } else {
            $lower = strtolower($direction);
            $resolvedDirection = in_array($lower, ['asc', 'desc'], true) ? $lower : $normalizedDefault;
        }

        return [
            'sort' => $resolvedSort,
            'direction' => $resolvedDirection,
        ];
    }

    /**
     * 表示件数（per_page）をホワイトリスト検証して解決
     *
     * @param  array<int, int>  $allowed
     */
    public static function resolvePerPage(
        mixed $perPage,
        int $default = 15,
        array $allowed = [15, 30, 50, 100],
    ): int {
        if ($perPage === null || $perPage === '') {
            return $default;
        }

        $intVal = (int) $perPage;

        return in_array($intVal, $allowed, true) ? $intVal : $default;
    }
}
