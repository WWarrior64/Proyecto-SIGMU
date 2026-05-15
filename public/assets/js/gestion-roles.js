/**
 * JavaScript para Gestión de Roles
 */

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
    const modal = document.getElementById('modalRol');
    if (modal) {
        modal.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const formRol = document.getElementById('formRol');
    if (formRol) {
        formRol.addEventListener('submit', function(e) {
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
    }
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
