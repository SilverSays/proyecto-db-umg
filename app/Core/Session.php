<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Manejo de sesión endurecido:
 *  - cookie HttpOnly + SameSite=Lax (+ Secure bajo HTTPS)
 *  - modo estricto (rechaza IDs de sesión no inicializados por el servidor)
 *  - expiración por inactividad
 *  - mensajes flash y "old input" para formularios
 */
final class Session
{
    private const FLASH_KEY = '_flash';
    private const FLASH_NEXT = '_flash_next';

    public static function start(Request $request): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.gc_maxlifetime', (string) max(1440, (int) config('app.session.idle_timeout', 1800)));

        session_name((string) config('app.session.name', 'UMG_SESSID'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $request->isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        self::ageFlash();
        self::enforceIdleTimeout();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Regenera el ID de sesión (prevención de fijación de sesión). */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
            session_destroy();
        }
    }

    // ---------------------------------------------------------------- flash

    /** Mensaje disponible solo en la siguiente petición. */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION[self::FLASH_NEXT][$key] = $value;
    }

    /** Lee un valor flash de la petición actual. */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        return $_SESSION[self::FLASH_KEY][$key] ?? $default;
    }

    /** @return list<array{type: string, message: string}> */
    public static function messages(): array
    {
        $messages = [];
        foreach (['success', 'error', 'warning', 'info'] as $type) {
            $value = self::getFlash($type);
            if (is_string($value) && $value !== '') {
                $messages[] = ['type' => $type, 'message' => $value];
            }
        }

        return $messages;
    }

    private static function ageFlash(): void
    {
        $_SESSION[self::FLASH_KEY] = $_SESSION[self::FLASH_NEXT] ?? [];
        $_SESSION[self::FLASH_NEXT] = [];
    }

    private static function enforceIdleTimeout(): void
    {
        $timeout = (int) config('app.session.idle_timeout', 1800);
        $now = time();
        $last = (int) ($_SESSION['_last_activity'] ?? $now);

        if (isset($_SESSION['auth']) && $timeout > 0 && ($now - $last) > $timeout) {
            $_SESSION = [];
            self::regenerate();
            self::ageFlash();
            self::flash('warning', 'Su sesión expiró por inactividad. Ingrese nuevamente.');
        }

        $_SESSION['_last_activity'] = $now;
    }
}
