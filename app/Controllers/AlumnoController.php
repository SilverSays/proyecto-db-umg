<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\HttpException;

/**
 * Ficha académica del alumno: datos generales, historial y métricas.
 */
final class AlumnoController extends Controller
{
    public function ficha(string $id): void
    {
        if (!ctype_digit($id)) {
            throw HttpException::notFound('Alumno no encontrado.');
        }

        $db = Database::instance();
        $params = ['p_id' => (int) $id];

        $alumno = $db->first(
            'SELECT v.pk_alumnoId AS ID, v.carnet AS CARNET, v.nombre_completo AS NOMBRE, v.email AS EMAIL,
                    v.carrera AS CARRERA, v.nivel AS NIVEL, v.facultad AS FACULTAD
               FROM VW_ALUMNOS_CARRERA v
              WHERE v.pk_alumnoId = :p_id',
            $params
        );
        if ($alumno === null) {
            throw HttpException::notFound('Alumno no encontrado.');
        }

        $aprobacion = (int) config('app.nota_aprobacion', 61);

        $historial = $db->select(
            'SELECT i.pk_inscripcionId AS ID, c.codigo AS CODIGO, c.nombre AS CURSO, c.creditos AS CREDITOS,
                    p.nombre AS PERIODO, s.pk_seccionId AS SECCION, s.horario AS HORARIO,
                    cat.nombres || \' \' || cat.apellidos AS CATEDRATICO,
                    i.fecha AS FECHA, i.estado AS ESTADO, i.nota AS NOTA
               FROM INSCRIPCION i
               JOIN SECCION s           ON s.pk_seccionId     = i.fk_seccionId
               JOIN CURSO c             ON c.pk_cursoId       = s.fk_cursoId
               JOIN PERIODO_ACADEMICO p ON p.pk_periodoId     = s.fk_periodoId
               JOIN CATEDRATICO cat     ON cat.pk_catedraticoId = s.fk_catedraticoId
              WHERE i.fk_alumnoId = :p_id
           ORDER BY p.fechaInicio DESC, c.codigo',
            $params
        );

        $resumen = $db->first(
            'SELECT COUNT(*) AS CURSOS,
                    SUM(CASE WHEN i.nota >= :p_aprob THEN 1 ELSE 0 END) AS APROBADOS,
                    NVL(SUM(CASE WHEN i.nota >= :p_aprob2 THEN c.creditos ELSE 0 END), 0) AS CREDITOS,
                    ROUND(AVG(i.nota), 2) AS PROMEDIO
               FROM INSCRIPCION i
               JOIN SECCION s ON s.pk_seccionId = i.fk_seccionId
               JOIN CURSO c   ON c.pk_cursoId   = s.fk_cursoId
              WHERE i.fk_alumnoId = :p_id',
            $params + ['p_aprob' => $aprobacion, 'p_aprob2' => $aprobacion]
        ) ?? [];

        $this->view('alumnos/ficha', [
            'pageTitle' => 'Ficha académica',
            'alumno' => $alumno,
            'historial' => $historial,
            'resumen' => $resumen,
            'aprobacion' => $aprobacion,
        ]);
    }
}
