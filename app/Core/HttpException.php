<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(int $status, string $message = '')
    {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return (int) $this->getCode();
    }

    public static function notFound(string $message = 'El recurso solicitado no existe.'): self
    {
        return new self(404, $message);
    }

    public static function forbidden(string $message = 'No tiene permisos para realizar esta acción.'): self
    {
        return new self(403, $message);
    }
}
