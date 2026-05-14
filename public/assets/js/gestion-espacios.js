/**
 * Gestión de Espacios - Lógica de Modales y UI
 */

document.addEventListener('DOMContentLoaded', () => {
    // Escuchar clics en botones para cerrar modales
    document.querySelectorAll('.modal-close, .btn-cancel').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal-overlay');
            if (modal) modal.style.display = 'none';
        });
    });

    // Cerrar modal al hacer clic fuera
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-overlay')) {
            e.target.style.display = 'none';
        }
    });
});

/**
 * Abre el modal de Edificio
 * @param {Object|null} data Datos del edificio para editar
 */
function abrirModalEdificio(data = null) {
    const modal = document.getElementById('modalEdificio');
    if (!modal) return;

    const title = modal.querySelector('.modal-title');
    const form = modal.querySelector('form');
    
    // Limpiar formulario
    form.reset();
    document.getElementById('edificio_id').value = '';

    if (data) {
        title.innerText = 'EDITAR EDIFICIO';
        document.getElementById('edificio_id').value = data.id;
        document.getElementById('edificio_nombre').value = data.nombre;
        document.getElementById('edificio_descripcion').value = data.descripcion || '';
        document.getElementById('edificio_pisos').value = data.cantidad_pisos;

        // Poblar responsable si existe el select (solo admin)
        const selectResp = document.getElementById('edificio_responsable_id');
        if (selectResp && data.responsable_id) {
            selectResp.value = data.responsable_id;
        } else if (selectResp) {
            selectResp.value = '0';
        }
    } else {
        title.innerText = 'NUEVO EDIFICIO';
        const selectResp = document.getElementById('edificio_responsable_id');
        if (selectResp) selectResp.value = '0';
    }

    modal.style.display = 'flex';
}

/**
 * Abre el modal de Sala
 * @param {Object|null} data Datos de la sala para editar
 */
function abrirModalSala(data = null) {
    const modal = document.getElementById('modalSala');
    if (!modal) return;

    const title = modal.querySelector('.modal-title');
    const form = modal.querySelector('form');
    const selectPiso = document.getElementById('sala_piso');
    const maxPisos = parseInt(modal.getAttribute('data-max-pisos')) || 1;
    
    // Generar opciones de piso dinámicamente
    selectPiso.innerHTML = '';
    for (let i = 1; i <= maxPisos; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = `Piso ${i}`;
        selectPiso.appendChild(option);
    }
    
    // Limpiar formulario (sin limpiar el edificio_id oculto)
    const edificioId = document.getElementById('sala_edificio_id').value;
    form.reset();
    document.getElementById('sala_edificio_id').value = edificioId;
    document.getElementById('sala_id').value = '';

    if (data) {
        title.innerText = 'EDITAR SALA';
        document.getElementById('sala_id').value = data.id;
        document.getElementById('sala_nombre').value = data.nombre;
        document.getElementById('sala_descripcion').value = data.descripcion || '';
        document.getElementById('sala_piso').value = data.numero_piso;
    } else {
        title.innerText = 'NUEVA SALA';
        document.getElementById('sala_piso').value = 1;
    }

    modal.style.display = 'flex';
}

/**
 * Abre el modal para actualizar solo la foto de un edificio
 */
function abrirModalFoto(edificioId) {
    const modal = document.getElementById('modalFoto');
    if (!modal) return;

    document.getElementById('foto_edificio_id').value = edificioId;
    modal.style.display = 'flex';
}

/**
 * Abre el modal de confirmación de eliminación segura (pide contraseña)
 * @param {number} id ID de la entidad a eliminar
 * @param {string} tipo 'edificio' o 'sala'
 * @param {number|null} edificioId Requerido para salas
 */
function abrirModalEliminar(id, tipo, edificioId = null) {
    const modal = document.getElementById('modalEliminar');
    if (!modal) return;

    const form = modal.querySelector('form');
    const inputId = document.getElementById('eliminar_id');
    const inputEdificioId = document.getElementById('eliminar_edificio_id');
    const title = modal.querySelector('.modal-title');
    
    inputId.value = id;
    if (tipo === 'edificio') {
        form.action = '/sigmu/edificio/eliminar';
        title.innerText = 'ELIMINAR EDIFICIO';
        inputEdificioId.value = '';
    } else {
        form.action = '/sigmu/sala/eliminar';
        title.innerText = 'ELIMINAR SALA';
        inputEdificioId.value = edificioId;
    }

    modal.style.display = 'flex';
}
