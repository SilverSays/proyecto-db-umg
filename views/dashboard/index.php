<?php
/**
 * @var array<string, mixed> $kpi
 * @var list<array<string, mixed>> $porCarrera
 * @var list<array<string, mixed>> $porEstado
 * @var list<array<string, mixed>> $ocupacion
 * @var list<array<string, mixed>> $promedios
 * @var list<array<string, mixed>> $ultimas
 * @var list<array<string, mixed>> $actividad
 * @var string $servidor
 */
$user = auth();
$hora = (int) date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$nombre = explode(' ', (string) $user['nombre'])[0];
$aprobacion = (int) config('app.nota_aprobacion', 61);

/** Cifra enlazada dentro del resumen: "10 alumnos" -> /gestion/alumnos */
$cifra = static function (mixed $n, string $singular, string $plural, string $ruta): string {
    $n = (int) $n;

    return '<a href="' . e(url($ruta)) . '"><span class="cifra">' . number_format($n, 0, '.', ',') . '</span> '
        . e($n === 1 ? $singular : $plural) . '</a>';
};

$colorEstado = ['INSCRITO' => 'var(--azul)', 'CONFIRMADO' => 'var(--verde)', 'RETIRADO' => '#9aa8b6', 'FINALIZADO' => 'var(--tinta)'];
$totalEstados = max(1, array_sum(array_map('intval', array_column($porEstado, 'TOTAL'))));
$maxCarrera = max(1, ...array_map('intval', array_column($porCarrera, 'TOTAL') ?: [1]));
?>
<header class="page-header">
    <div>
        <h1 class="page-title"><?= e($saludo) ?>, <?= e($nombre) ?></h1>
        <p class="page-subtitle">Conectado a <?= e($servidor) ?>.</p>
    </div>
    <?php if (!empty($user['ultimo_acceso'])): ?>
        <p class="section-note mb-0">Su último acceso fue el <?= e(fecha((string) $user['ultimo_acceso'], true)) ?>.</p>
    <?php endif; ?>
</header>

<p class="acta-resumen">
    El registro académico cuenta con <?= $cifra($kpi['ALUMNOS'] ?? 0, 'alumno', 'alumnos', '/gestion/alumnos') ?>
    en <?= $cifra($kpi['CARRERAS'] ?? 0, 'carrera', 'carreras', '/gestion/carreras') ?>
    de <?= $cifra($kpi['FACULTADES'] ?? 0, 'facultad', 'facultades', '/gestion/facultades') ?>.
    Hay <?= $cifra($kpi['INSCRIPCIONES_ACTIVAS'] ?? 0, 'inscripción activa', 'inscripciones activas', '/gestion/inscripciones') ?>
    en <?= $cifra($kpi['SECCIONES'] ?? 0, 'sección', 'secciones', '/gestion/secciones') ?>,
    a cargo de <?= $cifra($kpi['CATEDRATICOS'] ?? 0, 'catedrático', 'catedráticos', '/gestion/catedraticos') ?>.
    <?php if (($kpi['PROMEDIO'] ?? null) !== null): ?>
        El promedio general es <span class="cifra"><?= e(numero($kpi['PROMEDIO'], 1)) ?></span>
        y aprueba el <span class="cifra"><?= e(numero($kpi['PORCENTAJE_APROBACION'] ?? 0, 0)) ?>&nbsp;%</span>
        de los cursos calificados (nota mínima <?= $aprobacion ?>).
    <?php else: ?>
        Todavía no hay notas registradas.
    <?php endif; ?>
</p>

<div class="panel-grid">
    <div>
        <section class="panel-block" aria-labelledby="t-ocupacion">
            <h2 class="section-title" id="t-ocupacion">Ocupación de secciones</h2>
            <?php if ($ocupacion === []): ?>
                <p class="section-note">No hay secciones abiertas.</p>
            <?php else: ?>
                <ul class="renglones">
                    <?php foreach ($ocupacion as $s):
                        $cupo = max(1, (int) $s['CUPO']);
                        $inscritos = (int) $s['INSCRITOS'];
                        $pct = min(100, round($inscritos / $cupo * 100));
                        ?>
                        <li class="renglon">
                            <div class="renglon-titulo">
                                <code><?= e($s['CODIGO']) ?></code> <?= e($s['CURSO']) ?>
                                <small>Sección <?= e($s['SECCION']) ?></small>
                            </div>
                            <div class="renglon-cifra"><strong><?= $inscritos ?></strong> de <?= (int) $s['CUPO'] ?> lugares</div>
                            <div class="barra <?= (int) $s['DISPONIBLE'] <= 0 ? 'llena' : '' ?>" role="img"
                                 aria-label="<?= $pct ?> % del cupo ocupado"><span style="width: <?= $pct ?>%"></span></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="section-note mt-2"><a href="<?= e(url('/reportes/secciones-disponibles')) ?>">Ver horarios, aulas y catedráticos de cada sección</a></p>
            <?php endif; ?>
        </section>

        <section class="panel-block" aria-labelledby="t-recientes">
            <h2 class="section-title" id="t-recientes">Inscripciones recientes</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Alumno</th><th>Curso</th><th>Fecha</th><th>Estado</th><th class="text-end">Nota</th></tr></thead>
                    <tbody>
                    <?php foreach ($ultimas as $r): ?>
                        <tr>
                            <td><?= e($r['ALUMNO']) ?><div class="small text-body-secondary">Carné <?= e($r['CARNET']) ?></div></td>
                            <td><code><?= e($r['CODIGO']) ?></code> <?= e($r['CURSO']) ?></td>
                            <td class="text-nowrap"><?= e(fecha($r['FECHA'])) ?></td>
                            <td><?= badge_estado($r['ESTADO']) ?></td>
                            <td class="text-end"><?= badge_nota($r['NOTA']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="section-note mt-2"><a href="<?= e(url('/reportes/historial-academico')) ?>">Abrir el historial académico completo</a></p>
        </section>
    </div>

    <div>
        <section class="panel-block" aria-labelledby="t-carreras">
            <h2 class="section-title" id="t-carreras">Alumnos por carrera</h2>
            <ul class="renglones">
                <?php foreach ($porCarrera as $c): $n = (int) $c['TOTAL']; ?>
                    <li class="renglon">
                        <div class="renglon-titulo"><?= e($c['CARRERA']) ?></div>
                        <div class="renglon-cifra"><strong><?= $n ?></strong></div>
                        <div class="barra" aria-hidden="true"><span style="width: <?= round($n / $maxCarrera * 100) ?>%"></span></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="panel-block" aria-labelledby="t-estados">
            <h2 class="section-title" id="t-estados">Estado de las inscripciones</h2>
            <div class="barra-segmentos" role="img" aria-label="Distribución de inscripciones por estado">
                <?php foreach ($porEstado as $e): ?>
                    <span style="width: <?= round((int) $e['TOTAL'] / $totalEstados * 100, 2) ?>%; background: <?= $colorEstado[$e['ESTADO']] ?? 'var(--tinta-3)' ?>"></span>
                <?php endforeach; ?>
            </div>
            <ul class="leyenda">
                <?php foreach ($porEstado as $e): ?>
                    <li><i style="background: <?= $colorEstado[$e['ESTADO']] ?? 'var(--tinta-3)' ?>"></i><?= e(ucfirst(mb_strtolower((string) $e['ESTADO']))) ?>: <strong><?= (int) $e['TOTAL'] ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="panel-block" aria-labelledby="t-promedios">
            <h2 class="section-title" id="t-promedios">Promedio por curso</h2>
            <?php if ($promedios === []): ?>
                <p class="section-note">Aún no hay cursos calificados.</p>
            <?php else: ?>
                <ul class="renglones">
                    <?php foreach (array_slice($promedios, 0, 8) as $p): ?>
                        <li class="renglon">
                            <div class="renglon-titulo"><code><?= e($p['CODIGO']) ?></code> <?= e($p['CURSO']) ?>
                                <small><?= (int) $p['EVALUADOS'] ?> <?= (int) $p['EVALUADOS'] === 1 ? 'nota registrada' : 'notas registradas' ?></small>
                            </div>
                            <div class="renglon-cifra"><?= badge_nota($p['PROMEDIO']) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <?php if (is_admin()): ?>
            <section class="panel-block" aria-labelledby="t-actividad">
                <h2 class="section-title" id="t-actividad">Últimos cambios</h2>
                <?php if ($actividad === []): ?>
                    <p class="section-note">Aquí aparecerán los registros creados, modificados o eliminados.</p>
                <?php else: ?>
                    <ul class="bitacora-lista">
                        <?php foreach ($actividad as $a):
                            $verbo = ['INSERT' => 'registró', 'UPDATE' => 'modificó', 'DELETE' => 'eliminó'][$a['ACCION']] ?? mb_strtolower((string) $a['ACCION']);
                            ?>
                            <li>
                                <strong><?= e($a['USERNAME']) ?></strong> <?= e($verbo) ?> el registro <?= e($a['REGISTRO']) ?> de <code><?= e($a['TABLA']) ?></code>
                                <time><?= e(fecha((string) $a['FECHA'], true)) ?></time>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="section-note mt-2"><a href="<?= e(url('/bitacora')) ?>">Revisar la bitácora</a></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</div>
