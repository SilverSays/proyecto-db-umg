<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Generación de URLs independiente de la carpeta donde se instale el proyecto
 * (raíz de un VirtualHost, subcarpeta de XAMPP, contenedor Docker, etc.).
 */
final class Url
{
    private static ?string $base = null;

    /** Prefijo base sin barra final; "" cuando la app está en la raíz del dominio. */
    public static function base(): string
    {
        if (self::$base !== null) {
            return self::$base;
        }

        $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
        $scriptDir = rtrim($scriptDir, '/.');

        $requestPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');

        // Si se accede a través del .htaccess de la raíz (sin "/public" en la URL),
        // el prefijo real es la carpeta del proyecto.
        if ($scriptDir !== '' && !str_starts_with($requestPath, $scriptDir . '/') && $requestPath !== $scriptDir) {
            $scriptDir = (string) preg_replace('#/public$#', '', $scriptDir);
        }

        return self::$base = $scriptDir;
    }

    /** @param array<string, mixed> $query */
    public static function to(string $path = '/', array $query = []): string
    {
        $url = self::base() . '/' . ltrim($path, '/');
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $url === '' ? '/' : $url;
    }

    public static function asset(string $path): string
    {
        $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
        $version = is_file($file) ? (string) filemtime($file) : '1';

        return self::base() . '/assets/' . ltrim($path, '/') . '?v=' . $version;
    }
}
