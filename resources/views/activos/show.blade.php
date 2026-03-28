<?php
declare(strict_types=1);

$activo = $activo ?? null;
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SIGMU - Detalle del Activo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .detail-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .detail-card h3 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-size: 16px;
            color: #333;
            font-weight: bold;
        }
        .image-preview {
            grid-column: 1 / -1;
            text-align: center;
            margin-bottom: 20px;
        }
        .image-preview img {
            max-width: 300px;
            max-height: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f9fa;
        }
        .no-image {
            color: #6c757d;
            font-style: italic;
        }
        .actions {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Detalle del Activo</h1>

        <?php if ($activo): ?>
            <?php if ($activo['imagen']): ?>
                <div class="image-preview">
                    <img src="/storage/uploads/<?= htmlspecialchars((string) $activo['imagen'], ENT_QUOTES, 'UTF-8') ?>" 
                         alt="Imagen del activo" 
                         onerror="this.style.display='none'; document.getElementById('no-image').style.display='block';">
                    <div id="no-image" class="no-image" style="display: none;">Imagen no disponible</div>
                </div>
            <?php else: ?>
                <div class="image-preview">
                    <div class="no-image">No hay imagen disponible para este activo</div>
                </div>
            <?php endif; ?>

            <div class="detail-grid">
                <div class="detail-card">
                    <h3>ID</h3>
                    <div class="detail-value"><?= (int) $activo['id'] ?></div>
                </div>
                <div class="detail-card">
                    <h3>Código</h3>
                    <div class="detail-value"><?= htmlspecialchars((string) $activo['codigo'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="detail-card">
                    <h3>Nombre</h3>
                    <div class="detail-value"><?= htmlspecialchars((string) $activo['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="detail-card">
                    <h3>Tipo</h3>
                    <div class="detail-value"><?= htmlspecialchars((string) $activo['tipo'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="detail-card">
                    <h3>Estado</h3>
                    <div class="detail-value"><?= htmlspecialchars((string) $activo['estado'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="detail-card">
                    <h3>Habitación ID</h3>
                    <div class="detail-value"><?= (int) $activo['habitacion_id'] ?></div>
                </div>
                <div class="detail-card">
                    <h3>Creado Por</h3>
                    <div class="detail-value"><?= htmlspecialchars((string) $activo['creado_por'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="detail-card">
                    <h3>Fecha de Creación</h3>
                    <div class="detail-value"><?= htmlspecialchars((string) $activo['fecha_creacion'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>

            <?php if ($activo['descripcion']): ?>
                <div class="detail-card" style="grid-column: 1 / -1; background: #fff; border: 1px solid #dee2e6;">
                    <h3>Descripción</h3>
                    <div class="detail-value" style="font-weight: normal; color: #555;"><?= htmlspecialchars((string) $activo['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <div class="actions">
                <a href="/activos/<?= (int) $activo['id'] ?>/edit" class="btn">Editar</a>
                <a href="/activos" class="btn btn-secondary">Volver al Listado</a>
                <a href="#" onclick="confirmarEliminacion(<?= (int) $activo['id'] ?>)" class="btn btn-danger">Eliminar</a>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <h2>Activo no encontrado</h2>
                <p>El activo que estás buscando no existe o ha sido eliminado.</p>
                <a href="/activos" class="btn btn-secondary" style="margin-top: 20px;">Volver al Listado</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este activo? Esta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/activos/' + id;
                
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                
                form.appendChild(methodInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>