# API - SIGMU

## Endpoints de rutas web

El sistema utiliza un router PHP propio. Todas las rutas se definen en `routes/web.php`.

### Autenticación

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/sigmu` | Dashboard principal |
| POST | `/sigmu/login` | Iniciar sesión |
| GET | `/sigmu/logout` | Cerrar sesión |
| GET | `/sigmu/perfil` | Ver perfil de usuario |
| POST | `/sigmu/perfil/actualizar` | Actualizar perfil |
| GET | `/sigmu/recuperar` | Formulario recuperar contraseña |
| POST | `/sigmu/recuperar` | Enviar recuperación |
| GET | `/sigmu/reset` | Resetear contraseña (con token) |
| POST | `/sigmu/reset` | Guardar nueva contraseña |

### Edificios y Salas

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/sigmu/edificios` | Panel de edificios |
| GET | `/sigmu/edificio` | Salas de un edificio |
| POST | `/sigmu/edificios/guardar` | Crear/editar edificio |
| POST | `/sigmu/edificios/guardar-sala` | Crear/editar sala |
| POST | `/sigmu/edificio/eliminar` | Eliminar edificio |
| POST | `/sigmu/sala/eliminar` | Eliminar sala |
| POST | `/sigmu/edificios/verificar-nombre` | Verificar nombre edificio (AJAX) |
| POST | `/sigmu/salas/verificar-nombre` | Verificar nombre sala (AJAX) |

### Activos

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/sigmu/activo/ver` | Ver detalle del activo |
| GET | `/sigmu/activo/registrar` | Formulario de registro |
| POST | `/sigmu/activo/registrar` | Guardar nuevo activo |
| GET | `/sigmu/activo/editar` | Formulario de edición |
| POST | `/sigmu/activo/actualizar` | Guardar cambios del activo |
| POST | `/sigmu/activo/dar-baja` | Dar de baja un activo |
| POST | `/sigmu/activo/eliminar` | Eliminar activo |
| GET | `/sigmu/activo/importar` | Importar activos desde Excel |
| POST | `/sigmu/activo/importar` | Procesar importación |
| GET | `/sigmu/activo/historial` | Historial de cambios del activo |
| GET | `/sigmu/activo/generar-codigo` | Generar código (AJAX) |

### Mantenimiento

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/sigmu/mantenimiento` | Dashboard de mantenimiento |
| GET | `/sigmu/mantenimiento/reportar` | Reportar falla |
| POST | `/sigmu/mantenimiento/reportar` | Guardar reporte de falla |
| POST | `/sigmu/mantenimiento/agendar` | Agendar reparación |
| GET | `/sigmu/mantenimiento/listado` | Listado de mantenimientos |
| POST | `/sigmu/mantenimiento/completar` | Finalizar mantenimiento |

### Reportes

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/sigmu/reportes` | Reportes generales |
| POST | `/sigmu/reporte/individual/exportar` | Exportar PDF individual |
| POST | `/sigmu/reporte/general/exportar` | Exportar PDF general |

### Administración

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/sigmu/user_administration/role_management` | Role management |
| POST | `/sigmu/administracion_usuarios/rol/guardar` | Guardar rol |
| POST | `/sigmu/administracion_usuarios/rol/eliminar` | Eliminar rol |
| POST | `/sigmu/administracion_usuarios/guardar_usuario` | Crear/editar usuario |
| GET | `/sigmu/user_administration/space_allocation` | Assign spaces |
| POST | `/sigmu/administracion_usuarios/asignar_espacio` | Asignar edificio a usuario |