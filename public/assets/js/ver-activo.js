/**
 * JavaScript for Asset Detail View - SIGMU
 * Fusionado con galería interactiva y zoom de imágenes
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initImageInteraction();
    initAnimations();
    initModalDarBaja();
    
    // Inicializar borde de la primera miniatura si existe
    const firstThumb = document.querySelector('.gallery-thumbnails img');
    if (firstThumb) firstThumb.style.borderColor = '#007bff';
});

/**
 * Modal confirmación Dar de Baja Activo
 */
function initModalDarBaja() {
    const btnDarBaja = document.getElementById('btnDarBaja');
    const modalConfirmacion = document.getElementById('modalConfirmacionBaja');
    const btnCerrarModal = document.getElementById('btnCerrarModal');
    const btnCancelarBaja = document.getElementById('btnCancelarBaja');
    
    if (btnDarBaja && modalConfirmacion) {
        btnDarBaja.addEventListener('click', function() {
            modalConfirmacion.style.display = 'flex';
        });
    }
    
    const cerrarModal = function() {
        if (modalConfirmacion) modalConfirmacion.style.display = 'none';
    };
    
    if (btnCerrarModal) btnCerrarModal.addEventListener('click', cerrarModal);
    if (btnCancelarBaja) btnCancelarBaja.addEventListener('click', cerrarModal);
    
    if (modalConfirmacion) {
        modalConfirmacion.addEventListener('click', function(e) {
            if (e.target === modalConfirmacion) cerrarModal();
        });
    }
}

/**
 * Image interaction functionality (Zoom y Cambio de Miniatura)
 */
function initImageInteraction() {
    const imageContainer = document.querySelector('.image-container');
    const assetImage = document.querySelector('.asset-image');
    
    if (!imageContainer || !assetImage) return;
    
    // Zoom al hacer clic
    imageContainer.addEventListener('click', function() {
        openImageModal(assetImage.src);
    });
    
    imageContainer.style.cursor = 'pointer';
}

/**
 * Cambia la imagen principal por la miniatura seleccionada
 * @param {HTMLImageElement} thumbnail 
 */
function changeMainImage(thumbnail) {
    const mainImg = document.getElementById('mainImage');
    if (!mainImg) return;

    mainImg.src = thumbnail.src;

    // Actualizar bordes de miniaturas
    const gallery = thumbnail.parentElement;
    if (gallery) {
        gallery.querySelectorAll('img').forEach(img => {
            img.style.borderColor = 'transparent';
        });
    }
    thumbnail.style.borderColor = '#007bff';
}

/**
 * Open image in modal/lightbox
 */
function openImageModal(imageSrc) {
    const modal = document.createElement('div');
    modal.className = 'image-modal';
    modal.innerHTML = `
        <div class="modal-overlay-zoom">
            <div class="modal-content-zoom">
                <button class="modal-close-zoom">×</button>
                <img src="${imageSrc}" alt="Zoom" class="modal-image-zoom">
            </div>
        </div>
    `;
    
    const style = document.createElement('style');
    style.id = 'zoom-modal-styles';
    style.textContent = `
        .image-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 2000; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-overlay-zoom { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); display: flex; align-items: center; justify-content: center; padding: 20px; }
        .modal-content-zoom { position: relative; max-width: 95%; max-height: 95%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5); }
        .modal-close-zoom { position: absolute; top: 15px; right: 15px; background: rgba(0, 0, 0, 0.6); border: none; color: white; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 24px; z-index: 10; display: flex; align-items: center; justify-content: center; }
        .modal-image-zoom { width: 100%; height: auto; max-height: 85vh; object-fit: contain; display: block; }
    `;
    document.head.appendChild(style);
    document.body.appendChild(modal);
    
    const close = () => {
        modal.remove();
        document.getElementById('zoom-modal-styles')?.remove();
    };

    modal.querySelector('.modal-close-zoom').onclick = close;
    modal.querySelector('.modal-overlay-zoom').onclick = (e) => { if(e.target.classList.contains('modal-overlay-zoom')) close(); };
    
    document.addEventListener('keydown', function closeOnEscape(e) {
        if (e.key === 'Escape') {
            close();
            document.removeEventListener('keydown', closeOnEscape);
        }
    });
}

/**
 * Initialize animations
 */
function initAnimations() {
    const detailGroups = document.querySelectorAll('.detail-group');
    detailGroups.forEach((group, index) => {
        group.style.opacity = '0';
        group.style.transform = 'translateY(20px)';
        setTimeout(() => {
            group.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            group.style.opacity = '1';
            group.style.transform = 'translateY(0)';
        }, index * 80);
    });
    
    const metadataCards = document.querySelectorAll('.metadata-card');
    metadataCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateX(-20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateX(0)';
        }, 400 + (index * 80));
    });
}

/**
 * Utility function to show notifications
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    const style = document.createElement('style');
    style.textContent = `
        .notification { position: fixed; top: 80px; right: 24px; padding: 16px 24px; border-radius: 12px; color: white; font-weight: 500; z-index: 1001; animation: slideInRight 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .notification-success { background: #28a745; }
        .notification-error { background: #dc3545; }
        .notification-info { background: #17a2b8; }
    `;
    document.head.appendChild(style);
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(20px)';
        notification.style.transition = 'all 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3500);
}

window.SIGMU = { showNotification, openImageModal };
