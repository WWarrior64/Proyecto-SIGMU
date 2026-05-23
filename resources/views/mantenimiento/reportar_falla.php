<?php
/** @var array $sessionUser */
/** @var array $edificios */

$sigmuPageTitle = 'REPORTAR FALLA';
$sigmuLayoutAdmin = false;
$sigmuExtraCss = ['/assets/css/mantenimiento.css', '/assets/css/reportar-falla.css'];
$sigmuExtraScripts = ['/assets/js/reportar-falla.js'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>

    <div class="back-btn-container">
        <button class="back-btn" onclick="window.location.href='/sigmu/mantenimiento'" title="Regresar">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </button>
    </div>

    <div class="report-container">
        <div class="report-card">
            <div class="report-header">FORMULARIO DE REPORTE DE INCIDENCIA</div>
            <form id="formReportarFalla" class="report-body">
                <?= \App\Support\Csrf::field() ?>
                <div class="form-section">
                    <div class="form-section-title">Localización del Activo</div>
                    <div class="form-group">
                        <label for="edificio_id">Seleccione Edificio:</label>
                        <select id="edificio_id" name="edificio_id" class="form-control" required>
                            <option value="">-- Seleccione un edificio --</option>
                            <?php foreach ($edificios as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sala_id">Seleccione Sala: <span class="loader" id="loaderSalas">⏳</span></label>
                        <select id="sala_id" name="sala_id" class="form-control" required disabled>
                            <option value="">-- Primero seleccione edificio --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="activo_id">Seleccione Activo: <span class="loader" id="loaderActivos">⏳</span></label>
                        <select id="activo_id" name="activo_id" class="form-control" required disabled>
                            <option value="">-- Primero seleccione sala --</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Detalle de la Falla</div>
                    <div class="form-group">
                        <label for="tipo_falla">Tipo de Falla:</label>
                        <select id="tipo_falla" name="tipo_falla" class="form-control" required>
                            <option value="">-- Seleccione tipo --</option>
                            <option value="hardware">Falla de Hardware</option>
                            <option value="software">Falla de Software</option>
                            <option value="electrico">Problema Eléctrico</option>
                            <option value="fisico">Daño Físico / Estructural</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción detallada del problema:</label>
                        <textarea id="descripcion" name="descripcion" class="form-control" rows="4" placeholder="Describa qué sucede con el activo..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="fecha_deteccion">Fecha de detección:</label>
                        <input type="date" id="fecha_deteccion" name="fecha_deteccion" class="form-control" required value="<?= date('Y-m-d') ?>" max="&lt;?= date('Y-m-d') ?&gt;"&gt;
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="button" class="btn-secondary" style="flex: 1;" onclick="window.location.href='/sigmu/mantenimiento'">CANCELAR</button>
                    <button type="submit" class="btn-primary" style="flex: 2; background: #8b0000;">REGISTRAR REPORTE</button>
                </div>
            </form>
        </div>
    </div>

<?php require __DIR__ . '/../partials/sigmu_shell_end.php';
