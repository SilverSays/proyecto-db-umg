<?php

declare(strict_types=1);

namespace App\Support;

final class Paginator
{
    public readonly int $total;
    public readonly int $perPage;
    public readonly int $page;
    public readonly int $pages;

    public function __construct(int $total, int $page, int $perPage)
    {
        $this->total = max(0, $total);
        $this->perPage = max(1, $perPage);
        $this->pages = max(1, (int) ceil($this->total / $this->perPage));
        $this->page = min(max(1, $page), $this->pages);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function from(): int
    {
        return $this->total === 0 ? 0 : $this->offset() + 1;
    }

    public function to(): int
    {
        return min($this->total, $this->offset() + $this->perPage);
    }

    /**
     * Ventana de páginas a mostrar (null = separador "…").
     *
     * @return list<int|null>
     */
    public function window(int $around = 2): array
    {
        $pages = [];
        for ($i = 1; $i <= $this->pages; $i++) {
            if ($i === 1 || $i === $this->pages || abs($i - $this->page) <= $around) {
                $pages[] = $i;
            } elseif (end($pages) !== null) {
                $pages[] = null;
            }
        }

        return $pages;
    }
}
