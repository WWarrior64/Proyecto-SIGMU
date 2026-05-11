<?php
/** @var array $sessionUser */
/** @var array $activo */

$sigmuPageTitle = 'REPORTAR FALLA';
$sigmuLayoutAdmin = (($sessionUser['rol_nombre'] ?? '') === 'Administrador');
$sigmuExtraCss = ['/assets/css/reporte-falla.css'];
$sigmuExtraScripts = ['/assets/js/reporte-falla.js'];
$fechaDeteccion = $fechaDeteccion ?? date('Y-m-d H:i');
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>

    <div class="back-btn-container">
        <button class="back-btn" onclick="history.back()" title="Regresar">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </button>
    </div>

    <div class="main-container">
        <div class="ticket-card">
            <div class="ticket-header">
                <h2>TICKETS</h2>
            </div>

            <form id="formReporteFalla">
                <input type="hidden" name="activo_id" value="<?= (int)$activo['id'] ?>">
                
                <div class="form-grid">
                    <!-- Nombres (Activo) -->
                    <div class="form-group">
                        <label for="activo_nombre">Nombres:</label>
                        <input type="text" id="activo_nombre" class="form-control" value="<?= htmlspecialchars($activo['nombre']) ?>" disabled>
                    </div>

                    <!-- Usuario que reporta -->
                    <div class="form-group">
                        <label for="usuario_reporta">Usuario que reporta:</label>
                        <input type="text" id="usuario_reporta" class="form-control" value="<?= htmlspecialchars($sessionUser['nombre_completo']) ?>" disabled>
                    </div>

                    <!-- Fecha de detección -->
                    <div class="form-group">
                        <label for="fecha_deteccion">Fecha de detección:</label>
                        <input type="text" id="fecha_deteccion" class="form-control" value="<?= htmlspecialchars($fechaDeteccion) ?>" disabled>
                    </div>

                    <!-- Estado (Pendiente por defecto) -->
                    <div class="form-group">
                        <label for="estado">Estado:</label>
                        <select id="estado" class="form-control" disabled>
                            <option value="pendiente" selected>Pendiente</option>
                        </select>
                    </div>

                    <!-- Tipo de incidencia -->
                    <div class="form-group">
                        <label for="tipo_falla">Tipo de incidencia:</label>
                        <select name="tipo_falla" id="tipo_falla" class="form-control" required>
                            <option value="">Seleccione tipo...</option>
                            <option value="hardware">Falla de Hardware</option>
                            <option value="software">Falla de Software</option>
                            <option value="electrico">Problema Eléctrico</option>
                            <option value="fisico">Daño Físico / Estructural</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group full-width">
                        <label for="descripcion">Descripción:</label>
                        <textarea name="descripcion" id="descripcion" class="form-control" placeholder="Describa el problema detectado..." required></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" onclick="history.back()">CANCELAR</button>
                    <button type="submit" class="btn btn-confirm">CONFIRMAR</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        globalThis.authUser = {
            id: <?= (int)$sessionUser['id'] ?>,
            nombre_completo: <?= json_encode($sessionUser['nombre_completo'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>,
            foto: <?= json_encode($sessionUser['foto'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>,
            rol_nombre: <?= json_encode($sessionUser['rol_nombre'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>
        };
    </script>
<?php require __DIR__ . '/../partials/sigmu_shell_end.php';
