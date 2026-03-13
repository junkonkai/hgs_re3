<?php

namespace App\Support;

class Pager
{
    public function __construct(
        private readonly int $currentPage,
        private readonly int $totalPages,
        private readonly string $routeName,
        private readonly array $routeParams = [],
        private readonly string $dataHgnScope = 'full'
    ) {
    }

    public function hasMultiplePages(): bool
    {
        return $this->totalPages > 1;
    }

    public function showFirst(): bool
    {
        return $this->currentPage > 1;
    }

    public function showPrev(): bool
    {
        return $this->currentPage > 1;
    }

    public function showNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function showLast(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function firstPageUrl(): string
    {
        return $this->urlForPage(1);
    }

    public function prevPageUrl(): string
    {
        return $this->urlForPage($this->currentPage - 1);
    }

    public function nextPageUrl(): string
    {
        return $this->urlForPage($this->currentPage + 1);
    }

    public function lastPageUrl(): string
    {
        return $this->urlForPage($this->totalPages);
    }

    public function urlForPage(int $page): string
    {
        return route($this->routeName, array_merge($this->routeParams, ['page' => $page]));
    }

    /**
     * 表示するページ番号の配列（現在を中心に前後2、最大5件）
     *
     * @return int[]
     */
    public function pageNumbers(): array
    {
        $start = max(1, $this->currentPage - 2);
        $end = min($this->totalPages, $this->currentPage + 2);

        return range($start, $end);
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function totalPages(): int
    {
        return $this->totalPages;
    }

    public function dataHgnScope(): string
    {
        return $this->dataHgnScope;
    }
}
