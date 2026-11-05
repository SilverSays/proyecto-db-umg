<?php

declare(strict_types=1);

/*
 * Funciones auxiliares globales usadas por vistas y controladores.
 */

use App\Auth\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\Session;
use App\Core\Url;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('e')) {
    /** Escapa texto para HTML (previene XSS). */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /** @param array<string, mixed> $query */
    function url(string $path = '/', array $query = []): string
    {
        return Url::to($path, $query);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return Url::asset($path);
    }
}

if (!function_exists('logo_url')) {
    /**
     * URL del logotipo institucional si existe en public/assets/img/
     * (logo-umg.svg | .png | .webp | .jpg); null para usar el ícono genérico.
     */
    function logo_url(): ?string
    {
        static $url = false;
        if ($url === false) {
            $url = null;
            foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
                if (is_file(BASE_PATH . "/public/assets/img/logo-umg.{$ext}")) {
                    $url = Url::asset("img/logo-umg.{$ext}");
                    break;
                }
            }
        }

        return $url;
    }
}

if (!function_exists('brand_mark')) {
    /** Logotipo (o ícono de respaldo) con el tamaño indicado: '' | 'lg'. */
    function brand_mark(string $size = '', string $extraClass = ''): string
    {
        $logo = logo_url();
        $sizeClass = $size === 'lg' ? ' brand-logo-lg' : '';
        if ($logo !== null) {
            return '<img src="' . e($logo) . '" alt="Logotipo de la Universidad Mariano Gálvez de Guatemala" class="brand-logo'
                . $sizeClass . ' ' . e($extraClass) . '">';
        }

        return '<span class="brand-mark' . ($size === 'lg' ? ' brand-mark-lg' : '') . ' ' . e($extraClass) . '"><i class="bi bi-mortarboard-fill"></i></span>';
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('old')) {
    /** Valor enviado previamente en un formulario que no pasó la validación. */
    function old(string $key, mixed $default = ''): mixed
    {
        $old = Session::getFlash('_old', []);

        return is_array($old) && array_key_exists($key, $old) ? $old[$key] : $default;
    }
}

if (!function_exists('errors')) {
    /** @return array<string, string> */
    function errors(): array
    {
        $errors = Session::getFlash('_errors', []);

        return is_array($errors) ? $errors : [];
    }
}

if (!function_exists('error_for')) {
    function error_for(string $field): ?string
    {
        return errors()[$field] ?? null;
    }
}

if (!function_exists('auth')) {
    /** @return array<string, mixed>|null */
    function auth(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return Auth::isAdmin();
    }
}

if (!function_exists('current_path')) {
    function current_path(): string
    {
        return (new App\Core\Request())->path();
    }
}

if (!function_exists('nav_active')) {
    /** Devuelve "active" si la ruta actual comienza con el prefijo dado. */
    function nav_active(string $prefix, bool $exact = false): string
    {
        $path = current_path();
        $match = $exact ? $path === $prefix : ($path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/'));

        return $match ? 'active' : '';
    }
}

if (!function_exists('fecha')) {
    /** Convierte "YYYY-MM-DD[ HH:MI:SS]" al formato local dd/mm/aaaa. */
    function fecha(?string $value, bool $withTime = false): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $date = DateTimeImmutable::createFromFormat($withTime ? 'Y-m-d H:i:s' : 'Y-m-d', substr($value, 0, $withTime ? 19 : 10));

        return $date ? $date->format($withTime ? 'd/m/Y H:i' : 'd/m/Y') : $value;
    }
}

if (!function_exists('numero')) {
    function numero(mixed $value, int $decimals = 0): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, $decimals, '.', ',');
    }
}

if (!function_exists('iniciales')) {
    function iniciales(string $nombre): string
    {
        $parts = preg_split('/\s+/u', trim($nombre)) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $letters !== '' ? $letters : '?';
    }
}

if (!function_exists('json_script')) {
    /** JSON seguro para incrustar en <script type="application/json">. */
    function json_script(mixed $data): string
    {
        return (string) json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    }
}
