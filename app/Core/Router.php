<?php

declare(strict_types=1);

namespace App\Core;

use App\Auth\Auth;

/**
 * Enrutador minimalista con parámetros de ruta y middlewares.
 *
 * Middlewares disponibles:
 *   guest  – solo usuarios NO autenticados (login)
 *   auth   – requiere sesión iniciada
 *   admin  – requiere rol ADMIN
 * Toda petición POST valida automáticamente el token CSRF.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, handler: array{0: class-string, 1: string}, middleware: list<string>}> */
    private array $routes = [];

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     */
    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     */
    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function dispatch(Request $request): void
    {
        $path = $request->path();
        $methodMismatch = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $request->method()) {
                $methodMismatch = true;
                continue;
            }

            $params = array_values(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));

            if ($request->isPost() && !Csrf::validate($request->input(Csrf::FIELD))) {
                throw new HttpException(419, 'El formulario expiró o el token de seguridad no es válido. Recargue la página e intente de nuevo.');
            }

            foreach ($route['middleware'] as $middleware) {
                $this->applyMiddleware($middleware);
            }

            [$class, $method] = $route['handler'];
            $controller = new $class($request);
            $controller->{$method}(...$params);
            return;
        }

        throw $methodMismatch
            ? new HttpException(405, 'Método HTTP no permitido para esta ruta.')
            : HttpException::notFound('La página que busca no existe.');
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     */
    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $pattern = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[A-Za-z0-9_-]+)', rtrim($path, '/') ?: '/');
        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $pattern . '$#',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    private function applyMiddleware(string $name): void
    {
        switch ($name) {
            case 'guest':
                if (Auth::check()) {
                    Response::redirect('/');
                }
                break;

            case 'auth':
                if (!Auth::check()) {
                    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                        Session::put('_intended', $_SERVER['REQUEST_URI'] ?? null);
                    }
                    Session::flash('info', 'Inicie sesión para continuar.');
                    Response::redirect('/login');
                }
                Response::noCache();
                break;

            case 'admin':
                if (!Auth::isAdmin()) {
                    throw HttpException::forbidden('Esta sección requiere privilegios de administrador.');
                }
                break;

            default:
                throw new \LogicException("Middleware desconocido: {$name}");
        }
    }
}
