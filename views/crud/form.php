<?php
/**
 * @var App\Support\Entidad $entidad
 * @var array<string, int>|null $key
 * @var array<string, mixed> $row
 * @var array<string, list<array{ID: string, TXT: string}>> $opciones
 */
$path = '/gestion/' . $entidad->slug;
$isEdit = $key !== null;
$action = $isEdit ? $path . '/actualizar' : $path . '/guardar';

$valor = static function (string $col, array $campo) use ($row, $isEdit): string {
    $default = $row[$col] ?? null;
    if (!$isEdit && $default === null && isset($campo['default'])) {
        $default = $campo['default'] === 'hoy' ? date('Y-m-d') : $campo['default'];
    }

    return (string) old($col, $default ?? '');
};
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $isEdit ? 'Editar ' . e(mb_strtolower($entidad->singular())) : 'Registrar ' . e(mb_strtolower($entidad->singular())) ?>
        </h1>
        <?php if ($isEdit): ?>
            <p class="page-subtitle">Identificador: <code><?= e($entidad->llaveTexto($key)) ?></code></p>
        <?php else: ?>
            <p class="page-subtitle">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-xxl-9">
        <form method="post" action="<?= e(url($action)) ?>" class="card needs-validation" novalidate>
            <?= csrf_field() ?>
            <?php if ($isEdit): foreach ($key as $k => $v): ?>
                <input type="hidden" name="key[<?= e($k) ?>]" value="<?= e($v) ?>">
            <?php endforeach; endif; ?>

            <div class="card-body p-4">
                <div class="row g-3">
                    <?php foreach ($entidad->editables() as $col => $campo):
                        $tipo = (string) ($campo['tipo'] ?? 'text');
                        $id = 'f_' . strtolower($col);
                        $error = error_for($col);
                        $value = $valor($col, $campo);
                        $required = !empty($campo['requerido']);
                        $colClass = 'col-md-' . (int) ($campo['col'] ?? 6);
                        $invalid = $error !== null ? ' is-invalid' : '';
                        ?>
                        <div class="<?= $colClass ?>">
                            <label for="<?= $id ?>" class="form-label fw-medium">
                                <?= e($campo['etiqueta']) ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?>
                            </label>

                            <?php if ($tipo === 'select'): ?>
                                <select id="<?= $id ?>" name="<?= e($col) ?>" class="form-select<?= $invalid ?>" <?= $required ? 'required' : '' ?>>
                                    <option value="">Seleccione…</option>
                                    <?php foreach ($opciones[$col] ?? [] as $op): ?>
                                        <option value="<?= e($op['ID']) ?>" <?= (string) $op['ID'] === $value ? 'selected' : '' ?>><?= e($op['TXT']) ?></option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($tipo === 'enum'): ?>
                                <select id="<?= $id ?>" name="<?= e($col) ?>" class="form-select<?= $invalid ?>" <?= $required ? 'required' : '' ?>>
                                    <option value="">Seleccione…</option>
                                    <?php foreach ((array) $campo['opciones'] as $opValue => $opLabel): ?>
                                        <option value="<?= e($opValue) ?>" <?= (string) $opValue === $value ? 'selected' : '' ?>><?= e($opLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($tipo === 'textarea'): ?>
                                <textarea id="<?= $id ?>" name="<?= e($col) ?>" rows="3" class="form-control<?= $invalid ?>"
                                    <?= isset($campo['max']) ? 'maxlength="' . (int) $campo['max'] . '"' : '' ?> <?= $required ? 'required' : '' ?>><?= e($value) ?></textarea>

                            <?php else:
                                $inputType = match ($tipo) {
                                    'number', 'decimal' => 'number',
                                    'date' => 'date',
                                    'email' => 'email',
                                    default => 'text',
                                };
                                $attrs = [];
                                if (isset($campo['max']) && $inputType !== 'number') {
                                    $attrs[] = 'maxlength="' . (int) $campo['max'] . '"';
                                }
                                if (isset($campo['min'])) {
                                    $attrs[] = 'min="' . e($campo['min']) . '"';
                                }
                                if (isset($campo['maxValor'])) {
                                    $attrs[] = 'max="' . e($campo['maxValor']) . '"';
                                }
                                if ($tipo === 'decimal') {
                                    $attrs[] = 'step="' . e(1 / (10 ** (int) ($campo['decimales'] ?? 2))) . '"';
                                } elseif ($tipo === 'number') {
                                    $attrs[] = 'step="1"';
                                }
                                if (isset($campo['patron'])) {
                                    $attrs[] = 'pattern="' . e(trim((string) $campo['patron'], '^$')) . '"';
                                }
                                if (isset($campo['placeholder'])) {
                                    $attrs[] = 'placeholder="' . e($campo['placeholder']) . '"';
                                }
                                if (!empty($campo['mayusculas'])) {
                                    $attrs[] = 'data-uppercase="1"';
                                }
                                ?>
                                <input type="<?= $inputType ?>" id="<?= $id ?>" name="<?= e($col) ?>" value="<?= e($value) ?>"
                                       class="form-control<?= $invalid ?>" <?= implode(' ', $attrs) ?> <?= $required ? 'required' : '' ?>>
                            <?php endif; ?>

                            <?php if ($error !== null): ?>
                                <div class="invalid-feedback d-block"><?= e($error) ?></div>
                            <?php else: ?>
                                <div class="invalid-feedback"><?= e($campo['patron_msg'] ?? 'Revise este campo.') ?></div>
                            <?php endif; ?>
                            <?php if (isset($campo['ayuda'])): ?>
                                <div class="form-text"><?= e($campo['ayuda']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card-footer bg-transparent d-flex justify-content-end gap-2 p-3">
                <a href="<?= e(url($path)) ?>" class="btn btn-light">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4">
                    <?= $isEdit ? 'Guardar cambios' : 'Registrar' ?>
                </button>
            </div>
        </form>
    </div>

</div>
