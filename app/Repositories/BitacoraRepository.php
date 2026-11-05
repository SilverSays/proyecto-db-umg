<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Auth\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Support\Paginator;

/**
 * Auditoría: registra accesos y operaciones de escritura en BITACORA.
 */
final class BitacoraRepository
{
    public const ACCIONES = ['LOGIN', 'LOGIN_FALLIDO', 'LOGOUT', 'BLOQUEO', 'INSERT', 'UPDATE', 'DELETE', 'PASSWORD'];

    private readonly Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /**
     * Inserta un evento. Si hay una transacción abierta, el registro se confirma
     * (o revierte) junto con la operación auditada.
     *
     * @param array<string, mixed>|string|null $detalle
     */
    public function registrar(
        string $accion,
        ?string $tabla = null,
        ?string $registro = null,
        array|string|null $detalle = null,
        ?int $usuarioId = null,
        ?string $username = null
    ): void {
        $user = Auth::user();
        $usuarioId ??= $user !== null ? (int) $user['id'] : null;
        $username ??= $user !== null ? (string) $user['username'] : 'anónimo';

        if (is_array($detalle)) {
            $detalle = json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
        if (is_string($detalle) && strlen($detalle) > 4000) {
            $detalle = mb_strcut($detalle, 0, 3990, 'UTF-8') . '…';
        }

        $this->db->execute(
            'INSERT INTO BITACORA (pk_bitacoraId, fk_usuarioId, username, accion, tabla, registro, detalle, ip, fecha)
             VALUES (SEQ_BITACORA.NEXTVAL, :p_uid, :p_user, :p_accion, :p_tabla, :p_registro, :p_detalle, :p_ip, LOCALTIMESTAMP)',
            [
                'p_uid' => $usuarioId,
                'p_user' => mb_strcut($username, 0, 30, 'UTF-8'),
                'p_accion' => $accion,
                'p_tabla' => $tabla,
                'p_registro' => $registro !== null ? mb_strcut($registro, 0, 100, 'UTF-8') : null,
                'p_detalle' => $detalle,
                'p_ip' => (new Request())->ip(),
            ]
        );
    }

    /**
     * @param array{usuario?: string, accion?: string, desde?: string, hasta?: string} $filtros
     * @return array{rows: list<array<string, mixed>>, paginator: Paginator}
     */
    public function paginate(array $filtros, int $page, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if (($filtros['usuario'] ?? '') !== '') {
            $where[] = 'LOWER(b.username) LIKE :p_usuario';
            $params['p_usuario'] = '%' . mb_strtolower($filtros['usuario']) . '%';
        }
        if (in_array($filtros['accion'] ?? '', self::ACCIONES, true)) {
            $where[] = 'b.accion = :p_accion';
            $params['p_accion'] = $filtros['accion'];
        }
        if (($filtros['desde'] ?? '') !== '') {
            $where[] = "b.fecha >= TO_TIMESTAMP(:p_desde, 'YYYY-MM-DD')";
            $params['p_desde'] = $filtros['desde'];
        }
        if (($filtros['hasta'] ?? '') !== '') {
            $where[] = "b.fecha < TO_TIMESTAMP(:p_hasta, 'YYYY-MM-DD') + INTERVAL '1' DAY";
            $params['p_hasta'] = $filtros['hasta'];
        }

        $whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';
        $total = (int) $this->db->value('SELECT COUNT(*) FROM BITACORA b' . $whereSql, $params);
        $paginator = new Paginator($total, $page, $perPage);

        $rows = $this->db->select(
            'SELECT b.pk_bitacoraId AS ID, b.username AS USERNAME, b.accion AS ACCION, b.tabla AS TABLA,
                    b.registro AS REGISTRO, b.detalle AS DETALLE, b.ip AS IP, b.fecha AS FECHA
               FROM BITACORA b' . $whereSql . '
           ORDER BY b.fecha DESC, b.pk_bitacoraId DESC
             OFFSET :p_off ROWS FETCH NEXT :p_lim ROWS ONLY',
            $params + ['p_off' => $paginator->offset(), 'p_lim' => $perPage]
        );

        return ['rows' => $rows, 'paginator' => $paginator];
    }

    /** @return list<array<string, mixed>> */
    public function recientes(int $limit = 6): array
    {
        return $this->db->select(
            "SELECT username AS USERNAME, accion AS ACCION, tabla AS TABLA, registro AS REGISTRO, fecha AS FECHA
               FROM BITACORA
              WHERE accion IN ('INSERT', 'UPDATE', 'DELETE')
           ORDER BY fecha DESC, pk_bitacoraId DESC
              FETCH FIRST :p_lim ROWS ONLY",
            ['p_lim' => $limit]
        );
    }
}
