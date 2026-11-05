<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\DatabaseException;
use App\Core\Session;
use App\Core\View;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        View::render('auth/login', [
            'pageTitle' => 'Iniciar sesión',
            'showDemo' => (bool) config('app.show_demo_credentials'),
        ], 'layouts/blank');
    }

    public function login(): void
    {
        $username = $this->request->string('username');
        $password = (string) ($this->request->post()['password'] ?? '');

        if ($username === '' || $password === '') {
            Session::flash('_old', ['username' => $username]);
            $this->flash('error', 'Ingrese su usuario y contraseña.');
            $this->redirect('/login');
        }

        if (mb_strlen($username) > 30 || strlen($password) > 200) {
            $this->flash('error', 'Usuario o contraseña incorrectos.');
            $this->redirect('/login');
        }

        try {
            $result = Auth::attempt($username, $password);
        } catch (DatabaseException $e) {
            error_log('[UMG] Login: ' . $e->getMessage());
            Session::flash('_old', ['username' => $username]);
            $this->flash('error', $e->isConnectionError()
                ? 'No hay conexión con la base de datos Oracle. Intente más tarde.'
                : 'No fue posible validar las credenciales. Verifique que el script 03 de la base de datos se haya ejecutado.');
            $this->redirect('/login');
        }

        if (!$result['ok']) {
            Session::flash('_old', ['username' => $username]);
            $this->flash('error', $result['message']);
            $this->redirect('/login');
        }

        $this->flash('success', $result['message']);

        $intended = Session::get('_intended');
        Session::forget('_intended');
        // Solo rutas relativas del mismo sitio (evita redirecciones abiertas a dominios externos).
        if (is_string($intended)
            && preg_match('#^/(?![/\\\\])[^\r\n]*$#', $intended) === 1
            && str_starts_with($intended, \App\Core\Url::base() . '/')
            && !str_contains($intended, '/login')
        ) {
            header('Location: ' . $intended, true, 303);
            exit;
        }

        $this->redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::start($this->request);
        $this->flash('success', 'Sesión cerrada correctamente.');
        $this->redirect('/login');
    }
}
