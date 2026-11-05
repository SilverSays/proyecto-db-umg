<?php
/** @var array<string, mixed>|null $usuario */
$u = $usuario ?? [];
$esAdmin = ($u['ROL'] ?? '') === 'ADMIN';
?>
<header class="page-header">
    <div class="ficha-encabezado mb-0">
        <span class="avatar avatar-xl"><?= e(iniciales((string) ($u['NOMBRE'] ?? '?'))) ?></span>
        <div>
            <h1 class="page-title"><?= e($u['NOMBRE'] ?? 'Mi perfil') ?></h1>
            <p class="page-subtitle">
                Usuario <strong><?= e($u['USERNAME'] ?? '') ?></strong> con acceso
                <?= $esAdmin ? 'de administrador' : 'de solo lectura' ?>. <?= e($u['EMAIL'] ?? '') ?>
            </p>
        </div>
    </div>
</header>

<div class="row g-5">
    <div class="col-lg-4">
        <h2 class="section-title">Cuenta</h2>
        <div class="mini-stat"><span>Creada el</span><strong><?= e(fecha(isset($u['FECHA_CREACION']) ? (string) $u['FECHA_CREACION'] : null, true)) ?></strong></div>
        <div class="mini-stat"><span>Último acceso</span><strong><?= e(fecha(isset($u['ULTIMO_ACCESO']) ? (string) $u['ULTIMO_ACCESO'] : null, true)) ?></strong></div>
        <div class="mini-stat"><span>Rol</span><strong><?= $esAdmin ? 'Administrador' : 'Consulta' ?></strong></div>
    </div>

    <div class="col-lg-7 offset-lg-1">
        <form method="post" action="<?= e(url('/perfil/password')) ?>" class="card needs-validation" novalidate>
            <?= csrf_field() ?>
            <div class="card-body">
                <h2 class="section-title">Cambiar contraseña</h2>
                <?php foreach ([
                    'password_actual' => ['Contraseña actual', 'current-password'],
                    'password' => ['Nueva contraseña', 'new-password'],
                    'password_confirmation' => ['Repita la nueva contraseña', 'new-password'],
                ] as $name => [$label, $autocomplete]):
                    $err = error_for($name);
                    ?>
                    <div class="mb-3">
                        <label class="form-label" for="<?= $name ?>"><?= e($label) ?></label>
                        <div class="input-group">
                            <input type="password" id="<?= $name ?>" name="<?= $name ?>" maxlength="72" required autocomplete="<?= $autocomplete ?>"
                                   class="form-control<?= $name === 'password' ? ' js-strength' : '' ?><?= $err ? ' is-invalid' : '' ?>">
                            <button class="btn btn-outline-secondary js-toggle-password" type="button" data-target="<?= $name ?>" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
                        </div>
                        <?php if ($name === 'password'): ?>
                            <div class="strength mt-2" data-for="password"><div class="strength-bar"></div></div>
                            <div class="form-text">Use al menos 8 caracteres con mayúscula, minúscula, número y símbolo.</div>
                        <?php endif; ?>
                        <?php if ($err): ?><div class="invalid-feedback d-block"><?= e($err) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">Cambiar contraseña</button>
            </div>
        </form>
    </div>
</div>
