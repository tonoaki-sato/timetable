<?php

namespace App\DTOs\ConeMap;

/**
 * カラーコーン・バリケード配置集計および機材在庫比較サマリー DTO
 */
readonly class ConeMapSummaryDTO
{
    /**
     * @param  array<string, int>  $colorBreakdown
     * @param  array<string, int>  $barStyleBreakdown
     * @param  array<string, array{
     *     key: string,
     *     label: string,
     *     registered: bool,
     *     equipment_name: string|null,
     *     stock_quantity: int|null,
     *     is_approximate: bool,
     *     unit: string,
     *     used_quantity: int,
     *     remaining_quantity: int|null,
     *     is_over_stock: bool
     * }>  $inventory
     */
    public function __construct(
        public int $conesCount,
        public int $standaloneConesCount,
        public int $linkedConesCount,
        public int $linkedGroupsCount,
        public int $weightsCount,
        public int $barsCount,
        public int $barricadesTypeACount,
        public int $barricadesResinPipeCount,
        public int $calloutsCount,
        public array $colorBreakdown,
        public array $barStyleBreakdown,
        public array $inventory,
    ) {}

    /**
     * JSON シリアライズ・配列出力用
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cones_count' => $this->conesCount,
            'standalone_cones_count' => $this->standaloneConesCount,
            'linked_cones_count' => $this->linkedConesCount,
            'linked_groups_count' => $this->linkedGroupsCount,
            'weights_count' => $this->weightsCount,
            'bars_count' => $this->barsCount,
            'barricades_type_a_count' => $this->barricadesTypeACount,
            'barricades_resin_pipe_count' => $this->barricadesResinPipeCount,
            'callouts_count' => $this->calloutsCount,
            'color_breakdown' => $this->colorBreakdown,
            'bar_style_breakdown' => $this->barStyleBreakdown,
            'inventory' => $this->inventory,
        ];
    }
}
