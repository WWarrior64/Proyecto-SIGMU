/**
 * JavaScript for Asset Detail View - SIGMU
 * Provides interactive functionality for the asset detail interface
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initImageInteraction();
    initAnimations();
    initModalDarBaja();
});

/**
 * Modal confirmación Dar de Baja Activo
 */
function initModalDarBaja() {
    const btnDarBaja = document.getElementById('btnDarBaja');
    const modalConfirmacion = document.getElementById('modalConfirmacionBaja');
    const btnCerrarModal = document.getElementById('btnCerrarModal');
    const btnCancelarBaja = document.getElementById('btnCancelarBaja');
    
    if (btnDarBaja) {
        btnDarBaja.addEventListener('click', function() {
            modalConfirmacion.style.display = 'flex';
        });
    }
    
    const cerrarModal = function() {
        modalConfirmacion.style.display = 'none';
    };
    
    if (btnCerrarModal) btnCerrarModal.addEventListener('click', cerrarModal);
    if (btnCancelarBaja) btnCancelarBaja.addEventListener('click', cerrarModal);
    
    // Cerrar al hacer click fuera del modal
    modalConfirmacion.addEventListener('click', function(e) {
        if (e.target === modalConfirmacion) {
            cerrarModal();
        }
    });
}


/**
 * Image interaction functionality
 */
function initImageInteraction() {
    const imageContainer = document.querySelector('.image-container');
    const assetImage = document.querySelector('.asset-image');
    
    if (!imageContainer || !assetImage) return;
    
    // Add click to zoom functionality
    imageContainer.addEventListener('click', function() {
        openImageModal(assetImage.src);
    });
    
    // Add cursor pointer style
    imageContainer.style.cursor = 'pointer';
}

/**
 * Open image in modal/lightbox
 */
function openImageModal(imageSrc) {
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'image-modal';
    modal.innerHTML = `
        <div class="modal-overlay">
            <div class="modal-content">
                <button class="modal-close">×</button>
                <img src="${imageSrc}" alt="Imagen del activo" class="modal-image">
            </div>
        </div>
    `;
    
    // Add modal styles
    const style = document.createElement('style');
    style.textContent = `
        .image-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2000;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.5);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: background-color 0.2s;
        }
        .modal-close:hover {
            background: rgba(0, 0, 0, 0.7);
        }
        .modal-image {
            width: 100%;
            height: auto;
            max-height: 80vh;
            object-fit: contain;
            display: block;
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(modal);
    
    // Close functionality
    const closeBtn = modal.querySelector('.modal-close');
    const overlay = modal.querySelector('.modal-overlay');
    
    closeBtn.addEventListener('click', () => modal.remove());
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            modal.remove();
        }
    });
    
    // Close on escape key
    document.addEventListener('keydown', function closeOnEscape(e) {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', closeOnEscape);
        }
    });
}

/**
 * Initialize animations
 */
function initAnimations() {
    // Animate detail groups on load
    const detailGroups = document.querySelectorAll('.detail-group');
    detailGroups.forEach((group, index) => {
        group.style.opacity = '0';
        group.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            group.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            group.style.opacity = '1';
            group.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Animate metadata cards on load
    const metadataCards = document.querySelectorAll('.metadata-card');
    metadataCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateX(0)';
        }, 300 + (index * 100));
    });
    
    // Add hover effects to edit button
    const editBtn = document.querySelector('.edit-btn');
    if (editBtn) {
        editBtn.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });
        
        editBtn.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    }
    
    // Add hover effects to back button
    const backBtn = document.querySelector('.back-btn');
    if (backBtn) {
        backBtn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(-3px)';
        });
        
        backBtn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    }
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
        .notification {
            position: fixed;
            top: 80px;
            right: 24px;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            z-index: 1001;
            animation: slideInRight 0.3s ease;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .notification-success { background: #28a745; }
        .notification-error { background: #dc3545; }
        .notification-info { background: #17a2b8; }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Export for global use
window.SIGMU = {
    showNotification,
    openImageModal
};