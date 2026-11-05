<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

abstract class Controller
{
    public function __construct(protected readonly Request $request)
    {
    }

    /** @param array<string, mixed> $data */
    protected function view(string $template, array $data = []): void
    {
        View::render($template, $data);
    }

    /** @param array<string, mixed> $query */
    protected function redirect(string $path, array $query = []): never
    {
        Response::redirect($path, $query);
    }

    /**
     * Regresa al formulario conservando lo capturado y mostrando los errores.
     *
     * @param array<string, string> $errors
     * @param array<string, mixed> $query
     */
    protected function backWithErrors(string $path, array $errors, array $query = [], ?string $message = null): never
    {
        $old = $this->request->post();
        unset($old['_token'], $old['password'], $old['password_confirmation'], $old['password_actual']);

        Session::flash('_errors', $errors);
        Session::flash('_old', $old);
        Session::flash('error', $message ?? 'Revise los campos marcados en el formulario.');

        $this->redirect($path, $query);
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }
}
