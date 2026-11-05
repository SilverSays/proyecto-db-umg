<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

/**
 * Validación del lado del servidor a partir de los metadatos de la entidad.
 * (La validación HTML5 del navegador es solo una ayuda de usabilidad.)
 */
final class Validator
{
    /**
     * @param array<string, mixed> $input
     * @return array{0: array<string, string|int|float|null>, 1: array<string, string>} [datos limpios, errores]
     */
    public static function entidad(Entidad $entidad, array $input): array
    {
        $clean = [];
        $errors = [];

        foreach ($entidad->editables() as $col => $campo) {
            $label = (string) ($campo['etiqueta'] ?? $col);
            $raw = $input[$col] ?? '';
            $raw = is_scalar($raw) ? trim((string) $raw) : '';

            if (!empty($campo['mayusculas'])) {
                $raw = mb_strtoupper($raw);
            }

            if ($raw === '') {
                if (!empty($campo['requerido'])) {
                    $errors[$col] = "El campo «{$label}» es obligatorio.";
                }
                $clean[$col] = null;
                continue;
            }

            $error = self::campo($campo, $raw, $label);
            if ($error !== null) {
                $errors[$col] = $error;
                continue;
            }

            $clean[$col] = match ($campo['tipo'] ?? 'text') {
                'number', 'select' => (int) $raw,
                'decimal' => round((float) $raw, (int) ($campo['decimales'] ?? 2)),
                default => $raw,
            };
        }

        // Reglas entre campos
        foreach ($entidad->editables() as $col => $campo) {
            if (isset($errors[$col]) || ($clean[$col] ?? null) === null) {
                continue;
            }
            if (isset($campo['despues_de'])) {
                $other = (string) $campo['despues_de'];
                if (($clean[$other] ?? null) !== null && (string) $clean[$col] <= (string) $clean[$other]) {
                    $errors[$col] = sprintf('«%s» debe ser posterior a «%s».', $campo['etiqueta'], $entidad->etiqueta($other));
                }
            }
            if (isset($campo['distinto_de'])) {
                $other = (string) $campo['distinto_de'];
                if (($clean[$other] ?? null) !== null && $clean[$col] === $clean[$other]) {
                    $errors[$col] = sprintf('«%s» no puede ser igual a «%s».', $campo['etiqueta'], $entidad->etiqueta($other));
                }
            }
        }

        return [$clean, $errors];
    }

    /** @param array<string, mixed> $campo */
    private static function campo(array $campo, string $value, string $label): ?string
    {
        $tipo = (string) ($campo['tipo'] ?? 'text');

        if (isset($campo['max']) && mb_strlen($value) > (int) $campo['max']) {
            return "«{$label}» admite como máximo {$campo['max']} caracteres.";
        }
        // Oracle VARCHAR2 se mide en bytes por defecto
        if (isset($campo['max']) && strlen($value) > (int) $campo['max'] * 4) {
            return "«{$label}» es demasiado largo.";
        }

        switch ($tipo) {
            case 'email':
                if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    return "«{$label}» no es un correo electrónico válido.";
                }
                break;

            case 'number':
            case 'select':
                if (!preg_match('/^-?\d{1,10}$/', $value)) {
                    return $tipo === 'select' ? "Seleccione una opción válida para «{$label}»." : "«{$label}» debe ser un número entero.";
                }
                $error = self::rango((float) $value, $campo, $label);
                if ($error !== null) {
                    return $error;
                }
                break;

            case 'decimal':
                $dec = (int) ($campo['decimales'] ?? 2);
                if (!preg_match('/^-?\d{1,8}(\.\d{1,' . $dec . '})?$/', $value)) {
                    return "«{$label}» debe ser un número con hasta {$dec} decimales.";
                }
                $error = self::rango((float) $value, $campo, $label);
                if ($error !== null) {
                    return $error;
                }
                break;

            case 'date':
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                if ($date === false || $date->format('Y-m-d') !== $value) {
                    return "«{$label}» no es una fecha válida (aaaa-mm-dd).";
                }
                break;

            case 'enum':
                if (!array_key_exists($value, (array) ($campo['opciones'] ?? []))) {
                    return "Seleccione una opción válida para «{$label}».";
                }
                break;
        }

        if (isset($campo['patron']) && !preg_match('/' . str_replace('/', '\/', (string) $campo['patron']) . '/u', $value)) {
            return (string) ($campo['patron_msg'] ?? "«{$label}» no tiene el formato esperado.");
        }

        return null;
    }

    /** @param array<string, mixed> $campo */
    private static function rango(float $value, array $campo, string $label): ?string
    {
        if (isset($campo['min']) && $value < (float) $campo['min']) {
            return "«{$label}» debe ser mayor o igual a {$campo['min']}.";
        }
        if (isset($campo['maxValor']) && $value > (float) $campo['maxValor']) {
            return "«{$label}» debe ser menor o igual a {$campo['maxValor']}.";
        }

        return null;
    }

    public static function email(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
