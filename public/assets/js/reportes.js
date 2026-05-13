/**
 * Reportes JS - SIGMU
 */
document.addEventListener('DOMContentLoaded', function() {
    // Mejorar la interacción de los list-group-items (que funcionen como el checkbox)
    const listItems = document.querySelectorAll('.list-group-item');
    listItems.forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.tagName !== 'INPUT') {
                const checkbox = this.querySelector('input[type="checkbox"]');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                }
            }
        });
    });

    // Validación básica antes de enviar
    const reportForm = document.querySelector('form[action*="/exportar"]');
    if (reportForm) {
        reportForm.addEventListener('submit', function(e) {
            const checkboxes = this.querySelectorAll('input[name*="sec_"], input[name*="datos_"], input[name="imagenes"], input[name="historial"], input[name="mantenimientos"]');
            let checkedOne = false;
            checkboxes.forEach(cb => {
                if (cb.checked) checkedOne = true;
            });

            if (!checkedOne) {
                e.preventDefault();
                alert('Debes seleccionar al menos una sección para el reporte.');
            }
        });
    }

    // Efecto de carga en el botón al generar
    const submitBtn = document.querySelector('button[type="submit"]');
    if (submitBtn && reportForm) {
        reportForm.addEventListener('submit', function() {
            const originalHtml = submitBtn.innerHTML;
            setTimeout(() => {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando PDF...';
            }, 50);

            // Re-habilitar después de unos segundos (ya que la descarga no recarga la página)
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
            }, 5000);
        });
    }
});
