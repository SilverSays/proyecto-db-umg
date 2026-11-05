<?php
/**
 * @var array<string, mixed> $alumno
 * @var list<array<string, mixed>> $historial
 * @var array<string, mixed> $resumen
 * @var int $aprobacion
 */
$cursos = (int) ($resumen['CURSOS'] ?? 0);
$aprobados = (int) ($resumen['APROBADOS'] ?? 0);
?>
<header class="page-header">
    <div class="ficha-encabezado mb-0">
        <span class="avatar avatar-xl"><?= e(iniciales((string) $alumno['NOMBRE'])) ?></span>
        <div>
            <h1 class="page-title"><?= e($alumno['NOMBRE']) ?></h1>
            <p class="page-subtitle">
                Carné <strong><?= e($alumno['CARNET']) ?></strong>, <?= e($alumno['NIVEL']) ?> en <?= e($alumno['CARRERA']) ?>,
                <?= e($alumno['FACULTAD']) ?>.
                <a href="mailto:<?= e($alumno['EMAIL']) ?>"><?= e($alumno['EMAIL']) ?></a>
            </p>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary js-print">Imprimir ficha</button>
        <?php if (is_admin()): ?>
            <a href="<?= e(url('/gestion/alumnos/editar', ['key' => ['PK_ALUMNOID' => $alumno['ID']]])) ?>" class="btn btn-primary">Editar datos</a>
        <?php endif; ?>
    </div>
</header>

<dl class="ficha-datos">
    <div><dt>Cursos inscritos</dt><dd><?= $cursos ?></dd></div>
    <div><dt>Cursos aprobados</dt><dd><?= $aprobados ?></dd></div>
    <div><dt>Créditos aprobados</dt><dd><?= (int) ($resumen['CREDITOS'] ?? 0) ?></dd></div>
    <div><dt>Promedio</dt><dd><?= e(numero($resumen['PROMEDIO'] ?? null, 2)) ?></dd></div>
</dl>

<h2 class="section-title">Historial de inscripciones</h2>
<div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead>
        <tr><th>Periodo</th><th>Curso</th><th class="text-end">Créditos</th><th>Sección</th><th>Catedrático</th><th>Estado</th><th class="text-end">Nota</th></tr>
        </thead>
        <tbody>
        <?php if ($historial === []): ?>
            <tr><td colspan="7"><div class="empty-state"><p class="mb-0">Este alumno todavía no tiene inscripciones.</p></div></td></tr>
        <?php endif; ?>
        <?php foreach ($historial as $h): ?>
            <tr>
                <td class="text-nowrap"><?= e($h['PERIODO']) ?></td>
                <td><code><?= e($h['CODIGO']) ?></code> <?= e($h['CURSO']) ?></td>
                <td class="text-end"><?= (int) $h['CREDITOS'] ?></td>
                <td>Sección <?= e($h['SECCION']) ?><div class="small text-body-secondary"><?= e($h['HORARIO']) ?></div></td>
                <td><?= e($h['CATEDRATICO']) ?></td>
                <td><?= badge_estado((string) $h['ESTADO']) ?></td>
                <td class="text-end"><?= badge_nota($h['NOTA']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="section-note mt-3">Se aprueba un curso con <?= $aprobacion ?> puntos o más.</p>
