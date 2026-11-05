<?php

use App\Core\View;

/** @var bool $showDemo */
?>
<main class="login-page">
    <section class="login-sello" aria-label="Universidad Mariano Gálvez de Guatemala">
        <?= brand_mark('lg') ?>
        <p class="login-lema">Conoceréis la verdad, y la verdad os hará libres.</p>
        <p class="login-lema-fuente">Lema de la Universidad Mariano Gálvez de Guatemala, fundada en 1966.</p>
    </section>

    <section class="login-hoja">
        <h1>Sistema Académico</h1>
        <p class="login-inst">Ingrese con su cuenta para consultar o actualizar el registro académico.</p>

        <?php View::partial('flash'); ?>

        <form method="post" action="<?= e(url('/login')) ?>" class="needs-validation" novalidate autocomplete="on">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="username" class="form-label">Usuario</label>
                <input type="text" class="form-control form-control-lg" id="username" name="username" maxlength="30"
                       value="<?= e(old('username')) ?>" autocomplete="username" autocapitalize="none"
                       spellcheck="false" required autofocus>
                <div class="invalid-feedback">Escriba su nombre de usuario.</div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group input-group-lg">
                    <input type="password" class="form-control" id="password" name="password" maxlength="200"
                           autocomplete="current-password" required>
                    <button class="btn btn-outline-secondary js-toggle-password" type="button" data-target="password" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye"></i>
                    </button>
                    <div class="invalid-feedback">Escriba su contraseña.</div>
                </div>
                <div class="form-text d-none" id="capsWarning">Bloq Mayús está activado.</div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">Ingresar</button>
        </form>

        <?php if ($showDemo): ?>
            <div class="demo-credentials">
                <p>Cuentas de demostración</p>
                <button type="button" class="demo-fill" data-user="admin" data-pass="Admin#2026">
                    <span><strong>admin</strong>, contraseña <strong>Admin#2026</strong></span>
                    <small>Puede consultar, registrar, modificar y eliminar</small>
                    <span class="demo-usar">Usar</span>
                </button>
                <button type="button" class="demo-fill" data-user="consulta" data-pass="Consulta#2026">
                    <span><strong>consulta</strong>, contraseña <strong>Consulta#2026</strong></span>
                    <small>Solo puede consultar</small>
                    <span class="demo-usar">Usar</span>
                </button>
            </div>
        <?php endif; ?>

        <p class="login-pie">La sesión se cierra tras <?= (int) round((int) config('app.session.idle_timeout', 1800) / 60) ?> minutos sin actividad. Proyecto académico de <?= e(config('app.author')) ?>.</p>
    </section>
</main>
