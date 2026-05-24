/**
 * JS para Listado de Mantenimientos
 */

function getLocalDate() {
    const ahora = new Date();
    const anio = ahora.getFullYear();
    const mes = String(ahora.getMonth() + 1).padStart(2, '0');
    const dia = String(ahora.getDate()).padStart(2, '0');
    return `${anio}-${mes}-${dia}`;
}

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalCompletar');
    const formCompletar = document.getElementById('formCompletar');
    const fechaInput = document.getElementById('fecha_real');
    const hoy = getLocalDate();
    
    window.abrirModalCompletar = function(id, codigo) {
        document.getElementById('mantenimiento_id_completar').value = id;
        document.getElementById('modalActivoCodigo').textContent = codigo;
        // Resetear la fecha al día actual (hora local)
        if (fechaInput) fechaInput.value = hoy;
        if (modal) modal.style.display = 'flex';
    };

    window.cerrarModalCompletar = function() {
        if (modal) modal.style.display = 'none';
    };
    
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
            
            const formData = new FormData(this);
            
            fetch('/sigmu/mantenimiento/completar', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('success', 'Mantenimiento finalizado con éxito');
                    window.location.href = url.toString();
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error al procesar la solicitud', 'error');
            });
        });
    }
});
