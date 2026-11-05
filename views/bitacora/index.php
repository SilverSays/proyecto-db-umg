<?php

use App\Core\View;

/**
 * @var list<array<string, mixed>> $rows
 * @var App\Support\Paginator $paginator
 * @var array{usuario: string, accion: string, desde: string, hasta: string} $filtros
 * @var list<string> $acciones
 */
$query = array_filter($filtros, static fn ($v) => $v !== '');
$estilos = [
    'LOGIN' => ['badge-soft-success', 'Inicio de sesión'],
    'LOGOUT' => ['badge-soft-secondary', 'Cierre de sesión'],
    'LOGIN_FALLIDO' => ['badge-soft-danger', 'Intento fallido'],
    'BLOQUEO' => ['badge-soft-danger', 'Cuenta bloqueada'],
    'INSERT' => ['badge-soft-success', 'Registro'],
    'UPDATE' => ['badge-soft-primary', 'Modificación'],
    'DELETE' => ['badge-soft-danger', 'Eliminación'],
    'PASSWORD' => ['badge-soft-warning', 'Cambio de contraseña'],
];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Bitácora de auditoría</h1>
        <p class="page-subtitle">Quién entró al sistema y qué registros creó, modificó o eliminó.</p>
    </div>
</div>

<div class="card card-table">
    <form method="get" action="<?= e(url('/bitacora')) ?>" class="table-toolbar">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1" for="usuario">Usuario</label>
                <input type="search" class="form-control" id="usuario" name="usuario" value="<?= e($filtros['usuario']) ?>" maxlength="30" placeholder="Todos">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="accion">Acción</label>
                <select class="form-select" id="accion" name="accion">
                    <option value="">Todas</option>
                    <?php foreach ($acciones as $a): ?>
                        <option value="<?= e($a) ?>" <?= $filtros['accion'] === $a ? 'selected' : '' ?>><?= e($estilos[$a][1] ?? $a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="desde">Desde</label>
                <input type="date" class="form-control" id="desde" name="desde" value="<?= e($filtros['desde']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="hasta">Hasta</label>
                <input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($filtros['hasta']) ?>">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-dark flex-grow-1">Filtrar</button>
                <?php if ($query !== []): ?><a href="<?= e(url('/bitacora')) ?>" class="btn btn-light" >Limpiar</a><?php endif; ?>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-sm-text">
            <thead>
            <tr><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Tabla</th><th>Registro</th><th>Detalle</th><th>IP</th></tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="7"><div class="empty-state"><p class="mb-0">No hay eventos para los filtros seleccionados.</p></div></td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r):
                [$cls, $etiqueta] = $estilos[$r['ACCION']] ?? ['badge-soft-secondary', (string) $r['ACCION']];
                $detalle = (string) ($r['DETALLE'] ?? '');
                $json = json_decode($detalle, true);
                ?>
                <tr>
                    <td class="text-nowrap small"><?= e(fecha((string) $r['FECHA'], true)) ?></td>
                    <td class="fw-medium">@<?= e($r['USERNAME']) ?></td>
                    <td><span class="badge <?= $cls ?>"><?= e($etiqueta) ?></span></td>
                    <td><code><?= e($r['TABLA'] ?? '—') ?></code></td>
                    <td><?= e($r['REGISTRO'] ?? '—') ?></td>
                    <td class="detalle-cell">
                        <?php if (is_array($json)): ?>
                            <details>
                                <summary class="small"><?= count($json) ?> campo(s)</summary>
                                <pre class="small mb-0 mt-1"><?= e(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                            </details>
                        <?php else: ?>
                            <span class="small text-body-secondary"><?= e($detalle !== '' ? $detalle : '—') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-body-secondary"><?= e($r['IP'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php View::partial('pagination', ['paginator' => $paginator, 'path' => '/bitacora', 'query' => $query]); ?>
</div>
