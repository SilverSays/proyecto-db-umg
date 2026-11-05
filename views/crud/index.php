<?php

use App\Core\View;

/**
 * @var App\Support\Entidad $entidad
 * @var list<array<string, mixed>> $rows
 * @var App\Support\Paginator $paginator
 * @var array<string, string> $alias
 * @var array{q: string, f: array<string, string>, orden: string, dir: string} $state
 * @var array<string, array<string, string>> $opcionesFiltro
 */
$path = '/gestion/' . $entidad->slug;
$query = array_filter(['q' => $state['q'], 'f' => $state['f'], 'orden' => $state['orden'], 'dir' => $state['dir']], static fn ($v) => $v !== '' && $v !== []);
$backQuery = http_build_query($query + ['page' => $paginator->page]);
$hayFiltros = $state['q'] !== '' || $state['f'] !== [];
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($entidad->titulo()) ?></h1>
        <p class="page-subtitle"><?= e($entidad->descripcion()) ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e(url($path . '/exportar', $query)) ?>" class="btn btn-outline-secondary">
            Exportar CSV
        </a>
        <?php if (is_admin()): ?>
            <a href="<?= e(url($path . '/nuevo')) ?>" class="btn btn-primary">
                Agregar <?= e(mb_strtolower($entidad->singular())) ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card card-table">
    <!-- Barra de búsqueda y filtros -->
    <form method="get" action="<?= e(url($path)) ?>" class="table-toolbar">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-xl">
                <div class="input-group">
                    <input type="search" name="q" value="<?= e($state['q']) ?>" class="form-control"
                           placeholder="Buscar por <?= e(mb_strtolower(implode(', ', array_map(fn ($c) => $entidad->etiqueta($c), $entidad->busqueda())))) ?>…"
                           maxlength="100" aria-label="Buscar">
                </div>
            </div>
            <?php foreach ($entidad->filtros() as $col => $campo): ?>
                <div class="col-6 col-md-auto">
                    <select name="f[<?= e($col) ?>]" class="form-select js-autosubmit" aria-label="Filtrar por <?= e($campo['etiqueta']) ?>">
                        <option value=""><?= e($campo['etiqueta']) ?>: todos</option>
                        <?php foreach ($opcionesFiltro[$col] ?? [] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= (string) ($state['f'][$col] ?? '') === (string) $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
            <input type="hidden" name="orden" value="<?= e($state['orden']) ?>">
            <input type="hidden" name="dir" value="<?= e($state['dir']) ?>">
            <div class="col-auto">
                <button class="btn btn-dark" type="submit">Filtrar</button>
                <?php if ($hayFiltros): ?>
                    <a class="btn btn-link text-decoration-none" href="<?= e(url($path)) ?>">Limpiar</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <?php foreach ($entidad->listado() as $col): ?>
                    <th scope="col"><?= sort_link($path, $col, $entidad->etiqueta($col), $state) ?></th>
                <?php endforeach; ?>
                <th scope="col" class="text-end">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="<?= count($entidad->listado()) + 1 ?>">
                        <div class="empty-state">
                            <p class="mb-1 fw-semibold"><?= $hayFiltros ? 'No hay resultados para los criterios indicados.' : 'Aún no hay registros.' ?></p>
                            <?php if (is_admin() && !$hayFiltros): ?>
                                <a href="<?= e(url($path . '/nuevo')) ?>" class="btn btn-sm btn-primary mt-2">Registrar el primero</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($rows as $row):
                $key = $entidad->llaveDeFila($row);
                $descripcion = $entidad->describir($row, $alias);
                ?>
                <tr>
                    <?php foreach ($entidad->listado() as $col): ?>
                        <td><?= celda($col, (array) $entidad->campo($col), $row, $alias) ?></td>
                    <?php endforeach; ?>
                    <td class="text-end text-nowrap">
                        <?php foreach ($entidad->acciones() as $accion):
                            $ruta = preg_replace_callback('/\{([A-Z_]+)\}/', static fn ($m) => rawurlencode((string) ($row[$m[1]] ?? '')), $accion['ruta']);
                            ?>
                            <a href="<?= e(url((string) $ruta)) ?>" class="btn btn-sm btn-icon btn-light" data-bs-toggle="tooltip" title="<?= e($accion['titulo']) ?>">
                                <i class="bi <?= e($accion['icono']) ?>"></i>
                            </a>
                        <?php endforeach; ?>

                        <?php if (is_admin()): ?>
                            <a href="<?= e(url($path . '/editar', ['key' => $key])) ?>" class="btn btn-sm btn-icon btn-light" data-bs-toggle="tooltip" title="Editar">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="<?= e(url($path . '/eliminar')) ?>" method="post" class="d-inline js-confirm"
                                  data-confirm="Se eliminará «<?= e($descripcion) ?>». Esta acción no se puede deshacer.">
                                <?= csrf_field() ?>
                                <?php foreach ($key as $k => $v): ?>
                                    <input type="hidden" name="key[<?= e($k) ?>]" value="<?= e($v) ?>">
                                <?php endforeach; ?>
                                <input type="hidden" name="_back" value="<?= e($backQuery) ?>">
                                <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" data-bs-toggle="tooltip" title="Eliminar">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php View::partial('pagination', ['paginator' => $paginator, 'path' => $path, 'query' => $query]); ?>
</div>

<?php if (!is_admin()): ?>
    <p class="text-body-secondary small mt-3">Su cuenta tiene acceso de solo lectura. Contacte a un administrador para realizar cambios.</p>
<?php endif; ?>
