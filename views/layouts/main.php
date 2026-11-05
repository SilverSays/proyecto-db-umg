<?php

use App\Core\View;
use App\Support\Entidad;

/** @var string $content */
$user = auth();
$reportes = (array) config('reportes', []);
$esPanel = current_path() === '/';
?>
<!doctype html>
<html lang="es">
<head>
    <?php View::partial('head', ['pageTitle' => $pageTitle ?? null]); ?>
</head>
<body class="app-body">
<a class="visually-hidden-focusable skip-link" href="#contenido">Saltar al contenido</a>

<div class="app-shell">
    <!-- Menú: índice del archivador -->
    <aside class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="sidebar" aria-label="Menú principal">
        <div class="sidebar-inner">
            <div class="sidebar-brand">
                <a href="<?= e(url('/')) ?>" class="d-flex align-items-center gap-2 text-decoration-none">
                    <?= brand_mark() ?>
                    <span>
                        <span class="brand-name">Sistema Académico</span>
                        <span class="brand-sub">Universidad Mariano Gálvez</span>
                    </span>
                </a>
                <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Cerrar menú"></button>
            </div>

            <nav class="sidebar-nav">
                <a class="nav-link <?= nav_active('/', true) ?>" href="<?= e(url('/')) ?>">
                    <i class="bi bi-journal-text"></i><span>Panel principal</span>
                </a>

                <?php foreach (Entidad::grouped() as $grupo => $entidades): ?>
                    <div class="nav-section"><?= e($grupo) ?></div>
                    <?php foreach ($entidades as $ent): ?>
                        <a class="nav-link <?= nav_active('/gestion/' . $ent->slug) ?>" href="<?= e(url('/gestion/' . $ent->slug)) ?>">
                            <i class="bi <?= e($ent->icono()) ?>"></i><span><?= e($ent->titulo()) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <div class="nav-section">Reportes</div>
                <?php foreach ($reportes as $slug => $rep): ?>
                    <a class="nav-link <?= nav_active('/reportes/' . $slug) ?>" href="<?= e(url('/reportes/' . $slug)) ?>">
                        <i class="bi <?= e($rep['icono']) ?>"></i><span><?= e($rep['titulo']) ?></span>
                    </a>
                <?php endforeach; ?>

                <?php if (is_admin()): ?>
                    <div class="nav-section">Administración</div>
                    <a class="nav-link <?= nav_active('/usuarios') ?>" href="<?= e(url('/usuarios')) ?>">
                        <i class="bi bi-people"></i><span>Usuarios</span>
                    </a>
                    <a class="nav-link <?= nav_active('/bitacora') ?>" href="<?= e(url('/bitacora')) ?>">
                        <i class="bi bi-shield-check"></i><span>Bitácora</span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">Versión <?= e(config('app.version')) ?></div>
        </div>
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <button class="btn btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Abrir menú">
                <i class="bi bi-list fs-4"></i>
            </button>

            <nav class="topbar-trail text-truncate" aria-label="Ubicación">
                <?php if ($esPanel): ?>
                    <span class="actual">Panel principal</span>
                <?php else: ?>
                    <a href="<?= e(url('/')) ?>">Panel principal</a><span class="sep" aria-hidden="true">/</span><span class="actual"><?= e($pageTitle ?? '') ?></span>
                <?php endif; ?>
            </nav>

            <?php if ($user !== null): ?>
                <div class="ms-auto dropdown">
                    <button class="btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar"><?= e(iniciales($user['nombre'])) ?></span>
                        <span class="d-none d-md-inline text-start lh-sm">
                            <span class="d-block fw-bold"><?= e($user['nombre']) ?></span>
                            <span class="d-block role-tag"><?= is_admin() ? 'Administrador' : 'Solo lectura' ?></span>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header"><?= e($user['email']) ?></h6></li>
                        <li><a class="dropdown-item" href="<?= e(url('/perfil')) ?>">Mi perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="<?= e(url('/logout')) ?>" method="post">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger">Cerrar sesión</button>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </header>

        <main id="contenido" class="app-content sheet">
            <?php View::partial('flash'); ?>
            <?= $content ?>
        </main>

        <footer class="app-footer">
            <span>&copy; <?= date('Y') ?> <?= e(config('app.author')) ?>. Proyecto académico para la <?= e(config('app.institution')) ?>.</span>
            <span>PHP <?= e(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) ?> con Oracle Database</span>
        </footer>
    </div>
</div>

<!-- Confirmación de eliminación (formularios .js-confirm) -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-4">
                <h2 class="modal-title h4 mb-2" id="confirmModalTitle">¿Eliminar este registro?</h2>
                <p class="text-body-secondary mb-0" id="confirmModalText">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Conservar</button>
                <button type="button" class="btn btn-danger" id="confirmModalOk">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<?php View::partial('scripts'); ?>
</body>
</html>
