# Formulario de Registro de Activo - SIGMU

## Descripción

Formulario completo para registro de activos con todos los campos requeridos según la estructura de la base de datos.

## Características Implementadas

### 1. Campos del Formulario

**Fila 1:**
- **ID**: Campo deshabilitado (se genera automáticamente)
- **Tipo de activo**: Dropdown con tipos disponibles
- **Usuario**: Campo deshabilitado (usuario actual)
- **Código**: Campo de solo lectura (autogenerado)

**Fila 2:**
- **Nombre**: Campo de texto obligatorio (máx. 100 caracteres)
- **Estado**: Dropdown (disponible, en_uso, reparacion, descartado)
- **Fecha creado**: Campo de fecha (automático)
- **Fecha actualizado**: Campo de fecha (deshabilitado)

**Campos adicionales:**
- **Descripción**: Textarea para descripción detallada
- **Foto principal**: Upload de imagen con drag & drop

**Campos ocultos:**
- **Sala ID**: Se detecta automáticamente desde la URL
- **Token CSRF**: Para protección de seguridad

### 2. Funcionalidades Especiales

#### Botón "Agregar Activo"
- **Ubicación**: En la vista de listado de activos por sala
- **Función**: Enlace directo al formulario con sala preseleccionada
- **Formato**: `/sigmu/activo/registrar?sala_id={id}`

#### Código Autogenerado
- **Formato**: `ACT-XXX` (ej: ACT-001, ACT-002)
- **Lógica**: Busca el último código y genera el siguiente
- **Campo**: Solo lectura en el formulario

#### Detección Automática de Sala
- **Implementación**: El sala_id se obtiene de la URL automáticamente
- **Validación**: Verifica que se especifique una sala válida
- **Error**: Muestra mensaje si no se especifica sala

### 3. Validaciones

**Validaciones del lado del cliente:**
- Campos obligatorios marcados con asterisco (*)
- Validación en tiempo real al perder foco
- Mensajes de error claros
- Prevención de envío si hay errores
- **Nota**: No valida sala_id ya que es campo oculto

**Validaciones del lado del servidor:**
- Verificación de token CSRF
- Validación de campos obligatorios
- Verificación de formato de código
- Validación de longitud de nombre
- Validación de estado válido
- Verificación de código único en base de datos
- Verificación de sala_id válido

### 4. Seguridad

- **Protección CSRF**: Token generado automáticamente
- **Sanitización de entrada**: Todos los datos se sanitizan
- **Validación de archivos**: Solo imágenes (JPG, PNG, GIF)
- **Límite de tamaño**: Máximo 5MB por archivo
- **Nombres únicos**: Archivos con nombre único generado
- **Control de acceso**: Solo usuarios autenticados pueden registrar

### 5. Interfaz de Usuario

- **Diseño moderno**: Basado en el prototipo proporcionado
- **Header institucional**: Logo UNICAES y colores corporativos
- **Header simplificado**: Solo botón de menú y botón de volver
- **Logo institucional**: Tamaño optimizado (60x60px)
- **Responsive**: Se adapta a diferentes tamaños de pantalla
- **Feedback visual**: Mensajes de éxito/error claros
- **Drag & drop**: Para subir archivos fácilmente

## Files Created/Modified

### Vistas
- `resources/views/inventario_catalogacion/registrar_activo.php`
- `resources/views/inventario_catalogacion/listado_activos.php` (modificado)
- `public/assets/css/activo-form.css`

### Controladores
- `app/Http/Controllers/SigmuController.php`
  - `registrarActivoGet()`: Muestra el formulario
  - `registrarActivoPost()`: Procesa el registro
  - `procesarFoto()`: Maneja la subida de archivos
  - `registrarActivoGetWithError()`: Helper para mostrar errores

### Servicios
- `app/Services/SigmuService.php`
  - `obtenerTiposActivo()`: Obtiene tipos de activo
  - `generarCodigoActivo()`: Genera código automático
  - `registrarActivo()`: Registra el activo

### Repositorio
- `app/Repositories/SigmuRepository.php`
  - `typesActive()`: Consulta tipos de activo
  - `generarCodigoActivo()`: Genera código automático
  - `existeCodigoActivo()`: Verifica código único
  - `registrarActivo()`: Ejecuta SP de registro
  - `agregarFotoActivo()`: Agrega foto al activo

### Rutas
- `routes/web.php`
  - `GET /sigmu/activo/registrar`: Muestra formulario
  - `POST /sigmu/activo/registrar`: Procesa registro

### Utilidades
- `app/Support/Csrf.php`: Protección CSRF
- `storage/uploads/activos/`: Directorio para fotos

## Uso del Formulario

### Acceso
1. Iniciar sesión en el sistema
2. Navegar a una sala específica
3. Hacer clic en "Agregar Activo"
4. El formulario se abre con la sala preseleccionada automáticamente
5. Llenar campos obligatorios
6. Subir foto (opcional)
7. Hacer clic en "AGREGAR"

### Campos Obligatorios
- Tipo de activo
- Código (autogenerado)
- Nombre
- Estado

### Formato de Código
- Formato: `ACT-XXX`
- Ejemplo: `ACT-001`, `ACT-002`, `ACT-003`
- Generación automática basada en último código

### Estados Disponibles
- **Disponible**: Activo listo para uso
- **En uso**: Activo asignado a alguien
- **Reparación**: Activo en mantenimiento
- **Descartado**: Activo fuera de servicio

## Procedimientos Almacenados Utilizados

- `sp_registrar_activo`: Registra el activo
- `sp_agregar_foto`: Agrega foto al activo

## Vistas de Base de Datos

- `vista_tipos_activo`: Tipos de activo disponibles

## Mensajes de Error

- "El código es obligatorio"
- "El nombre es obligatorio"
- "El tipo de activo es obligatorio"
- "El estado es obligatorio"
- "La sala es obligatoria"
- "El código solo puede contener letras, números y guiones"
- "El nombre no puede exceder 100 caracteres"
- "Ya existe un activo con el código: [código]"
- "Token CSRF inválido"
- "Error: No se especificó una sala"

## Mensajes de Éxito

- "Activo registrado exitosamente con ID: [id]"

## Próximos Pasos

1. **Integración con listado**: Conectar con la vista de listado de activos
2. **Edición de activos**: Formulario para editar activos existentes
3. **Galería de fotos**: Múltiples fotos por activo
4. **Reportes**: Generar reportes de activos registrados
5. **Búsqueda**: Filtrar activos por diferentes criterios

## Notas Técnicas

- El formulario usa PDO con prepared statements
- Los procedimientos almacenados validan permisos
- Las vistas restringidas filtran por usuario en sesión
- El sistema de archivos organiza fotos por fecha
- Los tokens CSRF expiran con la sesión
- El código se genera automáticamente basado en el último existente
- La sala se detecta automáticamente desde la URL
- No se requiere selección manual de sala
