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

function abrirModalCompletar(id, codigo) {
    document.getElementById('mantenimiento_id_completar').value = id;
    document.getElementById('modalActivoCodigo').textContent = codigo;
    document.getElementById('modalCompletar').style.display = 'flex';
}

function cerrarModalCompletar() {
    document.getElementById('modalCompletar').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    const formCompletar = document.getElementById('formCompletar');
    if (formCompletar) {
        formCompletar.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('/sigmu/mantenimiento/completar', {
                method: 'POST',
                body: new FormData(this)
            }).then(r => r.json()).then(data => {
                if (data.success) { 
                    alert('Guardado con éxito'); 
                    location.reload(); 
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
    }
});
