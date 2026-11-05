<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\BitacoraRepository;
use App\Repositories\UsuarioRepository;

final class Auth
{
    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_READONLY = 'CONSULTA';

    // Hash de una contraseña aleatoria: equilibra el tiempo de respuesta cuando el usuario no existe.
    private const DUMMY_HASH = '$2y$10$Y2o6Um1N7dJ1aCBlhe1eZ.616jCWRZwXn4T8KmFJhAd6wpB175.w6';

    private const GENERIC_ERROR = 'Usuario o contraseña incorrectos.';

    /**
     * @return array{ok: bool, message: string}
     */
    public static function attempt(string $username, string $password): array
    {
        $users = new UsuarioRepository();
        $audit = new BitacoraRepository();

        $maxAttempts = max(1, (int) config('app.login.max_attempts', 5));
        $lockMinutes = max(1, (int) config('app.login.lock_minutes', 15));

        $user = $users->findForLogin($username);

        if ($user === null) {
            password_verify($password, self::DUMMY_HASH);
            $audit->registrar('LOGIN_FALLIDO', 'USUARIO_APP', null, ['motivo' => 'usuario inexistente', 'username' => $username], null, $username);

            return ['ok' => false, 'message' => self::GENERIC_ERROR];
        }

        $id = (int) $user['ID'];

        if ((int) $user['BLOQUEADO'] === 1) {
            $audit->registrar('LOGIN_FALLIDO', 'USUARIO_APP', (string) $id, ['motivo' => 'cuenta bloqueada'], $id, (string) $user['USERNAME']);

            return [
                'ok' => false,
                'message' => 'La cuenta está bloqueada temporalmente por intentos fallidos. Intente de nuevo más tarde o contacte al administrador.',
            ];
        }

        if (!password_verify($password, (string) $user['PASSWORD_HASH'])) {
            $locked = $users->registerFailedAttempt($id, $maxAttempts, $lockMinutes);
            $audit->registrar($locked ? 'BLOQUEO' : 'LOGIN_FALLIDO', 'USUARIO_APP', (string) $id, ['motivo' => 'contraseña incorrecta'], $id, (string) $user['USERNAME']);

            return [
                'ok' => false,
                'message' => $locked
                    ? "Demasiados intentos fallidos. La cuenta se bloqueó por {$lockMinutes} minutos."
                    : self::GENERIC_ERROR,
            ];
        }

        if ((int) $user['ACTIVO'] !== 1) {
            $audit->registrar('LOGIN_FALLIDO', 'USUARIO_APP', (string) $id, ['motivo' => 'cuenta inactiva'], $id, (string) $user['USERNAME']);

            return ['ok' => false, 'message' => 'La cuenta se encuentra deshabilitada. Contacte al administrador.'];
        }

        // Actualiza el hash si cambió el algoritmo/costo por defecto de PHP.
        if (password_needs_rehash((string) $user['PASSWORD_HASH'], PASSWORD_DEFAULT)) {
            $users->updatePassword($id, $password);
        }

        $users->registerSuccessfulLogin($id);

        Session::regenerate();
        Csrf::rotate();
        Session::put('auth', [
            'id' => $id,
            'username' => (string) $user['USERNAME'],
            'nombre' => (string) $user['NOMBRE'],
            'email' => (string) $user['EMAIL'],
            'rol' => (string) $user['ROL'],
            'ultimo_acceso' => $user['ULTIMO_ACCESO'],
            'login_at' => time(),
        ]);

        $audit->registrar('LOGIN', 'USUARIO_APP', (string) $id);

        return ['ok' => true, 'message' => 'Bienvenido(a), ' . $user['NOMBRE'] . '.'];
    }

    public static function logout(): void
    {
        if (self::check()) {
            (new BitacoraRepository())->registrar('LOGOUT', 'USUARIO_APP', (string) self::id());
        }
        Session::destroy();
    }

    /** @return array{id: int, username: string, nombre: string, email: string, rol: string, ultimo_acceso: ?string, login_at: int}|null */
    public static function user(): ?array
    {
        $user = $_SESSION['auth'] ?? null;

        return is_array($user) ? $user : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user !== null ? (int) $user['id'] : null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['rol'] ?? null) === self::ROLE_ADMIN;
    }

    /** Actualiza los datos visibles del usuario en sesión (p. ej. tras editar su perfil). */
    public static function refresh(array $changes): void
    {
        if (self::check()) {
            $_SESSION['auth'] = array_merge($_SESSION['auth'], $changes);
        }
    }

    /**
     * Política de contraseñas: mínimo 8 caracteres con mayúscula, minúscula, número y símbolo.
     *
     * @return list<string>
     */
    public static function passwordPolicyErrors(string $password): array
    {
        $errors = [];
        if (mb_strlen($password) < 8) {
            $errors[] = 'al menos 8 caracteres';
        }
        if (strlen($password) > 72) {
            $errors[] = 'máximo 72 bytes';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'una letra mayúscula';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'una letra minúscula';
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'un número';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'un símbolo';
        }

        return $errors;
    }
}
