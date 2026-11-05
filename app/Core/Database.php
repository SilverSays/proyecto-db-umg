<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use Throwable;

/**
 * Capa de acceso a Oracle mediante la extensión OCI8.
 *
 * - Todas las consultas usan variables de enlace (bind variables) para evitar
 *   inyección SQL y aprovechar el caché de cursores de Oracle.
 * - Soporta transacciones explícitas (commit / rollback).
 */
final class Database
{
    private static ?self $instance = null;

    /** @var resource|object Conexión OCI8 */
    private $connection;

    private bool $inTransaction = false;

    /**
     * @param array{host: string, port: int|string, service: string, user: string, password: string, charset: string} $config
     */
    private function __construct(array $config)
    {
        if (!function_exists('oci_connect')) {
            throw new DatabaseException('La extensión OCI8 de PHP no está habilitada en este servidor.');
        }

        $dsn = sprintf('//%s:%d/%s', $config['host'], (int) $config['port'], $config['service']);
        $connection = @oci_connect($config['user'], $config['password'], $dsn, $config['charset']);

        if ($connection === false) {
            throw DatabaseException::fromOci(oci_error());
        }

        $this->connection = $connection;

        // Formatos homogéneos para fechas y números en toda la sesión.
        $this->execute(
            "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'"
            . " NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS'"
            . " NLS_NUMERIC_CHARACTERS = '.,'"
            . " TIME_ZONE = '" . self::timezoneOffset() . "'"
        );
    }

    public static function instance(): self
    {
        return self::$instance ??= new self((array) config('database'));
    }

    /**
     * Ejecuta un SELECT y devuelve todas las filas como arreglos asociativos
     * (las llaves son los nombres de columna en MAYÚSCULAS, como los devuelve Oracle).
     *
     * @param array<string, scalar|null> $params
     * @return list<array<string, mixed>>
     */
    public function select(string $sql, array $params = []): array
    {
        $statement = $this->run($sql, $params);
        $rows = [];
        while (($row = oci_fetch_array($statement, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) !== false) {
            $rows[] = $row;
        }
        oci_free_statement($statement);

        return $rows;
    }

    /**
     * @param array<string, scalar|null> $params
     * @return array<string, mixed>|null
     */
    public function first(string $sql, array $params = []): ?array
    {
        $statement = $this->run($sql, $params);
        $row = oci_fetch_array($statement, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
        oci_free_statement($statement);

        return $row === false ? null : $row;
    }

    /**
     * @param array<string, scalar|null> $params
     */
    public function value(string $sql, array $params = []): mixed
    {
        $row = $this->first($sql, $params);

        return $row === null ? null : reset($row);
    }

    /**
     * Ejecuta INSERT / UPDATE / DELETE / DDL y devuelve las filas afectadas.
     *
     * @param array<string, scalar|null> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        $statement = $this->run($sql, $params);
        $rows = oci_num_rows($statement);
        oci_free_statement($statement);

        return (int) $rows;
    }

    /** Obtiene el siguiente valor de una secuencia (nombre validado). */
    public function nextVal(string $sequence): int
    {
        if (!preg_match('/^[A-Z][A-Z0-9_]{0,127}$/', $sequence)) {
            throw new InvalidArgumentException("Nombre de secuencia inválido: {$sequence}");
        }

        return (int) $this->value("SELECT {$sequence}.NEXTVAL FROM DUAL");
    }

    /**
     * Ejecuta el callback dentro de una transacción. Si ocurre cualquier
     * excepción se hace ROLLBACK; en caso contrario COMMIT.
     *
     * @template T
     * @param callable(self): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        if ($this->inTransaction) {
            return $callback($this); // transacción anidada: se une a la actual
        }

        $this->inTransaction = true;
        try {
            $result = $callback($this);
            if (!oci_commit($this->connection)) {
                throw DatabaseException::fromOci(oci_error($this->connection));
            }

            return $result;
        } catch (Throwable $e) {
            oci_rollback($this->connection);
            throw $e;
        } finally {
            $this->inTransaction = false;
        }
    }

    public function serverVersion(): string
    {
        return (string) (oci_server_version($this->connection) ?: 'Oracle Database');
    }

    /**
     * @param array<string, scalar|null> $params
     * @return resource|object
     */
    private function run(string $sql, array $params)
    {
        $statement = @oci_parse($this->connection, $sql);
        if ($statement === false) {
            throw DatabaseException::fromOci(oci_error($this->connection), $sql);
        }

        // Las variables enlazadas deben existir hasta oci_execute (se pasan por referencia).
        $binds = [];
        foreach ($params as $name => $value) {
            $binds[ltrim((string) $name, ':')] = match (true) {
                $value === null => null,
                is_bool($value) => $value ? '1' : '0',
                default => (string) $value,
            };
        }
        foreach (array_keys($binds) as $name) {
            if (!@oci_bind_by_name($statement, ':' . $name, $binds[$name], -1)) {
                $error = oci_error($statement);
                oci_free_statement($statement);
                throw DatabaseException::fromOci($error, $sql);
            }
        }

        $mode = $this->inTransaction ? OCI_NO_AUTO_COMMIT : OCI_COMMIT_ON_SUCCESS;
        if (!@oci_execute($statement, $mode)) {
            $error = oci_error($statement);
            oci_free_statement($statement);
            throw DatabaseException::fromOci($error, $sql);
        }

        return $statement;
    }

    /** Desfase horario actual de PHP, p. ej. "-06:00" para America/Guatemala. */
    private static function timezoneOffset(): string
    {
        return (new \DateTimeImmutable('now'))->format('P');
    }
}
