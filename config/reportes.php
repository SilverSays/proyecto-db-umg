<?php

declare(strict_types=1);

/*
 * Reportes de solo lectura construidos sobre las VISTAS de Oracle.
 *   columnas  columna => etiqueta
 *   filtros   columna => etiqueta (las opciones se obtienen con SELECT DISTINCT)
 *   busqueda  columnas para la búsqueda de texto libre
 *   formatos  columna => fecha | estado | nota | cupo | numero
 */
return [
    'alumnos-carrera' => [
        'vista' => 'VW_ALUMNOS_CARRERA',
        'titulo' => 'Alumnos por carrera',
        'icono' => 'bi-people',
        'descripcion' => 'Listado de alumnos con su carrera, nivel y facultad (vista VW_ALUMNOS_CARRERA).',
        'orden' => 'CARNET',
        'columnas' => [
            'CARNET' => 'Carné',
            'NOMBRE_COMPLETO' => 'Nombre completo',
            'EMAIL' => 'Correo',
            'CARRERA' => 'Carrera',
            'NIVEL' => 'Nivel',
            'FACULTAD' => 'Facultad',
        ],
        'filtros' => ['FACULTAD' => 'Facultad', 'NIVEL' => 'Nivel'],
        'busqueda' => ['CARNET', 'NOMBRE_COMPLETO', 'EMAIL', 'CARRERA'],
        'formatos' => [],
    ],

    'secciones-disponibles' => [
        'vista' => 'VW_SECCIONES_DISPONIBLES',
        'titulo' => 'Cupo de secciones',
        'icono' => 'bi-bar-chart-steps',
        'descripcion' => 'Ocupación y cupo disponible por sección (vista VW_SECCIONES_DISPONIBLES).',
        'orden' => 'PK_SECCIONID',
        'columnas' => [
            'PK_SECCIONID' => 'Secc.',
            'CODIGO_CURSO' => 'Código',
            'CURSO' => 'Curso',
            'CATEDRATICO' => 'Catedrático',
            'PERIODO' => 'Periodo',
            'HORARIO' => 'Horario',
            'AULA' => 'Aula',
            'CUPO' => 'Cupo',
            'INSCRITOS' => 'Inscritos',
            'CUPO_DISPONIBLE' => 'Disponible',
        ],
        'filtros' => ['PERIODO' => 'Periodo', 'CATEDRATICO' => 'Catedrático'],
        'busqueda' => ['CODIGO_CURSO', 'CURSO', 'CATEDRATICO', 'AULA'],
        'formatos' => ['CUPO_DISPONIBLE' => 'cupo'],
    ],

    'historial-academico' => [
        'vista' => 'VW_HISTORIAL_ACADEMICO',
        'titulo' => 'Historial académico',
        'icono' => 'bi-journal-text',
        'descripcion' => 'Inscripciones con curso, periodo, estado y nota (vista VW_HISTORIAL_ACADEMICO).',
        'orden' => 'FECHA',
        'columnas' => [
            'CARNET' => 'Carné',
            'ALUMNO' => 'Alumno',
            'CODIGO_CURSO' => 'Código',
            'CURSO' => 'Curso',
            'PERIODO' => 'Periodo',
            'FECHA' => 'Fecha',
            'ESTADO' => 'Estado',
            'NOTA' => 'Nota',
        ],
        'filtros' => ['ALUMNO' => 'Alumno', 'PERIODO' => 'Periodo', 'ESTADO' => 'Estado'],
        'busqueda' => ['CARNET', 'ALUMNO', 'CODIGO_CURSO', 'CURSO'],
        'formatos' => ['FECHA' => 'fecha', 'ESTADO' => 'estado', 'NOTA' => 'nota'],
    ],
];
