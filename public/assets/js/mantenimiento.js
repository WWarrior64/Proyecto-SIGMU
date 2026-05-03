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
    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Enviar formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch('/sigmu/mantenimiento/agendar', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reparación agendada correctamente');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ocurrió un error al procesar la solicitud');
        });
    });
});
