<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Response;
use App\Repositories\ReporteRepository;

final class ReporteController extends Controller
{
    public function show(string $slug): void
    {
        $reporte = $this->reporte($slug);
        $repo = new ReporteRepository($reporte);
        [$q, $f] = $this->state($reporte);

        $result = $repo->paginate($q, $f, $this->request->int('page', 1), 15);

        $this->view('reportes/show', [
            'pageTitle' => $reporte['titulo'],
            'slug' => $slug,
            'reporte' => $reporte,
            'rows' => $result['rows'],
            'paginator' => $result['paginator'],
            'opciones' => $repo->opcionesFiltros(),
            'q' => $q,
            'f' => $f,
        ]);
    }

    public function export(string $slug): void
    {
        $reporte = $this->reporte($slug);
        [$q, $f] = $this->state($reporte);

        $columnas = array_keys($reporte['columnas']);
        $rows = array_map(
            static fn (array $row): array => array_map(static fn (string $c) => $row[$c] ?? '', $columnas),
            (new ReporteRepository($reporte))->all($q, $f)
        );

        Response::csv($slug . '_' . date('Ymd_His') . '.csv', array_values($reporte['columnas']), $rows);
    }

    /** @return array<string, mixed> */
    private function reporte(string $slug): array
    {
        $reporte = config('reportes.' . $slug);
        if (!is_array($reporte)) {
            throw HttpException::notFound('El reporte solicitado no existe.');
        }

        return $reporte;
    }

    /**
     * @param array<string, mixed> $reporte
     * @return array{0: string, 1: array<string, string>}
     */
    private function state(array $reporte): array
    {
        $f = [];
        foreach ($this->request->array('f') as $col => $value) {
            if (is_string($col) && array_key_exists($col, (array) $reporte['filtros']) && is_scalar($value) && (string) $value !== '') {
                $f[$col] = mb_substr((string) $value, 0, 200);
            }
        }

        return [mb_substr($this->request->string('q'), 0, 100), $f];
    }
}
