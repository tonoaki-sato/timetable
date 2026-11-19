<?php

namespace App\DTOs;

readonly class GlobalSearchResultDTO
{
    public function __construct(
        public string $category,
        public string $categoryLabel,
        public string $icon,
        public string $title,
        public string $subtitle,
        public string $url,
        public ?string $badge = null,
    ) {}

    /**
     * 配列へ変換
     *
     * @return array{
     *     category: string,
     *     category_label: string,
     *     icon: string,
     *     title: string,
     *     subtitle: string,
     *     url: string,
     *     badge: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'category_label' => $this->categoryLabel,
            'icon' => $this->icon,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'url' => $this->url,
            'badge' => $this->badge,
        ];
    }
}
