<?php

use App\Core\View;

/**
 * @var string $slug
 * @var array<string, mixed> $reporte
 * @var list<array<string, mixed>> $rows
 * @var App\Support\Paginator $paginator
 * @var array<string, list<string>> $opciones
 * @var string $q
 * @var array<string, string> $f
 */
$path = '/reportes/' . $slug;
$query = array_filter(['q' => $q, 'f' => $f], static fn ($v) => $v !== '' && $v !== []);
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($reporte['titulo']) ?></h1>
        <p class="page-subtitle"><?= e($reporte['descripcion']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary js-print">Imprimir</button>
        <a href="<?= e(url($path . '/exportar', $query)) ?>" class="btn btn-primary">Exportar CSV</a>
    </div>
</div>

<div class="card card-table">
    <form method="get" action="<?= e(url($path)) ?>" class="table-toolbar d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-xl">
                <div class="input-group">
                    <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Buscar…" maxlength="100" aria-label="Buscar">
                </div>
            </div>
            <?php foreach ((array) $reporte['filtros'] as $col => $label): ?>
                <div class="col-6 col-md-auto">
                    <select name="f[<?= e($col) ?>]" class="form-select js-autosubmit" aria-label="Filtrar por <?= e($label) ?>">
                        <option value=""><?= e($label) ?>: todos</option>
                        <?php foreach ($opciones[$col] ?? [] as $op): ?>
                            <option value="<?= e($op) ?>" <?= ($f[$col] ?? '') === $op ? 'selected' : '' ?>><?= e($op) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
            <div class="col-auto">
                <button class="btn btn-dark" type="submit">Filtrar</button>
                <?php if ($query !== []): ?>
                    <a class="btn btn-link text-decoration-none" href="<?= e(url($path)) ?>">Limpiar</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <?php foreach ((array) $reporte['columnas'] as $label): ?>
                    <th scope="col"><?= e($label) ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="<?= count((array) $reporte['columnas']) ?>"><div class="empty-state"><p class="mb-0">Ningún registro coincide con la búsqueda.</p></div></td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach (array_keys((array) $reporte['columnas']) as $col): ?>
                        <td><?= celda_reporte($col, $reporte['formatos'][$col] ?? null, $row) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php View::partial('pagination', ['paginator' => $paginator, 'path' => $path, 'query' => $query]); ?>
</div>
<p class="section-note mt-3 mb-0">Datos de la vista <code><?= e($reporte['vista']) ?></code>, consultados el <?= date('d/m/Y') ?> a las <?= date('H:i') ?>.</p>
