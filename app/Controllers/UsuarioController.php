<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\DatabaseException;
use App\Core\HttpException;
use App\Repositories\BitacoraRepository;
use App\Repositories\UsuarioRepository;
use App\Support\OracleErrorTranslator;
use App\Support\Validator;

/**
 * Administración de cuentas del portal (solo rol ADMIN).
 */
final class UsuarioController extends Controller
{
    private const ROLES = [Auth::ROLE_ADMIN => 'Administrador', Auth::ROLE_READONLY => 'Consulta (solo lectura)'];

    public function index(): void
    {
        $this->view('usuarios/index', [
            'pageTitle' => 'Usuarios del sistema',
            'usuarios' => (new UsuarioRepository())->all(),
            'roles' => self::ROLES,
        ]);
    }

    public function create(): void
    {
        $this->view('usuarios/form', [
            'pageTitle' => 'Nuevo usuario',
            'usuario' => null,
            'roles' => self::ROLES,
        ]);
    }

    public function store(): void
    {
        $repo = new UsuarioRepository();
        [$data, $errors] = $this->validate($repo, null);

        if ($errors !== []) {
            $this->backWithErrors('/usuarios/nuevo', $errors);
        }

        try {
            $id = $repo->create($data);
            (new BitacoraRepository())->registrar('INSERT', 'USUARIO_APP', (string) $id, [
                'username' => $data['username'], 'rol' => $data['rol'], 'activo' => $data['activo'],
            ]);
        } catch (DatabaseException $e) {
            $this->backWithErrors('/usuarios/nuevo', [], [], OracleErrorTranslator::translate($e));
        }

        $this->flash('success', "Usuario «{$data['username']}» creado correctamente.");
        $this->redirect('/usuarios');
    }

    public function edit(string $id): void
    {
        $usuario = $this->findOrFail($id);

        $this->view('usuarios/form', [
            'pageTitle' => 'Editar usuario',
            'usuario' => $usuario,
            'roles' => self::ROLES,
        ]);
    }

    public function update(string $id): void
    {
        $repo = new UsuarioRepository();
        $usuario = $this->findOrFail($id);
        $userId = (int) $usuario['ID'];

        [$data, $errors] = $this->validate($repo, $userId);

        // Protecciones: no quitarse a sí mismo el rol/estado y conservar al menos un administrador activo.
        if ($userId === Auth::id() && ($data['rol'] !== Auth::ROLE_ADMIN || $data['activo'] !== 1)) {
            $errors['rol'] = 'No puede quitarse a sí mismo el rol de administrador ni desactivar su propia cuenta.';
        }
        if (($data['rol'] !== Auth::ROLE_ADMIN || $data['activo'] !== 1) && $usuario['ROL'] === Auth::ROLE_ADMIN && $repo->countActiveAdmins($userId) === 0) {
            $errors['rol'] = 'Debe existir al menos un administrador activo.';
        }

        if ($errors !== []) {
            $this->backWithErrors("/usuarios/{$userId}/editar", $errors);
        }

        try {
            $repo->update($userId, $data);
            $cambios = [];
            foreach (['nombre' => 'NOMBRE', 'email' => 'EMAIL', 'rol' => 'ROL', 'activo' => 'ACTIVO'] as $k => $col) {
                if ((string) $usuario[$col] !== (string) $data[$k]) {
                    $cambios[$k] = ['antes' => $usuario[$col], 'despues' => $data[$k]];
                }
            }
            if ($data['password'] !== '') {
                $repo->updatePassword($userId, $data['password']);
                $cambios['password'] = 'restablecida por administrador';
            }
            if ($cambios !== []) {
                (new BitacoraRepository())->registrar('UPDATE', 'USUARIO_APP', (string) $userId, $cambios);
            }
        } catch (DatabaseException $e) {
            $this->backWithErrors("/usuarios/{$userId}/editar", [], [], OracleErrorTranslator::translate($e));
        }

        if ($userId === Auth::id()) {
            Auth::refresh(['nombre' => $data['nombre'], 'email' => $data['email']]);
        }

        $this->flash('success', "Usuario «{$usuario['USERNAME']}» actualizado.");
        $this->redirect('/usuarios');
    }

    public function unlock(string $id): void
    {
        $usuario = $this->findOrFail($id);
        (new UsuarioRepository())->unlock((int) $usuario['ID']);
        (new BitacoraRepository())->registrar('UPDATE', 'USUARIO_APP', (string) $usuario['ID'], ['accion' => 'desbloqueo de cuenta']);

        $this->flash('success', "La cuenta «{$usuario['USERNAME']}» fue desbloqueada.");
        $this->redirect('/usuarios');
    }

    public function destroy(string $id): void
    {
        $repo = new UsuarioRepository();
        $usuario = $this->findOrFail($id);
        $userId = (int) $usuario['ID'];

        if ($userId === Auth::id()) {
            $this->flash('error', 'No puede eliminar su propia cuenta.');
            $this->redirect('/usuarios');
        }
        if ($usuario['ROL'] === Auth::ROLE_ADMIN && $repo->countActiveAdmins($userId) === 0) {
            $this->flash('error', 'No se puede eliminar al único administrador activo.');
            $this->redirect('/usuarios');
        }

        $repo->delete($userId);
        (new BitacoraRepository())->registrar('DELETE', 'USUARIO_APP', (string) $userId, ['username' => $usuario['USERNAME']]);

        $this->flash('success', "Usuario «{$usuario['USERNAME']}» eliminado.");
        $this->redirect('/usuarios');
    }

    // ------------------------------------------------------------------ helpers

    /** @return array<string, mixed> */
    private function findOrFail(string $id): array
    {
        $usuario = ctype_digit($id) ? (new UsuarioRepository())->find((int) $id) : null;

        return $usuario ?? throw HttpException::notFound('El usuario no existe.');
    }

    /**
     * @return array{0: array{username: string, nombre: string, email: string, rol: string, activo: int, password: string}, 1: array<string, string>}
     */
    private function validate(UsuarioRepository $repo, ?int $id): array
    {
        $post = $this->request->post();
        $data = [
            'username' => mb_strtolower($this->request->string('username')),
            'nombre' => $this->request->string('nombre'),
            'email' => mb_strtolower($this->request->string('email')),
            'rol' => $this->request->string('rol'),
            'activo' => isset($post['activo']) ? 1 : 0,
            'password' => is_string($post['password'] ?? null) ? $post['password'] : '',
        ];
        $confirm = is_string($post['password_confirmation'] ?? null) ? $post['password_confirmation'] : '';
        $errors = [];

        if ($id === null) {
            if (!preg_match('/^[a-z0-9._-]{3,30}$/', $data['username'])) {
                $errors['username'] = 'El usuario debe tener de 3 a 30 caracteres: letras minúsculas, números, punto, guion o guion bajo.';
            } elseif ($repo->existsUsername($data['username'])) {
                $errors['username'] = 'El nombre de usuario ya está en uso.';
            }
        } else {
            unset($data['username']);
        }

        if ($data['nombre'] === '' || mb_strlen($data['nombre']) > 120) {
            $errors['nombre'] = 'Ingrese el nombre completo (máximo 120 caracteres).';
        }
        if (!Validator::email($data['email']) || mb_strlen($data['email']) > 100) {
            $errors['email'] = 'Ingrese un correo electrónico válido.';
        } elseif ($repo->existsEmail($data['email'], $id)) {
            $errors['email'] = 'El correo ya está asociado a otra cuenta.';
        }
        if (!array_key_exists($data['rol'], self::ROLES)) {
            $errors['rol'] = 'Seleccione un rol válido.';
        }

        if ($id === null || $data['password'] !== '') {
            $policy = Auth::passwordPolicyErrors($data['password']);
            if ($policy !== []) {
                $errors['password'] = 'La contraseña debe contener: ' . implode(', ', $policy) . '.';
            } elseif (!hash_equals($data['password'], $confirm)) {
                $errors['password_confirmation'] = 'La confirmación no coincide con la contraseña.';
            }
        }

        /** @var array{username: string, nombre: string, email: string, rol: string, activo: int, password: string} $data */
        return [$data, $errors];
    }
}
