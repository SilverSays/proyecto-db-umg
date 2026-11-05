<?php
/**
 * @var array<string, mixed>|null $usuario
 * @var array<string, string> $roles
 */
$isEdit = $usuario !== null;
$esYo = $isEdit && (int) $usuario['ID'] === (int) auth()['id'];
$action = $isEdit ? '/usuarios/' . $usuario['ID'] . '/actualizar' : '/usuarios/guardar';
$rolActual = (string) old('rol', $usuario['ROL'] ?? 'CONSULTA');
$hasOld = old('nombre', null) !== null;
$activo = $hasOld ? old('activo', null) !== null : (!$isEdit || (int) $usuario['ACTIVO'] === 1);

$field = static function (string $name) {
    $error = error_for($name);

    return [$error !== null ? ' is-invalid' : '', $error];
};
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $isEdit ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
        <?php if ($isEdit): ?><p class="page-subtitle">Usuario <?= e($usuario['USERNAME']) ?>, creado el <?= e(fecha((string) $usuario['FECHA_CREACION'], true)) ?></p><?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <form method="post" action="<?= e(url($action)) ?>" class="card needs-validation" novalidate>
            <?= csrf_field() ?>
            <div class="card-body p-4">
                <div class="row g-3">
                    <?php [$cls, $err] = $field('username'); ?>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" for="username">Usuario <span class="text-danger">*</span></label>
                        <?php if ($isEdit): ?>
                            <input type="text" id="username" class="form-control" value="<?= e($usuario['USERNAME']) ?>" disabled>
                            <div class="form-text">El nombre de usuario no puede modificarse.</div>
                        <?php else: ?>
                            <input type="text" id="username" name="username" class="form-control<?= $cls ?>" value="<?= e(old('username')) ?>"
                                   pattern="[a-z0-9._\-]{3,30}" maxlength="30" autocapitalize="none" required>
                            <div class="invalid-feedback <?= $err ? 'd-block' : '' ?>"><?= e($err ?? 'De 3 a 30 caracteres: minúsculas, números, punto o guiones.') ?></div>
                        <?php endif; ?>
                    </div>

                    <?php [$cls, $err] = $field('rol'); ?>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" for="rol">Rol <span class="text-danger">*</span></label>
                        <?php if ($esYo): ?>
                            <input type="hidden" name="rol" value="<?= e($usuario['ROL']) ?>">
                            <select id="rol" class="form-select" disabled><option><?= e($roles[$usuario['ROL']] ?? $usuario['ROL']) ?></option></select>
                            <div class="form-text">No puede cambiar su propio rol.</div>
                        <?php else: ?>
                            <select id="rol" name="rol" class="form-select<?= $cls ?>" required>
                                <?php foreach ($roles as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= $rolActual === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <?php if ($err): ?><div class="invalid-feedback d-block"><?= e($err) ?></div><?php endif; ?>
                    </div>

                    <?php [$cls, $err] = $field('nombre'); ?>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" for="nombre">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" id="nombre" name="nombre" class="form-control<?= $cls ?>" maxlength="120" required
                               value="<?= e(old('nombre', $usuario['NOMBRE'] ?? '')) ?>">
                        <div class="invalid-feedback <?= $err ? 'd-block' : '' ?>"><?= e($err ?? 'Ingrese el nombre completo.') ?></div>
                    </div>

                    <?php [$cls, $err] = $field('email'); ?>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" for="email">Correo electrónico <span class="text-danger">*</span></label>
                        <input type="email" id="email" name="email" class="form-control<?= $cls ?>" maxlength="100" required
                               value="<?= e(old('email', $usuario['EMAIL'] ?? '')) ?>">
                        <div class="invalid-feedback <?= $err ? 'd-block' : '' ?>"><?= e($err ?? 'Ingrese un correo válido.') ?></div>
                    </div>

                    <div class="col-12"><hr class="my-1"></div>

                    <?php [$cls, $err] = $field('password'); ?>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" for="password">
                            <?= $isEdit ? 'Nueva contraseña' : 'Contraseña' ?> <?php if (!$isEdit): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control js-strength<?= $cls ?>" maxlength="72"
                                   autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
                            <button class="btn btn-outline-secondary js-toggle-password" type="button" data-target="password" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="strength mt-2" data-for="password"><div class="strength-bar"></div></div>
                        <?php if ($err): ?><div class="invalid-feedback d-block"><?= e($err) ?></div><?php endif; ?>
                        <div class="form-text"><?= $isEdit ? 'Déjela vacía para conservar la actual. ' : '' ?>Mínimo 8 caracteres con mayúscula, minúscula, número y símbolo.</div>
                    </div>

                    <?php [$cls, $err] = $field('password_confirmation'); ?>
                    <div class="col-md-6">
                        <label class="form-label fw-medium" for="password_confirmation">Confirmar contraseña</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control<?= $cls ?>" maxlength="72" autocomplete="new-password">
                        <?php if ($err): ?><div class="invalid-feedback d-block"><?= e($err) ?></div><?php endif; ?>
                    </div>

                    <div class="col-12">
                        <?php if ($esYo): ?>
                            <input type="hidden" name="activo" value="1">
                        <?php endif; ?>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="activo" <?= $esYo ? 'disabled' : 'name="activo"' ?> value="1" <?= $activo ? 'checked' : '' ?>>
                            <label class="form-check-label" for="activo">Cuenta activa (puede iniciar sesión)</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-end gap-2 p-3">
                <a href="<?= e(url('/usuarios')) ?>" class="btn btn-light">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4"><?= $isEdit ? 'Guardar cambios' : 'Crear usuario' ?></button>
            </div>
        </form>
    </div>
    <div class="col-xl-4 mt-4 mt-xl-0">
        <div class="card info-card">
            <div class="card-body small">
                <h2 class="card-title-sm">Qué puede hacer cada rol</h2>
                <p class="mb-2"><span class="badge badge-soft-warning">Administrador</span> Puede crear, modificar y eliminar registros, gestionar usuarios y consultar la bitácora.</p>
                <p class="mb-0"><span class="badge badge-soft-info">Consulta</span> Acceso de solo lectura al panel, catálogos y reportes.</p>
            </div>
        </div>
    </div>
</div>
