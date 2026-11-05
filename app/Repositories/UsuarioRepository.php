<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Acceso a la tabla USUARIO_APP (cuentas del portal).
 */
final class UsuarioRepository
{
    private const COLUMNS = 'pk_usuarioId AS ID, username AS USERNAME, nombre_completo AS NOMBRE, email AS EMAIL,'
        . ' rol AS ROL, activo AS ACTIVO, intentos_fallidos AS INTENTOS, bloqueado_hasta AS BLOQUEADO_HASTA,'
        . ' CASE WHEN bloqueado_hasta > LOCALTIMESTAMP THEN 1 ELSE 0 END AS BLOQUEADO,'
        . ' ultimo_acceso AS ULTIMO_ACCESO, fecha_creacion AS FECHA_CREACION';

    private readonly Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->db->select('SELECT ' . self::COLUMNS . ' FROM USUARIO_APP ORDER BY rol, username');
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->first('SELECT ' . self::COLUMNS . ' FROM USUARIO_APP WHERE pk_usuarioId = :p_id', ['p_id' => $id]);
    }

    /**
     * Incluye el hash de la contraseña: usar únicamente para autenticación.
     *
     * @return array<string, mixed>|null
     */
    public function findForLogin(string $username): ?array
    {
        return $this->db->first(
            'SELECT ' . self::COLUMNS . ', password_hash AS PASSWORD_HASH
               FROM USUARIO_APP
              WHERE LOWER(username) = LOWER(:p_username)',
            ['p_username' => $username]
        );
    }

    public function passwordHash(int $id): ?string
    {
        $hash = $this->db->value('SELECT password_hash FROM USUARIO_APP WHERE pk_usuarioId = :p_id', ['p_id' => $id]);

        return $hash === null ? null : (string) $hash;
    }

    public function existsUsername(string $username, ?int $exceptId = null): bool
    {
        return (int) $this->db->value(
            'SELECT COUNT(*) FROM USUARIO_APP WHERE LOWER(username) = LOWER(:p_u) AND pk_usuarioId <> NVL(:p_id, -1)',
            ['p_u' => $username, 'p_id' => $exceptId]
        ) > 0;
    }

    public function existsEmail(string $email, ?int $exceptId = null): bool
    {
        return (int) $this->db->value(
            'SELECT COUNT(*) FROM USUARIO_APP WHERE LOWER(email) = LOWER(:p_e) AND pk_usuarioId <> NVL(:p_id, -1)',
            ['p_e' => $email, 'p_id' => $exceptId]
        ) > 0;
    }

    public function countActiveAdmins(?int $exceptId = null): int
    {
        return (int) $this->db->value(
            "SELECT COUNT(*) FROM USUARIO_APP WHERE rol = 'ADMIN' AND activo = 1 AND pk_usuarioId <> NVL(:p_id, -1)",
            ['p_id' => $exceptId]
        );
    }

    /**
     * @param array{username: string, nombre: string, email: string, rol: string, activo: int, password: string} $data
     */
    public function create(array $data): int
    {
        $id = $this->db->nextVal('SEQ_USUARIO_APP');
        $this->db->execute(
            'INSERT INTO USUARIO_APP (pk_usuarioId, username, nombre_completo, email, password_hash, rol, activo, fecha_creacion)
             VALUES (:p_id, :p_username, :p_nombre, :p_email, :p_hash, :p_rol, :p_activo, LOCALTIMESTAMP)',
            [
                'p_id' => $id,
                'p_username' => $data['username'],
                'p_nombre' => $data['nombre'],
                'p_email' => $data['email'],
                'p_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'p_rol' => $data['rol'],
                'p_activo' => $data['activo'],
            ]
        );

        return $id;
    }

    /**
     * @param array{nombre: string, email: string, rol: string, activo: int} $data
     */
    public function update(int $id, array $data): void
    {
        $this->db->execute(
            'UPDATE USUARIO_APP
                SET nombre_completo = :p_nombre, email = :p_email, rol = :p_rol, activo = :p_activo
              WHERE pk_usuarioId = :p_id',
            ['p_nombre' => $data['nombre'], 'p_email' => $data['email'], 'p_rol' => $data['rol'], 'p_activo' => $data['activo'], 'p_id' => $id]
        );
    }

    public function updatePassword(int $id, string $plainPassword): void
    {
        $this->db->execute(
            'UPDATE USUARIO_APP SET password_hash = :p_hash WHERE pk_usuarioId = :p_id',
            ['p_hash' => password_hash($plainPassword, PASSWORD_DEFAULT), 'p_id' => $id]
        );
    }

    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM USUARIO_APP WHERE pk_usuarioId = :p_id', ['p_id' => $id]);
    }

    // ----------------------------------------------------- control de accesos

    /** Incrementa los intentos fallidos y bloquea si se alcanza el máximo. Devuelve true si quedó bloqueado. */
    public function registerFailedAttempt(int $id, int $maxAttempts, int $lockMinutes): bool
    {
        $this->db->execute(
            'UPDATE USUARIO_APP
                SET intentos_fallidos = intentos_fallidos + 1,
                    bloqueado_hasta = CASE WHEN intentos_fallidos + 1 >= :p_max
                                           THEN LOCALTIMESTAMP + NUMTODSINTERVAL(:p_min, \'MINUTE\')
                                           ELSE bloqueado_hasta END
              WHERE pk_usuarioId = :p_id',
            ['p_max' => $maxAttempts, 'p_min' => $lockMinutes, 'p_id' => $id]
        );

        $locked = (int) $this->db->value(
            'SELECT CASE WHEN bloqueado_hasta > LOCALTIMESTAMP THEN 1 ELSE 0 END FROM USUARIO_APP WHERE pk_usuarioId = :p_id',
            ['p_id' => $id]
        ) === 1;

        if ($locked) {
            $this->db->execute('UPDATE USUARIO_APP SET intentos_fallidos = 0 WHERE pk_usuarioId = :p_id', ['p_id' => $id]);
        }

        return $locked;
    }

    public function registerSuccessfulLogin(int $id): void
    {
        $this->db->execute(
            'UPDATE USUARIO_APP
                SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = LOCALTIMESTAMP
              WHERE pk_usuarioId = :p_id',
            ['p_id' => $id]
        );
    }

    public function unlock(int $id): void
    {
        $this->db->execute(
            'UPDATE USUARIO_APP SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE pk_usuarioId = :p_id',
            ['p_id' => $id]
        );
    }
}
