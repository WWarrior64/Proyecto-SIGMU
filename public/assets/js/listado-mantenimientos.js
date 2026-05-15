/**
 * JS para Listado de Mantenimientos
 */

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalCompletar');
    const formCompletar = document.getElementById('formCompletar');
    
    window.abrirModalCompletar = function(id, codigo) {
        document.getElementById('mantenimiento_id_completar').value = id;
        document.getElementById('modalActivoCodigo').textContent = codigo;
        if (modal) modal.style.display = 'flex';
    };

    window.cerrarModalCompletar = function() {
        if (modal) modal.style.display = 'none';
    };

    if (formCompletar) {
        formCompletar.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('/sigmu/mantenimiento/completar', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Mantenimiento finalizado con éxito');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error al procesar la solicitud');
            });
        });
    }
});
