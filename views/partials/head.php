<?php /** @var string|null $pageTitle */ ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Sistema de gestión académica — <?= e(config('app.institution')) ?>">
<meta name="robots" content="noindex, nofollow">
<title><?= e(($pageTitle ?? '') !== '' ? $pageTitle . ' | ' : '') ?><?= e(config('app.name')) ?></title>
<?php if (is_file(BASE_PATH . '/public/assets/img/favicon-umg.png')): ?>
<link rel="icon" type="image/png" href="<?= e(asset('img/favicon-umg.png')) ?>">
<?php else: ?>
<link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Alegreya:ital,wght@0,400..700;1,400..500&family=Alegreya+Sans:ital,wght@0,400;0,500;0,700;1,400&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
      integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
