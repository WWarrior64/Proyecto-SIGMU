<?php
/** @var array $sessionUser */
/** @var array $mantenimientos */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGMU - Listado de Mantenimientos</title>
    <link rel="stylesheet" href="/assets/css/mantenimiento.css">
    <style>
        .list-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .table-header {
            background: #8b0000;
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .maint-table {
            width: 100%;
            border-collapse: collapse;
        }
        .maint-table th {
            text-align: left;
            padding: 12px 20px;
            background: #f8f9fa;
            color: #4a5568;
            font-size: 13px;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
        }
        .maint-table td {
            padding: 14px 20px;
            border-bottom: 1px solid #edf2f7;
            font-size: 14px;
            color: #2d3748;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-pendiente { background: #fef3c7; color: #92400e; }
        .status-en_proceso { background: #dcfce7; color: #166534; }
        .status-completado { background: #d1fae5; color: #065f46; }
        .status-cancelado { background: #fee2e2; color: #991b1b; }
        
        .btn-complete {
            background: #059669;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-complete:hover { background: #047857; }
        
        .empty-msg {
            padding: 40px;
            text-align: center;
            color: #718096;
        }
    </style>
</head>
<body>

    <!-- BARRA SUPERIOR -->
    <header class="header-bar">
        <div class="header-left">
            <button class="menu-btn" id="menuBtn" onclick="openSidebarMenu()">☰</button>
            <img src="/assets/img/unicaes_logo.png" alt="UNICAES" class="logo">
            <h1 class="header-title">MANTENIMIENTOS</h1>
        </div>
        <div class="header-right">
            <button class="icon-btn logout-btn" title="Cerrar Sesión" onclick="window.location.href='/sigmu/logout'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </button>
        </div>
    </header>

    <div class="back-btn-container">
        <button class="back-btn" onclick="window.location.href='/sigmu/mantenimiento'" title="Regresar al Panel">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </button>
    </div>

    <main class="list-container">
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
                                            <?= str_replace('_', ' ', $m['estado']) ?>
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
    </main>

    <!-- MODAL FINALIZAR -->
    <div class="modal-overlay" id="modalCompletar">
        <div class="modal-content" style="width: 500px;">
            <div class="modal-header">
                FINALIZAR REPARACIÓN - <span id="modalActivoCodigo"></span>
            </div>
            <form id="formCompletar">
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
                            <input type="date" name="fecha_real" id="fecha_real" class="form-control" required value="<?= date('Y-m-d') ?>">
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

    <script src="/assets/js/global-menu.js"></script>
    <script>
        const modal = document.getElementById('modalCompletar');
        
        function abrirModalCompletar(id, codigo) {
            document.getElementById('mantenimiento_id_completar').value = id;
            document.getElementById('modalActivoCodigo').textContent = codigo;
            modal.style.display = 'flex';
        }

        function cerrarModalCompletar() {
            modal.style.display = 'none';
        }

        document.getElementById('formCompletar').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('/sigmu/mantenimiento/completar', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Mantenimiento finalizado con éxito');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error al procesar la solicitud');
            });
        });
    </script>
</body>
</html>
