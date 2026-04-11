/**
 * JavaScript for Asset Detail View - SIGMU
 * Provides interactive functionality for the asset detail interface
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initMenu();
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
 * Menu functionality
 */
function initMenu() {
    const menuBtn = document.getElementById('menuBtn');
    
    if (!menuBtn) return;
    
    menuBtn.addEventListener('click', function() {
        // Toggle sidebar or menu
        let sidebar = document.querySelector('.sidebar');
        
        if (sidebar) {
            sidebar.remove();
            return;
        }
        
        sidebar = document.createElement('div');
        sidebar.className = 'sidebar';
        sidebar.innerHTML = `
            <div class="sidebar-header">
                <h3>Menú</h3>
                <button class="sidebar-close">×</button>
            </div>
            <nav class="sidebar-nav">
                <a href="/sigmu" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Inicio
                </a>
                <a href="/sigmu/edificio" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"></path>
                    </svg>
                    Edificios
                </a>
                <a href="/activos" class="nav-item active">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    Activos
                </a>
            </nav>
        `;
        
        // Add sidebar styles
        const style = document.createElement('style');
        style.textContent = `
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 280px;
                height: 100vh;
                background: white;
                box-shadow: 4px 0 16px rgba(0,0,0,0.1);
                z-index: 1000;
                animation: slideIn 0.3s ease;
            }
            @keyframes slideIn {
                from { transform: translateX(-100%); }
                to { transform: translateX(0); }
            }
            .sidebar-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px 24px;
                border-bottom: 1px solid #E0E0E0;
                background: #9C1C1C;
                color: white;
            }
            .sidebar-header h3 { margin: 0; font-size: 18px; }
            .sidebar-close {
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                padding: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                transition: background-color 0.2s;
            }
            .sidebar-close:hover { background: rgba(255,255,255,0.1); }
            .sidebar-nav { padding: 24px 0; }
            .nav-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 24px;
                color: #424242;
                text-decoration: none;
                font-weight: 500;
                transition: background-color 0.2s;
            }
            .nav-item:hover { background: #F5F5F5; }
            .nav-item.active { 
                background: #FFEBEE; 
                color: #9C1C1C;
                border-right: 3px solid #9C1C1C;
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(sidebar);
        
        // Close functionality
        const closeBtn = sidebar.querySelector('.sidebar-close');
        closeBtn.addEventListener('click', () => sidebar.remove());
        
        // Close on outside click
        setTimeout(() => {
            document.addEventListener('click', function closeSidebar(e) {
                if (!sidebar.contains(e.target) && e.target !== menuBtn) {
                    sidebar.remove();
                    document.removeEventListener('click', closeSidebar);
                }
            });
        }, 100);
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