/**
 * Sistema de Notificaciones Temporales
 */
function showToast(message, type = 'success') {
    const container = document.querySelector('.sigmu-toast-container') || createContainer();
    const toast = document.createElement('div');
    toast.className = `sigmu-toast sigmu-toast--${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    
    // Eliminar del DOM después de la animación
    setTimeout(() => toast.remove(), 4500);
}

function createContainer() {
    const container = document.createElement('div');
    container.className = 'sigmu-toast-container';
    document.body.appendChild(container);
    return container;
}

// Escuchar parámetros en la URL al cargar
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const success = params.get('success');
    const error = params.get('error');
    const info = params.get('info');
    
    if (success) {
        showToast(success, 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    if (error) {
        showToast(error, 'error');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    if (info) {
        showToast(info, 'info');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
