/**
 * JavaScript for SIGMU Asset Management Dashboard
 * Provides interactive functionality for the asset listing interface
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initSearch();
    initFilter();
    initMenu();
    initAnimations();
});

/**
 * Search functionality
 */
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('.table-body .table-row');
    
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        tableRows.forEach(row => {
            const cells = row.querySelectorAll('.table-cell');
            let found = false;
            
            cells.forEach(cell => {
                const text = cell.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    found = true;
                }
            });
            
            if (found || searchTerm === '') {
                row.style.display = '';
                row.style.opacity = '1';
            } else {
                row.style.display = 'none';
                row.style.opacity = '0';
            }
        });
        
        // Show empty state if no results
        updateEmptyState(searchTerm);
    });
    
    // Clear search on escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            this.dispatchEvent(new Event('input'));
        }
    });
}

/**
 * Update empty state based on search results
 */
function updateEmptyState(searchTerm) {
    const tableBody = document.querySelector('.table-body');
    const visibleRows = tableBody.querySelectorAll('.table-row[style=""], .table-row:not([style])');
    const existingEmpty = tableBody.querySelector('.search-empty-state');
    
    if (visibleRows.length === 0 && searchTerm !== '') {
        if (!existingEmpty) {
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state search-empty-state';
            emptyState.innerHTML = `
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <p>No se encontraron activos con "${searchTerm}"</p>
            `;
            tableBody.appendChild(emptyState);
        }
    } else if (existingEmpty) {
        existingEmpty.remove();
    }
}

/**
 * Filter functionality
 */
function initFilter() {
    const filterBtn = document.getElementById('filterBtn');
    
    if (!filterBtn) return;
    
    filterBtn.addEventListener('click', function() {
        // Create filter dropdown if it doesn't exist
        let dropdown = document.querySelector('.filter-dropdown');
        
        if (dropdown) {
            dropdown.remove();
            return;
        }
        
        dropdown = document.createElement('div');
        dropdown.className = 'filter-dropdown';
        dropdown.innerHTML = `
            <div class="filter-options">
                <label class="filter-option">
                    <input type="checkbox" value="disponible" checked>
                    <span class="status-badge status-disponible">Disponible</span>
                </label>
                <label class="filter-option">
                    <input type="checkbox" value="en_uso" checked>
                    <span class="status-badge status-en_uso">En uso</span>
                </label>
                <label class="filter-option">
                    <input type="checkbox" value="reparacion" checked>
                    <span class="status-badge status-reparacion">Reparación</span>
                </label>
                <label class="filter-option">
                    <input type="checkbox" value="descartado" checked>
                    <span class="status-badge status-descartado">Descartado</span>
                </label>
            </div>
            <div class="filter-actions">
                <button class="filter-apply">Aplicar</button>
                <button class="filter-clear">Limpiar</button>
            </div>
        `;
        
        // Position dropdown
        const rect = filterBtn.getBoundingClientRect();
        dropdown.style.cssText = `
            position: absolute;
            top: ${rect.bottom + 8}px;
            right: ${window.innerWidth - rect.right}px;
            background: white;
            border: 1px solid #E0E0E0;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            z-index: 1000;
            min-width: 200px;
        `;
        
        document.body.appendChild(dropdown);
        
        // Add styles for filter options
        const style = document.createElement('style');
        style.textContent = `
            .filter-options { margin-bottom: 12px; }
            .filter-option { 
                display: flex; 
                align-items: center; 
                gap: 8px; 
                padding: 8px 0;
                cursor: pointer;
            }
            .filter-option input { margin: 0; }
            .filter-actions { 
                display: flex; 
                gap: 8px; 
                justify-content: flex-end;
            }
            .filter-apply, .filter-clear {
                padding: 8px 16px;
                border: 1px solid #E0E0E0;
                border-radius: 8px;
                background: white;
                cursor: pointer;
                font-size: 12px;
                font-weight: 500;
            }
            .filter-apply {
                background: #9C1C1C;
                color: white;
                border-color: #9C1C1C;
            }
            .filter-apply:hover { background: #B71C1C; }
            .filter-clear:hover { background: #F5F5F5; }
        `;
        document.head.appendChild(style);
        
        // Filter functionality
        const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');
        const applyBtn = dropdown.querySelector('.filter-apply');
        const clearBtn = dropdown.querySelector('.filter-clear');
        
        applyBtn.addEventListener('click', function() {
            const selectedStatuses = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            filterByStatus(selectedStatuses);
            dropdown.remove();
        });
        
        clearBtn.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = true);
            filterByStatus([]);
            dropdown.remove();
        });
        
        // Close dropdown when clicking outside
        setTimeout(() => {
            document.addEventListener('click', function closeDropdown(e) {
                if (!dropdown.contains(e.target) && e.target !== filterBtn) {
                    dropdown.remove();
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }, 100);
    });
}

/**
 * Filter table rows by status
 */
function filterByStatus(statuses) {
    const tableRows = document.querySelectorAll('.table-body .table-row');
    
    tableRows.forEach(row => {
        const statusBadge = row.querySelector('.status-badge');
        if (!statusBadge) return;
        
        const rowStatus = Array.from(statusBadge.classList)
            .find(cls => cls.startsWith('status-'))
            ?.replace('status-', '');
        
        if (statuses.length === 0 || statuses.includes(rowStatus)) {
            row.style.display = '';
            row.style.opacity = '1';
        } else {
            row.style.display = 'none';
            row.style.opacity = '0';
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
 * Initialize animations
 */
function initAnimations() {
    // Animate table rows on load
    const tableRows = document.querySelectorAll('.table-row');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, index * 50);
    });
    
    // Add hover effects to action buttons
    const actionBtns = document.querySelectorAll('.action-btn');
    actionBtns.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
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
        .notification-info { background: #1E88E5; }
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
    filterByStatus
};