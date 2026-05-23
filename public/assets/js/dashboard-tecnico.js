/**
 * JavaScript para el Panel Técnico
 */
function abrirModalCompletarDesdeBoton(btn) {
    if (!btn) return;
    const id = parseInt(btn.dataset.mantenimientoId || '0', 10);
    const codigo = btn.dataset.activoCodigo || '';
    if (!id) return;
    abrirModalCompletar(id, codigo);
}

function getLocalDate() {
    const ahora = new Date();
    const anio = ahora.getFullYear();
    const mes = String(ahora.getMonth() + 1).padStart(2, '0');
    const dia = String(ahora.getDate()).padStart(2, '0');
    return `${anio}-${mes}-${dia}`;
}

function abrirModalCompletar(id, codigo) {
    document.getElementById('mantenimiento_id_completar').value = id;
    document.getElementById('modalActivoCodigo').textContent = codigo;
    // Resetear la fecha al día actual (hora local)
    const fechaInput = document.getElementById('fecha_real');
    fechaInput.value = obtenerFechaLocal();
    document.getElementById('modalCompletar').style.display = 'flex';
}

function cerrarModalCompletar() {
    document.getElementById('modalCompletar').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    const formCompletar = document.getElementById('formCompletar');
    const fechaInput = document.getElementById('fecha_real');
    const hoy = obtenerFechaLocal();
    
    // Bloquear fechas anteriores a hoy al cambiar el input
    if (fechaInput) {
        fechaInput.addEventListener('change', function() {
            if (this.value < hoy) {
                this.value = hoy;
                showToast('No puedes seleccionar una fecha anterior a hoy', 'error');
            }
        });
    }
    
    if (formCompletar) {
        formCompletar.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar que la fecha no sea anterior a hoy (hora local)
            const fechaReal = document.getElementById('fecha_real').value;
            if (fechaReal < hoy) {
                showToast('La fecha de intervención no puede ser anterior a hoy', 'error');
                return;
            }
            
            fetch('/sigmu/mantenimiento/completar', {
                method: 'POST',
                body: new FormData(this)
            }).then(r => r.json()).then(data => {
                if (data.success) { 
                    showToast('Guardado con éxito'); 
                    location.reload(); 
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            });
        });
    }
});
