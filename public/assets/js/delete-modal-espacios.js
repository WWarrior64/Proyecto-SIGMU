/**
 * Lógica para modales de eliminación en Edificios y Salas
 */

function abrirModalEliminar(id, tipo, edificioId = null) {
    const overlay = document.getElementById('deleteOverlayEspacios');
    const form = document.getElementById('formEliminarEspacio');
    const title = document.getElementById('deleteModalTitle');
    
    document.getElementById('eliminar_id').value = id;
    document.getElementById('eliminar_edificio_id').value = edificioId || '';
    
    if (tipo === 'edificio') {
        form.action = '/sigmu/edificio/eliminar';
        title.innerText = 'ELIMINAR EDIFICIO';
    } else {
        form.action = '/sigmu/sala/eliminar';
        title.innerText = 'ELIMINAR SALA';
    }
    
    overlay.classList.add('active');
}

function closeDeleteModalEspacios() {
    document.getElementById('deleteOverlayEspacios').classList.remove('active');
}

// Cerrar al hacer clic fuera
document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('deleteOverlayEspacios');
    if(overlay) {
        overlay.addEventListener('click', (e) => {
            if (e.target.id === 'deleteOverlayEspacios') closeDeleteModalEspacios();
        });
    }
});
