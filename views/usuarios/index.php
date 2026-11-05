<?php
/**
 * @var list<array<string, mixed>> $usuarios
 * @var array<string, string> $roles
 */
$me = auth();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Usuarios del sistema</h1>
        <p class="page-subtitle">Cuentas con acceso al portal. Las contraseñas se almacenan con hash bcrypt.</p>
    </div>
    <a href="<?= e(url('/usuarios/nuevo')) ?>" class="btn btn-primary">Nuevo usuario</a>
</div>

<div class="card card-table">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>Usuario</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Último acceso</th>
                <th class="text-end">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u):
                $esYo = (int) $u['ID'] === (int) $me['id'];
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm"><?= e(iniciales((string) $u['NOMBRE'])) ?></span>
                            <div>
                                <div class="fw-semibold"><?= e($u['NOMBRE']) ?> <?php if ($esYo): ?><span class="badge text-bg-light">Usted</span><?php endif; ?></div>
                                <div class="small text-body-secondary">@<?= e($u['USERNAME']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($u['EMAIL']) ?></td>
                    <td>
                        <span class="badge rounded-pill <?= $u['ROL'] === 'ADMIN' ? 'badge-soft-warning' : 'badge-soft-info' ?>">
                            <?= e($roles[$u['ROL']] ?? $u['ROL']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ((int) $u['BLOQUEADO'] === 1): ?>
                            <span class="badge rounded-pill badge-soft-danger" title="Hasta <?= e(fecha((string) $u['BLOQUEADO_HASTA'], true)) ?>">Bloqueado</span>
                        <?php elseif ((int) $u['ACTIVO'] === 1): ?>
                            <span class="badge rounded-pill badge-soft-success">Activo</span>
                        <?php else: ?>
                            <span class="badge rounded-pill badge-soft-secondary">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-body-secondary text-nowrap"><?= e(fecha($u['ULTIMO_ACCESO'] === null ? null : (string) $u['ULTIMO_ACCESO'], true)) ?></td>
                    <td class="text-end text-nowrap">
                        <?php if ((int) $u['BLOQUEADO'] === 1): ?>
                            <form action="<?= e(url('/usuarios/' . $u['ID'] . '/desbloquear')) ?>" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-icon btn-light text-warning" data-bs-toggle="tooltip" title="Desbloquear"><i class="bi bi-unlock"></i></button>
                            </form>
                        <?php endif; ?>
                        <a href="<?= e(url('/usuarios/' . $u['ID'] . '/editar')) ?>" class="btn btn-sm btn-icon btn-light" data-bs-toggle="tooltip" title="Editar"><i class="bi bi-pencil-square"></i></a>
                        <?php if (!$esYo): ?>
                            <form action="<?= e(url('/usuarios/' . $u['ID'] . '/eliminar')) ?>" method="post" class="d-inline js-confirm"
                                  data-confirm="Se eliminará la cuenta «<?= e($u['USERNAME']) ?>». Sus registros en la bitácora se conservarán.">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-icon btn-light text-danger" data-bs-toggle="tooltip" title="Eliminar"><i class="bi bi-trash3"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
