/**
 * Menu lateral GLOBAL para todas las vistas SIGMU
 * Este archivo se incluye en todas las paginas
 */

/**
 * Menu lateral global - disponible en TODAS las vistas
 * Funciona directamente sin esperar DOM
 */
window.openSidebarMenu = function() {
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
                <div class="sidebar-user-section">
                    <div class="user-avatar-menu" id="sidebarUserAvatar">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="sidebar-username" id="sidebarUserName">Usuario</div>
                </div>
                <div class="sidebar-menu-header">
                    <h3>Menú</h3>
                    <button class="sidebar-close">×</button>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="/sigmu" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Inicio
                </a>
                <a href="/sigmu/perfil" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Mi información
                </a>
                <a href="/sigmu/edificios" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"></path>
                    </svg>
                    Edificios
                </a>
                <a href="/sigmu/historial" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Historial
                </a>
                <a href="/sigmu/reporte" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    Reporte
                </a>
                <a href="/sigmu/mantenimiento" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                    </svg>
                    Mantenimiento
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
                background: #455A64;
                border-bottom: none;
                padding: 0;
            }
            .sidebar-user-section {
                padding: 32px 24px;
                text-align: center;
            }
            .user-avatar-menu {
                width: 100px;
                height: 100px;
                border-radius: 50%;
                background: rgba(255,255,255,0.15);
                margin: 0 auto 12px auto;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                overflow: hidden;
            }
            .user-avatar-menu img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: 50%;
            }
            .sidebar-username {
                color: white;
                font-size: 18px;
                font-weight: 600;
            }
            .sidebar-menu-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 16px 24px;
                background: #9C1C1C;
            }
            .sidebar-menu-header h3 { margin: 0; font-size: 18px; color: white; }
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
        
        // ✅ Cargar automaticamente los datos del usuario
        const userNameEl = document.getElementById('sidebarUserName');
        const userAvatarEl = document.getElementById('sidebarUserAvatar');
        
        if (window.authUser) {
            if (userNameEl && window.authUser.nombre_completo) {
                userNameEl.textContent = window.authUser.nombre_completo;
            }
            
            if (userAvatarEl && window.authUser.foto) {
                userAvatarEl.innerHTML = `<img src="${window.authUser.foto}" alt="">`;
            }
        }
        
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
}

// ✅ Inicializar inmediatamente
setTimeout(() => {
    const menuBtn = document.getElementById('menuBtn');
    if (menuBtn) {
        menuBtn.onclick = openSidebarMenu;
    }
}, 0);

/**
 * Alterna la visibilidad del formulario de carga de fotos de edificio
 * @param {number} id 
 */
function toggleUploadForm(id) {
    const form = document.getElementById('form-upload-' + id);
    if (!form) return;
    
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}
