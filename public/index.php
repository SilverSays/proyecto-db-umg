<?php

declare(strict_types=1);

/*
 * Front controller: toda petición HTTP entra por aquí.
 * El DocumentRoot del servidor web debe apuntar a esta carpeta (/public)
 * para que el código fuente, la configuración y el archivo .env no sean accesibles.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

App\Core\App::run();
