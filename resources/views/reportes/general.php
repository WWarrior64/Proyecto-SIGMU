<?php
/**
 * @var array $edificios
 * @var array $tiposActivo
 * @var array $usuarios
 * @var array $authUser
 */
declare(strict_types=1);

$sigmuPageTitle = 'REPORTE GENERAL CONFIGURABLE';
$user = \App\Support\Session::get('auth_user');
$sigmuLayoutAdmin = \App\Support\Roles::is($user['rol_id'] ?? 0, \App\Support\Roles::ADMIN);
$sigmuExtraCss = ['/assets/css/reportes.css'];
$sigmuExtraScripts = ['/assets/js/reportes.js'];
require __DIR__ . '/../partials/sigmu_shell_start.php';

$user = \App\Support\Session::get('auth_user');
$homeUrl = (\App\Support\Roles::is($user['rol_id'] ?? 0, \App\Support\Roles::ADMIN)) ? '/sigmu' : '/sigmu/edificios';
?>

<div class="report-config-card">
    <div class="sigmu-back-row">
        <a href="<?= $homeUrl ?>" class="btn-home">
            <i class="fas fa-home"></i> Ir al Inicio
        </a>
    </div>

    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-file-invoice"></i> Reporte General de Activos</h1>
    </div>

    <form action="/sigmu/reporte/general/exportar" method="POST" id="reportForm">
        <?= \App\Support\Csrf::field() ?>
        <div class="content-grid">
            <!-- Left Column: Filters -->
            <div class="left-column">
                <div class="detail-group">
                    <label class="detail-label"><i class="fas fa-filter"></i> Filtros de Alcance</label>
                    
                    <div class="filter-group">
                        <label class="form-label">Edificios</label>
                        <div class="scroll-checkbox-list">
                            <?php foreach($edificios as $e): ?>
                            <label class="checkbox-item">
                                <input type="checkbox" name="edificios[]" value="<?= $e['id'] ?>" class="edificio-selector" data-id="<?= $e['id'] ?>">
                                <span><?= htmlspecialchars($e['nombre']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Si no selecciona ninguno, se incluirán todos los accesibles.</small>
                    </div>

                    <div class="filter-group" id="groupSalas" style="display: none;">
                        <label class="form-label">Salas Específicas</label>
                        <div id="salasContainer" class="scroll-checkbox-list">
                            <!-- Se cargará dinámicamente vía JS -->
                        </div>
                        <small class="text-muted">Filtra activos por salas específicas dentro de los edificios seleccionados.</small>
                    </div>

                    <div class="filter-group">
                        <label class="form-label">Tipos de Activo</label>
                        <div class="scroll-checkbox-list">
                            <?php foreach($tiposActivo as $t): ?>
                            <label class="checkbox-item">
                                <input type="checkbox" name="tipos[]" value="<?= $t['id'] ?>">
                                <span><?= htmlspecialchars($t['nombre']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label class="form-label">Estados</label>
                        <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded border">
                            <label class="checkbox-item">
                                <input type="checkbox" name="estados[]" value="disponible" checked>
                                <span>Disponible</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="estados[]" value="en_uso" checked>
                                <span>En Uso</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="estados[]" value="reparacion" checked>
                                <span>Reparación</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="estados[]" value="descartado">
                                <span>Descartado</span>
                            </label>
                        </div>
                    </div>

                    <?php if (!empty($usuarios)): ?>
                    <div class="filter-group">
                        <label class="form-label">Usuario Creador</label>
                        <select name="usuario_creador_id" class="form-select">
                            <option value="">Todos los usuarios</option>
                            <?php foreach($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre_completo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="filter-group">
                        <label class="form-label">Rango de Fecha (Registro)</label>
                        <div class="d-flex gap-2">
                            <input type="date" name="fecha_inicio" class="form-control">
                            <span class="align-self-center">a</span>
                            <input type="date" name="fecha_fin" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Sections -->
            <div class="right-column">
                <div class="detail-group">
                    <label class="detail-label"><i class="fas fa-cog"></i> Secciones a Incluir</label>
                    
                    <div class="sections-list">
                        <label class="list-group-item">
                            <input class="form-check-input" type="checkbox" name="sec_datos" checked value="1">
                            <div>
                                <strong>Datos generales</strong>
                                <small class="text-muted">Código, nombre, tipo, descripción, estado, ubicación y fechas.</small>
                            </div>
                        </label>
                        <label class="list-group-item">
                            <input class="form-check-input" type="checkbox" name="sec_historial" value="1">
                            <div>
                                <strong>Historial de movimientos</strong>
                                <small class="text-muted">Registros de traslados, cambios de estado y modificaciones.</small>
                            </div>
                        </label>
                        <label class="list-group-item">
                            <input class="form-check-input" type="checkbox" name="sec_mantenimientos" value="1">
                            <div>
                                <strong>Historial de mantenimientos</strong>
                                <small class="text-muted">Detalle de fallas, técnicos y notas de intervención.</small>
                            </div>
                        </label>
                        <label class="list-group-item">
                            <input class="form-check-input" type="checkbox" name="sec_resumen" checked value="1">
                            <div>
                                <strong>Resumen estadístico</strong>
                                <small class="text-muted">Conteos y distribución por estado, sala y edificio.</small>
                            </div>
                        </label>
                    </div>

                    <div class="filter-group mt-4">
                        <label class="detail-label"><i class="fas fa-sort-amount-down"></i> Ordenamiento del Reporte</label>
                        <div class="bg-light p-3 rounded border">
                            <div class="mb-3">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="agrupar_ubicacion" value="1" checked>
                                    <span>Agrupar por Edificio y Sala (Recomendado)</span>
                                </label>
                            </div>
                            <div class="d-flex gap-3">
                                <div class="flex-grow-1">
                                    <label class="form-label mb-1">Criterio Principal</label>
                                    <select name="ordenar_por" class="form-select">
                                        <option value="nombre">Nombre del Activo</option>
                                        <option value="fecha_creado">Fecha de Registro</option>
                                        <option value="estado">Estado Operativo</option>
                                        <option value="tipo">Tipo de Activo</option>
                                        <option value="valor_adquisicion">Valor de Adquisición</option>
                                        <option value="codigo">Código Institucional</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label mb-1">Dirección</label>
                                    <select name="orden_dir" class="form-select">
                                        <option value="ASC">Ascendente</option>
                                        <option value="DESC">Descendente</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-3">
                        <button type="submit" class="btn-reporte">
                            <i class="fas fa-file-pdf"></i>
                            GENERAR REPORTE PDF
                        </button>
                        <button type="button" class="btn-reporte btn-reporte--preview" id="btnPreview">
                            <i class="fas fa-eye"></i>
                            VISTA PREVIA
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../partials/sigmu_shell_end.php'; ?>
