<?php
/**
 * @var int $status
 * @var string $title
 * @var string $message
 * @var string|null $detail
 */
?>
<main class="error-page">
    <div class="error-card login-hoja">
        <h1><?= e($title) ?></h1>
        <p class="mb-4"><?= e($message) ?></p>

        <?php if (!empty($detail)): ?>
            <pre class="error-detail mb-4"><?= e($detail) ?></pre>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= e(url(auth() !== null ? '/' : '/login')) ?>" class="btn btn-primary">
                <?= auth() !== null ? 'Ir al panel principal' : 'Iniciar sesión' ?>
            </a>
            <a href="<?= e(url('/')) ?>" class="btn btn-light js-back">Volver a la página anterior</a>
        </div>
        <p class="error-code mb-0">Código de respuesta HTTP <?= (int) $status ?>.</p>
    </div>
</main>
