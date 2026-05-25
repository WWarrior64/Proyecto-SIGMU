/**
 * JavaScript para modal de confirmación de eliminación
 * SIGMU - Sistema de Gestión de Activos
 */

document.addEventListener('DOMContentLoaded', function() {
    createDeleteModal();
    setupDeleteForms();
    
    // Re-inicializar cuando se cargue contenido via AJAX
    document.addEventListener('ajax-content-loaded', function() {
        // No es necesario re-crear el modal (ya existe),
        // solo necesitamos que los nuevos formularios sean captados
        // por el event delegation en setupDeleteForms()
    });
});

function createDeleteModal() {
    const modalHTML = `
        <div class="delete-overlay" id="deleteOverlay">
            <div class="delete-modal">
                <div class="delete-modal-header">
                    Confirmar Eliminación
                </div>
                <div class="delete-modal-body">
                    <p>¿Estás seguro de que deseas eliminar este activo? Esta acción no se puede deshacer.</p>
                    <div id="passwordContainer" style="margin: 15px 0;"></div>
                    <div class="delete-modal-actions">
                        <button class="btn-delete" id="confirmDelete">Eliminar</button>
                        <button class="btn-cancel" id="cancelDelete">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    document.getElementById('cancelDelete').addEventListener('click', closeDeleteModal);
    document.getElementById('deleteOverlay').addEventListener('click', (e) => {
        if (e.target.id === 'deleteOverlay') closeDeleteModal();
    });
}

function setupDeleteForms() {
    // Event delegation: capturar submits de formularios de eliminación
    // incluso si son cargados dinámicamente via AJAX
    document.addEventListener('submit', function(e) {
        const form = e.target.closest('form[action*="eliminar"]');
        if (form) {
            e.preventDefault();
            showDeleteModal(form);
        }
    });
}

function showDeleteModal(form) {
    const overlay = document.getElementById('deleteOverlay');
    const container = document.getElementById('passwordContainer');
    const confirmBtn = document.getElementById('confirmDelete');
    
    container.innerHTML = `
        <label style="display:block; margin-bottom: 5px;">Ingrese su contraseña para autorizar:</label>
        <input type="password" id="password_confirm" autocomplete="new-password" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    `;
    
    window.currentDeleteForm = form;
    overlay.classList.add('active');
    document.getElementById('password_confirm').focus();
    
    confirmBtn.onclick = function() {
        const password = document.getElementById('password_confirm').value;
        if (!password) {
            alert('Debe ingresar su contraseña.');
            return;
        }
        
        let passHidden = form.querySelector('input[name="password"]');
        if (!passHidden) {
            passHidden = document.createElement('input');
            passHidden.type = 'hidden';
            passHidden.name = 'password';
            form.appendChild(passHidden);
        }
        passHidden.value = password;
        
        closeDeleteModal();
        form.submit();
    };
}

function closeDeleteModal() {
    document.getElementById('deleteOverlay').classList.remove('active');
    document.getElementById('passwordContainer').innerHTML = '';
    window.currentDeleteForm = null;
}
