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
                <a href="/sigmu/activo" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    Activos
                </a>
                <a href="/sigmu/historial" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Historial
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
}

// ✅ Inicializar inmediatamente
setTimeout(() => {
    const menuBtn = document.getElementById('menuBtn');
    if (menuBtn) {
        menuBtn.onclick = openSidebarMenu;
    }
}, 0);
