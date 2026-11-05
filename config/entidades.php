<?php

declare(strict_types=1);

/*
 * ============================================================================
 *  Metadatos de las entidades del modelo académico.
 * ============================================================================
 *  El módulo CRUD es genérico: a partir de esta definición se construyen
 *  listados, búsquedas, filtros, formularios, validaciones y sentencias SQL.
 *
 *  Claves por entidad:
 *    tabla, titulo, singular, icono, grupo, descripcion
 *    pk         lista de columnas de la llave primaria
 *    secuencia  secuencia Oracle para generar la PK (null = PK capturada por el usuario)
 *    orden      columna de ordenamiento por defecto
 *    listado    columnas visibles en la tabla
 *    busqueda   columnas incluidas en la búsqueda de texto
 *    campos     definición de cada columna:
 *        etiqueta, tipo (number|decimal|text|email|date|select|enum|textarea),
 *        requerido, max (longitud), min/maxValor (rango numérico), decimales,
 *        patron + patron_msg (regex), mayusculas, opciones + colores (enum),
 *        ref (llave foránea: tabla|subconsulta, id, texto con {a} como alias),
 *        filtro (muestra filtro en el listado), default ('hoy' o valor),
 *        despues_de / distinto_de (reglas entre campos), col (ancho en el grid),
 *        ayuda, placeholder, auto (PK generada)
 *  Los identificadores provienen de este archivo, nunca de la petición HTTP.
 * ============================================================================
 */

$refFacultad = ['tabla' => 'FACULTAD', 'id' => 'PK_FACULTADID', 'texto' => '{a}.NOMBRE'];
$refCarrera = ['tabla' => 'CARRERA', 'id' => 'PK_CARRERAID', 'texto' => '{a}.NOMBRE'];
$refCurso = ['tabla' => 'CURSO', 'id' => 'PK_CURSOID', 'texto' => "{a}.CODIGO || ' · ' || {a}.NOMBRE"];
$refCatedratico = ['tabla' => 'CATEDRATICO', 'id' => 'PK_CATEDRATICOID', 'texto' => "{a}.NOMBRES || ' ' || {a}.APELLIDOS"];
$refPeriodo = ['tabla' => 'PERIODO_ACADEMICO', 'id' => 'PK_PERIODOID', 'texto' => '{a}.NOMBRE'];
$refAlumno = ['tabla' => 'ALUMNO', 'id' => 'PK_ALUMNOID', 'texto' => "{a}.CARNET || ' · ' || {a}.NOMBRES || ' ' || {a}.APELLIDOS"];
$refSeccion = [
    'tabla' => '(SELECT s.PK_SECCIONID, c.CODIGO, c.NOMBRE, s.HORARIO, p.NOMBRE AS PERIODO'
        . ' FROM SECCION s'
        . ' JOIN CURSO c ON c.PK_CURSOID = s.FK_CURSOID'
        . ' JOIN PERIODO_ACADEMICO p ON p.PK_PERIODOID = s.FK_PERIODOID)',
    'id' => 'PK_SECCIONID',
    'texto' => "'Secc. ' || {a}.PK_SECCIONID || ' · ' || {a}.CODIGO || ' ' || {a}.NOMBRE || ' (' || {a}.PERIODO || ')'",
];

$id = static fn (string $etiqueta = 'ID'): array => ['etiqueta' => $etiqueta, 'tipo' => 'number', 'auto' => true];

return [

    // ------------------------------------------------------------ Estructura
    'facultades' => [
        'tabla' => 'FACULTAD',
        'titulo' => 'Facultades',
        'singular' => 'Facultad',
        'icono' => 'bi-bank',
        'grupo' => 'Estructura institucional',
        'descripcion' => 'Unidades académicas de la universidad y sus decanos.',
        'pk' => ['PK_FACULTADID'],
        'secuencia' => 'SEQ_FACULTAD',
        'orden' => 'PK_FACULTADID',
        'listado' => ['PK_FACULTADID', 'NOMBRE', 'DECANO'],
        'busqueda' => ['NOMBRE', 'DECANO'],
        'campos' => [
            'PK_FACULTADID' => $id(),
            'NOMBRE' => ['etiqueta' => 'Nombre de la facultad', 'tipo' => 'text', 'requerido' => true, 'max' => 100, 'col' => 12, 'placeholder' => 'Facultad de Ingeniería'],
            'DECANO' => ['etiqueta' => 'Decano(a)', 'tipo' => 'text', 'requerido' => true, 'max' => 100, 'col' => 12, 'placeholder' => 'Dr. Nombre Apellido'],
        ],
    ],

    'carreras' => [
        'tabla' => 'CARRERA',
        'titulo' => 'Carreras',
        'singular' => 'Carrera',
        'icono' => 'bi-diagram-3',
        'grupo' => 'Estructura institucional',
        'descripcion' => 'Programas académicos ofrecidos por cada facultad.',
        'pk' => ['PK_CARRERAID'],
        'secuencia' => 'SEQ_CARRERA',
        'orden' => 'PK_CARRERAID',
        'listado' => ['PK_CARRERAID', 'NOMBRE', 'NIVEL', 'FK_FACULTADID'],
        'busqueda' => ['NOMBRE', 'NIVEL', 'FK_FACULTADID'],
        'campos' => [
            'PK_CARRERAID' => $id(),
            'NOMBRE' => ['etiqueta' => 'Nombre de la carrera', 'tipo' => 'text', 'requerido' => true, 'max' => 100, 'col' => 12],
            'NIVEL' => [
                'etiqueta' => 'Nivel', 'tipo' => 'enum', 'requerido' => true, 'filtro' => true,
                'opciones' => ['Técnico' => 'Técnico', 'Licenciatura' => 'Licenciatura', 'Maestría' => 'Maestría', 'Doctorado' => 'Doctorado'],
                'colores' => ['Técnico' => 'info', 'Licenciatura' => 'primary', 'Maestría' => 'warning', 'Doctorado' => 'dark'],
            ],
            'FK_FACULTADID' => ['etiqueta' => 'Facultad', 'tipo' => 'select', 'requerido' => true, 'filtro' => true, 'ref' => $refFacultad],
        ],
    ],

    'periodos' => [
        'tabla' => 'PERIODO_ACADEMICO',
        'titulo' => 'Periodos académicos',
        'singular' => 'Periodo académico',
        'icono' => 'bi-calendar3',
        'grupo' => 'Estructura institucional',
        'descripcion' => 'Semestres y ciclos de vacaciones con sus fechas.',
        'pk' => ['PK_PERIODOID'],
        'secuencia' => 'SEQ_PERIODO_ACADEMICO',
        'orden' => 'FECHAINICIO',
        'listado' => ['PK_PERIODOID', 'NOMBRE', 'FECHAINICIO', 'FECHAFIN'],
        'busqueda' => ['NOMBRE'],
        'campos' => [
            'PK_PERIODOID' => $id(),
            'NOMBRE' => ['etiqueta' => 'Nombre del periodo', 'tipo' => 'text', 'requerido' => true, 'max' => 30, 'col' => 12, 'placeholder' => '2027 - Segundo Semestre'],
            'FECHAINICIO' => ['etiqueta' => 'Fecha de inicio', 'tipo' => 'date', 'requerido' => true],
            'FECHAFIN' => ['etiqueta' => 'Fecha de finalización', 'tipo' => 'date', 'requerido' => true, 'despues_de' => 'FECHAINICIO'],
        ],
    ],

    // ------------------------------------------------------------ Personas
    'alumnos' => [
        'tabla' => 'ALUMNO',
        'titulo' => 'Alumnos',
        'singular' => 'Alumno',
        'icono' => 'bi-mortarboard',
        'grupo' => 'Comunidad universitaria',
        'descripcion' => 'Estudiantes inscritos y la carrera a la que pertenecen.',
        'pk' => ['PK_ALUMNOID'],
        'secuencia' => 'SEQ_ALUMNO',
        'orden' => 'CARNET',
        'listado' => ['CARNET', 'NOMBRES', 'APELLIDOS', 'EMAIL', 'FK_CARRERAID'],
        'busqueda' => ['CARNET', 'NOMBRES', 'APELLIDOS', 'EMAIL', 'FK_CARRERAID'],
        'acciones' => [
            ['ruta' => '/alumnos/{PK_ALUMNOID}/ficha', 'icono' => 'bi-person-vcard', 'titulo' => 'Ficha académica'],
        ],
        'campos' => [
            'PK_ALUMNOID' => $id(),
            'CARNET' => [
                'etiqueta' => 'Carné', 'tipo' => 'text', 'requerido' => true, 'max' => 20,
                'patron' => '^[0-9A-Za-z-]{4,20}$', 'patron_msg' => 'Use solo números, letras o guiones (4 a 20 caracteres).',
                'placeholder' => '20261001',
            ],
            'EMAIL' => ['etiqueta' => 'Correo institucional', 'tipo' => 'email', 'requerido' => true, 'max' => 100, 'placeholder' => 'nombre.apellido@umg.edu.gt'],
            'NOMBRES' => ['etiqueta' => 'Nombres', 'tipo' => 'text', 'requerido' => true, 'max' => 60],
            'APELLIDOS' => ['etiqueta' => 'Apellidos', 'tipo' => 'text', 'requerido' => true, 'max' => 60],
            'FK_CARRERAID' => ['etiqueta' => 'Carrera', 'tipo' => 'select', 'requerido' => true, 'filtro' => true, 'col' => 12, 'ref' => $refCarrera],
        ],
    ],

    'catedraticos' => [
        'tabla' => 'CATEDRATICO',
        'titulo' => 'Catedráticos',
        'singular' => 'Catedrático',
        'icono' => 'bi-person-workspace',
        'grupo' => 'Comunidad universitaria',
        'descripcion' => 'Cuerpo docente de la universidad.',
        'pk' => ['PK_CATEDRATICOID'],
        'secuencia' => 'SEQ_CATEDRATICO',
        'orden' => 'APELLIDOS',
        'listado' => ['PK_CATEDRATICOID', 'NOMBRES', 'APELLIDOS', 'EMAIL'],
        'busqueda' => ['NOMBRES', 'APELLIDOS', 'EMAIL'],
        'campos' => [
            'PK_CATEDRATICOID' => $id(),
            'NOMBRES' => ['etiqueta' => 'Nombres', 'tipo' => 'text', 'requerido' => true, 'max' => 60],
            'APELLIDOS' => ['etiqueta' => 'Apellidos', 'tipo' => 'text', 'requerido' => true, 'max' => 60],
            'EMAIL' => ['etiqueta' => 'Correo institucional', 'tipo' => 'email', 'requerido' => true, 'max' => 100, 'col' => 12, 'placeholder' => 'usuario@umg.edu.gt'],
        ],
    ],

    // ------------------------------------------------------------ Académico
    'cursos' => [
        'tabla' => 'CURSO',
        'titulo' => 'Cursos',
        'singular' => 'Curso',
        'icono' => 'bi-journal-bookmark',
        'grupo' => 'Gestión académica',
        'descripcion' => 'Pensum de cursos por carrera con sus créditos.',
        'pk' => ['PK_CURSOID'],
        'secuencia' => 'SEQ_CURSO',
        'orden' => 'CODIGO',
        'listado' => ['CODIGO', 'NOMBRE', 'CREDITOS', 'FK_CARRERAID'],
        'busqueda' => ['CODIGO', 'NOMBRE', 'FK_CARRERAID'],
        'campos' => [
            'PK_CURSOID' => $id(),
            'CODIGO' => [
                'etiqueta' => 'Código', 'tipo' => 'text', 'requerido' => true, 'max' => 15, 'mayusculas' => true,
                'patron' => '^[A-Z]{2,5}-[0-9]{3}$', 'patron_msg' => 'Formato esperado: ABC-123.', 'placeholder' => 'ISW-301', 'col' => 4,
            ],
            'NOMBRE' => ['etiqueta' => 'Nombre del curso', 'tipo' => 'text', 'requerido' => true, 'max' => 100, 'col' => 8],
            'CREDITOS' => ['etiqueta' => 'Créditos', 'tipo' => 'number', 'requerido' => true, 'min' => 1, 'maxValor' => 20, 'col' => 4],
            'FK_CARRERAID' => ['etiqueta' => 'Carrera', 'tipo' => 'select', 'requerido' => true, 'filtro' => true, 'col' => 8, 'ref' => $refCarrera],
        ],
    ],

    'prerrequisitos' => [
        'tabla' => 'PRERREQUISITO',
        'titulo' => 'Prerrequisitos',
        'singular' => 'Prerrequisito',
        'icono' => 'bi-signpost-split',
        'grupo' => 'Gestión académica',
        'descripcion' => 'Cursos que deben aprobarse antes de cursar otro.',
        'pk' => ['FK_CURSOID', 'FK_CURSOPREREQUISITOID'],
        'secuencia' => null,
        'orden' => 'FK_CURSOID',
        'listado' => ['FK_CURSOID', 'FK_CURSOPREREQUISITOID'],
        'busqueda' => ['FK_CURSOID', 'FK_CURSOPREREQUISITOID'],
        'campos' => [
            'FK_CURSOID' => ['etiqueta' => 'Curso', 'tipo' => 'select', 'requerido' => true, 'filtro' => true, 'col' => 12, 'ref' => $refCurso],
            'FK_CURSOPREREQUISITOID' => [
                'etiqueta' => 'Requiere aprobar', 'tipo' => 'select', 'requerido' => true, 'col' => 12, 'ref' => $refCurso,
                'distinto_de' => 'FK_CURSOID', 'ayuda' => 'Un curso no puede ser prerrequisito de sí mismo.',
            ],
        ],
    ],

    'secciones' => [
        'tabla' => 'SECCION',
        'titulo' => 'Secciones',
        'singular' => 'Sección',
        'icono' => 'bi-grid-3x3-gap',
        'grupo' => 'Gestión académica',
        'descripcion' => 'Oferta de cursos por periodo: catedrático, horario, aula y cupo.',
        'pk' => ['PK_SECCIONID'],
        'secuencia' => 'SEQ_SECCION',
        'orden' => 'PK_SECCIONID',
        'listado' => ['PK_SECCIONID', 'FK_CURSOID', 'FK_CATEDRATICOID', 'FK_PERIODOID', 'HORARIO', 'AULA', 'CUPO'],
        'busqueda' => ['FK_CURSOID', 'FK_CATEDRATICOID', 'HORARIO', 'AULA'],
        'campos' => [
            'PK_SECCIONID' => $id('Secc.'),
            'FK_CURSOID' => ['etiqueta' => 'Curso', 'tipo' => 'select', 'requerido' => true, 'filtro' => true, 'col' => 12, 'ref' => $refCurso],
            'FK_CATEDRATICOID' => ['etiqueta' => 'Catedrático', 'tipo' => 'select', 'requerido' => true, 'filtro' => true, 'ref' => $refCatedratico],
            'FK_PERIODOID' => ['etiqueta' => 'Periodo', 'tipo' => 'select', 'requerido' => true, 'filtro' => true, 'ref' => $refPeriodo],
            'HORARIO' => ['etiqueta' => 'Horario', 'tipo' => 'text', 'requerido' => true, 'max' => 50, 'col' => 12, 'placeholder' => 'Lunes y Miércoles 07:00-08:30'],
            'AULA' => ['etiqueta' => 'Aula', 'tipo' => 'text', 'requerido' => true, 'max' => 20, 'mayusculas' => true, 'placeholder' => 'A-101'],
            'CUPO' => ['etiqueta' => 'Cupo', 'tipo' => 'number', 'requerido' => true, 'min' => 1, 'maxValor' => 999],
        ],
    ],

    'inscripciones' => [
        'tabla' => 'INSCRIPCION',
        'titulo' => 'Inscripciones',
        'singular' => 'Inscripción',
        'icono' => 'bi-clipboard-check',
        'grupo' => 'Gestión académica',
        'descripcion' => 'Asignación de alumnos a secciones, estado y nota final.',
        'pk' => ['PK_INSCRIPCIONID'],
        'secuencia' => 'SEQ_INSCRIPCION',
        'orden' => 'PK_INSCRIPCIONID',
        'listado' => ['PK_INSCRIPCIONID', 'FK_ALUMNOID', 'FK_SECCIONID', 'FECHA', 'ESTADO', 'NOTA'],
        'busqueda' => ['FK_ALUMNOID', 'FK_SECCIONID', 'ESTADO'],
        'campos' => [
            'PK_INSCRIPCIONID' => $id('No.'),
            'FK_ALUMNOID' => ['etiqueta' => 'Alumno', 'tipo' => 'select', 'requerido' => true, 'filtro' => true, 'col' => 12, 'ref' => $refAlumno],
            'FK_SECCIONID' => ['etiqueta' => 'Sección', 'tipo' => 'select', 'requerido' => true, 'filtro' => true, 'col' => 12, 'ref' => $refSeccion],
            'FECHA' => ['etiqueta' => 'Fecha de inscripción', 'tipo' => 'date', 'requerido' => true, 'default' => 'hoy', 'col' => 4],
            'ESTADO' => [
                'etiqueta' => 'Estado', 'tipo' => 'enum', 'requerido' => true, 'filtro' => true, 'default' => 'INSCRITO', 'col' => 4,
                'opciones' => ['INSCRITO' => 'Inscrito', 'CONFIRMADO' => 'Confirmado', 'RETIRADO' => 'Retirado', 'FINALIZADO' => 'Finalizado'],
                'colores' => ['INSCRITO' => 'info', 'CONFIRMADO' => 'success', 'RETIRADO' => 'secondary', 'FINALIZADO' => 'primary'],
            ],
            'NOTA' => [
                'etiqueta' => 'Nota final', 'tipo' => 'decimal', 'requerido' => false, 'min' => 0, 'maxValor' => 100, 'decimales' => 2,
                'formato' => 'nota', 'col' => 4, 'ayuda' => 'Escala 0-100. Dejar vacío si aún no se ha calificado.',
            ],
        ],
    ],

    'asignaciones' => [
        'tabla' => 'ASIGNACION_CATEDRATICO',
        'titulo' => 'Asignación de catedráticos',
        'singular' => 'Asignación',
        'icono' => 'bi-person-check',
        'grupo' => 'Gestión académica',
        'descripcion' => 'Cursos asignados a cada catedrático por facultad y periodo.',
        'pk' => ['PK_ASIGNACIONID'],
        'secuencia' => 'SEQ_ASIGNACION_CATEDRATICO',
        'orden' => 'PK_ASIGNACIONID',
        'listado' => ['PK_ASIGNACIONID', 'FK_CATEDRATICOID', 'FK_CURSOID', 'FK_FACULTADID', 'FK_PERIODOID', 'FECHAINICIO', 'FECHAFIN'],
        'busqueda' => ['FK_CATEDRATICOID', 'FK_CURSOID', 'FK_FACULTADID'],
        'campos' => [
            'PK_ASIGNACIONID' => $id('No.'),
            'FK_CATEDRATICOID' => ['etiqueta' => 'Catedrático', 'tipo' => 'select', 'requerido' => true, 'filtro' => true, 'ref' => $refCatedratico],
            'FK_CURSOID' => ['etiqueta' => 'Curso', 'tipo' => 'select', 'requerido' => true, 'filtro' => true, 'ref' => $refCurso],
            'FK_FACULTADID' => ['etiqueta' => 'Facultad', 'tipo' => 'select', 'requerido' => true, 'filtro' => true, 'ref' => $refFacultad],
            'FK_PERIODOID' => ['etiqueta' => 'Periodo', 'tipo' => 'select', 'requerido' => true, 'filtro' => true, 'ref' => $refPeriodo],
            'FECHAINICIO' => ['etiqueta' => 'Fecha de inicio', 'tipo' => 'date', 'requerido' => true],
            'FECHAFIN' => ['etiqueta' => 'Fecha de finalización', 'tipo' => 'date', 'requerido' => true, 'despues_de' => 'FECHAINICIO'],
        ],
    ],
];
