<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\HttpException;
use App\Support\Entidad;
use App\Support\Paginator;

/**
 * Repositorio genérico: genera SQL parametrizado a partir de los metadatos
 * de la entidad. Los nombres de tablas y columnas provienen exclusivamente de
 * config/entidades.php (lista blanca); los valores del usuario viajan siempre
 * como variables de enlace.
 */
final class CrudRepository
{
    private readonly Database $db;
    private readonly BitacoraRepository $bitacora;

    /** @var array<string, string> columna FK => alias del texto legible (R1_TXT...) */
    private array $aliasRef = [];

    /** @var array<string, string> columna FK => expresión SQL del texto legible */
    private array $exprRef = [];

    private string $joins = '';
    private string $selectRefs = '';

    public function __construct(private readonly Entidad $entidad, ?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
        $this->bitacora = new BitacoraRepository($this->db);
        $this->buildJoins();
    }

    /** @return array<string, string> */
    public function aliasReferencias(): array
    {
        return $this->aliasRef;
    }

    // ================================================================ lectura

    /**
     * @param array<string, mixed> $filtros columna => valor
     * @return array{rows: list<array<string, mixed>>, paginator: Paginator}
     */
    public function paginate(string $buscar, array $filtros, string $orden, string $direccion, int $page, int $perPage): array
    {
        [$where, $params] = $this->where($buscar, $filtros);

        $total = (int) $this->db->value("SELECT COUNT(*) FROM {$this->from()}{$where}", $params);
        $paginator = new Paginator($total, $page, $perPage);

        $sql = "SELECT t.*{$this->selectRefs} FROM {$this->from()}{$where}"
            . ' ORDER BY ' . $this->orderBy($orden, $direccion)
            . ' OFFSET :p_off ROWS FETCH NEXT :p_lim ROWS ONLY';

        $rows = $this->db->select($sql, $params + ['p_off' => $paginator->offset(), 'p_lim' => $paginator->perPage]);

        return ['rows' => $rows, 'paginator' => $paginator];
    }

    /**
     * @param array<string, mixed> $filtros
     * @return list<array<string, mixed>>
     */
    public function exportar(string $buscar, array $filtros, string $orden, string $direccion): array
    {
        [$where, $params] = $this->where($buscar, $filtros);

        return $this->db->select(
            "SELECT t.*{$this->selectRefs} FROM {$this->from()}{$where} ORDER BY " . $this->orderBy($orden, $direccion),
            $params
        );
    }

    /**
     * @param array<string, int> $key
     * @return array<string, mixed>|null
     */
    public function find(array $key): ?array
    {
        [$where, $params] = $this->wherePk($key, 't.');

        return $this->db->first("SELECT t.*{$this->selectRefs} FROM {$this->from()} WHERE {$where}", $params);
    }

    /**
     * Opciones para un <select> de llave foránea.
     *
     * @return list<array{ID: string, TXT: string}>
     */
    public function opciones(string $columna): array
    {
        $ref = $this->entidad->referencias()[$columna] ?? null;
        if ($ref === null) {
            return [];
        }
        $texto = str_replace('{a}', 'o', $ref['texto']);

        /** @var list<array{ID: string, TXT: string}> */
        return $this->db->select("SELECT o.{$ref['id']} AS ID, {$texto} AS TXT FROM {$ref['tabla']} o ORDER BY TXT");
    }

    /**
     * Valores para filtros de tipo enum o select.
     *
     * @return array<string, string> valor => texto
     */
    public function opcionesFiltro(string $columna): array
    {
        $campo = $this->entidad->campo($columna) ?? [];
        if (($campo['tipo'] ?? '') === 'enum') {
            return (array) $campo['opciones'];
        }
        $result = [];
        foreach ($this->opciones($columna) as $op) {
            $result[(string) $op['ID']] = (string) $op['TXT'];
        }

        return $result;
    }

    // ================================================================ escritura

    /**
     * @param array<string, scalar|null> $data datos ya validados
     * @return array<string, int> llave del nuevo registro
     */
    public function create(array $data): array
    {
        return $this->db->transaction(function (Database $db) use ($data): array {
            $tabla = $this->entidad->tabla();
            $columns = [];
            $values = [];
            $params = [];

            if ($this->entidad->pkAutomatica()) {
                $pk = $this->entidad->pk()[0];
                $data = [$pk => $db->nextVal((string) $this->entidad->secuencia())] + $data;
            }

            $i = 0;
            foreach ($data as $col => $value) {
                $campo = $this->entidad->campo($col);
                if ($campo === null) {
                    continue;
                }
                $bind = 'p' . $i++;
                $columns[] = $col;
                $values[] = $this->bindExpression($campo, $bind);
                $params[$bind] = $value;
            }

            $db->execute(
                "INSERT INTO {$tabla} (" . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')',
                $params
            );

            $key = [];
            foreach ($this->entidad->pk() as $col) {
                $key[$col] = (int) $data[$col];
            }

            $this->bitacora->registrar('INSERT', $tabla, $this->entidad->llaveTexto($key), $data);

            return $key;
        });
    }

    /**
     * @param array<string, int> $key
     * @param array<string, scalar|null> $data
     * @return array<string, int> llave resultante (puede cambiar en llaves compuestas)
     */
    public function update(array $key, array $data): array
    {
        return $this->db->transaction(function (Database $db) use ($key, $data): array {
            $tabla = $this->entidad->tabla();
            $anterior = $this->lockRow($key);

            $sets = [];
            $params = [];
            $cambios = [];
            $i = 0;
            foreach ($data as $col => $value) {
                $campo = $this->entidad->campo($col);
                if ($campo === null || !empty($campo['auto'])) {
                    continue;
                }
                $bind = 'p' . $i++;
                $sets[] = "{$col} = " . $this->bindExpression($campo, $bind);
                $params[$bind] = $value;

                $old = $anterior[$col] ?? null;
                if ((string) $old !== (string) $value && !(is_numeric($old) && is_numeric($value) && (float) $old === (float) $value)) {
                    $cambios[$col] = ['antes' => $old, 'despues' => $value];
                }
            }

            [$where, $whereParams] = $this->wherePk($key);
            $db->execute("UPDATE {$tabla} SET " . implode(', ', $sets) . " WHERE {$where}", $params + $whereParams);

            $nuevaLlave = [];
            foreach ($this->entidad->pk() as $col) {
                $nuevaLlave[$col] = (int) ($data[$col] ?? $key[$col]);
            }

            if ($cambios !== []) {
                $this->bitacora->registrar('UPDATE', $tabla, $this->entidad->llaveTexto($key), $cambios);
            }

            return $nuevaLlave;
        });
    }

    /** @param array<string, int> $key */
    public function delete(array $key): void
    {
        $this->db->transaction(function (Database $db) use ($key): void {
            $tabla = $this->entidad->tabla();
            $anterior = $this->lockRow($key);

            [$where, $params] = $this->wherePk($key);
            $db->execute("DELETE FROM {$tabla} WHERE {$where}", $params);

            $this->bitacora->registrar('DELETE', $tabla, $this->entidad->llaveTexto($key), array_intersect_key($anterior, $this->entidad->campos()));
        });
    }

    // ================================================================ SQL helpers

    private function from(): string
    {
        return $this->entidad->tabla() . ' t' . $this->joins;
    }

    private function buildJoins(): void
    {
        $i = 0;
        foreach ($this->entidad->referencias() as $col => $ref) {
            $i++;
            $alias = 'r' . $i;
            $expr = str_replace('{a}', $alias, $ref['texto']);
            $this->joins .= " LEFT JOIN {$ref['tabla']} {$alias} ON {$alias}.{$ref['id']} = t.{$col}";
            $this->selectRefs .= ", ({$expr}) AS R{$i}_TXT";
            $this->aliasRef[$col] = "R{$i}_TXT";
            $this->exprRef[$col] = $expr;
        }
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array{0: string, 1: array<string, scalar|null>}
     */
    private function where(string $buscar, array $filtros): array
    {
        $conditions = [];
        $params = [];

        $buscar = trim($buscar);
        if ($buscar !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtoupper($buscar)) . '%';
            $or = [];
            foreach ($this->entidad->busqueda() as $n => $col) {
                $campo = $this->entidad->campo($col) ?? [];
                $expr = match (true) {
                    isset($this->exprRef[$col]) => "UPPER({$this->exprRef[$col]})",
                    ($campo['tipo'] ?? '') === 'date' => "TO_CHAR(t.{$col}, 'DD/MM/YYYY')",
                    in_array($campo['tipo'] ?? '', ['number', 'decimal'], true) => "TO_CHAR(t.{$col})",
                    default => "UPPER(t.{$col})",
                };
                $bind = 'q' . $n;
                $or[] = "{$expr} LIKE :{$bind} ESCAPE '\\'";
                $params[$bind] = $like;
            }
            if ($or !== []) {
                $conditions[] = '(' . implode(' OR ', $or) . ')';
            }
        }

        $n = 0;
        foreach ($this->entidad->filtros() as $col => $campo) {
            $value = $filtros[$col] ?? '';
            if (!is_scalar($value) || (string) $value === '') {
                continue;
            }
            $value = (string) $value;
            if (($campo['tipo'] ?? '') === 'enum' && !array_key_exists($value, (array) $campo['opciones'])) {
                continue;
            }
            if (($campo['tipo'] ?? '') === 'select' && !ctype_digit($value)) {
                continue;
            }
            $bind = 'f' . $n++;
            $conditions[] = "t.{$col} = :{$bind}";
            $params[$bind] = $value;
        }

        return [$conditions !== [] ? ' WHERE ' . implode(' AND ', $conditions) : '', $params];
    }

    /**
     * @param array<string, int> $key
     * @return array{0: string, 1: array<string, int>}
     */
    private function wherePk(array $key, string $prefix = ''): array
    {
        $conditions = [];
        $params = [];
        foreach ($this->entidad->pk() as $n => $col) {
            if (!isset($key[$col])) {
                throw HttpException::notFound('Llave primaria incompleta.');
            }
            $conditions[] = "{$prefix}{$col} = :k{$n}";
            $params['k' . $n] = $key[$col];
        }

        return [implode(' AND ', $conditions), $params];
    }

    private function orderBy(string $orden, string $direccion): string
    {
        $direccion = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';
        if ($this->entidad->campo($orden) === null) {
            $orden = $this->entidad->orden();
        }
        $expr = $this->exprRef[$orden] ?? "t.{$orden}";

        $tiebreak = implode(', ', array_map(static fn (string $c): string => "t.{$c}", $this->entidad->pk()));

        return "{$expr} {$direccion} NULLS LAST, {$tiebreak}";
    }

    /** @param array<string, mixed> $campo */
    private function bindExpression(array $campo, string $bind): string
    {
        return ($campo['tipo'] ?? '') === 'date' ? "TO_DATE(:{$bind}, 'YYYY-MM-DD')" : ":{$bind}";
    }

    /**
     * Bloquea la fila (SELECT ... FOR UPDATE NOWAIT) para evitar actualizaciones perdidas.
     *
     * @param array<string, int> $key
     * @return array<string, mixed>
     */
    private function lockRow(array $key): array
    {
        [$where, $params] = $this->wherePk($key);
        $row = $this->db->first("SELECT * FROM {$this->entidad->tabla()} WHERE {$where} FOR UPDATE NOWAIT", $params);
        if ($row === null) {
            throw HttpException::notFound("El registro de {$this->entidad->singular()} ya no existe.");
        }

        return $row;
    }
}
