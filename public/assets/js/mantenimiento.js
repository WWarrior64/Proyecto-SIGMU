document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalProgramar');
    const closeBtn = document.getElementById('closeModal');
    const form = document.getElementById('formProgramar');
    const programButtons = document.querySelectorAll('.program-btn');

    // Abrir modal
    programButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const code = this.getAttribute('data-code');
            
            document.getElementById('mantenimiento_id').value = id;
            document.getElementById('modalTitle').textContent = 'AGENDAR REPARACIÓN - ' + code;
            
            modal.style.display = 'flex';
        });
    });

    // Cerrar modal
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    }

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Enviar formulario
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const fechaInput = document.getElementById('fecha');
            const fechaSeleccionada = new Date(fechaInput.value + 'T00:00:00');
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);

            if (fechaSeleccionada < hoy) {
                showToast('No se puede agendar en una fecha pasada', 'error');
                return;
            }

            const formData = new FormData(form);
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            fetch('/sigmu/mantenimiento/agendar', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('success', 'Reparación agendada correctamente');
                    window.location.href = url.toString();
                } else {
                    showToast('Error: ' + data.message, 'error');
                    if (submitBtn) submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Ocurrió un error al procesar la solicitud', 'error');
                if (submitBtn) submitBtn.disabled = false;
            });
        });
    }
});
