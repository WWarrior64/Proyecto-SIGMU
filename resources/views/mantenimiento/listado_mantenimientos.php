<?php
/** @var array $sessionUser */
/** @var array $mantenimientos */

$sigmuPageTitle = 'LISTADO MANTENIMIENTOS';
$sigmuLayoutAdmin = (\App\Support\Roles::is($sessionUser['rol_id'] ?? 0, \App\Support\Roles::ADMIN));
$sigmuExtraCss = ['/assets/css/mantenimiento.css', '/assets/css/listado-mantenimientos.css'];
$sigmuExtraScripts = ['/assets/js/listado-mantenimientos.js'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>

    <div class="back-btn-container">
        <button class="back-btn" onclick="window.location.href='/sigmu/mantenimiento'" title="Regresar al Panel">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </button>
    </div>

    <div class="list-container">
        <div class="table-card">
            <div class="table-header">
                LISTADO GENERAL DE REPARACIONES
            </div>
            
            <div style="overflow-x: auto;">
                <table class="maint-table">
                    <thead>
                        <tr>
                            <th>Activo</th>
                            <th>Descripción del Problema</th>
                            <th>Fecha Programada</th>
                            <th>Responsable</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mantenimientos)): ?>
                            <tr>
                                <td colspan="6" class="empty-msg">No se encontraron registros de mantenimiento.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mantenimientos as $m): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($m['activo_codigo']) ?></strong><br>
                                        <small><?= htmlspecialchars($m['activo_nombre']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($m['descripcion_problema']) ?></td>
                                    <td><?= $m['fecha_agendada'] ? date('d/m/Y', strtotime($m['fecha_agendada'])) : '<i>No asignada</i>' ?></td>
                                    <td><?= htmlspecialchars($m['responsable'] ?? 'Sin asignar') ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $m['estado'] ?>">
                                            <?= str_replace(['_', '-'], ' ', $m['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($m['estado'] === 'en_proceso'): ?>
                                            <button class="btn-complete" onclick="abrirModalCompletar(<?= $m['id'] ?>, '<?= htmlspecialchars($m['activo_codigo']) ?>')">
                                                Finalizar
                                            </button>
                                        <?php elseif ($m['estado'] === 'completado'): ?>
                                            <small style="color: #059669;">Terminado el <?= date('d/m/Y', strtotime($m['fecha_completada'])) ?></small>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL FINALIZAR -->
    <div class="modal-overlay" id="modalCompletar">
        <div class="modal-content" style="width: 500px;">
            <div class="modal-header">
                FINALIZAR REPARACIÓN - <span id="modalActivoCodigo"></span>
            </div>
            <form id="formCompletar">
                <?= \App\Support\Csrf::field() ?>
                <input type="hidden" name="mantenimiento_id" id="mantenimiento_id_completar">
                <div class="modal-body">
                    <p style="font-size: 13px; color: #4a5568; margin-bottom: 15px;">
                        Complete la información del trabajo realizado para cerrar este reporte.
                    </p>
                    
                    <div class="form-group">
                        <label for="trabajo_realizado">Descripción del trabajo realizado:</label>
                        <textarea name="notas" id="trabajo_realizado" class="form-control" rows="3" placeholder="Detalle las acciones tomadas..." required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_real">Fecha intervención:</label>
                            <input type="date" name="fecha_real" id="fecha_real" class="form-control" required value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label for="resultado">Resultado del mantenimiento:</label>
                            <select name="resultado" id="resultado" class="form-control" required>
                                <option value="resuelto">Resuelto (El activo vuelve a estar Activo)</option>
                                <option value="parcial">Parcial (Requiere más trabajo)</option>
                                <option value="no_resuelto">No Resuelto</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones opcionales:</label>
                        <textarea name="observaciones" id="observaciones" class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModalCompletar()">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background: #059669;">Guardar y Finalizar</button>
                </div>
            </form>
        </div>
    </div>

<?php require __DIR__ . '/../partials/sigmu_shell_end.php';
