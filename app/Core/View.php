<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Motor de plantillas basado en PHP nativo con layouts.
 * Toda salida dinámica en las vistas debe pasar por e() (htmlspecialchars).
 */
final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/main'): void
    {
        $content = self::capture($template, $data);

        if ($layout === null) {
            echo $content;
            return;
        }

        echo self::capture($layout, array_merge($data, ['content' => $content]));
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function capture(string $template, array $data = []): string
    {
        $file = BASE_PATH . '/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Vista no encontrada: {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }

    /** Incluye un parcial dentro de otra vista. */
    public static function partial(string $template, array $data = []): void
    {
        echo self::capture('partials/' . $template, $data);
    }
}
