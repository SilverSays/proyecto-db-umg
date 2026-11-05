<?php
/**
 * @var App\Support\Paginator $paginator
 * @var string $path   ruta base del listado
 * @var array  $query  parámetros actuales (búsqueda, filtros, orden)
 */
$query = $query ?? [];
$link = static fn (int $p): string => url($path, array_merge($query, ['page' => $p]));
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 pt-3">
    <div class="section-note">
        <?php if ($paginator->total > 0): ?>
            Mostrando <strong><?= $paginator->from() ?>–<?= $paginator->to() ?></strong> de <strong><?= $paginator->total ?></strong> registros
        <?php else: ?>
            Sin registros
        <?php endif; ?>
    </div>
    <?php if ($paginator->pages > 1): ?>
        <nav aria-label="Paginación">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $paginator->page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e($link(max(1, $paginator->page - 1))) ?>" aria-label="Anterior"><i class="bi bi-chevron-left"></i></a>
                </li>
                <?php foreach ($paginator->window() as $p): ?>
                    <?php if ($p === null): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php else: ?>
                        <li class="page-item <?= $p === $paginator->page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e($link($p)) ?>"><?= $p ?></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <li class="page-item <?= $paginator->page >= $paginator->pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e($link(min($paginator->pages, $paginator->page + 1))) ?>" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
