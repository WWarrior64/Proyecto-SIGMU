<?php
declare(strict_types=1);

$habitaciones = (isset($habitaciones) && is_array($habitaciones)) ? $habitaciones : [];
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SIGMU - Crear Activo</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Crear Nuevo Activo</h1>

        <?php if (isset($_GET['error'])): ?>
            <div class="messages">
                <div class="alert alert-error"><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="/activos" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre">Nombre <span class="required">*</span></label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="codigo">Código <span class="required">*</span></label>
                    <input type="text" id="codigo" name="codigo" required>
                </div>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tipo">Tipo <span class="required">*</span></label>
                    <input type="text" id="tipo" name="tipo" required>
                </div>
                <div class="form-group">
                    <label for="estado">Estado <span class="required">*</span></label>
                    <input type="text" id="estado" name="estado" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="habitacion_id">Habitación <span class="required">*</span></label>
                    <select id="habitacion_id" name="habitacion_id" required>
                        <option value="">Seleccione una habitación</option>
                        <?php foreach ($habitaciones as $habitacion): ?>
                            <option value="<?= (int) $habitacion['id'] ?>">
                                <?= htmlspecialchars((string) $habitacion['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="imagen">Imagen</label>
                    <input type="file" id="imagen" name="imagen" accept="image/*">
                    <small style="color: #666;">Formatos permitidos: JPG, JPEG, PNG, GIF</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Guardar Activo</button>
                <a href="/activos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>