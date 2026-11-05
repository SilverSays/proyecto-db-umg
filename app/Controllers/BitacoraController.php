<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\BitacoraRepository;

final class BitacoraController extends Controller
{
    public function index(): void
    {
        $filtros = [
            'usuario' => mb_substr($this->request->string('usuario'), 0, 30),
            'accion' => $this->request->string('accion'),
            'desde' => $this->fecha($this->request->string('desde')),
            'hasta' => $this->fecha($this->request->string('hasta')),
        ];

        $result = (new BitacoraRepository())->paginate($filtros, $this->request->int('page', 1), 20);

        $this->view('bitacora/index', [
            'pageTitle' => 'Bitácora de auditoría',
            'rows' => $result['rows'],
            'paginator' => $result['paginator'],
            'filtros' => $filtros,
            'acciones' => BitacoraRepository::ACCIONES,
        ]);
    }

    private function fecha(string $value): string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }
}
