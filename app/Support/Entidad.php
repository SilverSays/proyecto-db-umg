<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Objeto de valor que envuelve la definición de una entidad de config/entidades.php.
 */
final class Entidad
{
    /** @param array<string, mixed> $def */
    private function __construct(public readonly string $slug, private readonly array $def)
    {
    }

    public static function find(string $slug): ?self
    {
        $all = (array) config('entidades', []);

        return isset($all[$slug]) && is_array($all[$slug]) ? new self($slug, $all[$slug]) : null;
    }

    /** @return array<string, self> */
    public static function all(): array
    {
        $result = [];
        foreach ((array) config('entidades', []) as $slug => $def) {
            $result[$slug] = new self((string) $slug, $def);
        }

        return $result;
    }

    /** @return array<string, list<self>> entidades agrupadas para el menú lateral */
    public static function grouped(): array
    {
        $groups = [];
        foreach (self::all() as $entidad) {
            $groups[$entidad->grupo()][] = $entidad;
        }

        return $groups;
    }

    public function tabla(): string
    {
        return (string) $this->def['tabla'];
    }

    public function titulo(): string
    {
        return (string) $this->def['titulo'];
    }

    public function singular(): string
    {
        return (string) $this->def['singular'];
    }

    public function icono(): string
    {
        return (string) ($this->def['icono'] ?? 'bi-table');
    }

    public function grupo(): string
    {
        return (string) ($this->def['grupo'] ?? 'Catálogos');
    }

    public function descripcion(): string
    {
        return (string) ($this->def['descripcion'] ?? '');
    }

    /** @return list<string> */
    public function pk(): array
    {
        return array_values((array) $this->def['pk']);
    }

    public function secuencia(): ?string
    {
        $seq = $this->def['secuencia'] ?? null;

        return is_string($seq) && $seq !== '' ? $seq : null;
    }

    public function pkAutomatica(): bool
    {
        return $this->secuencia() !== null;
    }

    /** @return array<string, array<string, mixed>> */
    public function campos(): array
    {
        return (array) $this->def['campos'];
    }

    /** @return array<string, mixed>|null */
    public function campo(string $columna): ?array
    {
        return $this->campos()[$columna] ?? null;
    }

    public function etiqueta(string $columna): string
    {
        return (string) ($this->campo($columna)['etiqueta'] ?? $columna);
    }

    /** Campos que el usuario captura en el formulario (excluye PK autogenerada). */
    public function editables(): array
    {
        return array_filter($this->campos(), static fn (array $c): bool => empty($c['auto']));
    }

    /** @return list<string> */
    public function listado(): array
    {
        return array_values(array_filter((array) ($this->def['listado'] ?? array_keys($this->campos())), fn ($c) => $this->campo($c) !== null));
    }

    /** @return list<string> */
    public function busqueda(): array
    {
        return array_values(array_filter((array) ($this->def['busqueda'] ?? []), fn ($c) => $this->campo($c) !== null));
    }

    /** @return array<string, array<string, mixed>> */
    public function filtros(): array
    {
        return array_filter($this->campos(), static fn (array $c): bool => !empty($c['filtro']));
    }

    public function orden(): string
    {
        $orden = (string) ($this->def['orden'] ?? $this->pk()[0]);

        return $this->campo($orden) !== null ? $orden : $this->pk()[0];
    }

    /** @return list<array{ruta: string, icono: string, titulo: string}> */
    public function acciones(): array
    {
        return array_values((array) ($this->def['acciones'] ?? []));
    }

    /** @return array<string, array{tabla: string, id: string, texto: string}> */
    public function referencias(): array
    {
        $refs = [];
        foreach ($this->campos() as $col => $campo) {
            if (($campo['tipo'] ?? '') === 'select' && isset($campo['ref'])) {
                $refs[$col] = $campo['ref'];
            }
        }

        return $refs;
    }

    // ------------------------------------------------------------- llaves

    /**
     * Extrae y valida la llave primaria enviada como key[COLUMNA]=valor.
     *
     * @param array<string, mixed> $input
     * @return array<string, int>|null
     */
    public function llaveDesde(array $input): ?array
    {
        $key = [];
        foreach ($this->pk() as $col) {
            $value = $input[$col] ?? null;
            if (!is_scalar($value) || !preg_match('/^\d{1,10}$/', (string) $value)) {
                return null;
            }
            $key[$col] = (int) $value;
        }

        return $key;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function llaveDeFila(array $row): array
    {
        $key = [];
        foreach ($this->pk() as $col) {
            $key[$col] = $row[$col] ?? null;
        }

        return $key;
    }

    /** @param array<string, mixed> $key */
    public function llaveTexto(array $key): string
    {
        return implode('/', array_map('strval', $key));
    }

    /**
     * Texto legible para identificar un registro (confirmaciones, bitácora).
     *
     * @param array<string, mixed> $row
     * @param array<string, string> $aliasRef columna => alias del texto de la referencia
     */
    public function describir(array $row, array $aliasRef = []): string
    {
        $parts = [];
        foreach ($this->listado() as $col) {
            $campo = $this->campo($col);
            if (!empty($campo['auto'])) {
                continue;
            }
            $value = isset($aliasRef[$col]) ? ($row[$aliasRef[$col]] ?? null) : ($row[$col] ?? null);
            if ($value !== null && $value !== '') {
                $parts[] = (string) $value;
            }
            if (count($parts) === 2) {
                break;
            }
        }

        return $parts !== [] ? implode(' — ', $parts) : $this->singular() . ' #' . $this->llaveTexto($this->llaveDeFila($row));
    }
}
