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

    // 3. Lógica para CARGA DINÁMICA DE SALAS
    const edificioSelectors = document.querySelectorAll('.edificio-selector');
    const groupSalas = document.getElementById('groupSalas');
    const salasContainer = document.getElementById('salasContainer');

    function updateSalas() {
        const selectedEdificios = Array.from(edificioSelectors)
            .filter(i => i.checked)
            .map(i => i.value);

        if (selectedEdificios.length === 0) {
            groupSalas.style.display = 'none';
            salasContainer.innerHTML = '';
            return;
        }

        // Mostrar grupo y cargar via API
        groupSalas.style.display = 'block';
        salasContainer.innerHTML = '<div class="p-3 text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando salas...</div>';

        const csrfToken = document.querySelector('input[name="_csrf_token"]')?.value;

        const params = new URLSearchParams();
        params.append('_csrf_token', csrfToken || '');
        params.append('edificios', JSON.stringify(selectedEdificios));

        // Petición al endpoint de búsqueda (reutilizamos la lógica de SigmuController)
        fetch('/sigmu/buscar-salas', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        })
        .then(response => response.json())
        .then(salas => {
            if (salas.length === 0) {
                salasContainer.innerHTML = '<div class="p-3 text-center text-muted">No hay salas en los edificios seleccionados.</div>';
                return;
            }

            salasContainer.innerHTML = '';
            salas.forEach(s => {
                const label = document.createElement('label');
                label.className = 'checkbox-item';
                label.innerHTML = `
                    <input type="checkbox" name="salas[]" value="${s.id}">
                    <span>${s.nombre} <small class="text-muted">(${s.edificio_nombre})</small></span>
                `;
                salasContainer.appendChild(label);
            });
        })
        .catch(err => {
            console.error('Error cargando salas:', err);
            salasContainer.innerHTML = '<div class="p-3 text-center text-danger">Error al cargar salas.</div>';
        });
    }

    edificioSelectors.forEach(s => s.addEventListener('change', updateSalas));

    // 4. Lógica robusta para VISTA PREVIA
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
