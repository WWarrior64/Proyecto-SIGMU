/**
 * JS para Reporte de Falla
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formReporteFalla');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const activoId = formData.get('activo_id');
        
        // Deshabilitar botón para evitar doble clic
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        fetch('/sigmu/reporte-falla/guardar', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Reporte registrado correctamente. El activo ha cambiado a estado de reparación.');
                window.location.href = '/sigmu/activo/ver?id=' + activoId;
            } else {
                showToast('Error: ' + data.message, 'error');
                if (submitBtn) submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Ocurrió un error al procesar el reporte.', 'error');
            if (submitBtn) submitBtn.disabled = false;
        });
    });
});
