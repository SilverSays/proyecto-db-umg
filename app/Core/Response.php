<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** @param array<string, mixed> $query */
    public static function redirect(string $path, array $query = []): never
    {
        $target = preg_match('#^https?://#i', $path) ? $path : Url::to($path, $query);
        header('Location: ' . $target, true, 303);
        exit;
    }

    /** Envía las cabeceras de seguridad comunes a todas las respuestas. */
    public static function securityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "script-src 'self' https://cdn.jsdelivr.net; "
            . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
            . "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; "
            . "img-src 'self' data:; connect-src 'self'; form-action 'self'; "
            . "frame-ancestors 'none'; base-uri 'self'; object-src 'none'"
        );
        header_remove('X-Powered-By');
    }

    public static function noCache(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    /**
     * Descarga de CSV (UTF-8 con BOM para compatibilidad con Excel).
     *
     * @param list<string> $headers
     * @param iterable<list<scalar|null>> $rows
     */
    public static function csv(string $filename, array $headers, iterable $rows): never
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'export.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        self::noCache();

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers, ',', '"', '\\');
        foreach ($rows as $row) {
            // Neutraliza fórmulas (CSV injection) en celdas que inician con = + - @
            $safe = array_map(
                static fn ($v) => is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true) && !is_numeric($v)
                    ? "'" . $v
                    : $v,
                $row
            );
            fputcsv($out, $safe, ',', '"', '\\');
        }
        fclose($out);
        exit;
    }
}
