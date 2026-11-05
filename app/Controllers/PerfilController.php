<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Repositories\BitacoraRepository;
use App\Repositories\UsuarioRepository;

final class PerfilController extends Controller
{
    public function show(): void
    {
        $this->view('perfil/show', [
            'pageTitle' => 'Mi perfil',
            'usuario' => (new UsuarioRepository())->find((int) Auth::id()),
        ]);
    }

    public function updatePassword(): void
    {
        $post = $this->request->post();
        $actual = is_string($post['password_actual'] ?? null) ? $post['password_actual'] : '';
        $nueva = is_string($post['password'] ?? null) ? $post['password'] : '';
        $confirm = is_string($post['password_confirmation'] ?? null) ? $post['password_confirmation'] : '';

        $repo = new UsuarioRepository();
        $id = (int) Auth::id();
        $hash = $repo->passwordHash($id);
        $errors = [];

        if ($hash === null || !password_verify($actual, $hash)) {
            $errors['password_actual'] = 'La contraseña actual no es correcta.';
        }
        $policy = Auth::passwordPolicyErrors($nueva);
        if ($policy !== []) {
            $errors['password'] = 'La contraseña debe contener: ' . implode(', ', $policy) . '.';
        } elseif (!hash_equals($nueva, $confirm)) {
            $errors['password_confirmation'] = 'La confirmación no coincide.';
        } elseif ($hash !== null && password_verify($nueva, $hash)) {
            $errors['password'] = 'La nueva contraseña debe ser distinta a la actual.';
        }

        if ($errors !== []) {
            $this->backWithErrors('/perfil', $errors);
        }

        $repo->updatePassword($id, $nueva);
        (new BitacoraRepository())->registrar('PASSWORD', 'USUARIO_APP', (string) $id, 'Cambio de contraseña por el propio usuario');

        $this->flash('success', 'Su contraseña fue actualizada correctamente.');
        $this->redirect('/perfil');
    }
}
