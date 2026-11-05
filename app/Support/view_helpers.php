<?php

declare(strict_types=1);

/*
 * Funciones de presentación reutilizables (badges y formato de celdas).
 * Todas devuelven HTML ya escapado. Cargadas por app/bootstrap.php.
 */

if (!function_exists('badge_estado')) {
    function badge_estado(?string $estado): string
    {
        $map = ['INSCRITO' => 'info', 'CONFIRMADO' => 'success', 'RETIRADO' => 'secondary', 'FINALIZADO' => 'primary'];
        if ($estado === null || $estado === '') {
            return '<span class="text-body-tertiary">—</span>';
        }
        $color = $map[$estado] ?? 'secondary';

        return '<span class="badge rounded-pill badge-soft-' . $color . '">' . e(ucfirst(mb_strtolower($estado))) . '</span>';
    }
}

if (!function_exists('badge_nota')) {
    function badge_nota(mixed $nota): string
    {
        if ($nota === null || $nota === '') {
            return '<span class="text-body-tertiary small">Sin nota</span>';
        }
        $aprobado = (float) $nota >= (float) config('app.nota_aprobacion', 61);
        $texto = rtrim(rtrim(number_format((float) $nota, 2, '.', ''), '0'), '.');

        return '<span class="nota ' . ($aprobado ? 'nota-ok' : 'nota-fail') . '" title="' . ($aprobado ? 'Aprobado' : 'Reprobado') . '">'
            . e($texto) . '</span>';
    }
}

if (!function_exists('badge_cupo')) {
    function badge_cupo(mixed $disponible, mixed $cupo = null): string
    {
        $d = (int) $disponible;
        $umbral = $cupo !== null ? max(3, (int) ceil((int) $cupo * 0.15)) : 3;
        $color = $d <= 0 ? 'danger' : ($d <= $umbral ? 'warning' : 'success');

        return '<span class="badge rounded-pill badge-soft-' . $color . '">' . $d . ' disp.</span>';
    }
}

if (!function_exists('badge_enum')) {
    /** @param array<string, mixed> $campo */
    function badge_enum(array $campo, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '<span class="text-body-tertiary">—</span>';
        }
        $value = (string) $value;
        $label = $campo['opciones'][$value] ?? $value;
        $color = (string) ($campo['colores'][$value] ?? 'secondary');

        return '<span class="badge rounded-pill badge-soft-' . e($color) . '">' . e($label) . '</span>';
    }
}

if (!function_exists('celda')) {
    /**
     * Formatea una celda del listado CRUD según el tipo de campo.
     *
     * @param array<string, mixed> $campo
     * @param array<string, mixed> $row
     * @param array<string, string> $alias
     */
    function celda(string $col, array $campo, array $row, array $alias): string
    {
        $value = $row[$col] ?? null;
        $tipo = (string) ($campo['tipo'] ?? 'text');

        if (isset($alias[$col])) {
            $text = $row[$alias[$col]] ?? null;

            return $text === null ? '<span class="text-body-tertiary">—</span>' : e($text);
        }
        if (($campo['formato'] ?? null) === 'nota') {
            return badge_nota($value);
        }

        return match ($tipo) {
            'enum' => badge_enum($campo, $value),
            'date' => e(fecha($value === null ? null : (string) $value)),
            'email' => $value === null ? '—' : '<a href="mailto:' . e($value) . '" class="link-body-emphasis">' . e($value) . '</a>',
            'decimal' => e(numero($value, (int) ($campo['decimales'] ?? 2))),
            'number' => !empty($campo['auto']) ? '<span class="text-body-secondary">#' . e($value) . '</span>' : e($value),
            default => $value === null || $value === '' ? '<span class="text-body-tertiary">—</span>' : e($value),
        };
    }
}

if (!function_exists('celda_reporte')) {
    /** @param array<string, mixed> $row */
    function celda_reporte(string $col, ?string $formato, array $row): string
    {
        $value = $row[$col] ?? null;

        return match ($formato) {
            'fecha' => e(fecha($value === null ? null : (string) $value)),
            'estado' => badge_estado($value === null ? null : (string) $value),
            'nota' => badge_nota($value),
            'cupo' => badge_cupo($value, $row['CUPO'] ?? null),
            'numero' => e(numero($value)),
            default => $value === null || $value === '' ? '<span class="text-body-tertiary">—</span>' : e($value),
        };
    }
}

if (!function_exists('sort_link')) {
    /**
     * Encabezado de columna ordenable.
     *
     * @param array{q: string, f: array<string, string>, orden: string, dir: string} $state
     */
    function sort_link(string $path, string $col, string $label, array $state): string
    {
        $active = $state['orden'] === $col;
        $dir = $active && $state['dir'] === 'asc' ? 'desc' : 'asc';
        $query = array_filter(['q' => $state['q'], 'f' => $state['f'], 'orden' => $col, 'dir' => $dir], static fn ($v) => $v !== '' && $v !== []);
        $icon = $active ? ($state['dir'] === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up opacity-25';

        return '<a class="sort-link' . ($active ? ' active' : '') . '" href="' . e(url($path, $query)) . '">'
            . e($label) . ' <i class="bi ' . $icon . '"></i></a>';
    }
}
