# SIGMU - Estructura completa PHP + MySQL

Base de proyecto PHP organizada por capas, preparada para web, API y CLI.

## Estructura principal

- `app/`
  - `Http/Controllers` y `Http/Middleware`
  - `Models`, `Repositories`, `Services`
  - `Support` (router, DB y utilidades)
  - `Exceptions`, `Console/Commands`, `Contracts`, `Traits`, `Providers`
- `bootstrap/`
  - `app.php` y `helpers.php`
- `config/`
  - `app.php`, `database.php`, `cache.php`, `session.php`, `mail.php`, `services.php`
- `routes/`
  - `web.php`, `api.php`, `cli.php`
- `database/`
  - `migrations/`, `seeders/`, `factories/`, `dumps/`
- `resources/`
  - `views/` (incluye `layouts/` y `partials/`)
  - `lang/es` y `lang/en`
- `public/`
  - `index.php`, `.htaccess`, `assets/`
- `storage/`
  - `cache/`, `logs/`, `uploads/`, `sessions/`, `framework/`
- `tests/`
  - `Unit/`, `Integration/`, `Feature/`
- `docs/`
  - `architecture/`, `api/`
- `bin/`
  - `console`

## Puesta en marcha

1. `composer install`
2. Copiar `.env.example` a `.env`
3. Configurar MySQL en `.env`
4. `composer run serve`
5. Abrir `http://localhost:8000`

## Flujo MVC SIGMU (ya conectado)

- `GET /sigmu`: dashboard, carga edificios con `vista_mis_edificios`
- `POST /sigmu/login`: valida `username` + `contrasena_hash` en `usuarios` y crea sesion
- `GET /sigmu/edificio?edificio_id=1`: lista salas desde `vista_mis_salas`
- `GET /sigmu/sala?sala_id=1`: lista activos desde `vista_mis_activos`
- `GET /sigmu/logout`: limpia sesion con `CALL limpiar_usuario_sesion()`

### Mapeo MVC que usa este flujo

- **Controller**: `app/Http/Controllers/SigmuController.php`
- **Service**: `app/Services/SigmuService.php`
- **Repository**: `app/Repositories/SigmuRepository.php`
- **View**: `resources/views/sigmu/*.php`

Primero entra por el controlador, luego el servicio, despues el repositorio consulta la BD y finalmente responde una vista.
