<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Error proveniente de Oracle (ORA-xxxxx) o de la capa OCI8.
 */
final class DatabaseException extends RuntimeException
{
    private string $oracleMessage;

    public function __construct(string $message, int $code = 0, ?string $sql = null)
    {
        $this->oracleMessage = $message;
        $text = $code > 0 ? sprintf('ORA-%05d: %s', $code, self::stripPrefix($message)) : $message;
        if ($sql !== null && config('app.debug')) {
            $text .= ' | SQL: ' . preg_replace('/\s+/', ' ', $sql);
        }
        parent::__construct($text, $code);
    }

    /**
     * @param array{code?: int, message?: string, sqltext?: string}|false $error
     */
    public static function fromOci(array|false $error, ?string $sql = null): self
    {
        if ($error === false) {
            return new self('Error desconocido de la base de datos.', 0, $sql);
        }

        return new self((string) ($error['message'] ?? 'Error de base de datos'), (int) ($error['code'] ?? 0), $sql);
    }

    public function oracleCode(): int
    {
        return (int) $this->getCode();
    }

    public function oracleMessage(): string
    {
        return $this->oracleMessage;
    }

    /**
     * Nombre de la restricción involucrada, p. ej. UQ_ALUMNO_CARNET.
     */
    public function constraint(): ?string
    {
        if (preg_match('/\(\s*"?[A-Z0-9_$#]+"?\s*\.\s*"?([A-Z0-9_$#]+)"?\s*\)/i', $this->oracleMessage, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }

    /** Errores de conectividad / autenticación contra el servidor Oracle. */
    public function isConnectionError(): bool
    {
        return in_array(
            $this->oracleCode(),
            [0, 1017, 12154, 12170, 12505, 12514, 12541, 12543, 12545, 3113, 3114, 28000, 28001],
            true
        );
    }

    private static function stripPrefix(string $message): string
    {
        return (string) preg_replace('/^ORA-\d+:\s*/', '', trim($message));
    }
}
