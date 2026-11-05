<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Repositorio de configuración. Cada archivo de /config se expone por su nombre:
 * config('app.name'), config('entidades.alumnos'), etc.
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    public static function load(string $directory): void
    {
        foreach (glob(rtrim($directory, '/\\') . '/*.php') ?: [] as $file) {
            $name = basename($file, '.php');
            if ($name === 'routes') {
                continue; // las rutas se cargan aparte, en el kernel HTTP
            }
            // Cada archivo se evalúa en un ámbito aislado
            self::$items[$name] = (static fn (string $path): mixed => require $path)($file);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
