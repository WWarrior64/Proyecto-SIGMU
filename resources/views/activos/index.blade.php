<?php
declare(strict_types=1);

$activos = (isset($activos) && is_array($activos)) ? $activos : [];
$pagina = (int) ($pagina ?? 1);
$totalPaginas = (int) ($totalPaginas ?? 1);
$busqueda = (string) ($busqueda ?? '');
$total = (int) ($total ?? 0);
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>SIGMU - Gestión de Activos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
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
        .actions {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
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
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .search-form {
            display: flex;
            gap: 10px;
        }
        .search-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 300px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f1f3f4;
        }
        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
        }
        .pagination a:hover {
            background-color: #e9ecef;
        }
        .pagination .current {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .empty {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }
        .messages {
            margin-bottom: 20px;
        }
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestión de Activos</h1>
        
        <div class="actions">
            <a href="/activos/create" class="btn">Crear Nuevo Activo</a>
            <form class="search-form" method="GET" action="/activos">
                <input type="text" name="busqueda" class="search-input" placeholder="Buscar por nombre, tipo o código..." value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn">Buscar</button>
                <?php if (!empty($busqueda)): ?>
                    <a href="/activos" class="btn btn-secondary">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="messages">
                <?php if ($_GET['success'] === 'activo_creado'): ?>
                    <div class="alert alert-success">Activo creado exitosamente.</div>
                <?php elseif ($_GET['success'] === 'activo_actualizado'): ?>
                    <div class="alert alert-success">Activo actualizado exitosamente.</div>
                <?php elseif ($_GET['success'] === 'activo_eliminado'): ?>
                    <div class="alert alert-success">Activo eliminado exitosamente.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="messages">
                <div class="alert alert-error"><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <?php if ($total > 0): ?>
            <p>Total de activos: <?= $total ?></p>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activos as $activo): ?>
                        <tr>
                            <td><?= (int) $activo['id'] ?></td>
                            <td><?= htmlspecialchars((string) $activo['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $activo['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $activo['estado'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <a href="/activos/<?= (int) $activo['id'] ?>" class="btn btn-secondary">Ver</a>
                                <a href="/activos/<?= (int) $activo['id'] ?>/edit" class="btn">Editar</a>
                                <a href="#" onclick="confirmarEliminacion(<?= (int) $activo['id'] ?>)" class="btn btn-danger">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($totalPaginas > 1): ?>
                <div class="pagination">
                    <?php if ($pagina > 1): ?>
                        <a href="/activos?pagina=<?= $pagina - 1 ?>&busqueda=<?= urlencode($busqueda) ?>">&laquo; Anterior</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <?php if ($i == $pagina): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="/activos?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($pagina < $totalPaginas): ?>
                        <a href="/activos?pagina=<?= $pagina + 1 ?>&busqueda=<?= urlencode($busqueda) ?>">Siguiente &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty">
                No hay activos registrados.
                <?php if (!empty($busqueda)): ?>
                    <br><br>Intenta con una búsqueda diferente o <a href="/activos/create">crea un nuevo activo</a>.
                <?php else: ?>
                    <br><br><a href="/activos/create">Crea tu primer activo</a>.
                <?php endif; ?>
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