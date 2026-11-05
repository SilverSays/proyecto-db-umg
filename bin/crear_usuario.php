<?php

declare(strict_types=1);

/*
 * Herramienta de consola para crear o restablecer cuentas del portal.
 *
 *   php bin/crear_usuario.php <usuario> <rol> "<nombre completo>" <email>
 *   php bin/crear_usuario.php --reset <usuario>
 *
 * La contraseña se solicita de forma interactiva (no queda en el historial).
 * Roles: ADMIN | CONSULTA
 */

use App\Auth\Auth;
use App\Core\DatabaseException;
use App\Repositories\UsuarioRepository;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

function prompt_password(string $label): string
{
    echo $label;
    if (DIRECTORY_SEPARATOR === '/') {
        system('stty -echo');
        $value = (string) fgets(STDIN);
        system('stty echo');
        echo PHP_EOL;
    } else {
        $value = (string) fgets(STDIN); // En Windows la entrada es visible
    }

    return rtrim($value, "\r\n");
}

function fail(string $message): never
{
    fwrite(STDERR, "\033[31m✖ {$message}\033[0m" . PHP_EOL);
    exit(1);
}

function ask_new_password(): string
{
    $password = prompt_password('Contraseña: ');
    $errors = Auth::passwordPolicyErrors($password);
    if ($errors !== []) {
        fail('La contraseña debe contener: ' . implode(', ', $errors) . '.');
    }
    if (!hash_equals($password, prompt_password('Confirmar contraseña: '))) {
        fail('Las contraseñas no coinciden.');
    }

    return $password;
}

$args = array_slice($argv, 1);
$repo = new UsuarioRepository();

try {
    if (($args[0] ?? '') === '--reset') {
        $username = strtolower((string) ($args[1] ?? ''));
        $user = $username !== '' ? $repo->findForLogin($username) : null;
        if ($user === null) {
            fail("El usuario «{$username}» no existe.");
        }
        $repo->updatePassword((int) $user['ID'], ask_new_password());
        $repo->unlock((int) $user['ID']);
        echo "\033[32m✔ Contraseña de «{$username}» restablecida y cuenta desbloqueada.\033[0m" . PHP_EOL;
        exit(0);
    }

    if (count($args) < 4) {
        echo "Uso:\n  php bin/crear_usuario.php <usuario> <ADMIN|CONSULTA> \"<nombre completo>\" <email>\n"
            . "  php bin/crear_usuario.php --reset <usuario>\n";
        exit(1);
    }

    [$username, $rol, $nombre, $email] = $args;
    $username = strtolower($username);
    $rol = strtoupper($rol);

    if (!preg_match('/^[a-z0-9._-]{3,30}$/', $username)) {
        fail('Nombre de usuario inválido (3-30 caracteres: a-z, 0-9, punto, guiones).');
    }
    if (!in_array($rol, [Auth::ROLE_ADMIN, Auth::ROLE_READONLY], true)) {
        fail('Rol inválido. Use ADMIN o CONSULTA.');
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        fail('Correo electrónico inválido.');
    }
    if ($repo->existsUsername($username)) {
        fail("El usuario «{$username}» ya existe. Use --reset para cambiar su contraseña.");
    }

    $id = $repo->create([
        'username' => $username,
        'nombre' => $nombre,
        'email' => strtolower($email),
        'rol' => $rol,
        'activo' => 1,
        'password' => ask_new_password(),
    ]);

    echo "\033[32m✔ Usuario «{$username}» ({$rol}) creado con ID {$id}.\033[0m" . PHP_EOL;
} catch (DatabaseException $e) {
    fail('Error de base de datos: ' . $e->getMessage());
}
