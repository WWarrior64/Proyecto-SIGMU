/**
 * JavaScript para modal de confirmación de eliminación
 * SIGMU - Sistema de Gestión de Activos
 */

document.addEventListener('DOMContentLoaded', function() {
    // Crear el modal de confirmación
    createDeleteModal();
    
    // Configurar todos los formularios de eliminación
    setupDeleteForms();
});

/**
 * Crea el modal de confirmación de eliminación
 */
function createDeleteModal() {
    const modalHTML = `
        <div class="delete-overlay" id="deleteOverlay">
            <div class="delete-modal">
                <div class="delete-modal-header">
                    Confirmar Eliminación
                </div>
                <div class="delete-modal-body">
                    <p>¿Estás seguro de que deseas eliminar este activo? Esta acción no se puede deshacer.</p>
                    <div class="delete-modal-actions">
                        <button class="btn-delete" id="confirmDelete">Eliminar</button>
                        <button class="btn-cancel" id="cancelDelete">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Configure modal events
    const overlay = document.getElementById('deleteOverlay');
    const confirmBtn = document.getElementById('confirmDelete');
    const cancelBtn = document.getElementById('cancelDelete');
    
    // Cerrar modal al hacer clic en cancelar
    cancelBtn.addEventListener('click', closeDeleteModal);
    
    // Cerrar modal al hacer clic en el overlay
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closeDeleteModal();
        }
    });
    
    // Cerrar modal con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay.classList.contains('active')) {
            closeDeleteModal();
        }
    });
}

/**
 * Configura todos los formularios de eliminación
 */
function setupDeleteForms() {
    const deleteForms = document.querySelectorAll('form[action*="eliminar"]');
    
    deleteForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            showDeleteModal(form);
        });
    });
}

/**
 * Muestra el modal de confirmación
 * @param {HTMLFormElement} form - Formulario de eliminación
 */
function showDeleteModal(form) {
    const overlay = document.getElementById('deleteOverlay');
    const confirmBtn = document.getElementById('confirmDelete');
    
    // Guardar referencia al formulario
    window.currentDeleteForm = form;
    
    // Mostrar el modal
    overlay.classList.add('active');
    
    // Enfocar el botón de cancelar para accesibilidad
    document.getElementById('cancelDelete').focus();
    
    // Configurar confirmación
    confirmBtn.onclick = function() {
        closeDeleteModal();
        form.submit();
    };
}

/**
 * Cierra el modal de confirmación
 */
function closeDeleteModal() {
    const overlay = document.getElementById('deleteOverlay');
    overlay.classList.remove('active');
    
    // Limpiar referencia al formulario
    window.currentDeleteForm = null;
}

/**
 * Función global para mostrar el modal (puede ser llamada desde otros scripts)
 * @param {string} formId - ID del formulario de eliminación
 */
window.showDeleteConfirmation = function(formId) {
    const form = document.getElementById(formId);
    if (form) {
        showDeleteModal(form);
    }
};