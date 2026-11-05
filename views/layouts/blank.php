<?php

use App\Core\View;

/** @var string $content */
?>
<!doctype html>
<html lang="es">
<head>
    <?php View::partial('head', ['pageTitle' => $pageTitle ?? ($title ?? null)]); ?>
</head>
<body class="blank-body">
<?= $content ?>
<?php View::partial('scripts'); ?>
</body>
</html>
