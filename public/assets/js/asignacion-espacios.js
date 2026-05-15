/**
 * Lógica para Administración de Espacios por Usuario
 */

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-input');
    const tableRows = document.querySelectorAll('.asignacion-table tbody tr');
    const modal = document.getElementById('asignar-modal');
    const closeBtn = document.querySelector('.btn-close');
    const cancelBtn = document.querySelector('.btn-secondary');
    const asignarForm = document.getElementById('asignar-form');
    
    // Filtrado de tabla
    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        
        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });

    // Abrir modal de asignación
    window.abrirModalAsignar = async (usuarioId, usuarioNombre) => {
        document.getElementById('modal-usuario-id').value = usuarioId;
        document.getElementById('modal-usuario-nombre').textContent = usuarioNombre;
        
        const selectEdificio = document.getElementById('edificio_id');
        selectEdificio.innerHTML = '<option value="">Cargando edificios...</option>';
        
        try {
            const response = await fetch('/sigmu/administracion_usuarios/edificios_disponibles');
            const edificios = await response.json();
            
            selectEdificio.innerHTML = '<option value="">-- Seleccione un edificio --</option>';
            
            if (edificios.length === 0) {
                selectEdificio.innerHTML = '<option value="">No hay edificios libres disponibles</option>';
            } else {
                edificios.forEach(e => {
                    const option = document.createElement('option');
                    option.value = e.id;
                    option.textContent = e.nombre;
                    selectEdificio.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error al cargar edificios:', error);
            selectEdificio.innerHTML = '<option value="">Error al cargar listado</option>';
        }

        modal.style.display = 'flex';
    };

    // Cerrar modal
    const cerrarModal = () => {
        modal.style.display = 'none';
        asignarForm.reset();
    };

    closeBtn.addEventListener('click', cerrarModal);
    cancelBtn.addEventListener('click', cerrarModal);
    window.addEventListener('click', (e) => {
        if (e.target === modal) cerrarModal();
    });

    // Enviar asignación (AJAX)
    asignarForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(asignarForm);
        
        try {
            const response = await fetch('/sigmu/administracion_usuarios/asignar_espacio', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                location.reload(); // Recargar para mostrar nueva asignación
            } else {
                alert(result.message || 'Error al asignar espacio');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión al servidor');
        }
    });

    // Quitar asignación (AJAX)
    window.quitarAsignacion = async (usuarioId, edificioId, edificioNombre) => {
        if (!confirm(`¿Estás seguro de quitar el acceso a "${edificioNombre}" para este usuario?`)) {
            return;
        }

        // Obtener el token CSRF del formulario existente en la página
        const csrfToken = document.querySelector('#asignar-form [name="_csrf_token"]')?.value;

        const formData = new FormData();
        formData.append('usuario_id', usuarioId);
        formData.append('edificio_id', edificioId);
        if (csrfToken) {
            formData.append('_csrf_token', csrfToken);
        }

        try {
            const response = await fetch('/sigmu/administracion_usuarios/quitar_espacio', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                location.reload();
            } else {
                alert(result.message || 'Error al quitar asignación');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión al servidor');
        }
    };
});
