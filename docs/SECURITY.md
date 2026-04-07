# Guía de Seguridad - SIGMU

## Resumen de Mejoras de Seguridad Implementadas

Este documento describe las mejoras de seguridad implementadas en el sistema SIGMU.

---

## 1. Protección CSRF (Cross-Site Request Forgery)

### Implementación
- **Archivo**: `app/Support/Csrf.php`
- **Descripción**: Sistema de tokens CSRF que genera y valida tokens únicos para cada sesión

### Características
- Generación de tokens criptográficamente seguros usando `random_bytes()`
- Validación con `hash_equals()` para prevenir timing attacks
- Tokens almacenados en sesión PHP
- Campo oculto en formularios HTML

### Archivos Afectados
- `resources/views/administracion_usuarios/login.php` - Token CSRF agregado
- `app/Http/Controllers/SigmuController.php` - Validación CSRF en login y reset password

---

## 2. Control de Sesiones con Expiración Automática

### Implementación
- **Archivo**: `app/Support/Session.php`
- **Descripción**: Sistema de gestión de sesiones con timeout automático por inactividad

### Características
- **Timeout configurable**: 2 minutos por defecto (120 segundos)
- **Verificación de inactividad**: Compara timestamp de última actividad
- **Regeneración de ID**: Previene session fixation attacks
- **Configuración segura de cookies**:
  - `httponly: true` - Previene acceso desde JavaScript
  - `samesite: Lax` - Protección contra CSRF

---

## 3. Protección XSS (Cross-Site Scripting)

### Implementación
- **Escapado de salida**: Uso consistente de `htmlspecialchars()`
- **Codificación**: UTF-8 para prevenir ataques de encoding
- **Contextos protegidos**: Contenido HTML, atributos, JavaScript embebido

---

## 4. Protección contra SQL Injection

### Implementación
- **Consultas preparadas**: Uso de PDO con prepared statements
- **Parámetros con nombre**: `:param` en lugar de `?`
- **Validación de tipos**: `filter_input()` para parámetros GET/POST

---

## 5. Pruebas de Seguridad

### Archivo de Pruebas
- **Ubicación**: `tests/manual/security_test.php`
- **Descripción**: Suite de pruebas manuales para verificar protecciones

### Pruebas Incluidas
1. Login válido
2. Login inválido
3. SQL Injection username
4. SQL Injection password
5. XSS username
6. Logout
7. Ruta protegida sin auth

### Ejecutar Pruebas
```bash
php -S localhost:8000 -t public
php tests/manual/security_test.php
```

---

## 6. Configuración de Seguridad Recomendada

### Archivo .env
```bash
APP_ENV=production
APP_DEBUG=false
DB_USERNAME=sigmu_user
DB_PASSWORD=contraseña_segura_123+
SESSION_LIFETIME=120
```

### Headers de Seguridad (Apache .htaccess)
```apache
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
```

---

## 7. Mejores Prácticas de Seguridad

### Para Desarrolladores
1. Nunca confíes en la entrada del usuario
2. Escapa toda la salida con `htmlspecialchars()`
3. Usa prepared statements
4. Mantén las dependencias actualizadas
5. No almacenes secrets en código

### Para Administradores
1. HTTPS obligatorio
2. Firewall de aplicación
3. Monitoreo de logs
4. Backups regulares
5. Actualizaciones regulares

---

## 8. Auditoría de Seguridad

### Checklist de Seguridad
- [ ] Todos los formularios tienen tokens CSRF
- [ ] Todas las salidas están escapadas con htmlspecialchars()
- [ ] Todas las consultas SQL usan prepared statements
- [ ] Sesiones expiran correctamente por inactividad
- [ ] Passwords están hasheados con bcrypt
- [ ] Tokens de reset son hasheados en BD
- [ ] Logs de seguridad están habilitados
- [ ] HTTPS está configurado correctamente
- [ ] Headers de seguridad están presentes
- [ ] Dependencias están actualizadas

---

**Última actualización**: 25 de marzo de 2026
**Versión**: 1.0
**Autor**: Equipo de Desarrollo SIGMU