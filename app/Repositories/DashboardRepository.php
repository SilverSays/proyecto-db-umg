<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Consultas agregadas para el panel principal.
 */
final class DashboardRepository
{
    private readonly Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<string, mixed> */
    public function indicadores(): array
    {
        return $this->db->first(
            "SELECT (SELECT COUNT(*) FROM ALUMNO)       AS ALUMNOS,
                    (SELECT COUNT(*) FROM CATEDRATICO)  AS CATEDRATICOS,
                    (SELECT COUNT(*) FROM CURSO)        AS CURSOS,
                    (SELECT COUNT(*) FROM SECCION)      AS SECCIONES,
                    (SELECT COUNT(*) FROM CARRERA)      AS CARRERAS,
                    (SELECT COUNT(*) FROM FACULTAD)     AS FACULTADES,
                    (SELECT COUNT(*) FROM INSCRIPCION WHERE estado IN ('INSCRITO', 'CONFIRMADO')) AS INSCRIPCIONES_ACTIVAS,
                    (SELECT ROUND(AVG(nota), 2) FROM INSCRIPCION WHERE nota IS NOT NULL)            AS PROMEDIO,
                    (SELECT ROUND(100 * SUM(CASE WHEN nota >= :p_aprob THEN 1 ELSE 0 END) / NULLIF(COUNT(nota), 0), 1)
                       FROM INSCRIPCION)                                                             AS PORCENTAJE_APROBACION
               FROM DUAL",
            ['p_aprob' => (int) config('app.nota_aprobacion', 61)]
        ) ?? [];
    }

    /** @return list<array<string, mixed>> */
    public function alumnosPorCarrera(): array
    {
        return $this->db->select(
            'SELECT c.nombre AS CARRERA, COUNT(a.pk_alumnoId) AS TOTAL
               FROM CARRERA c
               LEFT JOIN ALUMNO a ON a.fk_carreraId = c.pk_carreraId
           GROUP BY c.nombre
           ORDER BY TOTAL DESC, c.nombre'
        );
    }

    /** @return list<array<string, mixed>> */
    public function inscripcionesPorEstado(): array
    {
        return $this->db->select(
            'SELECT estado AS ESTADO, COUNT(*) AS TOTAL FROM INSCRIPCION GROUP BY estado ORDER BY estado'
        );
    }

    /** @return list<array<string, mixed>> */
    public function ocupacionSecciones(): array
    {
        return $this->db->select(
            'SELECT pk_seccionId AS SECCION, codigo_curso AS CODIGO, curso AS CURSO,
                    cupo AS CUPO, inscritos AS INSCRITOS, cupo_disponible AS DISPONIBLE
               FROM VW_SECCIONES_DISPONIBLES
           ORDER BY pk_seccionId'
        );
    }

    /** @return list<array<string, mixed>> */
    public function promedioPorCurso(): array
    {
        return $this->db->select(
            'SELECT codigo_curso AS CODIGO, curso AS CURSO, ROUND(AVG(nota), 1) AS PROMEDIO, COUNT(nota) AS EVALUADOS
               FROM VW_HISTORIAL_ACADEMICO
              WHERE nota IS NOT NULL
           GROUP BY codigo_curso, curso
           ORDER BY PROMEDIO DESC'
        );
    }

    /** @return list<array<string, mixed>> */
    public function ultimasInscripciones(int $limit = 6): array
    {
        return $this->db->select(
            'SELECT carnet AS CARNET, alumno AS ALUMNO, codigo_curso AS CODIGO, curso AS CURSO,
                    fecha AS FECHA, estado AS ESTADO, nota AS NOTA
               FROM VW_HISTORIAL_ACADEMICO
           ORDER BY fecha DESC, carnet
              FETCH FIRST :p_lim ROWS ONLY',
            ['p_lim' => $limit]
        );
    }
}
