# SIGMU - University Maintenance Management System

Sistema web para la gestión integral de activos, mantenimientos y espacios físicos de la universidad.

## Tecnologías

- **Backend**: PHP 8.1+ con arquitectura MVC propia
- **Frontend**: HTML5, CSS3, JavaScript vanilla
- **Base de datos**: MySQL 8.0+ con procedimientos almacenados
- **Dependencias**: Composer (PHPMailer, DomPDF)

## Estructura del proyecto

```
app/
├── Http/Controllers/   → Controladores (lógica de rutas)
├── Middleware/          → Middleware de autorización
├── Models/              → Modelos de datos
├── Repositories/        → Acceso a base de datos
├── Services/            → Lógica de negocio
├── Support/             → Utilidades (Router, Session, CSRF, Cache, Logger)
config/                  → Configuraciones (base de datos, correo, etc.)
database/
├── dumps/               → Respaldos de base de datos
├── migrations/          → Scripts SQL de migración
docs/                    → Documentación del proyecto
public/
├── assets/              → CSS y JavaScript del frontend
├── uploads/             → Archivos subidos (fotos de activos, edificios)
├── index.php            → Punto de entrada
├── .htaccess            → Reglas de reescritura Apache
resources/views/         → Plantillas PHP de las vistas
routes/                  → Definiciones de rutas web y CLI
storage/
├── backups/             → Respaldos automáticos de BD
├── cache/               → Archivos de caché
├── logs/                → Registros de actividad y errores
├── sessions/            → Sesiones PHP
└── uploads/             → Archivos subidos
```

## Instalación

1. Clonar el repositorio
2. Ejecutar `composer install`
3. Configure `.env` with DB connection data
4. Importar `database/migrations/` a MySQL
5. Iniciar servidor: `php -S localhost:8000 -t public`