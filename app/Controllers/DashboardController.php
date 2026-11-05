<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Repositories\BitacoraRepository;
use App\Repositories\DashboardRepository;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $repo = new DashboardRepository();

        $this->view('dashboard/index', [
            'pageTitle' => 'Panel principal',
            'kpi' => $repo->indicadores(),
            'porCarrera' => $repo->alumnosPorCarrera(),
            'porEstado' => $repo->inscripcionesPorEstado(),
            'ocupacion' => $repo->ocupacionSecciones(),
            'promedios' => $repo->promedioPorCurso(),
            'ultimas' => $repo->ultimasInscripciones(),
            'actividad' => is_admin() ? (new BitacoraRepository())->recientes() : [],
            'servidor' => Database::instance()->serverVersion(),
        ]);
    }
}
