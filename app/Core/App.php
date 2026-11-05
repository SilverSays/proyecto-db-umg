<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\OracleErrorTranslator;
use Throwable;

/**
 * Kernel HTTP: arranca la sesión, resuelve la ruta y maneja los errores.
 */
final class App
{
    public static function run(): void
    {
        $request = new Request();
        Response::securityHeaders();

        try {
            Session::start($request);

            $router = new Router();
            (static function (Router $router): void {
                require BASE_PATH . '/config/routes.php';
            })($router);

            $router->dispatch($request);
        } catch (HttpException $e) {
            self::renderError($e->status(), $e->getMessage());
        } catch (DatabaseException $e) {
            self::log($e);
            $message = $e->isConnectionError()
                ? 'No fue posible conectar con el servidor Oracle. Verifique que la base de datos esté en línea y que las credenciales del archivo .env sean correctas.'
                : OracleErrorTranslator::translate($e);
            self::renderError(503, $message, config('app.debug') ? $e->getMessage() : null);
        } catch (Throwable $e) {
            self::log($e);
            self::renderError(500, 'Ocurrió un error inesperado. El incidente fue registrado.', config('app.debug') ? $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() : null);
        }
    }

    private static function renderError(int $status, string $message, ?string $detail = null): void
    {
        if (!headers_sent()) {
            http_response_code($status);
        }

        $titles = [
            403 => 'Acceso denegado',
            404 => 'Página no encontrada',
            405 => 'Método no permitido',
            419 => 'Sesión de formulario expirada',
            500 => 'Error interno',
            503 => 'Servicio no disponible',
        ];

        try {
            View::render('errors/error', [
                'status' => $status,
                'title' => $titles[$status] ?? 'Error',
                'message' => $message,
                'detail' => $detail,
            ], 'layouts/blank');
        } catch (Throwable) {
            echo '<h1>' . $status . '</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }

    private static function log(Throwable $e): void
    {
        error_log(sprintf('[UMG] %s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
    }
}
