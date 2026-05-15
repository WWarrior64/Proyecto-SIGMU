<?php
/**
 * @var array $roles
 */
declare(strict_types=1);

$sigmuPageTitle = 'GESTIÓN DE ROLES';
$sigmuLayoutAdmin = true;
$sigmuExtraCss = ['/assets/css/gestion-roles.css'];
$sigmuExtraScripts = ['/assets/js/gestion-roles.js'];
require __DIR__ . '/../partials/sigmu_shell_start.php';
?>

<div class="main-container">
    <div class="back-button">
        <button type="button" class="back-btn" onclick="window.location.href='/sigmu/administracion_usuarios/gestion_usuarios'" title="Volver a gestión de usuarios">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"></path>
            </svg>
        </button>
    </div>

    <div class="content-card">
        <div class="header-row">
            <h2 class="page-title">Gestión de Roles</h2>
            <button class="btn btn-primary" onclick="abrirModalRol()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                NUEVO ROL
            </button>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Nombre del Rol</th>
                        <th>Descripción</th>
                        <th style="width: 150px;">Acceso Global</th>
                        <th style="width: 120px; text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $rol): ?>
                    <tr>
                        <td><strong>#<?= (int)$rol['id'] ?></strong></td>
                        <td>
                            <span style="font-weight: 600; color: var(--roles-primary);"><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <span style="color: var(--roles-muted); font-size: 0.85rem;">
                                <?= htmlspecialchars($rol['descripcion'] ?? 'Sin descripción proporcionada', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <?php if (isset($rol['ver_todo']) && $rol['ver_todo']): ?>
                                <span class="badge badge--success">SÍ (Full Access)</span>
                            <?php else: ?>
                                <span class="badge badge--secondary">NO (Restringido)</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; justify-content: center; gap: 8px;">
                                <button class="btn-icon" onclick='abrirModalRol(<?= json_encode($rol) ?>)' title="Editar rol">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </button>
                                <?php if (!\App\Support\Roles::in($rol['id'], [\App\Support\Roles::ADMIN, \App\Support\Roles::RESPONSABLE_AREA, \App\Support\Roles::MANTENIMIENTO])): ?>
                                    <button class="btn-icon btn-icon--danger" onclick="eliminarRol(<?= (int)$rol['id'] ?>)" title="Eliminar rol">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL ROL REDISEÑADO -->
<div id="modalRol" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); justify-content: center; align-items: center; z-index: 1000;">
    <div class="modal-container" style="background: white; padding: 32px; border-radius: 12px; width: 450px; max-width: 95%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
            <h3 id="modalTitle" style="margin: 0; font-size: 1.25rem; color: var(--roles-primary); font-weight: 700;">Nuevo Rol</h3>
            <button type="button" onclick="cerrarModalRol()" style="background: none; border: none; color: var(--roles-muted); cursor: pointer; padding: 4px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <form id="formRol">
            <?= \App\Support\Csrf::field() ?>
            <input type="hidden" name="id" id="rol_id" value="0">
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; font-size: 0.85rem; color: var(--roles-text); margin-bottom: 8px;">Nombre del Rol:</label>
                <input type="text" name="nombre" id="rol_nombre" class="form-control" placeholder="Ej: Auditor Externo" required 
                       style="width: 100%; padding: 10px 12px; border: 1px solid var(--roles-border); border-radius: 8px; font-size: 0.95rem;">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; font-size: 0.85rem; color: var(--roles-text); margin-bottom: 8px;">Descripción:</label>
                <textarea name="descripcion" id="rol_descripcion" class="form-control" rows="3" placeholder="Breve explicación de las funciones..."
                          style="width: 100%; padding: 10px 12px; border: 1px solid var(--roles-border); border-radius: 8px; font-size: 0.95rem; resize: none;"></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 24px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; user-select: none;">
                    <input type="checkbox" name="ver_todo" id="rol_ver_todo" value="1" style="width: 18px; height: 18px; accent-color: var(--roles-primary);">
                    <div>
                        <span style="display: block; font-weight: 600; font-size: 0.9rem; color: var(--roles-text);">Acceso Global</span>
                        <span style="display: block; font-size: 0.75rem; color: var(--roles-muted);">Permite ver todos los edificios y salas sin asignación previa.</span>
                    </div>
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; padding-top: 12px; border-top: 1px solid var(--roles-bg);">
                <button type="button" class="btn" onclick="cerrarModalRol()" style="background: #f1f5f9; color: #475569;">CANCELAR</button>
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">GUARDAR ROL</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalRol(rol = null) {
    const modal = document.getElementById('modalRol');
    const title = document.getElementById('modalTitle');
    const form = document.getElementById('formRol');
    
    if (rol) {
        title.innerText = 'Editar Rol';
        document.getElementById('rol_id').value = rol.id;
        document.getElementById('rol_nombre').value = rol.nombre;
        document.getElementById('rol_descripcion').value = rol.descripcion || '';
        document.getElementById('rol_ver_todo').checked = !!rol.ver_todo;
        
        // Bloquear solo acciones críticas, permitir cambio de nombre para prueba de refactorización
        const isRoleAdmin = parseInt(rol.id) === 1;
        document.getElementById('rol_nombre').readOnly = false; 
        document.getElementById('rol_ver_todo').disabled = isRoleAdmin; // Admin siempre debe ser ver_todo
    } else {
        title.innerText = 'Nuevo Rol';
        form.reset();
        document.getElementById('rol_id').value = 0;
        document.getElementById('rol_nombre').readOnly = false;
        document.getElementById('rol_ver_todo').disabled = false;
    }
    
    modal.style.display = 'flex';
}

function cerrarModalRol() {
    document.getElementById('modalRol').style.display = 'none';
}

document.getElementById('formRol').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/sigmu/administracion_usuarios/rol/guardar', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error al procesar: ' + err.message));
});

function eliminarRol(id) {
    if (!confirm('¿Estás seguro de eliminar este rol? Esta acción no se puede deshacer.')) return;
    
    // Obtener token CSRF
    const csrfToken = document.querySelector('#formRol [name="_csrf_token"]')?.value;
    
    const formData = new FormData();
    formData.append('id', id);
    if (csrfToken) {
        formData.append('_csrf_token', csrfToken);
    }
    
    fetch('/sigmu/administracion_usuarios/rol/eliminar', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error al procesar: ' + err.message));
}
</script>

<?php require __DIR__ . '/../partials/sigmu_shell_end.php'; ?>
