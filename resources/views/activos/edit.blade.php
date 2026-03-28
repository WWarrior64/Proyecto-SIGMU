<?php
declare(strict_types=1);

$activo = $activo ?? null;
$habitaciones = (isset($habitaciones) && is_array($habitaciones)) ? $habitaciones : [];
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SIGMU - Editar Activo</title>
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .required {
            color: #dc3545;
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
            margin-right: 10px;
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
        .messages {
            margin-bottom: 20px;
        }
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-actions {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .image-preview {
            margin-bottom: 15px;
            text-align: center;
        }
        .image-preview img {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f9fa;
        }
        .no-image {
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Editar Activo</h1>

        <?php if (isset($_GET['error'])): ?>
            <div class="messages">
                <div class="alert alert-error"><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <?php if ($activo): ?>
            <form method="POST" action="/activos/<?= (int) $activo['id'] ?>" enctype="multipart/form-data">
                <input type="hidden" name="_method" value="PUT">

                <div class="image-preview">
                    <?php if ($activo['imagen']): ?>
                        <img src="/storage/uploads/<?= htmlspecialchars((string) $activo['imagen'], ENT_QUOTES, 'UTF-8') ?>" 
                             alt="Imagen actual" 
                             onerror="this.style.display='none'; document.getElementById('current-no-image').style.display='block';">
                        <div id="current-no-image" class="no-image" style="display: none;">Imagen no disponible</div>
                        <p style="font-size: 12px; color: #666; margin-top: 5px;">Imagen actual</p>
                    <?php else: ?>
                        <div class="no-image">No hay imagen actual</div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre <span class="required">*</span></label>
                        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars((string) $activo['nombre'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="codigo">Código <span class="required">*</span></label>
                        <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars((string) $activo['codigo'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion"><?= htmlspecialchars((string) $activo['descripcion'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo">Tipo <span class="required">*</span></label>
                        <input type="text" id="tipo" name="tipo" value="<?= htmlspecialchars((string) $activo['tipo'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado <span class="required">*</span></label>
                        <input type="text" id="estado" name="estado" value="<?= htmlspecialchars((string) $activo['estado'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="habitacion_id">Habitación <span class="required">*</span></label>
                        <select id="habitacion_id" name="habitacion_id" required>
                            <option value="">Seleccione una habitación</option>
                            <?php foreach ($habitaciones as $habitacion): ?>
                                <option value="<?= (int) $habitacion['id'] ?>" 
                                        <?= $habitacion['id'] == $activo['habitacion_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $habitacion['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="imagen">Nueva Imagen (opcional)</label>
                        <input type="file" id="imagen" name="imagen" accept="image/*">
                        <small style="color: #666;">Formatos permitidos: JPG, JPEG, PNG, GIF</small>
                        <p style="font-size: 12px; color: #666; margin-top: 5px;">Si selecciona una nueva imagen, reemplazará la actual</p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Actualizar Activo</button>
                    <a href="/activos/<?= (int) $activo['id'] ?>" class="btn btn-secondary">Cancelar</a>
                    <a href="/activos" class="btn btn-secondary">Volver al Listado</a>
                </div>
            </form>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <h2>Activo no encontrado</h2>
                <p>El activo que estás intentando editar no existe o ha sido eliminado.</p>
                <a href="/activos" class="btn btn-secondary" style="margin-top: 20px;">Volver al Listado</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>