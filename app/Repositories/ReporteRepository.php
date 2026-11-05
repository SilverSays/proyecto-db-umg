<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Paginator;

/**
 * Consultas de solo lectura sobre las vistas definidas en config/reportes.php.
 */
final class ReporteRepository
{
    private readonly Database $db;

    /** @param array<string, mixed> $reporte */
    public function __construct(private readonly array $reporte, ?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /**
     * @param array<string, string> $filtros
     * @return array{rows: list<array<string, mixed>>, paginator: Paginator}
     */
    public function paginate(string $buscar, array $filtros, int $page, int $perPage): array
    {
        [$where, $params] = $this->where($buscar, $filtros);
        $vista = $this->vista();

        $total = (int) $this->db->value("SELECT COUNT(*) FROM {$vista} v{$where}", $params);
        $paginator = new Paginator($total, $page, $perPage);

        $rows = $this->db->select(
            "SELECT {$this->columns()} FROM {$vista} v{$where} ORDER BY {$this->orderBy()}"
            . ' OFFSET :p_off ROWS FETCH NEXT :p_lim ROWS ONLY',
            $params + ['p_off' => $paginator->offset(), 'p_lim' => $perPage]
        );

        return ['rows' => $rows, 'paginator' => $paginator];
    }

    /**
     * @param array<string, string> $filtros
     * @return list<array<string, mixed>>
     */
    public function all(string $buscar, array $filtros): array
    {
        [$where, $params] = $this->where($buscar, $filtros);

        return $this->db->select("SELECT {$this->columns()} FROM {$this->vista()} v{$where} ORDER BY {$this->orderBy()}", $params);
    }

    /** @return array<string, list<string>> columna => valores distintos */
    public function opcionesFiltros(): array
    {
        $result = [];
        foreach (array_keys((array) $this->reporte['filtros']) as $col) {
            $rows = $this->db->select("SELECT DISTINCT v.{$col} AS V FROM {$this->vista()} v WHERE v.{$col} IS NOT NULL ORDER BY 1");
            $result[$col] = array_map(static fn (array $r): string => (string) $r['V'], $rows);
        }

        return $result;
    }

    private function vista(): string
    {
        return (string) $this->reporte['vista'];
    }

    private function columns(): string
    {
        return implode(', ', array_map(static fn (string $c): string => "v.{$c}", array_keys((array) $this->reporte['columnas'])));
    }

    private function orderBy(): string
    {
        $orden = (string) ($this->reporte['orden'] ?? array_key_first((array) $this->reporte['columnas']));
        $dir = $orden === 'FECHA' ? 'DESC' : 'ASC';
        $columnas = array_keys((array) $this->reporte['columnas']);

        // Columnas de desempate para que la paginación sea determinista
        $desempate = implode(', ', array_map(static fn (string $c): string => "v.{$c}", array_slice(array_diff($columnas, [$orden]), 0, 2)));

        return "v.{$orden} {$dir}" . ($desempate !== '' ? ", {$desempate}" : '');
    }

    /**
     * @param array<string, string> $filtros
     * @return array{0: string, 1: array<string, string>}
     */
    private function where(string $buscar, array $filtros): array
    {
        $conditions = [];
        $params = [];

        $buscar = trim($buscar);
        if ($buscar !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtoupper($buscar)) . '%';
            $or = [];
            foreach (array_values((array) $this->reporte['busqueda']) as $n => $col) {
                $or[] = "UPPER(v.{$col}) LIKE :q{$n} ESCAPE '\\'";
                $params['q' . $n] = $like;
            }
            $conditions[] = '(' . implode(' OR ', $or) . ')';
        }

        $n = 0;
        foreach (array_keys((array) $this->reporte['filtros']) as $col) {
            $value = $filtros[$col] ?? '';
            if ($value === '') {
                continue;
            }
            $conditions[] = "v.{$col} = :f{$n}";
            $params['f' . $n++] = $value;
        }

        return [$conditions !== [] ? ' WHERE ' . implode(' AND ', $conditions) : '', $params];
    }
}
