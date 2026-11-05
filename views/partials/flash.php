<?php

use App\Core\Session;

$icons = ['success' => 'bi-check-circle-fill', 'error' => 'bi-exclamation-octagon-fill', 'warning' => 'bi-exclamation-triangle-fill', 'info' => 'bi-info-circle-fill'];
$classes = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];

foreach (Session::messages() as $msg): ?>
    <div class="alert alert-<?= $classes[$msg['type']] ?> alert-dismissible fade show d-flex align-items-start gap-2 shadow-sm flash"
         role="alert" <?= $msg['type'] === 'success' ? 'data-autohide="6000"' : '' ?>>
        <i class="bi <?= $icons[$msg['type']] ?> fs-5 lh-1 mt-1"></i>
        <div class="flex-grow-1"><?= e($msg['message']) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endforeach;
