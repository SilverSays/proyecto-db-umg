<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\DatabaseException;
use App\Core\HttpException;
use App\Core\Response;
use App\Support\Entidad;
use App\Repositories\CrudRepository;
use App\Support\OracleErrorTranslator;
use App\Support\Validator;

/**
 * Controlador CRUD para todas las tablas del modelo académico.
 */
final class CrudController extends Controller
{
    public function index(string $slug): void
    {
        $entidad = $this->entidad($slug);
        $repo = new CrudRepository($entidad);
        $state = $this->listState($entidad);

        $result = $repo->paginate($state['q'], $state['f'], $state['orden'], $state['dir'], $this->request->int('page', 1), (int) config('app.per_page', 10));

        $opcionesFiltro = [];
        foreach ($entidad->filtros() as $col => $campo) {
            $opcionesFiltro[$col] = $repo->opcionesFiltro($col);
        }

        $this->view('crud/index', [
            'pageTitle' => $entidad->titulo(),
            'entidad' => $entidad,
            'rows' => $result['rows'],
            'paginator' => $result['paginator'],
            'alias' => $repo->aliasReferencias(),
            'state' => $state,
            'opcionesFiltro' => $opcionesFiltro,
        ]);
    }

    public function export(string $slug): void
    {
        $entidad = $this->entidad($slug);
        $repo = new CrudRepository($entidad);
        $state = $this->listState($entidad);
        $alias = $repo->aliasReferencias();

        $columnas = array_keys($entidad->campos());
        $headers = array_map(static fn (string $c): string => $entidad->etiqueta($c), $columnas);

        $rows = [];
        foreach ($repo->exportar($state['q'], $state['f'], $state['orden'], $state['dir']) as $row) {
            $rows[] = array_map(static function (string $c) use ($row, $alias, $entidad) {
                if (isset($alias[$c])) {
                    return $row[$alias[$c]] ?? '';
                }
                $campo = $entidad->campo($c) ?? [];
                if (($campo['tipo'] ?? '') === 'enum') {
                    return $campo['opciones'][$row[$c]] ?? $row[$c];
                }

                return $row[$c] ?? '';
            }, $columnas);
        }

        Response::csv($slug . '_' . date('Ymd_His') . '.csv', $headers, $rows);
    }

    public function create(string $slug): void
    {
        $entidad = $this->entidad($slug);
        $this->form($entidad, null, []);
    }

    public function store(string $slug): void
    {
        $entidad = $this->entidad($slug);
        [$data, $errors] = Validator::entidad($entidad, $this->request->post());

        if ($errors !== []) {
            $this->backWithErrors("/gestion/{$slug}/nuevo", $errors);
        }

        try {
            $key = (new CrudRepository($entidad))->create($data);
        } catch (DatabaseException $e) {
            $this->backWithErrors("/gestion/{$slug}/nuevo", [], [], OracleErrorTranslator::translate($e));
        }

        $this->flash('success', "{$entidad->singular()} registrado(a) correctamente (ID {$entidad->llaveTexto($key)}).");
        $this->redirect("/gestion/{$slug}");
    }

    public function edit(string $slug): void
    {
        $entidad = $this->entidad($slug);
        $key = $this->key($entidad);
        $row = (new CrudRepository($entidad))->find($key);
        if ($row === null) {
            throw HttpException::notFound("El registro de {$entidad->singular()} no existe.");
        }

        $this->form($entidad, $key, $row);
    }

    public function update(string $slug): void
    {
        $entidad = $this->entidad($slug);
        $key = $this->key($entidad);
        $query = ['key' => $key];

        [$data, $errors] = Validator::entidad($entidad, $this->request->post());
        if ($errors !== []) {
            $this->backWithErrors("/gestion/{$slug}/editar", $errors, $query);
        }

        try {
            (new CrudRepository($entidad))->update($key, $data);
        } catch (DatabaseException $e) {
            $this->backWithErrors("/gestion/{$slug}/editar", [], $query, OracleErrorTranslator::translate($e));
        }

        $this->flash('success', "{$entidad->singular()} actualizado(a) correctamente.");
        $this->redirect("/gestion/{$slug}");
    }

    public function destroy(string $slug): void
    {
        $entidad = $this->entidad($slug);
        $key = $this->key($entidad);

        try {
            (new CrudRepository($entidad))->delete($key);
            $this->flash('success', "{$entidad->singular()} eliminado(a) correctamente.");
        } catch (DatabaseException $e) {
            $this->flash('error', OracleErrorTranslator::translate($e));
        }

        $back = $this->request->string('_back');
        $this->redirect("/gestion/{$slug}", $this->safeBackQuery($back));
    }

    // ------------------------------------------------------------------ helpers

    /**
     * @param array<string, int>|null $key
     * @param array<string, mixed> $row
     */
    private function form(Entidad $entidad, ?array $key, array $row): void
    {
        $repo = new CrudRepository($entidad);
        $opciones = [];
        foreach ($entidad->referencias() as $col => $ref) {
            $opciones[$col] = $repo->opciones($col);
        }

        $this->view('crud/form', [
            'pageTitle' => ($key === null ? 'Registrar ' : 'Editar ') . mb_strtolower($entidad->singular()),
            'entidad' => $entidad,
            'key' => $key,
            'row' => $row,
            'opciones' => $opciones,
        ]);
    }

    private function entidad(string $slug): Entidad
    {
        return Entidad::find($slug) ?? throw HttpException::notFound('El catálogo solicitado no existe.');
    }

    /** @return array<string, int> */
    private function key(Entidad $entidad): array
    {
        return $entidad->llaveDesde($this->request->array('key'))
            ?? throw HttpException::notFound('Identificador de registro inválido.');
    }

    /**
     * @return array{q: string, f: array<string, string>, orden: string, dir: string}
     */
    private function listState(Entidad $entidad): array
    {
        $filtros = [];
        foreach ($this->request->array('f') as $col => $value) {
            if (is_string($col) && is_scalar($value) && $entidad->campo($col) !== null && (string) $value !== '') {
                $filtros[$col] = (string) $value;
            }
        }

        $orden = $this->request->string('orden', $entidad->orden());
        if ($entidad->campo($orden) === null) {
            $orden = $entidad->orden();
        }

        return [
            'q' => mb_substr($this->request->string('q'), 0, 100),
            'f' => $filtros,
            'orden' => $orden,
            'dir' => strtolower($this->request->string('dir', 'asc')) === 'desc' ? 'desc' : 'asc',
        ];
    }

    /** @return array<string, mixed> */
    private function safeBackQuery(string $back): array
    {
        parse_str($back, $query);
        $allowed = array_intersect_key($query, array_flip(['q', 'f', 'orden', 'dir', 'page']));

        return array_filter($allowed, static fn ($v) => is_scalar($v) || is_array($v));
    }
}
