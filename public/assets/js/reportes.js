/**
 * Reportes JS - SIGMU
 */
document.addEventListener('DOMContentLoaded', function() {
    const reportForm = document.getElementById('reportForm');
    const previewBtn = document.getElementById('btnPreview');
    const submitBtn = document.querySelector('button[type="submit"]');

    if (!reportForm) {
        console.error('No se encontró el formulario #reportForm');
        return;
    }

    // 1. Interacción de las tarjetas/secciones
    const listItems = document.querySelectorAll('.list-group-item');
    listItems.forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'LABEL') {
                const checkbox = this.querySelector('input[type="checkbox"]');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                }
            }
        });
    });

    // 2. Validación para la DESCARGA
    reportForm.addEventListener('submit', function(e) {
        // Si el target es una ventana de preview, no aplicamos el spinner ni validaciones de bloqueo
        if (this.target === 'SIGMU_PREVIEW') return;

        const checkboxes = this.querySelectorAll('input[type="checkbox"]:checked');
        if (checkboxes.length === 0) {
            e.preventDefault();
            alert('Debes seleccionar al menos una sección para generar el reporte.');
            return;
        }

        // Efecto visual en el botón de descarga
        if (submitBtn) {
            const originalHtml = submitBtn.innerHTML;
            setTimeout(() => {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando PDF...';
            }, 50);

            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
            }, 5000);
        }
    });

    // 3. Lógica robusta para VISTA PREVIA
    if (previewBtn) {
        previewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Botón de vista previa presionado');

            const checkboxes = reportForm.querySelectorAll('input[type="checkbox"]:checked');
            if (checkboxes.length === 0) {
                alert('Debes seleccionar al menos una sección para la vista previa.');
                return;
            }

            const originalAction = reportForm.action;
            const originalTarget = reportForm.target;

            // Paso 1: Abrir la ventana primero (clave para saltar bloqueadores de popups)
            const previewWindow = window.open('', 'SIGMU_PREVIEW');
            if (!previewWindow) {
                alert('El navegador bloqueó la ventana emergente. Por favor, permite los pop-ups para este sitio.');
                return;
            }

            // Paso 2: Configurar formulario para enviar a esa ventana
            reportForm.target = 'SIGMU_PREVIEW';
            reportForm.action = originalAction.replace('/exportar', '/preview');
            
            // Paso 3: Enviar
            console.log('Enviando a:', reportForm.action);
            reportForm.submit();

            // Paso 4: Restaurar formulario para futuras descargas
            setTimeout(() => {
                reportForm.target = originalTarget;
                reportForm.action = originalAction;
            }, 500);
        });
    }
});
