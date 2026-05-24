# Arquitectura - SIGMU

## Patrón arquitectónico

El sistema utiliza una arquitectura MVC (Modelo-Vista-Controlador) personalizada, con una capa adicional de Repositorios para el acceso a datos.

## Capas del sistema

### 1. Router (`app/Support/Router.php`)
- Enrutador simple que mapea métodos HTTP + URI a controladores
- Soporta middleware global para autorización
- Middleware implementado: `AuthorizationMiddleware`

### 2. Controladores (`app/Http/Controllers/`)
- Reciben la petición del router
- Validan entrada del usuario
- Delegan lógica de negocio a los servicios
- Renderizan vistas con datos

### 3. Servicios (`app/Services/`)
- Contienen la lógica de negocio
- Orquestan operaciones entre múltiples repositorios
- Mantienen el control de transacciones

### 4. Repositorios (`app/Repositories/`)
- Única capa de acceso a base de datos
- Ejecutan consultas SQL (prepared statements) y procedimientos almacenados
- Retornan arrays asociativos

### 5. Vistas (`resources/views/`)
- Plantillas PHP con HTML
- Reciben datos del controlador
- Escapan salida con `htmlspecialchars()`

### 6. Soporte (`app/Support/`)
- `Router.php` — Enrutador HTTP
- `Session.php` — Gestión de sesiones con timeout
- `Csrf.php` — Protección CSRF
- `Database.php` — Conexión PDO a MySQL
- `Roles.php` — Constantes y verificación de roles
- `Cache.php` — Sistema de caché en archivos
- `Logger.php` — Sistema de logs

## Flujo de una petición

```
Navegador → public/index.php → Router → Middleware → Controlador
                                                         ↓
                                                   Servicio ← → Repositorio → MySQL
                                                         ↓
                                                   Vista (HTML)
                                                         ↓
← HTML/CSS/JS Browser
```

## Base de datos

- **Motor**: MySQL 8.0+
- **Acceso**: PDO con prepared statements
- **Procedimientos almacenados**: Para operaciones CRUD complejas
- **Vistas**: Para consultas con restricción de acceso por usuario