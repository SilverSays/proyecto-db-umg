<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Envoltura inmutable de la petición HTTP actual.
 */
final class Request
{
    public function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /** Ruta relativa a la aplicación, p. ej. "/gestion/alumnos". */
    public function path(): string
    {
        $uri = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
        $uri = rawurldecode($uri);

        $base = Url::base();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        if (str_starts_with($uri, '/index.php')) {
            $uri = substr($uri, strlen('/index.php'));
        }

        return '/' . trim($uri, '/');
    }

    /** Valor de POST o GET (POST tiene prioridad). */
    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /** Valor de texto saneado (recorta espacios, descarta arreglos). */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        return is_scalar($value) && preg_match('/^-?\d+$/', (string) $value) ? (int) $value : $default;
    }

    /** @return array<string, mixed> */
    public function array(string $key): array
    {
        $value = $this->input($key, []);

        return is_array($value) ? $value : [];
    }

    /** @return array<string, mixed> */
    public function post(): array
    {
        return $_POST;
    }

    /** @return array<string, mixed> */
    public function query(): array
    {
        return $_GET;
    }

    public function ip(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }
}
