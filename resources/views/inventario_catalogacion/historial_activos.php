<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Cambios - Activo</title>
    <style>
        :root {
            --primary-color: #2563eb;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --hover-color: #f1f5f9;
            --success-color: #10b981;
            --danger-color: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 24px;
        }

        .title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            background: #fafafa;
        }

        .search-container {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-icon {
            color: var(--text-secondary);
            font-size: 16px;
        }

        .card-body {
            padding: 0;
        }

        .table-container {
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        thead {
            position: sticky;
            top: 0;
            background: #f8fafc;
            z-index: 1;
        }

        th {
            text-align: left;
            padding: 14px 16px;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--border-color);
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }

        tr:hover {
            background-color: var(--hover-color);
        }

        tr:nth-child(even) {
            background-color: #fafafa;
        }

        .field-name {
            font-weight: 500;
            color: var(--text-primary);
        }

        .value-old {
            color: var(--danger-color);
            font-weight: 500;
            background-color: #fef2f2;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #fecaca;
        }

        .value-new {
            color: var(--success-color);
            font-weight: 500;
            background-color: #f0fdf4;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #bbf7d0;
        }

        .date-time {
            color: var(--text-secondary);
            font-size: 12px;
            font-family: monospace;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        .user-name {
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-text {
            font-size: 16px;
            font-weight: 500;
        }

        .no-results {
            text-align: center;
            padding: 20px;
            color: var(--text-secondary);
            font-style: italic;
        }

        @media (max-width: 768px) {
            .search-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input {
                min-width: auto;
            }

            th, td {
                padding: 12px;
                font-size: 13px;
            }

            .table-container {
                max-height: 500px;
            }
        }

        @media (max-width: 480px) {
            .title {
                font-size: 20px;
            }

            th, td {
                padding: 10px 8px;
                font-size: 12px;
            }

            .table-container {
                max-height: 400px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">📋 Historial de Cambios</h1>
            <p class="subtitle">Registro de modificaciones realizadas al activo</p>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="search-container">
                    <span class="search-icon">🔍</span>
                    <input 
                        type="text" 
                        id="searchInput" 
                        class="search-input" 
                        placeholder="Buscar en el historial..."
                        onkeyup="filterTable()"
                    >
                </div>
            </div>

            <div class="card-body">
                <div class="table-container">
                    <table id="historialTable">
                        <thead>
                            <tr>
                                <th>Campo Modificado</th>
                                <th>Valor Anterior</th>
                                <th>Valor Nuevo</th>
                                <th>Fecha y Hora</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($historial->isEmpty())
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <div class="empty-icon">📝</div>
                                        <div class="empty-text">No hay historial disponible</div>
                                    </td>
                                </tr>
                            @else
                                @foreach($historial as $registro)
                                    <tr>
                                        <td class="field-name">{{ $registro->campo_modificado }}</td>
                                        <td>
                                            @if($registro->valor_anterior)
                                                <span class="value-old">{{ $registro->valor_anterior }}</span>
                                            @else
                                                <span class="value-old">[vacío]</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($registro->valor_nuevo)
                                                <span class="value-new">{{ $registro->valor_nuevo }}</span>
                                            @else
                                                <span class="value-new">[vacío]</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="date-time">{{ $registro->created_at->format('d/m/Y H:i:s') }}</span>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    {{ strtoupper(substr($registro->usuario->name ?? 'U', 0, 1)) }}
                                                </div>
                                                <span class="user-name">{{ $registro->usuario->name ?? 'Usuario desconocido' }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('historialTable');
            const tr = table.getElementsByTagName('tr');
            
            // Empezar desde 1 para saltar el encabezado
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;
                
                // Buscar en todas las columnas
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                // Mostrar u ocultar fila
                if (found) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>