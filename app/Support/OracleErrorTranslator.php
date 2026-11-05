<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\DatabaseException;

/**
 * Traduce errores ORA-xxxxx a mensajes comprensibles para el usuario final,
 * aprovechando los nombres de las restricciones definidas en el esquema.
 */
final class OracleErrorTranslator
{
    /** Mensajes específicos por restricción (UNIQUE / CHECK / PK). */
    private const CONSTRAINTS = [
        'UQ_CATEDRATICO_EMAIL' => 'Ya existe un catedrático registrado con ese correo electrónico.',
        'UQ_ALUMNO_CARNET' => 'Ya existe un alumno con ese número de carné.',
        'UQ_ALUMNO_EMAIL' => 'Ya existe un alumno registrado con ese correo electrónico.',
        'UQ_CURSO_CODIGO' => 'Ya existe un curso con ese código.',
        'PK_PRERREQUISITO' => 'Ese prerrequisito ya está registrado para el curso seleccionado.',
        'UQ_INSCRIPCION_ALUMNO_SECCION' => 'El alumno ya se encuentra inscrito en esa sección.',
        'UQ_USUARIO_USERNAME' => 'El nombre de usuario ya está en uso.',
        'UQ_USUARIO_EMAIL' => 'El correo electrónico ya está asociado a otra cuenta.',
        'CK_PERIODO_FECHAS' => 'La fecha de finalización debe ser posterior a la fecha de inicio.',
        'CK_ASIGNACION_FECHAS' => 'La fecha de finalización debe ser posterior a la fecha de inicio.',
        'CK_PRERREQ_DISTINTO' => 'Un curso no puede ser prerrequisito de sí mismo.',
        'CK_SECCION_CUPO' => 'El cupo de la sección debe ser mayor que cero.',
        'CK_INSCRIPCION_ESTADO' => 'El estado de la inscripción no es válido.',
        'CK_INSCRIPCION_NOTA' => 'La nota debe estar entre 0 y 100.',
        'CK_USUARIO_ROL' => 'El rol seleccionado no es válido.',
    ];

    /** Llaves foráneas => descripción de los registros dependientes. */
    private const DEPENDENCIAS = [
        'FK_CARRERA_FACULTAD' => 'Carreras',
        'FK_ALUMNO_CARRERA' => 'Alumnos',
        'FK_CURSO_CARRERA' => 'Cursos',
        'FK_PRERREQ_CURSO' => 'Prerrequisitos',
        'FK_PRERREQ_CURSOPREREQ' => 'Prerrequisitos',
        'FK_SECCION_CURSO' => 'Secciones',
        'FK_SECCION_CATEDRATICO' => 'Secciones',
        'FK_SECCION_PERIODO' => 'Secciones',
        'FK_INSCRIPCION_ALUMNO' => 'Inscripciones',
        'FK_INSCRIPCION_SECCION' => 'Inscripciones',
        'FK_ASIGNACION_CATEDRATICO' => 'Asignación de catedráticos',
        'FK_ASIGNACION_CURSO' => 'Asignación de catedráticos',
        'FK_ASIGNACION_FACULTAD' => 'Asignación de catedráticos',
        'FK_ASIGNACION_PERIODO' => 'Asignación de catedráticos',
    ];

    public static function translate(DatabaseException $e): string
    {
        $constraint = $e->constraint();

        switch ($e->oracleCode()) {
            case 1:      // unique constraint violated
            case 2290:   // check constraint violated
                return self::CONSTRAINTS[$constraint ?? ''] ?? ($e->oracleCode() === 1
                    ? 'Ya existe un registro con esos datos (valor duplicado).'
                    : 'Los datos no cumplen una regla de validación de la base de datos.');

            case 2292:   // child record found
                $dep = self::DEPENDENCIAS[$constraint ?? ''] ?? null;

                return ($dep !== null
                        ? "No se puede eliminar porque hay registros dependientes en «{$dep}». "
                        : 'No se puede eliminar porque otros registros dependen de este. ')
                    . 'Elimine o reasigne primero esos registros.';

            case 2291:   // parent key not found
                return 'El registro relacionado que seleccionó ya no existe. Recargue el formulario.';

            case 1400:
            case 1407:
                return 'Falta información obligatoria para guardar el registro.';

            case 12899:
                return 'Uno de los valores excede la longitud máxima permitida.';

            case 1438:
                return 'Un valor numérico excede la precisión permitida por la columna.';

            case 1830:
            case 1839:
            case 1840:
            case 1841:
            case 1843:
            case 1847:
            case 1861:
                return 'Una de las fechas ingresadas no es válida.';

            case 942:
            case 2289:
            case 904:
                return 'Faltan objetos en la base de datos (tablas, vistas o secuencias). '
                    . 'Ejecute los scripts de la carpeta /database en orden (01, 02 y 03).';

            case 54:
            case 60:
                return 'El registro está siendo modificado por otro usuario. Intente nuevamente.';

            default:
                return sprintf('No fue posible completar la operación en la base de datos (ORA-%05d).', $e->oracleCode());
        }
    }
}
